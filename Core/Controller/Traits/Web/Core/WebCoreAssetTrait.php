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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
            $SAVE_ASSETS_TO_DB = getenv('SAVE_ASSETS_TO_DB');
            if ($SAVE_ASSETS_TO_DB) {
                $cachedFolder = AssetService::getImageCachePath();
                if (!file_exists($cachedFolder)) {
                    mkdir($cachedFolder, 0777, true);
                }

                $cachedOriginalFolder = AssetService::getImageCachePath() . '../original/';
                if (!file_exists($cachedOriginalFolder)) {
                    mkdir($cachedOriginalFolder, 0777, true);
                }

                $fileType = $asset->getFileType();
                $fileSize = $asset->getFileSize();
                $fileLocation = $cachedOriginalFolder . $asset->getFileLocation();

                $assetBinaryFullClass = ModelService::fullClass($this->connection, 'AssetBinary');
                $assetBinary = $assetBinaryFullClass::getByField($this->connection, 'title', $asset->getId());
                if (!$assetBinary) {
                    throw new NotFoundHttpException();
                }
                $thumbnail = $fileLocation;
                file_put_contents($thumbnail, $assetBinary->getContent());

                $date = new \DateTimeImmutable('@' . filectime($thumbnail));
                $saveDate = $date->setTimezone(new \DateTimeZone("GMT"))->format("D, d M y H:i:s T");
                $response = BinaryFileResponse::create($thumbnail, Response::HTTP_OK, [
                    "content-length" => $fileSize,
                    "content-type" => $fileType,
                    "last-modified" => $saveDate,
                    "etag" => '"' . sprintf("%x-%x", $date->getTimestamp(), $fileSize) . '"',
                ], true, null, false, true);
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $asset->getFileName());
            } else {

            }

        }

        return $response;
    }

    /**
     * @Route("/images/assets/{assetCode}", methods={"GET"})
     * @Route("/images/assets/{assetCode}/{assetSizeCode}", methods={"GET"})
     * @Route("/images/assets/{assetCode}/{assetSizeCode}/{fileName}", methods={"GET"})
     */
    public function assetImage($assetCode, $assetSizeCode = null, $fileName = null)
    {
        $request = Request::createFromGlobals();
        $useWebp = in_array('image/webp', $request->getAcceptableContentTypes());

        $fullClass = ModelService::fullClass($this->connection, 'Asset');
        $asset = $fullClass::getByField($this->connection, 'code', $assetCode);
        if (!$asset) {
            $asset = $fullClass::getById($this->connection, $assetCode);
        }
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        $cachedKey = AssetService::getCacheKey($asset, $assetSizeCode ?: 1);
        $cachedFolder = AssetService::getImageCachePath();
        if (!file_exists($cachedFolder)) {
            mkdir($cachedFolder, 0777, true);
        }

        $uploadPath = AssetService::getUploadPath();
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $fileType = $asset->getFileType();
        $fileName = $asset->getFileName();
        $fileSize = $asset->getFileSize();
        $fileExtension = $asset->getFileExtension();
        $fileLocation = $uploadPath . $asset->getFileLocation();

        if ($assetSizeCode == 1) {
            $assetSizeCode = 1;
        }

        $isImage = $asset->getIsImage();
        $fileType = strpos($fileType, 'image/svg') !== false  ? 'image/svg+xml' : $fileType;
        if ($fileType == 'image/svg+xml') {
            $isImage = 1;
            $assetSizeCode = null;
        }
        

//        if ($fileType ==  'application/pdf') {
//            //1. build a url for the pdf
//            $url = $request->getSchemeAndHttpHost() . "/downloads/assets/{$assetCode}";
//
//            //replace this with guzzle????
//            //2. build a request for the pdf service
//            $payload = [
//                'url' => $url,
//                'token' => getenv('PDF_RASTER_TOKEN')
//            ];
//
//            //3. fetch the pdf.
//            $opts = array('http' =>
//                array(
//                    'method'  => 'POST',
//                    'header'  => 'Content-type: application/json',
//                    'content' => json_encode($payload)
//                )
//            );
//
//
//            $data = file_get_contents(getenv('PDF_RASTER_ENDPOINT'), false, stream_context_create($opts));
//
//            $ff = new \finfo(\FILEINFO_MIME_TYPE);
//            $mime  = $ff->buffer($data);
//
//            var_dump($mime);exit;
//
//        }

        if ($asset->getIsImage()) {
            $thumbnail = $fileLocation;

            if ($assetSizeCode) {

                if ($assetSizeCode !== 1) {
                    $fullClass = ModelService::fullClass($this->connection, 'AssetSize');
                    $assetSize = $fullClass::getByField($this->connection, 'code', $assetSizeCode);
                    if (!$assetSize) {
                        throw new NotFoundHttpException();
                    }
                } else {
                    $assetSize = null;
                }

                $fullClass = ModelService::fullClass($this->connection, 'AssetCrop');
                $assetCrop = $fullClass::data($this->connection, array(
                    'whereSql' => 'm.assetId = ? AND m.assetSizeId = ?',
                    'params' => array($asset->getId(), $assetSize ? $assetSize->getId() : null),
                    'limit' => 1,
                    'oneOrNull' => 1,
                ));
                if (!$assetCrop) {
                    $assetCrop = $fullClass::data($this->connection, array(
                        'whereSql' => 'm.assetId = ? AND m.assetSizeId = ?',
                        'params' => array($asset->getId(), 'All sizes'),
                        'limit' => 1,
                        'oneOrNull' => 1,
                    ));
                }

                $ext = $asset->getFileExtension();
                if ($useWebp && strtolower($ext) == 'gif') {
                    $ext = 'jpg';
                }

                $thumbnail = "{$cachedFolder}{$cachedKey}.$ext";
                if ($assetSizeCode !== 1) {
                    $resizeCmd = "-resize {$assetSize->getWidth()}";
                } else {
                    $resizeCmd = '';
                }
                $qualityCmd = "-quality 95";
                $colorCmd = '-colorspace sRGB';
                $cropCmd = '';
                if ($assetCrop AND $assetSizeCode !== 'cms_small') {
                    $cropCmd = "-crop {$assetCrop->getWidth()}x{$assetCrop->getHeight()}+{$assetCrop->getX()}+{$assetCrop->getY()}";
                }
                $command = getenv('CONVERT_CMD') . " $fileLocation {$qualityCmd} {$cropCmd} {$resizeCmd} {$colorCmd} -strip $thumbnail";
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

        $SAVE_ASSETS_TO_DB = getenv('SAVE_ASSETS_TO_DB');
        if ($SAVE_ASSETS_TO_DB) {
            if (!file_exists($thumbnail)) {
                $assetBinaryFullClass = ModelService::fullClass($this->connection, 'AssetBinary');
                $assetBinary = $assetBinaryFullClass::getByField($this->connection, 'title', $asset->getId());
                if (!$assetBinary) {
                    throw new NotFoundHttpException();
                }
                file_put_contents($fileLocation, $assetBinary->getContent());
                if ($assetSizeCode) {
                    $returnValue = AssetService::generateOutput($command);
                    unlink($fileLocation);
                }
            }
        } else {
            if (!file_exists($thumbnail)) {
                $returnValue = AssetService::generateOutput($command);
            }
        }

        if ($useWebp && $assetSizeCode) {
            $webpThumbnail = "{$cachedFolder}{$cachedKey}.webp";
            if (!file_exists($webpThumbnail)) {
                $command = getenv('CWEBP_CMD') . " $thumbnail -o $webpThumbnail";
                $returnValue = AssetService::generateOutput($command);
            }
            $thumbnail = $webpThumbnail;
            $fileType = 'image/webp';
        }
//var_dump($command);exit;
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