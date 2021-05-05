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
    public function assetDownload(Request $request, $assetCode, $fileName = null)
    {
        $response = $this->assetImage($request, $assetCode, null, $fileName);
        $asset = $this->getAsset($assetCode);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName && strpos($fileName, '.') !== false ? $fileName : $asset->getFileName());
        return $response;
    }

    /**
     * @Route("/images/assets/{assetCode}", methods={"GET"})
     * @Route("/images/assets/{assetCode}/{assetSizeCode}", methods={"GET"})
     * @Route("/images/assets/{assetCode}/{assetSizeCode}/{fileName}", methods={"GET"})
     */
    public function assetImage(Request $request, $assetCode, $assetSizeCode = null, $fileName = null)
    {
        ini_set('memory_limit', '512M');

        $useWebp = in_array('image/webp', $request->getAcceptableContentTypes());
        $returnOriginalFile = $assetSizeCode && $assetSizeCode != 1 ? 0 : 1;

        $asset = $this->getAsset($assetCode);

        $modifiedTime = '@' . strtotime($asset->getModified());
        $date = new \DateTimeImmutable($modifiedTime);
        $saveDate = $date->setTimezone(new \DateTimeZone("GMT"))->format("D, d M y H:i:s T");

        $fileType = $asset->getFileType();
        $fileName = $asset->getFileName();
        $fileSize = $asset->getFileSize();
        $ext = $asset->getFileExtension();
        if ($useWebp && strtolower($ext) == 'gif') {
//            $ext = "jpg";
        }

        $cachedKey = $this->getCacheKey($asset, $assetSizeCode ?: 1);
        $cachedFolder = $this->checkAndCreatePath($this->getImageCachePath());
        $uploadPath = $this->checkAndCreatePath($this->getUploadPath());

        $fileLocation = $uploadPath . $asset->getFileLocation();
        $thumbnail = "{$cachedFolder}{$cachedKey}.$ext";
        $thumbnailHeader = "{$cachedFolder}{$cachedKey}.$ext.txt";
        $webpThumbnail = "{$thumbnail}.webp";
        $webpThumbnailHeader = "{$thumbnail}.webp.txt";

        if (!$returnOriginalFile && !$useWebp && file_exists($thumbnail) && file_exists($thumbnailHeader)) {
            $header = json_decode(file_get_contents($thumbnailHeader));
            if ($header) {
                $header = (array)$header;
                $header['Surrogate-Key'] = 'asset' . $asset->getId();
                return $this->getBinaryFileResponse($thumbnail, $header);
            }
        }

        if (!$returnOriginalFile && $useWebp && file_exists($webpThumbnail) && file_exists($webpThumbnailHeader)) {
            $header = json_decode(file_get_contents($webpThumbnailHeader));
            if ($header) {
                $header = (array)$header;
                $header['Surrogate-Key'] = 'asset' . $asset->getId();
                return $this->getBinaryFileResponse($webpThumbnail, $header);
            }
        }

        $isImage = $asset->getIsImage();
        $fileType = strpos($fileType, 'image/svg') !== false ? 'image/svg+xml' : $fileType;

        if ($fileType == 'image/svg+xml') {
            $isImage = 1;
            $assetSizeCode = null;
        }

        if ($fileType == 'application/pdf' && !$returnOriginalFile) {
            $pdfRasterToken = getenv('PDF_RASTER_TOKEN');
            $pdfRasterEndPoint = getenv('PDF_RASTER_ENDPOINT');

            if ($pdfRasterToken && $pdfRasterEndPoint) {
                $url = $request->getSchemeAndHttpHost() . "/downloads/assets/{$assetCode}";
                $payload = [
                    'url' => $url,
                    'token' => $pdfRasterToken
                ];
                $opts = array('http' =>
                    array(
                        'method' => 'POST',
                        'header' => 'Content-type: application/json',
                        'content' => json_encode($payload)
                    )
                );
                $data = file_get_contents($pdfRasterEndPoint, false, stream_context_create($opts));

                $ff = new \finfo();
                $ffMime  = $ff->buffer($data, \FILEINFO_MIME_TYPE);
                $ffExt  = $ff->buffer($data, \FILEINFO_EXTENSION);

                $fileLocation = "{$cachedFolder}{$asset->getFileLocation()}.{$ffExt}";
                $thumbnail = "{$thumbnail}.{$ffExt}";
                file_put_contents($fileLocation, $data);

                $isImage = 1;
                $fileType = $ffMime;
            }
        }

        if (!$isImage && !$returnOriginalFile) {
            return $this->getBinaryFileResponse("{$this->getTemplateFilePath()}no-preview-big1.jpg", [
                "cache-control" => 'max-age=31536000',
                "content-length" => 11042,
                "content-type" => 'image/jpeg',
                "last-modified" => $saveDate,
                "etag" => '"' . sprintf("%x-%x", $date->getTimestamp(), $fileSize) . '"',
                "Surrogate-Key" => 'asset' . $asset->getId(),
            ]);
        }

        if ($assetSizeCode) {
            $qualityCmd = "-quality 90%";
            $colorCmd = '-colorspace sRGB';
            $resizeCmd = '';
            $cropCmd = '';

            $assetSize = null;
            if ($assetSizeCode != 1) {
                $fullClass = ModelService::fullClass($this->connection, 'AssetSize');
                $assetSize = $fullClass::getByField($this->connection, 'code', $assetSizeCode);
                if (!$assetSize) {
                    throw new NotFoundHttpException();
                }
            }
            if ($assetSize) {
                if ($assetSize->getResizeBy() == 1) {
                    $resizeCmd = "-resize \"x{$assetSize->getWidth()}>\"";
                } else {
                    $resizeCmd = "-resize \"{$assetSize->getWidth()}>\"";
                }
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
            if ($assetCrop AND $assetSizeCode !== 'cms_small') {
                $cropCmd = "-crop {$assetCrop->getWidth()}x{$assetCrop->getHeight()}+{$assetCrop->getX()}+{$assetCrop->getY()}";
            }

            $command = getenv('CONVERT_CMD') . " $fileLocation {$qualityCmd} {$cropCmd} {$resizeCmd} {$colorCmd} -strip $thumbnail";
        }

        $SAVE_ASSETS_TO_DB = getenv('SAVE_ASSETS_TO_DB');
        if ($SAVE_ASSETS_TO_DB && !file_exists($fileLocation)) {
            $assetBinaryFullClass = ModelService::fullClass($this->connection, 'AssetBinary');
            $assetBinary = $assetBinaryFullClass::getByField($this->connection, 'title', $asset->getId());
            if (!$assetBinary) {
                throw new NotFoundHttpException();
            }
            file_put_contents($fileLocation, $assetBinary->getContent());
        }

        if ($assetSizeCode) {
            $returnValue = AssetService::generateOutput($command);
            $fileSize == filesize($thumbnail);
        } else {
            copy($fileLocation, $thumbnail);
        }

        $thumbnailHeaderContent = [
            "cache-control" => 'max-age=31536000',
            "content-length" => $fileSize,
            "content-type" => $fileType,
            "last-modified" => $saveDate,
            "etag" => '"' . sprintf("%x-%x", $date->getTimestamp(), $fileSize) . '"',
            "Surrogate-Key" => 'asset' . $asset->getId(),
        ];
        file_put_contents($thumbnailHeader, json_encode($thumbnailHeaderContent));

        if ($SAVE_ASSETS_TO_DB && file_exists($fileLocation)) {
            unlink($fileLocation);
        }

        if ($useWebp && $assetSizeCode && !$returnOriginalFile && $fileType != 'image/gif') {
            $command = getenv('CWEBP_CMD') . " $thumbnail -o $webpThumbnail";
            $returnValue = AssetService::generateOutput($command);

            $fileSize == filesize($webpThumbnail);
            $fileType = 'image/webp';

            $webpThumbnailHeaderContent = [
                "cache-control" => 'max-age=31536000',
                "content-length" => $fileSize,
                "content-type" => $fileType,
                "last-modified" => $saveDate,
                "etag" => '"' . sprintf("%x-%x", $date->getTimestamp(), $fileSize) . '"',
                "Surrogate-Key" => 'asset' . $asset->getId(),
            ];
            file_put_contents($webpThumbnailHeader, json_encode($webpThumbnailHeaderContent));

            $thumbnail = $webpThumbnail;
            $thumbnailHeaderContent = $webpThumbnailHeaderContent;
        }

        return $this->getBinaryFileResponse($thumbnail, $thumbnailHeaderContent);
    }

    /**
     * @param $assetCode
     * @return mixed
     */
    private function getAsset($assetCode)
    {
        $fullClass = ModelService::fullClass($this->connection, 'Asset');
        $asset = $fullClass::getByField($this->connection, 'code', $assetCode);
        if (!$asset) {
            $asset = $fullClass::getById($this->connection, $assetCode);
        }
        if (!$asset) {
            throw new NotFoundHttpException();
        }
        return $asset;
    }

    /**
     * @return string
     */
    private function getImageCachePath()
    {
        return "{$this->kernel->getProjectDir()}/cache/image/";
    }

    /**
     * @return string
     */
    private function getUploadPath()
    {
        return "{$this->kernel->getProjectDir()}/uploads/";
    }

    /**
     * @return string
     */
    private function getTemplateFilePath()
    {
        return "{$this->kernel->getProjectDir()}/vendor/pozoltd/mf/Resources/files/";
    }

    /**
     * @param $path
     * @return mixed
     */
    private function checkAndCreatePath($path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }

    /**
     * @param $asset
     * @param $assetSizeCode
     * @return string
     */
    private function getCacheKey($asset, $assetSizeCode)
    {
        return "{$asset->getCode()}-{$assetSizeCode}";
    }

    /**
     * @param $file
     * @param $header
     * @return BinaryFileResponse
     */
    private function getBinaryFileResponse($file, $header)
    {
        $header = (array)$header;
        return BinaryFileResponse::create($file, Response::HTTP_OK, $header, true, null, false, true);
    }
}