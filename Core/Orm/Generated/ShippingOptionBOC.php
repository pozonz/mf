<?php
//Last updated: 2020-03-21 20:28:29
namespace MillenniumFalcon\Core\Orm\Generated;

use MillenniumFalcon\Core\Orm;

class ShippingOptionBOC extends Orm
{
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $price;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $from;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $to;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $countries;
    
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
    public function getPrice()
    {
        return $this->price;
    }
    
    /**
     * @param mixed price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }
    
    /**
     * @return mixed
     */
    public function getFrom()
    {
        return $this->from;
    }
    
    /**
     * @param mixed from
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }
    
    /**
     * @return mixed
     */
    public function getTo()
    {
        return $this->to;
    }
    
    /**
     * @param mixed to
     */
    public function setTo($to)
    {
        $this->to = $to;
    }
    
    /**
     * @return mixed
     */
    public function getCountries()
    {
        return $this->countries;
    }
    
    /**
     * @param mixed countries
     */
    public function setCountries($countries)
    {
        $this->countries = $countries;
    }
    
}