<?php

namespace MillenniumFalcon\Core\ORM\Generated;

use MillenniumFalcon\Core\Db\Base;

class ShippingByWeight extends Base
{
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $freeDeliveryIfPriceAbove;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $shippingMethod;
    
    /**
     * #pz mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $shippingCostRates;
    
    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }
    
    /**
     * @param mixed title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }
    
    /**
     * @return mixed
     */
    public function getFreeDeliveryIfPriceAbove()
    {
        return $this->freeDeliveryIfPriceAbove;
    }
    
    /**
     * @param mixed freeDeliveryIfPriceAbove
     */
    public function setFreeDeliveryIfPriceAbove($freeDeliveryIfPriceAbove)
    {
        $this->freeDeliveryIfPriceAbove = $freeDeliveryIfPriceAbove;
    }
    
    /**
     * @return mixed
     */
    public function getShippingMethod()
    {
        return $this->shippingMethod;
    }
    
    /**
     * @param mixed shippingMethod
     */
    public function setShippingMethod($shippingMethod)
    {
        $this->shippingMethod = $shippingMethod;
    }
    
    /**
     * @return mixed
     */
    public function getShippingCostRates()
    {
        return $this->shippingCostRates;
    }
    
    /**
     * @param mixed shippingCostRates
     */
    public function setShippingCostRates($shippingCostRates)
    {
        $this->shippingCostRates = $shippingCostRates;
    }
    
}