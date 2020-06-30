<?php

namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait AssetOrmTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo)
    {

    }

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