<?php

namespace MillenniumFalcon\Core\ORM\Generated;

use MillenniumFalcon\Core\Db\Base;

class Order extends Base
{
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $email;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $category;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $shippingFirstname;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $shippingLastname;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $shippingPhone;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $shippingAddress;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $shippingAddress2;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $shippingCity;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $shippingPostcode;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $shippingState;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $shippingCountry;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $shippingSave;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $billingSame;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $billingFirstname;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $billingLastname;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $billingPhone;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $billingAddress;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $billingAddress2;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $billingCity;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $billingPostcode;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $billingState;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $billingCountry;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $billingSave;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $note;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $payStatus;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $payRequest;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $payResponse;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $payToken;
    
    /**
     * #pz datetime DEFAULT NULL
     */
    private $payDate;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $emailContent;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $customerId;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $customerName;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $shippingId;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $shippingTitle;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $promoId;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $promoCode;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $weight;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $subtotal;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $discount;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $afterDiscount;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $tax;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $shippingCost;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $total;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $submitted;
    
    /**
     * #pz datetime DEFAULT NULL
     */
    private $submittedDate;
    
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
    public function getCategory()
    {
        return $this->category;
    }
    
    /**
     * @param mixed category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }
    
    /**
     * @return mixed
     */
    public function getShippingFirstname()
    {
        return $this->shippingFirstname;
    }
    
    /**
     * @param mixed shippingFirstname
     */
    public function setShippingFirstname($shippingFirstname)
    {
        $this->shippingFirstname = $shippingFirstname;
    }
    
    /**
     * @return mixed
     */
    public function getShippingLastname()
    {
        return $this->shippingLastname;
    }
    
    /**
     * @param mixed shippingLastname
     */
    public function setShippingLastname($shippingLastname)
    {
        $this->shippingLastname = $shippingLastname;
    }
    
    /**
     * @return mixed
     */
    public function getShippingPhone()
    {
        return $this->shippingPhone;
    }
    
    /**
     * @param mixed shippingPhone
     */
    public function setShippingPhone($shippingPhone)
    {
        $this->shippingPhone = $shippingPhone;
    }
    
    /**
     * @return mixed
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }
    
    /**
     * @param mixed shippingAddress
     */
    public function setShippingAddress($shippingAddress)
    {
        $this->shippingAddress = $shippingAddress;
    }
    
    /**
     * @return mixed
     */
    public function getShippingAddress2()
    {
        return $this->shippingAddress2;
    }
    
    /**
     * @param mixed shippingAddress2
     */
    public function setShippingAddress2($shippingAddress2)
    {
        $this->shippingAddress2 = $shippingAddress2;
    }
    
    /**
     * @return mixed
     */
    public function getShippingCity()
    {
        return $this->shippingCity;
    }
    
    /**
     * @param mixed shippingCity
     */
    public function setShippingCity($shippingCity)
    {
        $this->shippingCity = $shippingCity;
    }
    
    /**
     * @return mixed
     */
    public function getShippingPostcode()
    {
        return $this->shippingPostcode;
    }
    
    /**
     * @param mixed shippingPostcode
     */
    public function setShippingPostcode($shippingPostcode)
    {
        $this->shippingPostcode = $shippingPostcode;
    }
    
    /**
     * @return mixed
     */
    public function getShippingState()
    {
        return $this->shippingState;
    }
    
    /**
     * @param mixed shippingState
     */
    public function setShippingState($shippingState)
    {
        $this->shippingState = $shippingState;
    }
    
    /**
     * @return mixed
     */
    public function getShippingCountry()
    {
        return $this->shippingCountry;
    }
    
    /**
     * @param mixed shippingCountry
     */
    public function setShippingCountry($shippingCountry)
    {
        $this->shippingCountry = $shippingCountry;
    }
    
    /**
     * @return mixed
     */
    public function getShippingSave()
    {
        return $this->shippingSave;
    }
    
    /**
     * @param mixed shippingSave
     */
    public function setShippingSave($shippingSave)
    {
        $this->shippingSave = $shippingSave;
    }
    
