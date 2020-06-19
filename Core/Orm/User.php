<?php

namespace MillenniumFalcon\Core\ORM;

use MillenniumFalcon\Core\ORM\Traits\UserTrait;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User extends \MillenniumFalcon\Core\ORM\Generated\User implements UserInterface, EquatableInterface, \Serializable
{
    use UserTrait;
}