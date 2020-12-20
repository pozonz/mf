<?php

namespace MillenniumFalcon\Core\ORM\Generated;

use MillenniumFalcon\Core\Db\Base;

class FormDescriptor extends Base
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
    private $formName;
    
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
    private $antispam;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $formFields;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $formOverviewText;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $thankyouHeading;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $sendThankYouEmail;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $thankYouEmailSubject;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $thankYouEmailText;
    
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
    public function getFormName()
    {
        return $this->formName;
    }
    
    /**
     * @param mixed formName
     */
    public function setFormName($formName)
    {
        $this->formName = $formName;
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
    public function getFormOverviewText()
    {
        return $this->formOverviewText;
    }
    
    /**
     * @param mixed formOverviewText
     */
    public function setFormOverviewText($formOverviewText)
    {
        $this->formOverviewText = $formOverviewText;
    }
    
    /**
     * @return mixed
     */
    public function getThankyouHeading()
    {
        return $this->thankyouHeading;
    }
    
    /**
     * @param mixed thankyouHeading
     */
    public function setThankyouHeading($thankyouHeading)
    {
        $this->thankyouHeading = $thankyouHeading;
    }
    
    /**
     * @return mixed
     */
    public function getSendThankYouEmail()
    {
        return $this->sendThankYouEmail;
    }
    
    /**
     * @param mixed sendThankYouEmail
     */
    public function setSendThankYouEmail($sendThankYouEmail)
    {
        $this->sendThankYouEmail = $sendThankYouEmail;
    }
    
    /**
     * @return mixed
     */
    public function getThankYouEmailSubject()
    {
        return $this->thankYouEmailSubject;
    }
    
    /**
     * @param mixed thankYouEmailSubject
     */
    public function setThankYouEmailSubject($thankYouEmailSubject)
    {
        $this->thankYouEmailSubject = $thankYouEmailSubject;
    }
    
    /**
     * @return mixed
     */
    public function getThankYouEmailText()
    {
        return $this->thankYouEmailText;
    }
    
    /**
     * @param mixed thankYouEmailText
     */
    public function setThankYouEmailText($thankYouEmailText)
    {
        $this->thankYouEmailText = $thankYouEmailText;
    }
    
}