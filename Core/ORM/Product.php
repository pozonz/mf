<?php
//Last updated: 2019-09-16 21:43:24
namespace MillenniumFalcon\Core\ORM;

use MillenniumFalcon\Core\ORM\Traits\ProductTrait;
use MillenniumFalcon\Core\Pattern\Cart\CartProductInterface;
use MillenniumFalcon\Core\Pattern\Cart\CartProductTrait;

class Product extends \MillenniumFalcon\Core\ORM\Generated\Product implements CartProductInterface
{
    use ProductTrait, CartProductTrait;

    /**
     * To be overwritten
     * @return string
     */
    public function objImageUrl()
    {
        return '';
    }
}