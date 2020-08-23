<?php

namespace MillenniumFalcon\Core\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CartService
{
    const STATUS_UNPAID = 0;
    const STATUS_SUBMITTED = 1;
    const STATUS_SUCCESS = 2;

    const DELIVERY_HIDDEN = 0;
    const DELIVERY_VISIBLE = 1;

    const CUSTOMER_WEBSITE = 1;
    const CUSTOMER_GOOGLE = 2;
    const CUSTOMER_FACEBOOK = 3;

    const SESSION_ID = 'order_container_id';

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var null
     */
    protected $order = null;

    /**
     * CartService constructor.
     * @param Connection $container
     */
    public function __construct(Connection $connection, SessionInterface $session, TokenStorageInterface $tokenStorage)
    {
        $this->connection = $connection;
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return string|\Stringable|\Symfony\Component\Security\Core\User\UserInterface|null
     */
    public function getCustomer()
    {
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $customer = $token->getUser();
            if (gettype($customer) == 'object') {
                return $customer;
            }
        }
        return null;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getOrder()
    {
        if (!$this->order) {
            $fullClass = ModelService::fullClass($this->connection, 'Order');
            $orderId = $this->session->get(static::SESSION_ID);
            $order = $fullClass::getById($this->connection, $orderId);

            if (!$order) {

                //created a new order only
                $order = new $fullClass($this->connection);
                $order->setCategory(static::STATUS_UNPAID);
                $order->setBillingSame(1);
                $order->save();

                //reset the order id
                $order->setTitle(UtilsService::generateHex(4) . '-' . $order->getId());
                $order->save();

            } else if ($order->getCategory() != static::STATUS_UNPAID) {
                $oldOrder = clone $order;

                //created a new order and copy the current items over
                $order->setId(null);
                $order->setUniqId(uniqid());
                $order->setSubmitted(null);
                $order->setSubmittedDate(null);
                $order->setCategory(static::STATUS_UNPAID);
                $order->setAdded(date('Y-m-d H:i:s'));
                $order->setModified(date('Y-m-d H:i:s'));
                $order->save();

                //reset the order id
                $order->setTitle(UtilsService::generateHex(4) . '-' . $order->getId());
                $order->save();

                $this->copyOrderItems($order, $oldOrder);
            }

            $this->session->set(static::SESSION_ID, $order->getId());

            //convert 1/0 to boolean
            $order->setBillingSame($order->getBillingSame() ? true : false);
            $order->setBillingSave($order->getBillingSave() ? true : false);
            $order->setShippingSave($order->getShippingSave() ? true : false);

            $customer = static::getCustomer();
            if ($customer) {
                $order->setCustomerId($customer->getId());
                $order->setCustomerName($customer->getFirstName() . ' ' . $customer->getLastName());

                //if empty, fill customer's info as default
                $order->setBillingFirstName($order->getBillingFirstName() ?: $customer->getFirstName());
                $order->setBillingLastname($order->getBillingLastName() ?: $customer->getLastName());
                $order->setEmail($order->getEmail() && filter_var($order->getEmail(), FILTER_VALIDATE_EMAIL) ? $order->getEmail() : $customer->getTitle());
            }

            //update order
            $order->update($customer);

            //you know...
            $this->order = $order;
        }

        return $this->order;
    }

    /**
     * @param $newOrder
     * @param $oldOrder
     */
    protected function copyOrderItems($newOrder, $oldOrder)
    {
        foreach ($oldOrder->objOrderItems() as $oi) {
            $oi->setId(null);
            $oi->setUniqId(uniqid());
            $oi->setOrderId($newOrder->getId());
            $oi->save();
        }
    }

    /**
     * @return array|false|string
     */
    static public function getProductClassName()
    {
        return getenv('PRODUCT_CLASSNAME') ?: 'Product';
    }

    /**
     * @return array|false|string
     */
    static public function getProductVariantClassName()
    {
        return getenv('PRODUCT_VARIANT_CLASSNAME') ?: 'ProductVariant';
    }

    /**
     * @param $productOrVariant
     * @param $customer
     * @param $price
     * @return float|int
     */
    static public function getCalculatedPrice($productOrVariant, $customer, $price)
    {
        if ($productOrVariant->getNoMemberDiscount() || !$customer) {
            return $price;
        }
        $customerMembership = $customer->objMembership();
        if (!$customerMembership || !$customerMembership->getDiscount()) {
            return $price;
        }
        return $price * ((100 - $customerMembership->getDiscount()) / 100);
    }
}