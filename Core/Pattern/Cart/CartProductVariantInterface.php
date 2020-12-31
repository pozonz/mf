<?php

namespace MillenniumFalcon\Core\Pattern\Cart;

Interface CartProductVariantInterface
{
    //ORM
    public function getPrice();

    public function getSku();

    public function getShippingUnits();

    public function getStock();

    public function getAlertIfLessThan();

    //Trait
    public function calculatedSalePrice($customer);

    public function calculatedPrice($customer);

    public function objLowStock();

    public function objOutOfStock();

    public function objProduct();

    public function objImageUrl();

    public function objProductPageUrl();
}