<?php
//Last updated: 2019-11-19 22:57:46
namespace MillenniumFalcon\Core\Orm\Generated;

use MillenniumFalcon\Core\Orm;

class AssetCrop extends Orm
{
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $x;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $y;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $width;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $height;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $assetId;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $assetSizeId;
    
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
    public function getX()
    {
        return $this->x;
    }
    
    /**
     * @param mixed x
     */
    public function setX($x)
    {
        $this->x = $x;
    }
    
    /**
     * @return mixed
     */
    public function getY()
    {
        return $this->y;
    }
    
    /**
     * @param mixed y
     */
    public function setY($y)
    {
        $this->y = $y;
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
    public function getHeight()
    {
        return $this->height;
    }
    
    /**
     * @param mixed height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }
    
    /**
     * @return mixed
     */
    public function getAssetId()
    {
        return $this->assetId;
    }
    
    /**
     * @param mixed assetId
     */
    public function setAssetId($assetId)
    {
        $this->assetId = $assetId;
    }
    
    /**
     * @return mixed
     */
    public function getAssetSizeId()
    {
        return $this->assetSizeId;
    }
    
    /**
     * @param mixed assetSizeId
     */
    public function setAssetSizeId($assetSizeId)
    {
        $this->assetSizeId = $assetSizeId;
    }
    
}