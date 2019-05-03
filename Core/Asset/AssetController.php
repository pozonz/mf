<?php

namespace MillenniumFalcon\Core\Asset;

use MillenniumFalcon\Core\Orm\Asset;
use MillenniumFalcon\Core\Orm\AssetSize;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class AssetController extends Controller
{
    /**
     * @Route("/images/assets/{assetCode}/{assetSizeCode}/{fileName}", name="asset_image", methods={"GET"})
     */
    public function assetDownload($assetCode, $assetSizeCode, $fileName = null)
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $asset = Asset::getByField($pdo, 'code', $assetCode);
        $assetSize = AssetSize::getByField($pdo, 'title', $assetSizeCode);


//        $
        var_dump($assetSize->getWidth());exit;



        return $response;
    }
}