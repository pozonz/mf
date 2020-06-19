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
    /**
     * UserProvider constructor.
     * @param Connection $conn
     */
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @param string $username
     * @return UserInterface
     */
    public function loadUserByUsername($username)
    {
        return $this->fetchUser($username);
    }

    /**
     * @param UserInterface $user
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user)
    {
        return $this->fetchUser($user->getUsername());
    }

    /**
     * @param string $class
     * @return bool
     * @throws \Exception
     */
    public function supportsClass($class)
    {
        $pdo = $this->conn;
        $fullClass = ModelService::fullClass($pdo, 'User');

        $fullClass = ltrim($fullClass, '\\');
        $class = ltrim($class, '\\');

        return $fullClass === $class;
    }

    /**
     * @param $username
     * @return mixed
     * @throws \Exception
     */
    private function fetchUser($username)
    {

        if ($username == 'NONE_PROVIDED') {
            throw new UsernameNotFoundException(
                sprintf('Please enter a username')
            );
        }

        $pdo = $this->conn;
        $fullClass = ModelService::fullClass($pdo, 'User');
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

        if (!$this->supportsClass($fullClass)) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $user;
    }
}