<?php

namespace MillenniumFalcon\Orm\Generated;

use MillenniumFalcon\Core\Orm;

class DataGroup extends Orm
{
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $icon;
    
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
    public function getIcon()
    {
        return $this->icon;
    }
    
    /**
     * @param mixed icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }
    
}