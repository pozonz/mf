<?php
//Last updated: 2019-04-18 11:48:38
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait AssetOrmTrait
{
    public function objAsset() {
        $fullClass = ModelService::fullClass($this->getPdo(), 'Asset');
        return $fullClass::getById($this->getPdo(), $this->getTitle());
    }
}