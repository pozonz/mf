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
use Twig\Environment;

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
     * @var Environment
     */
    protected $environment;

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var null
     */
    protected $_cart = null;

    /**
     * CartService constructor.
     * @param Connection $container
     */
    public function __construct(Connection $connection, SessionInterface $session, TokenStorageInterface $tokenStorage, Environment $environment, \Swift_Mailer $mailer)
    {
        $this->connection = $connection;
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->environment = $environment;
        $this->mailer = $mailer;
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

            $this->updateOrder($cart, $customer);

            //you know...
            $this->_cart = $cart;
        }

        return $this->_cart;
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
     * @param $order
     * @return bool
     */
    public function updateOrder($order)
    {
        $customer = $this->getCustomer();
        $fullClass = ModelService::fullClass($this->connection, 'PromoCode');
        $promoCode = $fullClass::getByField($this->connection, 'code', $order->getPromoCode());
        if ($promoCode && $promoCode->isValid()) {
            $order->setDiscountType($promoCode->getType());
            $order->setDiscountValue($promoCode->getValue());
            $order->setPromoId($promoCode->getId());
        } else {
            $order->setDiscountType(null);
            $order->setDiscountValue(null);
            $order->setPromoId(null);
        }

        $subtotal = 0;
        $weight = 0;
        $discount = 0;
        $afterDiscount = 0;

        $orderItems = $order->objOrderItems();
        foreach ($orderItems as $idx => $itm) {
            $result = $this->updateOrderItem($order, $itm, $customer);
            if ($result) {
                $orderItemSubtotal = $itm->getPrice() * $itm->getQuantity();
                $orderItemWeight = $itm->getWeight() * $itm->getQuantity();

                $subtotal += $orderItemSubtotal;
                if ($order->getDiscountType() == 2 && !$itm->objVariant()->objProduct()->getNoPromoDiscount()) {
                    $discount += round($orderItemSubtotal * ($order->getDiscountValue() / 100), 2);
                }

                $weight += $orderItemWeight;
            }
        }
        $order->setOrderitems(null);

        if ($order->getDiscountType() == 1) {
            $discount = min($subtotal, $order->getDiscountValue());
        }

        $afterDiscount = $subtotal - $discount;

        if ($order->getIsPickup() == 2) {
            $deliveryOption = $this->getDeliveryOption($order);
            if ($deliveryOption) {
                $order->setShippingTitle($deliveryOption->getTitle());
                $order->setShippingCost($this->getDeliveryFee($deliveryOption));
            } else {
                $order->setShippingId(null);
                $order->setShippingTitle(null);
                $order->setShippingCost(null);
            }
        } else {
            $order->setShippingCost(null);
        }

        $deliveryFee = $order->getShippingCost() ?: 0;
        $total = $afterDiscount + $deliveryFee;
        $gst = ($total * 3) / 23;

        $order->setWeight($weight);
        $order->setSubtotal($subtotal);
        $order->setDiscount($discount);
        $order->setAfterDiscount($afterDiscount);
        $order->setTax($gst);
        $order->setShippingCost($deliveryFee);
        $order->setTotal($total);
        $order->save();
        return true;
    }

    /**
     * @param $order
     * @param $orderItem
     * @param $customer
     * @return bool
     */
    public function updateOrderItem($order, $orderItem, $customer)
    {
        if ($orderItem->getQuantity() <= 0) {
            $orderItem->delete();
            return false;
        }

        $variant = $orderItem->objVariant();
        if (!$variant || !$variant->getStatus()) {
            $orderItem->delete();
            return false;
        }

        $product = $variant->objProduct();
        if (!$product || !$product->getStatus()) {
            $orderItem->delete();
            return false;
        }

        if ($variant->getStockEnabled()) {
            $orderItem->setQuantity(min($orderItem->getQuantity(), $variant->getStock()));
        }

        if (!$orderItem->getQuantity()) {
            $orderItem->delete();
            return false;
        }

        $orderItem->setImageUrl('/images/assets/' . join('/', $product->objImage()));
        $orderItem->setProductPageUrl($product->objProductPageUrl());
        $orderItem->setWeight($variant->getShippingUnits() ?: 0);

        if ($product->objOnSaleActive() && $variant->getSalePrice()) {
            $orderItem->setPrice($variant->calculatedSalePrice($customer));
            $orderItem->setCompareAtPrice($variant->calculatedPrice($customer));
        } else {
            $orderItem->setPrice($variant->calculatedPrice($customer));
        }


        $discountType = $order->getDiscountType();
        $discountValue = $order->getDiscountValue();

        if ($discountType == 1 && !$product->getNoPromoDiscount()) {
            $orderItem->setCompareAtPrice($orderItem->getCompareAtPrice() ?: $orderItem->getPrice());
            $afterDiscount = $orderItem->getPrice() * (100 - $discountValue) / 100;
            $discountedTotal = $orderItem->getPrice() - $afterDiscount;
            $orderItem->setPrice($afterDiscount);
        }

        $orderItem->save();
        return true;
    }

    /**
     * @param $order
     * @return mixed
     */
    public function sendEmailInvoice($order)
    {
        $messageBody = $this->environment->render('email-invoice.twig', array(
            'order' => $order,
        ));
        $message = (new \Swift_Message())
            ->setSubject("Invoice {$order->getTitle()}")
            ->setFrom(getenv('EMAIL_FROM'))
            ->setTo([$order->getEmail()])
            ->setBcc(array_filter(explode(',', getenv('EMAIL_BCC_ORDER'))))
            ->setBody(
                $messageBody, 'text/html'
            );
        return $this->mailer->send($message);
    }

    /**
     * @param $order
     */
    public function updateStock($order)
    {
        foreach ($order->objOrderItems() as $orderItem) {
            $variant = $orderItem->objVariant();
            if ($variant) {
                $variant->setStock($variant->getStock() - $orderItem->getQuantity());
                $variant->save();
            }
        }
    }

    /**
     *
     */
    public function clearCart()
    {
        $this->session->set(static::SESSION_ID, null);
    }

    /**
     * @param $order
     * @return int
     */
    public function getDeliveryOption($order)
    {
        return null;
    }

    /**
     * @param $deliveryOption
     * @return int
     */
    public function getDeliveryFee($deliveryOption)
    {
        return 0;
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
     * @param $cartItem
     * @param $variant
     */
    public function setCustomOrderItem($cartItem, $variant)
    {

    }
}