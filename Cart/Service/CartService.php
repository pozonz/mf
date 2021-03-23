<?php

namespace MillenniumFalcon\Cart\Service;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\UtilsService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CartService
{
    const CUSTOMER_WEBSITE = 1;
    const CUSTOMER_GOOGLE = 2;
    const CUSTOMER_FACEBOOK = 3;

    const SESSION_ID = '__order_container_id';

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
    protected $_cart = null;

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
    public function getCart()
    {
        if (!$this->_cart) {
            $fullClass = ModelService::fullClass($this->connection, 'Order');
            $orderId = $this->session->get(static::SESSION_ID);
            $cart = $fullClass::getById($this->connection, $orderId);

            if (!$cart) {

                //created a new order only
                $cart = new $fullClass($this->connection);
                $cart->save();

                //reset the order id
                $cart->setTitle(UtilsService::generateHex(4) . '-' . $cart->getId());
                $cart->save();

            } else if ($cart->getCategory() != $this->getStatusNew()) {

                $oldOrder = clone $cart;

                //created a new order and copy the current items over
                $cart->setId(null);
                $cart->setUniqId(Uuid::uuid4());
                $cart->setAdded(date('Y-m-d H:i:s'));
                $cart->setModified(date('Y-m-d H:i:s'));
                $cart->setSubmitted(null);
                $cart->setSubmittedDate(null);
                $cart->setPayStatus(null);
                $cart->setPayToken(null);
                $cart->setPaySecret(null);
                $cart->setPayType(null);
                $cart->setEmailContent(null);
                $cart->setHummRequestQuery(null);
                $cart->setLogs(null);

                $cart->setCategory($this->getStatusNew());
                $cart->save();

                //reset the order id
                $cart->setTitle(UtilsService::generateHex(4) . '-' . $cart->getId());
                $cart->save();

                $this->copyOrderItems($cart, $oldOrder);
            }

            $this->session->set(static::SESSION_ID, $cart->getId());

            //convert 1/0 to boolean
            $cart = $this->setBooleanValues($cart);

            $customer = static::getCustomer();
            if ($customer) {
                $cart->setCustomerId($customer->getId());
                $cart->setCustomerName($customer->getFirstName() . ' ' . $customer->getLastName());

                //if empty, fill customer's info as default
                $cart->setShippingFirstName($cart->getShippingFirstName() ?: $customer->getFirstName());
                $cart->setShippingLastName($cart->getShippingLastName() ?: $customer->getLastName());
                $cart->setEmail($cart->getEmail() && filter_var($cart->getEmail(), FILTER_VALIDATE_EMAIL) ? $cart->getEmail() : $customer->getTitle());
            }

            //update order
            $cart->update($customer);

            //you know...
            $this->_cart = $cart;
        }

        return $this->_cart;
    }

    /**
     * @param $cart
     * @return mixed
     */
    public function setBooleanValues($cart)
    {
        $cart->setBillingSame($cart->getBillingSame() ? true : false);
        $cart->setBillingSave($cart->getBillingSave() ? true : false);
        $cart->setShippingSave($cart->getShippingSave() ? true : false);
        $cart->setCreateAnAccount($cart->getCreateAnAccount() ? true : false);
        return $cart;
    }

    /**
     * @return array
     */
    public function getGatewayClasses()
    {
        $gatewayClasses = [];

        $paymentMethods = explode(',', getenv('PAYMENT_METHODS'));
        foreach ($paymentMethods as $paymentMethod) {
            $gatewayClasses[] = $this->getGatewayClass($paymentMethod);
        }
        return array_filter($gatewayClasses);
    }

    /**
     * @param $code
     * @return mixed
     */
    public function getGatewayClass($code)
    {
        $nameSpaces = [
            '\\App\\Cart\\Payment\\',
            '\\MillenniumFalcon\\Cart\\Payment\\',
        ];
        foreach ($nameSpaces as $nameSpace) {
            $class = "{$nameSpace}Gateway{$code}";
            if (class_exists($class)) {
                return new $class($this->connection, $this);
            }
        }
        return null;
    }

    /**
     * @param $newOrder
     * @param $oldOrder
     */
    public function copyOrderItems($newOrder, $oldOrder)
    {
        foreach ($oldOrder->objOrderItems() as $oi) {
            $oi->setId(null);
            $oi->setUniqId(Uuid::uuid4());
            $oi->setOrderId($newOrder->getId());
            $oi->setAdded(date('Y-m-d H:i:s'));
            $oi->setModified(date('Y-m-d H:i:s'));
            $oi->save();
        }
    }

    /**
     * @return int
     */
    public function getStatusNew()
    {
        return 0;
    }

    /**
     * @return int
     */
    public function getStatusCreated()
    {
        return 10;
    }

    /**
     * @return int
     */
    public function getStatusGatewaySent()
    {
        return 20;
    }

    /**
     * @return int
     */
    public function getStatusAccepted()
    {
        return 30;
    }

    /**
     * @return int
     */
    public function getStatusDeclined()
    {
        return 40;
    }

    /**
     * @param $type
     * @param $url
     * @param $request
     * @param $response
     * @param $status
     * @param string $seconds
     * @return \stdClass
     */
    public function getLogBlock($type, $url, $request, $response, $status, $seconds = '')
    {
        $block = new \stdClass();
        $block->id = Uuid::uuid4();
        $block->title = 'Booking log';
        $block->status = 1;
        $block->block = "13";
        $block->twig = "_";
        $block->values = new \stdClass();
        $block->values->type = $type;
        $block->values->status = $status;
        $block->values->url = $url;
        $block->values->date = date('d M Y H:i:s');
        $block->values->secondsUsed = $seconds;
        $block->values->request = $request;
        $block->values->response = $response;
        return $block;
    }

    /**
     * @param $sections
     * @return \stdClass[]
     */
    public function getLogBlankSections($sections)
    {
        $section = new \stdClass();
        $section->id = Uuid::uuid4();
        $section->title = 'Logs';
        $section->attr = 'logs';
        $section->status = 1;
        $section->tags = ["11"];
        $section->blocks = [];
        return [$section];
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
}