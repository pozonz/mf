<?php

namespace MillenniumFalcon\Core\Asset;

use MillenniumFalcon\Core\Orm\Asset;
use MillenniumFalcon\Core\Orm\AssetSize;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AssetController extends Controller
{
    /**
     * @Route("/images/assets/{assetCode}/{assetSizeCode}/", name="asset_image", methods={"GET"})
     * @Route("/images/assets/{assetCode}/{assetSizeCode}/{fileName}", name="asset_image_filename", methods={"GET"})
     */
    public function assetDownload($assetCode, $assetSizeCode, $fileName = null)
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $asset = Asset::getByField($pdo, 'code', $assetCode);
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        $assetSize = AssetSize::getByField($pdo, 'title', $assetSizeCode);
        if (!$assetSize) {
            throw new NotFoundHttpException();
        }

        $path = $this->container->getParameter('kernel.project_dir') . '/uploads/';
        $from = $path . $asset->getFileLocation();
        $to = $path . 'text.jpg';
        $this->cachePath = $path;
//        var_dump($in);exit;
        $command = 'convert ' . $from . ' -quality 95 -resize 100x ' . $to;
//        var_dump($assetSize->getWidth());exit;

        $returnValue = $this->generateOutput($command);
//        var_dump($returnValue);exit;
//        var_dump($in, $out);exit;
        $response = BinaryFileResponse::create($to, Response::HTTP_OK, [
            "content-encoding" => "binary",
            "content-length" => 42787,
            "content-type" => "image/jpeg",
            "last-modified" => "Fri, 01 Dec 2017 13:28:56 GMT",
            "etag" => "bee08229a395421808ecfb7da4a79287"], true, null, false, true);


//        $response->headers->add([
//            'x-blob-cache' => (!$isCached || $this->debug) ? 'MISS':'HIT',
//            'x-blob-cache-deliver' => $this->nginxAccelMapping ? 'NGINX' : 'DIRECT'
//        ]);
        return $response;
    }

    protected function generateOutput($command, &$in = '', &$out = null)
    {

        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("file", $this->cachePath . '/error-output.txt', 'a') // stderr is a file to write to
        );

        $returnValue = -999;

        $process = proc_open($command, $descriptorspec, $pipes);
        if (is_resource($process)) {

            fwrite($pipes[0], $in);
            fclose($pipes[0]);

            $out="";
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