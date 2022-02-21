<?php

namespace MillenniumFalcon\Cart\Service;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\ORM\ShippingByWeight;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\UtilsService;
use MillenniumFalcon\Core\SymfonyKernel\RedirectException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class CartService
{
    public $STATUS_NEW = 0;
    public $STATUS_CREATED = 10;
    public $STATUS_GATEWAY_SENT = 20;
    public $STATUS_ACCEPTED = 30;
    public $STATUS_DECLINED = 40;
    public $STATUS_OFFLINE = 50;

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
     * @param $id
     * @return mixed
     */
    public function getOrderById($id)
    {
        $orderTitle = $id;
        $fullClass = ModelService::fullClass($this->connection, 'Order');
        return $fullClass::getByField($this->connection, 'title', $orderTitle);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getCart()
    {
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

        } else if ($cart->getCategory() != $this->STATUS_NEW) {

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

            $cart->setCategory($this->STATUS_NEW);
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

        if (getenv('SHIPPING_PICKUP_ALLOWED') != 1) {
            $cart->setIsPickup(2);
        }

        $this->updateCart($cart);

        //you know...
        return $cart;
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
    public function updateCart($cart)
    {
        $customer = $this->getCustomer();
        $fullClass = ModelService::fullClass($this->connection, 'PromoCode');
        $promoCode = $fullClass::getByField($this->connection, 'code', $cart->getPromoCode());
        if ($promoCode && $promoCode->isValid()) {
            $cart->setDiscountType($promoCode->getType());
            $cart->setDiscountValue($promoCode->getValue());
            $cart->setPromoId($promoCode->getId());
        } else {
            $cart->setDiscountType(null);
            $cart->setDiscountValue(null);
            $cart->setPromoId(null);
        }

        $subtotal = 0;
        $weight = 0;
        $discount = 0;
        $afterDiscount = 0;
        $totalSaving = 0;

        $cartItems = $cart->objOrderItems();
        foreach ($cartItems as $idx => $itm) {
            $result = $this->updateCartItem($cart, $itm, $customer);
            if ($result) {
                $cartItemSubtotal = $itm->getPrice() * $itm->getQuantity();
                $cartItemWeight = $itm->getWeight() * $itm->getQuantity();

                $subtotal += $cartItemSubtotal;
                if ($cart->getDiscountType() == 2 && !$itm->objVariant()->objProduct()->getNoPromoDiscount()) {
                    $discount += round($cartItemSubtotal * ($cart->getDiscountValue() / 100), 2);
                }

                $weight += $cartItemWeight;
                if ($itm->getCompareAtPrice()) {
                    $totalSaving += ($itm->getCompareAtPrice() - $itm->getPrice()) * $itm->getQuantity();
                }
            }
        }
        $cart->setOrderitems(null);
        $cart->setTotalSaving($totalSaving);

        if ($cart->getDiscountType() == 1) {
            $discount = min($subtotal, $cart->getDiscountValue());
        }

        $afterDiscount = $subtotal - $discount;

        if ($cart->getIsPickup() == 2) {

            $data = $this->getDeliveryOptions($cart);
            $data = array_filter(array_map(function ($itm) {
                return isset($itm['deliveryOption']) && $itm['deliveryOption'] ? $itm['deliveryOption']->getId() : null;
            }, array_filter($data, function ($itm) {
                return $itm['valid'] === 1 ? 1 : 0;
            })));

            if (!in_array($cart->getShippingId(), $data)) {
                if (count($data) > 0) {
                    $cart->setShippingId($data[0]);
                } else {
                    $cart->setShippingId(null);
                }
            }

            if ($cart->getShippingId()) {
                $fullClass = ModelService::fullClass($this->connection, 'ShippingByWeight');
                $deliveryOption = $fullClass::getById($this->connection, $cart->getShippingId());

                $cart->setShippingTitle($deliveryOption->getTitle());
                $cart->setShippingCost($this->getDeliveryFee($cart, $deliveryOption));
            } else {
                $cart->setShippingTitle(null);
                $cart->setShippingCost(null);
            }

        } else {
            $cart->setShippingId(null);
            $cart->setShippingTitle(null);
            $cart->setShippingCost(null);
        }

        $deliveryFee = $cart->getShippingCost() ?: 0;
        $total = $afterDiscount + $deliveryFee;
        $gst = ($total * 3) / 23;

        $cart->setWeight($weight);
        $cart->setSubtotal($subtotal);
        $cart->setDiscount($discount);
        $cart->setAfterDiscount($afterDiscount);
        $cart->setTax($gst);
        $cart->setShippingCost($deliveryFee);
        $cart->setTotal($total);
        $cart->save();
        return true;
    }

    /**
     * @param $cart
     * @param $cartItem
     * @param $customer
     * @return bool
     */
    public function updateCartItem($cart, $cartItem, $customer)
    {
        if ($cartItem->getQuantity() <= 0) {
            $cartItem->delete();
            return false;
        }

        $variant = $cartItem->objVariant();
        if (!$variant || !$variant->getStatus()) {
            $cartItem->delete();
            return false;
        }

        $product = $variant->objProduct();
        if (!$product || !$product->getStatus()) {
            $cartItem->delete();
            return false;
        }

        if ($variant->getStockEnabled()) {
            $cartItem->setQuantity(min($cartItem->getQuantity(), $variant->getStock()));
        }

        if (!$cartItem->getQuantity()) {
            $cartItem->delete();
            return false;
        }

        $cartItem->setImageUrl('/images/assets/' . join('/', $product->objImage()));
        $cartItem->setProductPageUrl($product->objProductPageUrl());
        $cartItem->setWeight($variant->getShippingUnits() ?: 0);

        if ($product->objOnSaleActive() && $variant->getSalePrice()) {
            $cartItem->setPrice($variant->calculatedSalePrice($customer));
            $cartItem->setCompareAtPrice($variant->calculatedPrice($customer));
        } else {
            $cartItem->setPrice($variant->calculatedPrice($customer));
        }


        $discountType = $cart->getDiscountType();
        $discountValue = $cart->getDiscountValue();

        if ($discountType == 1 && !$product->getNoPromoDiscount()) {
            $cartItem->setCompareAtPrice($cartItem->getCompareAtPrice() ?: $cartItem->getPrice());
            $afterDiscount = $cartItem->getPrice() * (100 - $discountValue) / 100;
            $discountedTotal = $cartItem->getPrice() - $afterDiscount;
            $cartItem->setPrice($afterDiscount);
        }

        $cartItem->save();
        return true;
    }

    /**
     * @param $cart
     * @return array
     */
    public function getDeliveryOptions($cart)
    {
        $country = $cart->getShippingCountry();
        $fullClass = ModelService::fullClass($this->connection, 'ShippingZone');
        $ormCountry = $fullClass::getByField($this->connection, 'code', $country);
        if (!$ormCountry) {
            return [];
        }

        $deliveryOptions = [];
        $data = ShippingByWeight::active($this->connection, [
            'whereSql' => 'm.country = ?',
            'params' => [$ormCountry->getId()],
        ]);

        if (getenv('SHIPPING_PRICE_MODE') == 1) {
            $region = $cart->getShippingState();
            $ormRegion = $fullClass::getByField($this->connection, 'title', $region);
            $data = array_filter($data, function ($itm) use ($ormRegion) {
                if (!$ormRegion) {
                    return 1;
                }
                $objShippingCostRates = $itm->objShippingCostRates();
                foreach ($objShippingCostRates as $objShippingCostRate) {
                    if (in_array($ormRegion->getId(), $objShippingCostRate->regions) || in_array('all', $objShippingCostRate->regions)) {
                        return 1;
                    }
                }
                return 0;
            });

        } else if (getenv('SHIPPING_PRICE_MODE') == 2) {
            $postcode = $cart->getShippingPostcode();

            $data = array_filter($data, function ($itm) use ($postcode) {
                if (!$postcode) {
                    return 1;
                }
                $objShippingCostRates = $itm->objShippingCostRates();
                foreach ($objShippingCostRates as $objShippingCostRate) {
                    if ($objShippingCostRate->zipFrom && $objShippingCostRate->zipFrom > $postcode) {
                        continue;
                    }
                    if ($objShippingCostRate->zipTo && $objShippingCostRate->zipTo < $postcode) {
                        continue;
                    }
                    return 1;
                }
                return 0;
            });

        } else {
            $data = [];
        }

        foreach ($data as $itm) {
            $deliveryFee = $this->getDeliveryFee($cart, $itm);
            $deliveryOptions[] = [
                'deliveryOption' => $itm,
                'valid' => $deliveryFee === null ? 0 : 1,
                'fee' => $deliveryFee,
            ];
        }
        return $deliveryOptions;
    }

    /**
     * @param $deliveryOption
     * @return int
     */
    public function getDeliveryFee($cart, $deliveryOption)
    {
        $country = $cart->getShippingCountry();
        $fullClass = ModelService::fullClass($this->connection, 'ShippingZone');
        $ormCountry = $fullClass::getByField($this->connection, 'code', $country);
        if (!$ormCountry) {
            return null;
        }

        if ($deliveryOption->getCountry() !== $ormCountry->getId()) {
            return null;
        }

        if (getenv('SHIPPING_PRICE_MODE') == 1) {
            $region = $cart->getShippingState();
            $ormRegion = $fullClass::getByField($this->connection, 'title', $region);

            if (!$ormRegion) {
                return null;
            }

            $objShippingCostRates = $deliveryOption->objShippingCostRates();
            foreach ($objShippingCostRates as $objShippingCostRate) {

                if (in_array($ormRegion->getId(), $objShippingCostRate->regions) || in_array('all', $objShippingCostRate->regions)) {

                    $weight = $cart->getWeight();

                    foreach ($objShippingCostRate->extra as $itm) {
                        $from = $itm->from ?: 0;
                        $to = $itm->to ?: 0;

                        if ($weight >= $from && $weight <= $to) {
                            return $itm->price;
                        }
                    }

                    return $objShippingCostRate->price * $weight;
                }
            }

        } else if (getenv('SHIPPING_PRICE_MODE') == 2) {
            $postcode = $cart->getShippingPostcode();

            $objShippingCostRates = $deliveryOption->objShippingCostRates();
            foreach ($objShippingCostRates as $objShippingCostRate) {
                if ($objShippingCostRate->zipFrom && $objShippingCostRate->zipFrom > $postcode) {
                    continue;
                }
                if ($objShippingCostRate->zipTo && $objShippingCostRate->zipTo < $postcode) {
                    continue;
                }

                $weight = $cart->getWeight();

                foreach ($objShippingCostRate->extra as $itm) {
                    $from = $itm->from ?: 0;
                    $to = $itm->to ?: 0;

                    if ($weight >= $from && $weight <= $to) {
                        return $itm->price;
                    }
                }

                return $objShippingCostRate->price * $weight;
            }
        }


        return null;
    }

    /**
     * @return array
     */
    public function getDeliverableCountries()
    {
        $data = ShippingByWeight::active($this->connection);
        return array_filter(array_map(function ($itm) {
            return $itm->objCountry();
        }, $data));
    }

    /**
     * @param $cart
     * @return array
     */
    public function getDeliverableRegions($cart)
    {
        $fullClass = ModelService::fullClass($this->connection, 'ShippingZone');
        $orm = $fullClass::getByField($this->connection, 'code', $cart->getShippingCountry());
        if (!$orm) {
            throw new NotFoundHttpException();
        }

        $fullClass = ModelService::fullClass($this->connection, 'ShippingByWeight');
        $data = ShippingByWeight::active($this->connection);
        $data = array_filter($data, function ($itm) use ($orm) {
            return $itm->getCountry() == $orm->getId() ? 1 : 0;
        });

        $regions = [];
        foreach ($data as $itm) {
            $objShippingCostRates = $itm->objShippingCostRates();
            foreach ($objShippingCostRates as $objShippingCostRate) {
                foreach ($objShippingCostRate->regions as $region) {
                    if ($region === 'all') {
                        $regions = array_merge($regions, array_map(function ($itm) use ($orm) {
                            return $itm->getTitle();
                        }, $orm->objChildren()));
                    } else {
                        $fullClass = ModelService::fullClass($this->connection, 'ShippingZone');
                        $r = $fullClass::getById($this->connection, $region);
                        $regions[] = $r ? $r->getTitle() : null;
                    }
                }
            }
        }

        $regions = array_filter(array_unique($regions));
        sort($regions);
        return $regions;
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
     * @return mixed
     */
    public function sendEmailInvoice($order)
    {
        $messageBody = $this->environment->render('/cart/email-invoice.twig', array(
            'order' => $order,
        ));
        $message = (new \Swift_Message())
            ->setSubject((getenv('EMAIL_ORDER_SUBJECT') ?: 'Your order has been received - #') . " - #{$order->getTitle()}")
            ->setFrom([
                getenv('EMAIL_FROM') => getenv('EMAIL_FROM_NAME')
            ])
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
}