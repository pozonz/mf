<?php
//Last updated: 2020-03-15 11:19:24
namespace MillenniumFalcon\Core\Orm\Generated;

use MillenniumFalcon\Core\Orm;

class ShippingOptionMethod extends Orm
{
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $className;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $selected;
    
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
    public function getClassName()
    {
        return $this->className;
    }
    
    /**
     * @param mixed className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }
    
    /**
     * @return mixed
     */
    public function getSelected()
    {
        return $this->selected;
    }
    
    /**
     * @param mixed selected
     */
    public function setSelected($selected)
    {
        $this->selected = $selected;
    }
    
}