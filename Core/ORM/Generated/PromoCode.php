<?php

namespace MillenniumFalcon\Core\ORM\Generated;

use MillenniumFalcon\Core\Db\Base;

class PromoCode extends Base
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
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $type;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $value;
    
    /**
     * #pz datetime DEFAULT NULL
     */
    private $start;
    
    /**
     * #pz datetime DEFAULT NULL
     */
    private $end;
    
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
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * @param mixed value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
    
    /**
     * @return mixed
     */
    public function getStart()
    {
        return $this->start;
    }
    
    /**
     * @param mixed start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }
    
    /**
     * @return mixed
     */
    public function getEnd()
    {
        return $this->end;
    }
    
    /**
     * @param mixed end
     */
    public function setEnd($end)
    {
        $this->end = $end;
    }
    
}