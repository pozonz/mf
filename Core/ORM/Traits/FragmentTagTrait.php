<?php
//Last updated: 2019-04-18 11:48:22
namespace MillenniumFalcon\Core\ORM\Traits;

trait FragmentTagTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo)
    {
        $orm = new static($pdo);
        $orm->setTitle('Page');
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('CMS');
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('Shipping');
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('Product');
        $orm->save();
    }
}