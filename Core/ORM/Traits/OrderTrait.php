<?php
//Last updated: 2019-09-27 09:24:36
namespace MillenniumFalcon\Core\ORM\Traits;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Service\ModelService;

trait OrderTrait
{
    protected $_orderItems;

    public function __construct(Connection $pdo)
    {
        $this->setCategory(0);
        $this->setCreateAnAccount(0);
        $this->setBillingSame(1);
        $this->setBillingSave(1);
        $this->setBillingUseExisting(0);
        $this->setShippingSave(1);
        $this->setShippingUseExisting(0);

        parent::__construct($pdo);
    }

    /**
     * @return mixed|string
     */
    public function objShippingAddress()
    {
        $address = $this->getShippingAddress();
        if ($this->getShippingApartmentNo()) {
            $address = $this->getShippingApartmentNo() . ', ' . $address;
        }
        if ($this->getShippingAddress2()) {
            $address = $address . ', ' . $this->getShippingAddress2();
        }
        if ($this->getShippingCity()) {
            $address = $address . ', ' . $this->getShippingCity();
        }
        if ($this->getShippingPostcode()) {
            $address = $address . ' ' . $this->getShippingPostcode();
        }
        if ($this->getShippingCountry()) {
            $address = $address . ', ' . $this->getShippingCountry();
        }
        return $address;
    }

    /**
     * @return mixed|string
     */
    public function objBillingAddress()
    {
        if ($this->getBillingSame()) {
            return $this->objShippingAddress();
        }
        $address = $this->getBillingAddress();
        if ($this->getBillingApartmentNo()) {
            $address = $this->getBillingApartmentNo() . ', ' . $address;
        }
        if ($this->getBillingAddress2()) {
            $address = $address . ', ' . $this->getBillingAddress2();
        }
        if ($this->getBillingCity()) {
            $address = $address . ', ' . $this->getBillingCity();
        }
        if ($this->getBillingPostcode()) {
            $address = $address . ' ' . $this->getBillingPostcode();
        }
        if ($this->getBillingCountry()) {
            $address = $address . ', ' . $this->getBillingCountry();
        }
        return $address;
    }

    /**
     * @param $orderItems
     */
    public function setOrderItems($orderItems)
    {
        $this->_orderItems = $orderItems;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objOrderItems()
    {
        if (!$this->_orderItems) {
            $fullClass = ModelService::fullClass($this->getPdo(), 'OrderItem');
            $this->_orderItems = $fullClass::active($this->getPdo(), array(
                'whereSql' => 'm.orderId = ?',
                'params' => array($this->getId()),
                'sort' => 'm.id',
                'order' => 'DESC',
            ));
        }
        return $this->_orderItems;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objJsonOrderItems()
    {
        return $this->objOrderItems();
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objCustomer()
    {
        $fullClass = ModelService::fullClass($this->getPdo(), 'Customer');
        return $fullClass::active($this->getPdo(), array(
            'whereSql' => 'm.id = ?',
            'params' => array($this->getCustomerId()),
            'limit' => 1,
            'oneOrNull' => 1,
        ));
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function objShippingOptions()
    {

    }

    /**
     * @return mixed
     */
    public function objHummRequestQuery()
    {
        return (array)json_decode($this->getHummRequestQuery());
    }

    /**
     * @return string
     */
    static public function getCmsOrmsTwig()
    {
        return 'cms/orms/orms-custom-order.html.twig';
    }

//    /**
//     * @return string
//     */
//    static public function getCmsOrmTwig()
//    {
//        return 'cms/orms/orm-custom-order.html.twig';
//    }
}