<?php

namespace MillenniumFalcon\Core\ORM\Generated;

use MillenniumFalcon\Core\Db\Base;

class Customer extends Base
{
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $password;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $passwordInput;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $firstname;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $lastname;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $source;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $sourceId;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $resetToken;
    
    /**
     * #pz datetime DEFAULT NULL
     */
    private $resetExpiry;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $isActivated;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $membership;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $description;
    
    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }
    
    /**
     * @param mixed title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }
    
    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }
    
    /**
     * @param mixed password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }
    
    /**
     * @return mixed
     */
    public function getPasswordInput()
    {
        return $this->passwordInput;
    }
    
    /**
     * @param mixed passwordInput
     */
    public function setPasswordInput($passwordInput)
    {
        $this->passwordInput = $passwordInput;
    }
    
    /**
     * @return mixed
     */
    public function getFirstname()
    {
        return $this->firstname;
    }
    
    /**
     * @param mixed firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }
    
    /**
     * @return mixed
     */
    public function getLastname()
    {
        return $this->lastname;
    }
    
    /**
     * @param mixed lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }
    
    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }
    
    /**
     * @param mixed source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }
    
    /**
     * @return mixed
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }
    
    /**
     * @param mixed sourceId
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;
    }
    
    /**
     * @return mixed
     */
    public function getResetToken()
    {
        return $this->resetToken;
    }
    
    /**
     * @param mixed resetToken
     */
    public function setResetToken($resetToken)
    {
        $this->resetToken = $resetToken;
    }
    
    /**
     * @return mixed
     */
    public function getResetExpiry()
    {
        return $this->resetExpiry;
    }
    
    /**
     * @param mixed resetExpiry
     */
    public function setResetExpiry($resetExpiry)
    {
        $this->resetExpiry = $resetExpiry;
    }
    
    /**
     * @return mixed
     */
    public function getIsActivated()
    {
        return $this->isActivated;
    }
    
    /**
     * @param mixed isActivated
     */
    public function setIsActivated($isActivated)
    {
        $this->isActivated = $isActivated;
    }
    
    /**
     * @return mixed
     */
    public function getMembership()
    {
        return $this->membership;
    }
    
    /**
     * @param mixed membership
     */
    public function setMembership($membership)
    {
        $this->membership = $membership;
    }
    
    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }
    
    /**
     * @param mixed description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
    
}