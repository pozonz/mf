<?php

namespace MillenniumFalcon\Core\ORM\Generated;

use MillenniumFalcon\Core\Db\Base;

class AssetSize extends Base
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
    private $resizeBy;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $width;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $convertRate;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $webpConvertRate;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $showInCrop;
    
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
    public function getResizeBy()
    {
        return $this->resizeBy;
    }
    
    /**
     * @param mixed resizeBy
     */
    public function setResizeBy($resizeBy)
    {
        $this->resizeBy = $resizeBy;
    }
    
    /**
     * @return mixed
     */
    public function getWidth()
    {
        return $this->width;
    }
    
    /**
     * @param mixed width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }
    
    /**
     * @return mixed
     */
    public function getConvertRate()
    {
        return $this->convertRate;
    }
    
    /**
     * @param mixed convertRate
     */
    public function setConvertRate($convertRate)
    {
        $this->convertRate = $convertRate;
    }
    
    /**
     * @return mixed
     */
    public function getWebpConvertRate()
    {
        return $this->webpConvertRate;
    }
    
    /**
     * @param mixed webpConvertRate
     */
    public function setWebpConvertRate($webpConvertRate)
    {
        $this->webpConvertRate = $webpConvertRate;
    }
    
    /**
     * @return mixed
     */
    public function getShowInCrop()
    {
        return $this->showInCrop;
    }
    
    /**
     * @param mixed showInCrop
     */
    public function setShowInCrop($showInCrop)
    {
        $this->showInCrop = $showInCrop;
    }
    
}