<?php

namespace MillenniumFalcon\Core\Pattern\Cart;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Service\CartService;
use MillenniumFalcon\Core\Service\ModelService;

trait CartProductTrait
{
    /**
     * @return string
     */
    public function objProductPageUrl()
    {
        return $this->getFrontendUrl();
    }

    /**
     * @param $customer
     * @return float|int
     */
    public function calculatedSalePrice($customer)
    {
        $price = $this->getSalePrice() ?: 0;
        return CartService::getCalculatedPrice($this, $customer, $price);
    }

    /**
     * @param $customer
     * @return float|int
     */
    public function calculatedPrice($customer)
    {
        $price = $this->getPrice() ?: 0;
        return CartService::getCalculatedPrice($this, $customer, $price);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objVariants()
    {
        $className = CartService::getProductVariantClassName();
        $fullClass = ModelService::fullClass($this->getPdo(), $className);
        return $fullClass::active($this->getPdo(), [
            'whereSql' => 'm.productUniqid = ? AND m.status = 1',
            'params' => [$this->getUniqid()],
        ]);
    }

    /**
     * @return bool
     */
    public function objOnSaleActive()
    {
        if (!$this->getOnSale()) {
            return false;
        }
        if ($this->getSaleStart() && strtotime($this->getSaleStart()) > time()) {
            return false;
        }
        if ($this->getSaleEnd() && strtotime($this->getSaleEnd()) < time()) {
            return false;
        }
        return true;
    }

    /**
     * @param bool $doNotSaveVersion
     * @param array $options
     * @return mixed|null
     * @throws \Exception
     */
    public function save($doNotSaveVersion = false, $options = [])
    {
        $className = CartService::getProductVariantClassName();
        $fullClass = ModelService::fullClass($this->getPdo(), $className);
        $data = $fullClass::active($this->getPdo(), [
            'whereSql' => 'm.productUniqid = ?',
            'params' => [$this->getUniqid()],
        ]);

        $lowStock = 0;
        $outOfStock = 1;

        foreach ($data as $itm) {
            if ($itm->objLowStock() == 1) {
                $lowStock = 1;
            }

            if ($itm->objOutOfStock() == 0) {
                $outOfStock = 0;
            }

            if ($this->getPrice() == null || $this->getPrice() > $itm->getPrice()) {
                $this->setPrice($itm->getPrice());
                $this->setSalePrice($itm->getSalePrice());
            }
        }

        $this->setLowStock($lowStock);
        $this->setOutOfStock($outOfStock);

        return parent::save($doNotSaveVersion, $options);
    }
}