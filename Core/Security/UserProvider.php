<?php

namespace MillenniumFalcon\Core\Security;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
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
        $fullClass = ModelService::fullClass($this->conn->getDatabase(), 'User');
        var_dump($fullClass);exit;
        if (!$user instanceof \Pz\Orm\User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        $username = $user->getUsername();

        return $this->fetchUser($username);
    }

    public function supportsClass($class)
    {
//        return User::class === $class;
    }

    private function fetchUser($username)
    {

//        $pdo = $this->conn->getWrappedConnection();
//
//        /** @var User $user */
//        $user = User::getByField($pdo, 'title', $username);
//
//        if (!$user) {
//            throw new UsernameNotFoundException(
//                sprintf('Username "%s" does not exist.', $username)
//            );
//        }
//
//        if ($user->getStatus() != 1) {
//            throw new UsernameNotFoundException(
//                sprintf('User "%s" is disabled.', $username)
//            );
//        }
//
//        return $user;

        return null;
    }
}