<?php

namespace MillenniumFalcon\Core\Asset;

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

class AssetController extends Controller
{
    /**
     * @Route("/downloads/assets/{assetCode}", name="asset_download", methods={"GET"})
     * @Route("/downloads/assets/{assetCode}/{fileName}", name="asset_download_filename", requirements={"fileName"=".*"}, methods={"GET"})
     */
    public function assetDownload($assetCode, $fileName = null)
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $orm = $fullClass::getByField($pdo, 'code', $assetCode);
        if (!$orm) {
            throw new NotFoundHttpException();
        }

        if ($orm->getIsImage() == 1) {

            $response = $this->assetImage($assetCode);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $orm->getFileName());

        } else {

            $fileType = $orm->getFileType();
            $fileName = $orm->getFileName();
            $fnlFile = AssetService::getUploadPath() . $orm->getFileLocation();
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
     * @Route("/images/assets/{assetCode}", name="asset_image_original", methods={"GET"})
     * @Route("/images/assets/{assetCode}/{assetSizeCode}", name="asset_image", methods={"GET"})
     * @Route("/images/assets/{assetCode}/{assetSizeCode}/{fileName}", name="asset_image_filename", methods={"GET"})
     */
    public function assetImage($assetCode, $assetSizeCode = null, $fileName = null)
    {
        $request = Request::createFromGlobals();
        $useWebp = in_array('image/webp', $request->getAcceptableContentTypes());

        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();
        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $asset = $fullClass::getByField($pdo, 'code', $assetCode);
        if (!$asset) {
            $asset = $fullClass::getById($pdo, $assetCode);
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
                $fullClass = ModelService::fullClass($pdo, 'AssetSize');
                $assetSize = $fullClass::getByField($pdo, 'code', $assetSizeCode);
                if (!$assetSize) {
                    throw new NotFoundHttpException();
                }

                $cachedKey = AssetService::getCacheKey($asset, $assetSize);
                $cachedFolder = AssetService::getImageCachePath();
                if (!file_exists($cachedFolder)) {
                    mkdir($cachedFolder, 0777, true);
                }

                $fullClass = ModelService::fullClass($pdo, 'AssetCrop');
                $assetCrop = $fullClass::data($pdo, array(
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

                $thumbnail = __DIR__ . '/no-preview-big1.jpg';
                $fileSize = 11042;
                $fileType = 'image/jpeg';

            } else {

                $thumbnail = __DIR__ . '/no-preview-big1.jpg';
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
            "content-length" => $fileSize,
            "content-type" => $fileType,
            "last-modified" => $saveDate,
            "etag" => '"' . sprintf("%x-%x", $date->getTimestamp(), $fileSize) . '"',
        ], true, null, false, true);
        return $response;
    }
}