<?php

namespace MillenniumFalcon\Core\ORM\Generated;

use MillenniumFalcon\Core\Db\Base;

class Product extends Base
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
    private $noMemberDiscount;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $noPromoDiscount;
    
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
     * #pz datetime DEFAULT NULL
     */
    private $saleStart;
    
    /**
     * #pz datetime DEFAULT NULL
     */
    private $saleEnd;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $onSale;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $relatedProducts;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $fromPrice;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $compareAtPrice;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $lowStock;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $outOfStock;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $onSaleActive;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $thumbnail;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $url;
    
    /**
     * #pz mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $content;
    
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
    public function getNoMemberDiscount()
    {
        return $this->noMemberDiscount;
    }
    
    /**
     * @param mixed noMemberDiscount
     */
    public function setNoMemberDiscount($noMemberDiscount)
    {
        $this->noMemberDiscount = $noMemberDiscount;
    }
    
    /**
     * @return mixed
     */
    public function getNoPromoDiscount()
    {
        return $this->noPromoDiscount;
    }
    
    /**
     * @param mixed noPromoDiscount
     */
    public function setNoPromoDiscount($noPromoDiscount)
    {
        $this->noPromoDiscount = $noPromoDiscount;
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
    public function getSaleStart()
    {
        return $this->saleStart;
    }
    
    /**
     * @param mixed saleStart
     */
    public function setSaleStart($saleStart)
    {
        $this->saleStart = $saleStart;
    }
    
    /**
     * @return mixed
     */
    public function getSaleEnd()
    {
        return $this->saleEnd;
    }
    
    /**
     * @param mixed saleEnd
     */
    public function setSaleEnd($saleEnd)
    {
        $this->saleEnd = $saleEnd;
    }
    
    /**
     * @return mixed
     */
    public function getOnSale()
    {
        return $this->onSale;
    }
    
    /**
     * @param mixed onSale
     */
    public function setOnSale($onSale)
    {
        $this->onSale = $onSale;
    }
    
    /**
     * @return mixed
     */
    public function getRelatedProducts()
    {
        return $this->relatedProducts;
    }
    
    /**
     * @param mixed relatedProducts
     */
    public function setRelatedProducts($relatedProducts)
    {
        $this->relatedProducts = $relatedProducts;
    }
    
    /**
     * @return mixed
     */
    public function getFromPrice()
    {
        return $this->fromPrice;
    }
    
    /**
     * @param mixed fromPrice
     */
    public function setFromPrice($fromPrice)
    {
        $this->fromPrice = $fromPrice;
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
    public function getLowStock()
    {
        return $this->lowStock;
    }
    
    /**
     * @param mixed lowStock
     */
    public function setLowStock($lowStock)
    {
        $this->lowStock = $lowStock;
    }
    
    /**
     * @return mixed
     */
    public function getOutOfStock()
    {
        return $this->outOfStock;
    }
    
    /**
     * @param mixed outOfStock
     */
    public function setOutOfStock($outOfStock)
    {
        $this->outOfStock = $outOfStock;
    }
    
    /**
     * @return mixed
     */
    public function getOnSaleActive()
    {
        return $this->onSaleActive;
    }
    
    /**
     * @param mixed onSaleActive
     */
    public function setOnSaleActive($onSaleActive)
    {
        $this->onSaleActive = $onSaleActive;
    }
    
    /**
     * @return mixed
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }
    
    /**
     * @param mixed thumbnail
     */
    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;
    }
    
    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }
    
    /**
     * @param mixed url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }
    
    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }
    
    /**
     * @param mixed content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
    
}