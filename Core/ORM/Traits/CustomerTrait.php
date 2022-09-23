<?php

namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\User\UserInterface;

trait CustomerTrait
{
    protected $membership;

    /**
     * @return array|null
     */
    public function objMembership()
    {
        if (!$this->membership) {
            $fullClass = ModelService::fullClass($this->getPdo(), 'CustomerMembership');
            $this->membership = $fullClass::getById($this->getPdo(), $this->getMembership());
        }
        return $this->membership;
    }

    /**
     * @return array|null
     */
    public function objAddresses()
    {
        $fullClass = ModelService::fullClass($this->getPdo(), 'CustomerAddress');
        return $fullClass::active($this->getPdo(), array(
            'whereSql' => 'm.customerId = ?',
            'params' => [$this->getId()],
        ));
    }

    /**
     * @param bool $doNotSaveVersion
     * @param array $options
     */
    public function save($doNotSaveVersion = false, $options = [])
    {
        if ($this->getPasswordInput()) {
            $encoder = new MessageDigestPasswordEncoder();
            $this->setPassword($encoder->encodePassword($this->getPasswordInput(), ''));
            $this->setPasswordInput(null);
        }
        return parent::save($doNotSaveVersion, $options);
    }

    /**
     * @return mixed|string
     */
    public function getPassword()
    {
        return parent::getPassword() ?: '';
    }

    /**
     * The equality comparison should neither be done by referential equality
     * nor by comparing identities (i.e. getId() === getId()).
     *
     * However, you do not need to compare every attribute, but only those that
     * are relevant for assessing whether re-authentication is required.
     *
     * @return bool
     */
    public function isEqualTo(UserInterface $user)
    {
        if ($this->getPassword() !== $user->getPassword()) {

            return false;
        }

        if ($this->getSalt() !== $user->getSalt()) {
            return false;
        }

        if ($this->getUsername() !== $user->getUsername()) {
            return false;
        }

        return true;
    }

    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return (Role|string)[] The user roles
     */
    public function getRoles()
    {
        return array('ROLE_ADMIN');
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return '';
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->getTitle();
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
        return $this;
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {

        $fields = array_keys(static::getFields());

        $obj = new \stdClass();
        foreach ($fields as $field) {
            $getMethod = "get" . ucfirst($field);
            $obj->{$field} = $this->$getMethod();
        }
        return serialize($obj);
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        $obj = unserialize($serialized);
        foreach ($obj as $idx => $itm) {
            $setMethod = "set" . ucfirst($idx);
            $this->$setMethod($itm);
        }

        $conn = \Doctrine\DBAL\DriverManager::getConnection(array(
            'url' => ($_ENV['DATABASE_URL'] ?? false),
        ), new \Doctrine\DBAL\Configuration());
        $this->setPdo($conn);
    }
}
