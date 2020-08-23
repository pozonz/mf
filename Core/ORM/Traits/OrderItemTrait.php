<?php
//Last updated: 2019-09-27 09:55:00
namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\CartService;
use MillenniumFalcon\Core\Service\ModelService;

trait OrderItemTrait
{
    protected $variant;

    /**
     * @param $pdo
     */
    static public function initData($pdo)
    {

    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objProductVariant()
    {
        if (!$this->variant) {
            $className = CartService::getProductVariantClassName();
            $fullClass = ModelService::fullClass($this->getPdo(), $className);
            $this->variant = $fullClass::getById($this->getPdo(), $this->getProductId());
        }
        return $this->variant;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objTitle()
    {
        $variant = $this->objProductVariant();
        return $variant->objTitle();
    }

    /**
     * @return bool
     */
    public function update($order, $customer)
    {
        $variant = $this->objProductVariant();
        if (!$variant || !$variant->getStatus()) {
            $this->delete();
            return false;
        }

        $product = $variant->objProduct();
        if ($product) {
            $this->setOnSaleActive($product->objOnSaleActive());
            $this->setNoPromoDiscount($product->getNoPromoDiscount());
            $this->setImageUrl($product->objImageUrl());
            $this->setProductPageUrl($product->objProductPageUrl());
        } else {
            $this->setOnSaleActive($variant->objOnSaleActive());
            $this->setNoPromoDiscount($variant->getNoPromoDiscount());
            $this->setImageUrl($variant->objImageUrl());
            $this->setProductPageUrl($variant->objProductPageUrl());
        }

        if ($this->getOnSaleActive()) {
            $this->setPrice($variant->calculatedSalePrice($customer));
        } else {
            $this->setPrice($this->getCompareAtPrice());
        }

        $this->setWeight($variant->getWeight());
        $this->setCompareAtPrice($variant->calculatedPrice($customer));

        $discountType = $order->getDiscountType();
        $discountValue = $order->getDiscountValue();

        $this->setDiscount(0);
        if ($discountType == 1 && !$this->getNoPromoDiscount()) {
            $afterDiscount = $this->getPrice() * (100 - $discountValue) / 100;
            $discountedTotal = $this->getPrice() - $afterDiscount;
            $this->setPrice($afterDiscount);
            $this->setDiscount($discountedTotal);
        }

        $this->setTotalPrice(($this->getPrice() ?: 0) * $this->getQuantity());
        $this->setTotalWeight(($this->getWeight() ?: 0) * $this->getQuantity());
        $this->setTotalDiscount(($this->getDiscount() ?: 0) * $this->getQuantity());
        $this->save();
        return true;
    }
}