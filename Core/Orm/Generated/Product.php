<?php
//Last updated: 2019-09-26 20:32:26
namespace MillenniumFalcon\Core\Orm\Generated;

use MillenniumFalcon\Core\Orm;

class Product extends Orm
{
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $subtitle;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $brand;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $type;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $categories;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $sku;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $pageRank;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $gallery;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $description;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $price;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $compareAtPrice;
    
    /**
     * #pz datetime DEFAULT NULL
     */
    private $promoStart;
    
    /**
     * #pz datetime DEFAULT NULL
     */
    private $promoEnd;
    
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
    public function getSubtitle()
    {
        return $this->subtitle;
    }
    
    /**
     * @param mixed subtitle
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
    }
    
    /**
     * @return mixed
     */
    public function getBrand()
    {
        return $this->brand;
    }
    
    /**
     * @param mixed brand
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
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
    public function getCategories()
    {
        return $this->categories;
    }
    
    /**
     * @param mixed categories
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
    }
    
    /**
     * @return mixed
     */
    public function getSku()
    {
        return $this->sku;
    }
    
    /**
     * @param mixed sku
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
    }
    
    /**
     * @return mixed
     */
    public function getPageRank()
    {
        return $this->pageRank;
    }
    
    /**
     * @param mixed pageRank
     */
    public function setPageRank($pageRank)
    {
        $this->pageRank = $pageRank;
    }
    
    /**
     * @return mixed
     */
    public function getGallery()
    {
        return $this->gallery;
    }
    
    /**
     * @param mixed gallery
     */
    public function setGallery($gallery)
    {
        $this->gallery = $gallery;
    }
    
    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }
    
    /**
     * @param mixed description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
    
    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }
    
    /**
     * @param mixed price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }
    
    /**
     * @return mixed
     */
    public function getCompareAtPrice()
    {
        return $this->compareAtPrice;
    }
    
    /**
     * @param mixed compareAtPrice
     */
    public function setCompareAtPrice($compareAtPrice)
    {
        $this->compareAtPrice = $compareAtPrice;
    }
    
    /**
     * @return mixed
     */
    public function getPromoStart()
    {
        return $this->promoStart;
    }
    
    /**
     * @param mixed promoStart
     */
    public function setPromoStart($promoStart)
    {
        $this->promoStart = $promoStart;
    }
    
    /**
     * @return mixed
     */
    public function getPromoEnd()
    {
        return $this->promoEnd;
    }
    
    /**
     * @param mixed promoEnd
     */
    public function setPromoEnd($promoEnd)
    {
        $this->promoEnd = $promoEnd;
    }
    
}