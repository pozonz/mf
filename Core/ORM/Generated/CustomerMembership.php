<?php

namespace MillenniumFalcon\Core\ORM\Generated;

use MillenniumFalcon\Core\Db\Base;

class CustomerMembership extends Base
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