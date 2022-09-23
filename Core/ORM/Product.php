<?php
//Last updated: 2019-09-16 21:43:24
namespace MillenniumFalcon\Core\ORM;

use MillenniumFalcon\Core\ORM\Traits\ProductTrait;
use MillenniumFalcon\Core\Pattern\Cart\CartProductInterface;
use MillenniumFalcon\Core\Pattern\Cart\CartProductTrait;
use MillenniumFalcon\Core\Pattern\Version\VersionInterface;
use MillenniumFalcon\Core\Pattern\Version\VersionTrait;

class Product extends \MillenniumFalcon\Core\ORM\Generated\Product
{
    use ProductTrait;
}