<?php

namespace MillenniumFalcon\Core\Pattern\Cart;

Interface CartProductVariantInterface
{
    //ORM
    public function getNoPromoDiscount();

    public function getNoMemberDiscount();

    public function getSalePrice();

    public function getPrice();

    public function getSaleStart();

    public function getSaleEnd();

    public function getOnSale();

    public function getSku();

    public function getWeight();

    public function getStock();

    public function getAlertIfLessThan();

    //Trait
    public function calculatedSalePrice($customer);

    public function calculatedPrice($customer);

    public function objLowStock();

    public function objOutOfStock();

    public function objProduct();

    public function objTitle();

    public function objOnSaleActive();

    public function objImageUrl();

    public function objProductPageUrl();
}