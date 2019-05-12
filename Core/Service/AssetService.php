<?php

namespace MillenniumFalcon\Core\Service;

use MillenniumFalcon\Core\Orm\_Model;

class AssetService
{
    /**
     * DbService constructor.
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function __construct(\Doctrine\DBAL\Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param $pdo
     * @param $asset
     * @throws \Exception
     */
    static public function removeAssetOrms($pdo, $asset) {
        $fullClass = ModelService::fullClass($pdo, 'AssetOrm');
        $assetOrms = $fullClass::data($pdo, array(
            'whereSql' => 'm.title = ?',
            'params' => array($asset->getId()),
        ));
        foreach ($assetOrms as $assetOrm) {
            $assetOrm->delete();
        }
    }

    /**
     * @param $asset
     */
    static public function removeFile($asset) {
        $link = static::getUploadPath() . $asset->getFileLocation();
        if (file_exists($link)) {
            unlink($link);
        }
    }

    /**
     * @param $pdo
     * @param $asset
     * @throws \Exception
     */
    static public function removeCaches($pdo, $asset) {
        $fullClass = ModelService::fullClass($pdo, 'AssetSize');
        $assetSizes = $fullClass::data($pdo);
        foreach ($assetSizes as $assetSize) {
            static::removeCache($asset, $assetSize);
        }
    }

    /**
     * @param $asset
     * @param $assetSize
     */
    static public function removeCache($asset, $assetSize) {
        $cachedFolder = AssetService::getImageCachePath();
        $cachedKey = AssetService::getCacheKey($asset, $assetSize);
        $cachedFile =  "{$cachedFolder}{$cachedKey}.{$asset->getFileExtension()}";
        if (file_exists($cachedFile)) {
            unlink($cachedFile);
        }
        $cachedFile = "{$cachedFolder}webp-{$cachedKey}.webp";
        if (file_exists($cachedFile)) {
            unlink($cachedFile);
        }
    }

    /**
     * @param $asset
     * @param $assetSize
     * @return string
     */
    static public function getCacheKey($asset, $assetSize) {
        return "{$asset->getCode()}-{$assetSize->getCode()}-{$asset->getId()}-{$assetSize->getId()}";
    }

    /**
     * @return string
     */
    static public function getUploadPath() {
        return __DIR__ . '/../../../../../uploads/';
    }

    /**
     * @return string
     */
    static public function getImageCachePath() {
        return __DIR__ . '/../../../../../cache/image/';
    }
}