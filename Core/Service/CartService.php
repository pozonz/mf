<?php

namespace MillenniumFalcon\Core\Service;

use Doctrine\DBAL\Connection;
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
                $order->save();

                //reset the order id
                $order->setTitle(UtilsService::generateHex(4) . '-' . $order->getId());
                $order->save();

            } else if ($order->getCategory() != static::STATUS_UNPAID) {

                $oldOrder = clone $order;

                //created a new order and copy the current items over
                $order->setId(null);
                $order->setUniqId(Uuid::uuid4());
                $order->setAdded(date('Y-m-d H:i:s'));
                $order->setModified(date('Y-m-d H:i:s'));
                $order->setSubmitted(null);
                $order->setSubmittedDate(null);
                $order->setPayStatus(null);
                $order->setPayToken(null);
                $order->setPaySecret(null);
                $order->setPayType(null);
                $order->setEmailContent(null);
                $order->setHummRequestQuery(null);
                $order->setLogs(null);

                $order->setCategory(static::STATUS_UNPAID);
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
            $order->setCreateAnAccount($order->getCreateAnAccount() ? true : false);

            $customer = static::getCustomer();
            if ($customer) {
                $order->setCustomerId($customer->getId());
                $order->setCustomerName($customer->getFirstName() . ' ' . $customer->getLastName());

                //if empty, fill customer's info as default
                $order->setShippingFirstName($order->getShippingFirstName() ?: $customer->getFirstName());
                $order->setShippingLastName($order->getShippingLastName() ?: $customer->getLastName());
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
            $oi->setUniqId(Uuid::uuid4());
            $oi->setOrderId($newOrder->getId());
            $oi->setAdded(date('Y-m-d H:i:s'));
            $oi->setModified(date('Y-m-d H:i:s'));
            $oi->save();
        }
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
        $paymentMethod = ucfirst($code);

        $nameSpaces = [
            '\\App\\Cms\\Cart\\Payment\\',
            '\\MillenniumFalcon\\Cart\\Payment\\',
        ];
        foreach ($nameSpaces as $nameSpace) {
            $class = "{$nameSpace}Gateway{$paymentMethod}";
            if (class_exists($class)) {
                return new $class($this->connection, $this);
            }
        }
        return null;
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