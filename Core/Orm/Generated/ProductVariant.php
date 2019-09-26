<?php
//Last updated: 2019-09-26 14:38:30
namespace MillenniumFalcon\Core\Orm\Generated;

use MillenniumFalcon\Core\Orm;

class ProductVariant extends Orm
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
    private $weight;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $stock;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $alertIfLessThan;
    
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