    /**
     * @return mixed
     */
    public function getBillingSame()
    {
        return $this->billingSame;
    }
    
    /**
     * @param mixed billingSame
     */
    public function setBillingSame($billingSame)
    {
        $this->billingSame = $billingSame;
    }
    
    /**
     * @return mixed
     */
    public function getBillingFirstname()
    {
        return $this->billingFirstname;
    }
    
    /**
     * @param mixed billingFirstname
     */
    public function setBillingFirstname($billingFirstname)
    {
        $this->billingFirstname = $billingFirstname;
    }
    
    /**
     * @return mixed
     */
    public function getBillingLastname()
    {
        return $this->billingLastname;
    }
    
    /**
     * @param mixed billingLastname
     */
    public function setBillingLastname($billingLastname)
    {
        $this->billingLastname = $billingLastname;
    }
    
    /**
     * @return mixed
     */
    public function getBillingPhone()
    {
        return $this->billingPhone;
    }
    
    /**
     * @param mixed billingPhone
     */
    public function setBillingPhone($billingPhone)
    {
        $this->billingPhone = $billingPhone;
    }
    
    /**
     * @return mixed
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }
    
    /**
     * @param mixed billingAddress
     */
    public function setBillingAddress($billingAddress)
    {
        $this->billingAddress = $billingAddress;
    }
    
    /**
     * @return mixed
     */
    public function getBillingAddress2()
    {
        return $this->billingAddress2;
    }
    
    /**
     * @param mixed billingAddress2
     */
    public function setBillingAddress2($billingAddress2)
    {
        $this->billingAddress2 = $billingAddress2;
    }
    
    /**
     * @return mixed
     */
    public function getBillingCity()
    {
        return $this->billingCity;
    }
    
    /**
     * @param mixed billingCity
     */
    public function setBillingCity($billingCity)
    {
        $this->billingCity = $billingCity;
    }
    
    /**
     * @return mixed
     */
    public function getBillingPostcode()
    {
        return $this->billingPostcode;
    }
    
    /**
     * @param mixed billingPostcode
     */
    public function setBillingPostcode($billingPostcode)
    {
        $this->billingPostcode = $billingPostcode;
    }
    
    /**
     * @return mixed
     */
    public function getBillingState()
    {
        return $this->billingState;
    }
    
    /**
     * @param mixed billingState
     */
    public function setBillingState($billingState)
    {
        $this->billingState = $billingState;
    }
    
    /**
     * @return mixed
     */
    public function getBillingCountry()
    {
        return $this->billingCountry;
    }
    
    /**
     * @param mixed billingCountry
     */
    public function setBillingCountry($billingCountry)
    {
        $this->billingCountry = $billingCountry;
    }
    
    /**
     * @return mixed
     */
    public function getBillingSave()
    {
        return $this->billingSave;
    }
    
    /**
     * @param mixed billingSave
     */
    public function setBillingSave($billingSave)
    {
        $this->billingSave = $billingSave;
    }
    
    /**
     * @return mixed
     */
    public function getNote()
    {
        return $this->note;
    }
    
    /**
     * @param mixed note
     */
    public function setNote($note)
    {
        $this->note = $note;
    }
    
    /**
     * @return mixed
     */
    public function getPayStatus()
    {
        return $this->payStatus;
    }
    
    /**
     * @param mixed payStatus
     */
    public function setPayStatus($payStatus)
    {
        $this->payStatus = $payStatus;
    }
    
    /**
     * @return mixed
     */
    public function getPayRequest()
    {
        return $this->payRequest;
    }
    
    /**
     * @param mixed payRequest
     */
    public function setPayRequest($payRequest)
    {
        $this->payRequest = $payRequest;
    }
    
    /**
     * @return mixed
     */
    public function getPayResponse()
    {
        return $this->payResponse;
    }
    
    /**
     * @param mixed payResponse
     */
    public function setPayResponse($payResponse)
    {
        $this->payResponse = $payResponse;
    }
    
    /**
     * @return mixed
     */
    public function getPayToken()
    {
        return $this->payToken;
    }
    
