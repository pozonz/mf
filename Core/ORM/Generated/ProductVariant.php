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
    private $stockEnabled;
    
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
    private $shippingUnits;
    
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
    public function getStockEnabled()
    {
        return $this->stockEnabled;
    }
    
    /**
     * @param mixed stockEnabled
     */
    public function setStockEnabled($stockEnabled)
    {
        $this->stockEnabled = $stockEnabled;
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
    public function getShippingUnits()
    {
        return $this->shippingUnits;
    }
    
    /**
     * @param mixed shippingUnits
     */
    public function setShippingUnits($shippingUnits)
    {
        $this->shippingUnits = $shippingUnits;
    }
    
}