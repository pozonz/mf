<?php

namespace MillenniumFalcon\Core\ORM\Generated;

use MillenniumFalcon\Core\Db\Base;

class User extends Base
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
    private $name;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $email;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $accessibleSections;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $image;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $resetToken;
    
    /**
     * #pz datetime DEFAULT NULL
     */
    private $resetDate;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $lastFailedLogin;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $lastSuccessfulLogin;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $failedLoginCount;
    
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
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * @param mixed name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
    
    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }
    
    /**
     * @param mixed email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }
    
    /**
     * @return mixed
     */
    public function getAccessibleSections()
    {
        return $this->accessibleSections;
    }
    
    /**
     * @param mixed accessibleSections
     */
    public function setAccessibleSections($accessibleSections)
    {
        $this->accessibleSections = $accessibleSections;
    }
    
    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }
    
    /**
     * @param mixed image
     */
    public function setImage($image)
    {
        $this->image = $image;
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
    public function getResetDate()
    {
        return $this->resetDate;
    }
    
    /**
     * @param mixed resetDate
     */
    public function setResetDate($resetDate)
    {
        $this->resetDate = $resetDate;
    }
    
    /**
     * @return mixed
     */
    public function getLastFailedLogin()
    {
        return $this->lastFailedLogin;
    }
    
    /**
     * @param mixed lastFailedLogin
     */
    public function setLastFailedLogin($lastFailedLogin)
    {
        $this->lastFailedLogin = $lastFailedLogin;
    }
    
    /**
     * @return mixed
     */
    public function getLastSuccessfulLogin()
    {
        return $this->lastSuccessfulLogin;
    }
    
    /**
     * @param mixed lastSuccessfulLogin
     */
    public function setLastSuccessfulLogin($lastSuccessfulLogin)
    {
        $this->lastSuccessfulLogin = $lastSuccessfulLogin;
    }
    
    /**
     * @return mixed
     */
    public function getFailedLoginCount()
    {
        return $this->failedLoginCount;
    }
    
    /**
     * @param mixed failedLoginCount
     */
    public function setFailedLoginCount($failedLoginCount)
    {
        $this->failedLoginCount = $failedLoginCount;
    }
    
}