    /**
     * @param mixed payToken
     */
    public function setPayToken($payToken)
    {
        $this->payToken = $payToken;
    }
    
    /**
     * @return mixed
     */
    public function getPayDate()
    {
        return $this->payDate;
    }
    
    /**
     * @param mixed payDate
     */
    public function setPayDate($payDate)
    {
        $this->payDate = $payDate;
    }
    
    /**
     * @return mixed
     */
    public function getEmailContent()
    {
        return $this->emailContent;
    }
    
    /**
     * @param mixed emailContent
     */
    public function setEmailContent($emailContent)
    {
        $this->emailContent = $emailContent;
    }
    
    /**
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }
    
    /**
     * @param mixed customerId
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
    }
    
    /**
     * @return mixed
     */
    public function getCustomerName()
    {
        return $this->customerName;
    }
    
    /**
     * @param mixed customerName
     */
    public function setCustomerName($customerName)
    {
        $this->customerName = $customerName;
    }
    
    /**
     * @return mixed
     */
    public function getShippingId()
    {
        return $this->shippingId;
    }
    
    /**
     * @param mixed shippingId
     */
    public function setShippingId($shippingId)
    {
        $this->shippingId = $shippingId;
    }
    
    /**
     * @return mixed
     */
    public function getShippingTitle()
    {
        return $this->shippingTitle;
    }
    
    /**
     * @param mixed shippingTitle
     */
    public function setShippingTitle($shippingTitle)
    {
        $this->shippingTitle = $shippingTitle;
    }
    
    /**
     * @return mixed
     */
    public function getPromoId()
    {
        return $this->promoId;
    }
    
    /**
     * @param mixed promoId
     */
    public function setPromoId($promoId)
    {
        $this->promoId = $promoId;
    }
    
    /**
     * @return mixed
     */
    public function getPromoCode()
    {
        return $this->promoCode;
    }
    
    /**
     * @param mixed promoCode
     */
    public function setPromoCode($promoCode)
    {
        $this->promoCode = $promoCode;
    }
    
    /**
     * @return mixed
     */
    public function getWeight()
    {
        return $this->weight;
    }
    
    /**
     * @param mixed weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }
    
    /**
     * @return mixed
     */
    public function getSubtotal()
    {
        return $this->subtotal;
    }
    
    /**
     * @param mixed subtotal
     */
    public function setSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;
    }
    
    /**
     * @return mixed
     */
    public function getDiscount()
    {
        return $this->discount;
    }
    
    /**
     * @param mixed discount
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;
    }
    
    /**
     * @return mixed
     */
    public function getAfterDiscount()
    {
        return $this->afterDiscount;
    }
    
    /**
     * @param mixed afterDiscount
     */
    public function setAfterDiscount($afterDiscount)
    {
        $this->afterDiscount = $afterDiscount;
    }
    
    /**
     * @return mixed
     */
    public function getTax()
    {
        return $this->tax;
    }
    
    /**
     * @param mixed tax
     */
    public function setTax($tax)
    {
        $this->tax = $tax;
    }
    
    /**
     * @return mixed
     */
    public function getShippingCost()
    {
        return $this->shippingCost;
    }
    
    /**
     * @param mixed shippingCost
     */
    public function setShippingCost($shippingCost)
    {
        $this->shippingCost = $shippingCost;
    }
    
    /**
     * @return mixed
     */
    public function getTotal()
    {
        return $this->total;
    }
    
    /**
     * @param mixed total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }
    
    /**
     * @return mixed
     */
    public function getSubmitted()
    {
        return $this->submitted;
    }
    
    /**
     * @param mixed submitted
     */
    public function setSubmitted($submitted)
    {
        $this->submitted = $submitted;
    }
    
    /**
     * @return mixed
     */
    public function getSubmittedDate()
    {
        return $this->submittedDate;
    }
    
    /**
     * @param mixed submittedDate
     */
    public function setSubmittedDate($submittedDate)
    {
        $this->submittedDate = $submittedDate;
    }
    
}