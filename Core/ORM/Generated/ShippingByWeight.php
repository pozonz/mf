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
    private $type;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $shortdescription;
    
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
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * @param mixed type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
    
    /**
     * @return mixed
     */
    public function getShortdescription()
    {
        return $this->shortdescription;
    }
    
    /**
     * @param mixed shortdescription
     */
    public function setShortdescription($shortdescription)
    {
        $this->shortdescription = $shortdescription;
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