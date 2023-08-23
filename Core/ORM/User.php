<?php

namespace MillenniumFalcon\Core\ORM;

use MillenniumFalcon\Core\ORM\Traits\UserTrait;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use GravitateNZ\fta\auth\Security\LockableUserInterface;

class User extends \MillenniumFalcon\Core\ORM\Generated\User implements UserInterface, EquatableInterface, \Serializable, LockableUserInterface
{
    use UserTrait;


    /**
     * @return mixed
     * @Assert\Length(min=12)
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

    public function incrementFailedLogins(?\DateTimeInterface $time = null): int
    {
        $count = ($this->getFailedLoginCount() ?? 0) + 1;
        $this->setFailedLoginCount($count);
        $this->setLastFailedLogin(
            date('Y-m-d H:i:s')
        );
        $this->save();
         
        return $count;
    }

    public function clearLoginAttempts(): void
    {
        $c = $this->getFailedLoginCount();
        $this->setFailedLoginCount(0);
        $this->setLastSuccessfulLogin(
            date('Y-m-d H:i:s')
        );
        $this->setLastFailedLogin(null);
        $this->save();
    }

    public function isLocked(int $limit, \DateTime $date): bool
    {
        $l = $this->getLastFailedLogin();
        $lastLogin = new \DateTime($l);
        $ll = $date <= $lastLogin;
        $lc = $this->getFailedLoginCount();

        return $ll && $lc >= $limit;
    }

    public function getFailedLoginCount(): int
    {
        return (int) parent::getFailedLoginCount();
    }
}
