<?php
//Last updated: 2020-04-17 14:52:17
namespace MillenniumFalcon\Core\Orm\Generated;

use MillenniumFalcon\Core\Orm;

class FormSubmission extends Orm
{
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $uniqueId;
    
    /**
     * #pz datetime DEFAULT NULL
     */
    private $date;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $fromAddress;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $recipients;
    
    /**
     * #pz mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $content;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $emailStatus;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $emailRequest;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $emailResponse;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $formDescriptorId;
    
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
    public function getUniqueId()
    {
        return $this->uniqueId;
    }
    
    /**
     * @param mixed uniqueId
     */
    public function setUniqueId($uniqueId)
    {
        $this->uniqueId = $uniqueId;
    }
    
    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }
    
    /**
     * @param mixed date
     */
    public function setDate($date)
    {
        $this->date = $date;
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
    public function getContent()
    {
        return $this->content;
    }
    
    /**
     * @param mixed content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
    
    /**
     * @return mixed
     */
    public function getEmailStatus()
    {
        return $this->emailStatus;
    }
    
    /**
     * @param mixed emailStatus
     */
    public function setEmailStatus($emailStatus)
    {
        $this->emailStatus = $emailStatus;
    }
    
    /**
     * @return mixed
     */
    public function getEmailRequest()
    {
        return $this->emailRequest;
    }
    
    /**
     * @param mixed emailRequest
     */
    public function setEmailRequest($emailRequest)
    {
        $this->emailRequest = $emailRequest;
    }
    
    /**
     * @return mixed
     */
    public function getEmailResponse()
    {
        return $this->emailResponse;
    }
    
    /**
     * @param mixed emailResponse
     */
    public function setEmailResponse($emailResponse)
    {
        $this->emailResponse = $emailResponse;
    }
    
    /**
     * @return mixed
     */
    public function getFormDescriptorId()
    {
        return $this->formDescriptorId;
    }
    
    /**
     * @param mixed formDescriptorId
     */
    public function setFormDescriptorId($formDescriptorId)
    {
        $this->formDescriptorId = $formDescriptorId;
    }
    
}