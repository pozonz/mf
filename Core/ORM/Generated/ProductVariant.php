<?php

namespace MillenniumFalcon\Core\ORM\Generated;

use MillenniumFalcon\Core\Db\Base;

class ProductVariant extends Base
{
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $productUniqid;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $sku;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $price;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $salePrice;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $stock;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $alertIfLessThan;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $weight;
    
    /**
     * #pz mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $content;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $noMemberDiscount;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $noPromoDiscount;
    
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
    public function getProductUniqid()
    {
        return $this->productUniqid;
    }
    
    /**
     * @param mixed productUniqid
     */
    public function setProductUniqid($productUniqid)
    {
        $this->productUniqid = $productUniqid;
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
    public function getSalePrice()
    {
        return $this->salePrice;
    }
    
    /**
     * @param mixed salePrice
     */
    public function setSalePrice($salePrice)
    {
        $this->salePrice = $salePrice;
    }
    
    /**
     * @return mixed
     */
    public function getStock()
    {
        return $this->stock;
    }
    
    /**
     * @param mixed stock
     */
    public function setStock($stock)
    {
        $this->stock = $stock;
    }
    
    /**
     * @return mixed
     */
    public function getAlertIfLessThan()
    {
        return $this->alertIfLessThan;
    }
    
    /**
     * @param mixed alertIfLessThan
     */
    public function setAlertIfLessThan($alertIfLessThan)
    {
        $this->alertIfLessThan = $alertIfLessThan;
    }
    
    /**
     * @return mixed
     */
    public function getWeight()
    {
        return $this->weight;
    }
    
    /**
     * @param mixed weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
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
    
}