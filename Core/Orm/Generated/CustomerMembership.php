<?php
//Last updated: 2019-11-19 22:52:40
namespace MillenniumFalcon\Core\Orm\Generated;

use MillenniumFalcon\Core\Orm;

class CustomerMembership extends Orm
{
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $discount;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $default;
    
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
    public function getDiscount()
    {
        return $this->discount;
    }
    
    /**
     * @param mixed discount
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;
    }
    
    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }
    
    /**
     * @param mixed default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }
    
}