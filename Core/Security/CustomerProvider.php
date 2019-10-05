<?php

namespace MillenniumFalcon\Core\Security;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class CustomerProvider implements UserProviderInterface
{
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function loadUserByUsername($username)
    {
        return $this->fetchUser($username);
    }

    public function refreshUser(UserInterface $user)
    {
        $username = $user->getUsername();
        return $this->fetchUser($username);
    }

    public function supportsClass($class)
    {
        return true;
    }

    private function fetchUser($username)
    {

        $pdo = $this->conn;

        $fullClass = ModelService::fullClass($pdo, 'Customer');
        $user = $fullClass::getByField($pdo, 'title', $username);

        if (!$user) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $username)
            );
        }

        if ($user->getStatus() != 1) {
            throw new UsernameNotFoundException(
                sprintf('User "%s" is disabled.', $username)
            );
        }

        return $user;
    }
}