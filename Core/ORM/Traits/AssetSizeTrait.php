<?php

namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\AssetService;

trait AssetSizeTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo)
    {
        $orm = new static($pdo);
        $orm->setTitle('CMS small');
        $orm->setCode('cms_small');
        $orm->setResizeBy(0);
        $orm->setWidth(200);
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('Small');
        $orm->setCode('small');
        $orm->setResizeBy(0);
        $orm->setWidth(400);
        $orm->setShowInCrop(1);
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('Medium');
        $orm->setCode('medium');
        $orm->setResizeBy(0);
        $orm->setWidth(1000);
        $orm->setShowInCrop(1);
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('Large');
        $orm->setCode('large');
        $orm->setResizeBy(0);
        $orm->setWidth(1800);
        $orm->setShowInCrop(1);
        $orm->save();
    }

    /**
     * @param false $doNotSaveVersion
     * @param array $options
     * @return mixed|null
     */
    public function save($doNotSaveVersion = false, $options = [])
    {
        AssetService::removeCachesByAssetSize($this->getPdo(), $this);
        return parent::save($doNotSaveVersion, $options);
    }
}