<?php

namespace MillenniumFalcon\Core\Service;

use BlueM\Tree;
use BlueM\Tree\Serializer\HierarchicalTreeJsonSerializer;
use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\ORM\_Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AssetUtilsService
{
    /**
     * AssetService constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getImagePath($assetId, $assetSizeCode)
    {
        $fullClass = ModelService::fullClass($this->connection, 'Asset');
        $asset = $fullClass::getByField($this->connection, 'code', $assetId);
        if (!$asset) {
            $asset = $fullClass::getById($this->connection, $assetId);
        }

        if ($asset) {
            return "/images/assets/{$asset->getId()}/{$assetSizeCode}/{$asset->getFileName()}";
        }

        return null;
    }
}