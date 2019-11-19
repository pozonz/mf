<?php
//Last updated: 2019-11-19 22:52:39
namespace MillenniumFalcon\Core\Orm\Generated;

use MillenniumFalcon\Core\Orm;

class ShippingCountry extends Orm
{
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $code;
    
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
    public function getCode()
    {
        return $this->code;
    }
    
    /**
     * @param mixed code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }
    
}