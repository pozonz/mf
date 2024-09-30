<?php

namespace MillenniumFalcon\Core\ORM\Generated;

use MillenniumFalcon\Core\Db\Base;

class Redirect extends Base
{
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $to;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $type;
    
    /**
     * #pz datetime DEFAULT NULL
     */
    private $lasthappened;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $count;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $referers;
    
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
    public function getLasthappened()
    {
        return $this->lasthappened;
    }
    
    /**
     * @param mixed lasthappened
     */
    public function setLasthappened($lasthappened)
    {
        $this->lasthappened = $lasthappened;
    }
    
    /**
     * @return mixed
     */
    public function getCount()
    {
        return $this->count;
    }
    
    /**
     * @param mixed count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }
    
    /**
     * @return mixed
     */
    public function getReferers()
    {
        return $this->referers;
    }
    
    /**
     * @param mixed referers
     */
    public function setReferers($referers)
    {
        $this->referers = $referers;
    }
    
}