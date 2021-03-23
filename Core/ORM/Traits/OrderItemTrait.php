<?php
//Last updated: 2019-09-27 09:55:00
namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait OrderItemTrait
{
    protected $variant;

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objVariant()
    {
        if (!$this->variant) {
            $fullClass = ModelService::fullClass($this->getPdo(), 'ProductVariant');
            $this->variant = $fullClass::getById($this->getPdo(), $this->getProductId());
        }
        return $this->variant;
    }

    /**
     * @return bool
     */
    public function update($order, $customer)
    {
        if ($this->getQuantity() <= 0) {
            $this->delete();
            return false;
        }

        $variant = $this->objVariant();
        if (!$variant || !$variant->getStatus()) {
            $this->delete();
            return false;
        }

        $product = $variant->objProduct();
        if (!$product || !$product->getStatus()) {
            $this->delete();
            return false;
        }

        $this->setImageUrl('/images/assets/' . join('/', $product->objImage()));
        $this->setProductPageUrl($product->objProductPageUrl());
        $this->setWeight($variant->getShippingUnits() ?: 0);

        if ($product->objOnSaleActive() && $variant->getSalePrice()) {
            $this->setPrice($variant->calculatedSalePrice($customer));
            $this->setCompareAtPrice($variant->calculatedPrice($customer));
        } else {
            $this->setPrice($variant->calculatedPrice($customer));
        }


        $discountType = $order->getDiscountType();
        $discountValue = $order->getDiscountValue();

        if ($discountType == 1 && !$product->getNoPromoDiscount()) {
            $this->setCompareAtPrice($this->getCompareAtPrice() ?: $this->getPrice());
            $afterDiscount = $this->getPrice() * (100 - $discountValue) / 100;
            $discountedTotal = $this->getPrice() - $afterDiscount;
            $this->setPrice($afterDiscount);
        }

        $this->save();
        return true;
    }
}