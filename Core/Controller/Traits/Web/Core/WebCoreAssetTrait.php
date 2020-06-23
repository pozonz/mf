<?php

namespace MillenniumFalcon\Core\Controller\Traits\Web\Core;

use MillenniumFalcon\Core\Service\AssetService;
use MillenniumFalcon\Core\Service\ModelService;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

trait WebCoreAssetTrait
{
    /**
     * @Route("/downloads/assets/{assetCode}", methods={"GET"})
     * @Route("/downloads/assets/{assetCode}/{fileName}", requirements={"fileName"=".*"}, methods={"GET"})
     */
    public function assetDownload($assetCode, $fileName = null)
    {
        $fullClass = ModelService::fullClass($this->connection, 'Asset');
        $asset = $fullClass::getByField($this->connection, 'code', $assetCode);
        if (!$asset) {
            $asset = $fullClass::getById($this->connection, $assetCode);
        }

        if (!$asset) {
            throw new NotFoundHttpException();
        }

        if ($asset->getIsImage() == 1) {

            $response = $this->assetImage($assetCode);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $asset->getFileName());

        } else {

            $fileType = $asset->getFileType();
            $fileName = $asset->getFileName();
            $fnlFile = AssetService::getUploadPath() . $asset->getFileLocation();
            if (!file_exists($fnlFile)) {
                throw new NotFoundHttpException();
            }
            $stream = function () use ($fnlFile) {
                readfile($fnlFile);
            };
            return new StreamedResponse($stream, 200, array(
                'Content-Type' => $fileType,
                'Content-length' => filesize($fnlFile),
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
            ));
        }

        return $response;
    }

    /**
     * @Route("/images/assets/{assetCode}", methods={"GET"})
     * @Route("/images/assets/{assetCode}/{assetSizeCode}", methods={"GET"})
     * @Route("/images/assets/{assetCode}/{assetSizeCode}/{fileName}", methods={"GET"})
     */
    public function assetImage(Request $request, $assetCode, $assetSizeCode = null, $fileName = null)
    {
        $useWebp = in_array('image/webp', $request->getAcceptableContentTypes());

        $fullClass = ModelService::fullClass($this->connection, 'Asset');
        $asset = $fullClass::getByField($this->connection, 'code', $assetCode);
        if (!$asset) {
            $asset = $fullClass::getById($this->connection, $assetCode);
        }
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        $uploadPath = AssetService::getUploadPath();
        $fileType = $asset->getFileType();
        $fileName = $asset->getFileName();
        $fileSize = $asset->getFileSize();
        $fileExtension = $asset->getFileExtension();
        $fileLocation = $uploadPath . $asset->getFileLocation();

        if ($asset->getIsImage()) {
            if ($assetSizeCode) {
                $fullClass = ModelService::fullClass($this->connection, 'AssetSize');
                $assetSize = $fullClass::getByField($this->connection, 'code', $assetSizeCode);
                if (!$assetSize) {
                    throw new NotFoundHttpException();
                }

                $cachedKey = AssetService::getCacheKey($asset, $assetSize);
                $cachedFolder = AssetService::getImageCachePath();
                if (!file_exists($cachedFolder)) {
                    mkdir($cachedFolder, 0777, true);
                }

                $fullClass = ModelService::fullClass($this->connection, 'AssetCrop');
                $assetCrop = $fullClass::data($this->connection, array(
                    'whereSql' => 'm.assetId = ? AND m.assetSizeId = ?',
                    'params' => array($asset->getId(), $assetSize->getId()),
                    'limit' => 1,
                    'oneOrNull' => 1,
                ));

                if ($useWebp) {
                    $thumbnail = "{$cachedFolder}webp-{$cachedKey}.webp";

                    $resizeCmd = "-resize {$assetSize->getWidth()} 0";
                    $cropCmd = '';
                    if ($assetCrop) {
                        $cropCmd = "-crop {$assetCrop->getX()} {$assetCrop->getY()} {$assetCrop->getWidth()} {$assetCrop->getHeight()}";
                    }
                    $command = getenv('CWEBP_CMD') . " $fileLocation {$cropCmd} {$resizeCmd} -o $thumbnail";

                } else {
                    $thumbnail = "{$cachedFolder}{$cachedKey}.{$asset->getFileExtension()}";
                    $resizeCmd = "-resize {$assetSize->getWidth()}";
                    $qualityCmd = "-quality 95";
                    $cropCmd = '';
                    if ($assetCrop) {
                        $cropCmd = "-crop {$assetCrop->getWidth()}x{$assetCrop->getHeight()}+{$assetCrop->getX()}+{$assetCrop->getY()}";
                    }
                    $command = getenv('CONVERT_CMD') . " $fileLocation {$qualityCmd} {$cropCmd} {$resizeCmd} $thumbnail";
                }

            } else {
                $thumbnail = $fileLocation;
            }
        } else {
            if ('application/pdf' == $fileType) {
                //TODO: implement pdf thumbnail

                $thumbnail = AssetService::getTemplateFilePath() . 'no-preview-big1.jpg';
                $fileSize = 11042;
                $fileType = 'image/jpeg';

            } else {

                $thumbnail = AssetService::getTemplateFilePath() . 'no-preview-big1.jpg';
                $fileSize = 11042;
                $fileType = 'image/jpeg';
            }
        }

        if (!file_exists($thumbnail)) {
            $returnValue = AssetService::generateOutput($command);
        }

        $date = new \DateTimeImmutable('@' . filectime($uploadPath));
        $saveDate = $date->setTimezone(new \DateTimeZone("GMT"))->format("D, d M y H:i:s T");
        $response = BinaryFileResponse::create($thumbnail, Response::HTTP_OK, [
            "cache-control" => 'max-age=31536000',
            "content-length" => $fileSize,
            "content-type" => $fileType,
            "last-modified" => $saveDate,
            "etag" => '"' . sprintf("%x-%x", $date->getTimestamp(), $fileSize) . '"',
        ], true, null, false, true);
        return $response;
    }
}