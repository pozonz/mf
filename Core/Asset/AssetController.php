<?php

namespace MillenniumFalcon\Core\Asset;

use MillenniumFalcon\Core\Orm\Asset;
use MillenniumFalcon\Core\Orm\AssetSize;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AssetController extends Controller
{
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

        /** @var Asset $asset */
        $asset = Asset::getByField($pdo, 'code', $assetCode);
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        $uploadPath = $this->container->getParameter('kernel.project_dir') . '/uploads/';
        $fileType = $asset->getFileType();
        $fileName = $asset->getFileName();
        $fileSize = $asset->getFileSize();
        $fileExtension = $asset->getFileExtension();
        $fileLocation = $uploadPath . $asset->getFileLocation();

        if ($assetSizeCode) {
            /** @var AssetSize $assetSize */
            $assetSize = AssetSize::getByField($pdo, 'code', $assetSizeCode);
            if (!$assetSize) {
                throw new NotFoundHttpException();
            }

            $cachedFolder = $this->container->getParameter('kernel.project_dir') . '/cache/image/';
            if (!file_exists($cachedFolder)) {
                mkdir($cachedFolder, 0777, true);
            }

            if ('application/pdf' == $fileType) {

            } elseif ($useWebp) {
                $thumbnail = $cachedFolder . md5($asset->getId() . '-' . $assetSize->getId() . '-' . $assetSize->getWidth()) . "-webp.webp";
                $command = getenv('CWEBP_CMD') . ' ' . $fileLocation . ' -resize ' . $assetSize->getWidth() . ' 0  -o ' . $thumbnail;

            } else {
                $thumbnail = $cachedFolder . md5($asset->getId() . '-' . $assetSize->getId() . '-' . $assetSize->getWidth()) . ".{$asset->getFileExtension()}";
                $command = getenv('CONVERT_CMD') .  ' ' . $fileLocation . ' -quality 95 -resize ' . $assetSize->getWidth() .'x ' . $thumbnail;
            }

            if (!file_exists($thumbnail)) {
                $returnValue = $this->generateOutput($command);
            }
        } else {
            $thumbnail = $fileLocation;
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

    protected function generateOutput($command, &$in = '', &$out = null)
    {
        $logFolder = $this->container->getParameter('kernel.project_dir') . '/cache/image/';
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("file", $logFolder . 'error-output.txt', 'a') // stderr is a file to write to
        );

        $returnValue = -999;

        $process = proc_open($command, $descriptorspec, $pipes);
        if (is_resource($process)) {

            fwrite($pipes[0], $in);
            fclose($pipes[0]);

            $out = "";
            //read the output
            while (!feof($pipes[1])) {
                $out .= fgets($pipes[1], 4096);
            }
            fclose($pipes[1]);
            $returnValue = proc_close($process);
        }

        return $returnValue;
    }
}