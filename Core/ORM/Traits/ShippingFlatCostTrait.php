<?php
//Last updated: 2019-09-27 09:57:41
namespace MillenniumFalcon\Core\ORM\Traits;

trait ShippingFlatCostTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo)
    {
        $orm = new static($pdo);
        $orm->setTitle('Standard delivery');
        $orm->setPrice(5);
        $orm->setCountries(json_encode(["139"]));
        $orm->save();
    }
}