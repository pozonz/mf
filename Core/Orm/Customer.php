<?php
//Last updated: 2019-09-27 10:36:35
namespace MillenniumFalcon\Core\Orm;

use MillenniumFalcon\Core\Orm\Traits\CustomerTrait;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class Customer extends \MillenniumFalcon\Core\Orm\Generated\Customer implements UserInterface, EquatableInterface, \Serializable
{
    use CustomerTrait;
}