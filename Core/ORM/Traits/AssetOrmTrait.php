<?php

namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait AssetOrmTrait
{
    /**
     * @return mixed
     * @throws \Exception
     */
    public function objAsset()
    {
        $fullClass = ModelService::fullClass($this->getPdo(), 'Asset');
        return $fullClass::getById($this->getPdo(), $this->getTitle());
    }
}