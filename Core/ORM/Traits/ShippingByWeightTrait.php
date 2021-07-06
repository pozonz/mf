<?php
//Last updated: 2019-09-27 09:57:41
namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\ORM\ShippingZone;

trait ShippingByWeightTrait
{
    /**
     * @return mixed
     */
    public function objShippingCostRates()
    {
        return $this->getShippingCostRates() ? json_decode($this->getShippingCostRates()) : [];
    }

    /**
     * @return array|null
     */
    public function objCountry()
    {
        return ShippingZone::getById($this->getPdo(), $this->getCountry());
    }

    /**
     * @return string
     */
    static public function getCmsOrmTwig()
    {
        return 'cms/orms/orm-custom-shipping-by-weight.twig';
    }

    /**
     * @return string
     */
    static public function getCmsOrmsTwig()
    {
        return 'cms/orms/orms-custom-shipping-by-weight.twig';
    }
}