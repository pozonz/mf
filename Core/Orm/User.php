<?php

namespace MillenniumFalcon\Core\Orm;

use MillenniumFalcon\Core\Orm\Traits\UserTrait;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User extends \MillenniumFalcon\Core\Orm\Generated\User implements UserInterface, EquatableInterface, \Serializable
{
    use UserTrait;
}