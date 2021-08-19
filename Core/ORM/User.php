<?php

namespace MillenniumFalcon\Core\ORM;

use MillenniumFalcon\Core\ORM\Traits\UserTrait;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

class User extends \MillenniumFalcon\Core\ORM\Generated\User implements UserInterface, EquatableInterface, \Serializable
{
    use UserTrait;


    /**
     * @return mixed
     * @Assert\Length(min=8)
     * @Assert\NotCompromisedPassword()
     */
    public function getPasswordInput(): ?string
    {
        return parent::getPasswordInput();
    }

    /**
     * @param mixed passwordInput
     */
    public function setPasswordInput($passwordInput): void
    {
        parent::setPasswordInput($passwordInput);
    }

}
