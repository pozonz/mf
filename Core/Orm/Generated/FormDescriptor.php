<?php
//Last updated: 2019-11-19 22:57:46
namespace MillenniumFalcon\Core\Orm\Generated;

use MillenniumFalcon\Core\Orm;

class FormDescriptor extends Orm
{
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $code;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $fromAddress;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $recipients;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $formFields;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $thankYouMessage;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $antispam;
    
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
    public function getCode()
    {
        return $this->code;
    }
    
    /**
     * @param mixed code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }
    
    /**
     * @return mixed
     */
    public function getFromAddress()
    {
        return $this->fromAddress;
    }
    
    /**
     * @param mixed fromAddress
     */
    public function setFromAddress($fromAddress)
    {
        $this->fromAddress = $fromAddress;
    }
    
    /**
     * @return mixed
     */
    public function getRecipients()
    {
        return $this->recipients;
    }
    
    /**
     * @param mixed recipients
     */
    public function setRecipients($recipients)
    {
        $this->recipients = $recipients;
    }
    
    /**
     * @return mixed
     */
    public function getFormFields()
    {
        return $this->formFields;
    }
    
    /**
     * @param mixed formFields
     */
    public function setFormFields($formFields)
    {
        $this->formFields = $formFields;
    }
    
    /**
     * @return mixed
     */
    public function getThankYouMessage()
    {
        return $this->thankYouMessage;
    }
    
    /**
     * @param mixed thankYouMessage
     */
    public function setThankYouMessage($thankYouMessage)
    {
        $this->thankYouMessage = $thankYouMessage;
    }
    
    /**
     * @return mixed
     */
    public function getAntispam()
    {
        return $this->antispam;
    }
    
    /**
     * @param mixed antispam
     */
    public function setAntispam($antispam)
    {
        $this->antispam = $antispam;
    }
    
}