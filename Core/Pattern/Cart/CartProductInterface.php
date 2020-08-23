<?php

namespace MillenniumFalcon\Core\Pattern\Cart;

Interface CartProductInterface
{
    //ORM
    public function getNoPromoDiscount();

    public function getNoMemberDiscount();

    public function getSalePrice();

    public function getPrice();

    public function getSaleStart();

    public function getSaleEnd();

    public function getOnSale();

    public function getLowStock();

    public function getOutOfStock();

    //Trait
    public function calculatedSalePrice($customer);

    public function calculatedPrice($customer);

    public function objOnSaleActive();

    public function objVariants();

    public function objImageUrl();

    public function objProductPageUrl();
}