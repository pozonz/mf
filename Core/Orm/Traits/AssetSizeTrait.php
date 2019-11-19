<?php
//Last updated: 2019-04-17 15:05:21
namespace MillenniumFalcon\Core\Orm\Traits;

trait AssetSizeTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo, $container)
    {
        $orm = new static($pdo);
        $orm->setTitle('CMS small');
        $orm->setCode('cms_small');
        $orm->setWidth(200);
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('Small');
        $orm->setCode('small');
        $orm->setWidth(400);
        $orm->setShowInCrop(1);
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('Medium');
        $orm->setCode('medium');
        $orm->setWidth(1000);
        $orm->setShowInCrop(1);
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('Large');
        $orm->setCode('large');
        $orm->setWidth(1800);
        $orm->setShowInCrop(1);
        $orm->save();
    }
}