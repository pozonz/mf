<?php
//Last updated: 2020-04-17 14:52:17
namespace MillenniumFalcon\Core\Orm\Generated;

use MillenniumFalcon\Core\Orm;

class PromoCode extends Orm
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
    private $perc;
    
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
    public function getPerc()
    {
        return $this->perc;
    }
    
    /**
     * @param mixed perc
     */
    public function setPerc($perc)
    {
        $this->perc = $perc;
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