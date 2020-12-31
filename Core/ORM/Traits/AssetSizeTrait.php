<?php

namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\AssetService;

trait AssetSizeTrait
{
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