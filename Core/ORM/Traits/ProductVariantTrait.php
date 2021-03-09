<?php
//Last updated: 2019-09-21 10:12:35
namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\CartService;
use MillenniumFalcon\Core\Service\ModelService;

trait ProductVariantTrait
{
    protected $_product;

    /**
     * @return mixed
     */
    public function objProduct()
    {
        if (!$this->_product) {
            $fullClass = ModelService::fullClass($this->getPdo(), 'Product');
            if ($fullClass) {
                $this->_product = $fullClass::getByField($this->getPdo(), 'uniqid', $this->getProductUniqid());
            }
        }
        return $this->_product;
    }

    /**
     * @param $customer
     * @return float|int
     */
    public function calculatedSalePrice($customer)
    {
        $product = $this->objProduct();
        $price = $this->getSalePrice() ?: 0;
        return CartService::getCalculatedPrice($product ?: $this, $customer, $price);
    }

    /**
     * @param $customer
     * @return float|int
     */
    public function calculatedPrice($customer)
    {
        $product = $this->objProduct();
        $price = $this->getPrice() ?: 0;
        return CartService::getCalculatedPrice($product ?: $this, $customer, $price);
    }

    /**
     * @return int
     */
    public function objLowStock()
    {
        if ($this->getAlertIfLessThan() > 0 && $this->getAlertIfLessThan() > $this->getStock()) {
            return 1;
        }
        return 0;
    }

    /**
     * @return int
     */
    public function objOutOfStock() {
        if ($this->getStock() > 0) {
            return 0;
        }
        return 1;
    }

    /**
     * @param bool $doNotSaveVersion
     * @param array $options
     * @return mixed|null
     * @throws \Exception
     */
    public function save($doNotSaveVersion = false, $options = [])
    {
        $result = parent::save($doNotSaveVersion, $options);

        $orm = $this->objProduct();
        if ($orm) {
            $orm->save();
        }

        return $result;
    }
}