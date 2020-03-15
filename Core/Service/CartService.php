<?php

namespace MillenniumFalcon\Core\Service;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

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
    protected $orderContainer;

    /**
     * Shop constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getOrderContainer()
    {
        if (!$this->orderContainer) {
            $pdo = $this->container->get('doctrine.dbal.default_connection');

            $fullClass = ModelService::fullClass($pdo, 'Order');
            $id = $this->container->get('session')->get(static::SESSION_ID);
            $orderContainer = $fullClass::getById($pdo, $id);
            $oldOrderContainer = null;
            if (!$orderContainer || $orderContainer->getCategory() != static::STATUS_UNPAID) {
                if ($orderContainer && $orderContainer->getCategory() == static::STATUS_SUBMITTED) {
                    $oldOrderContainer = clone $orderContainer;

                    $orderContainer->setId(null);
                    $orderContainer->setUniqId(uniqid());
                    $orderContainer->setSubmitted(null);
                    $orderContainer->setSubmittedDate(null);
                    $orderContainer->setTitle(UtilsService::generateUniqueHex(24, []));
                    $orderContainer->setCategory(static::STATUS_UNPAID);
                    $orderContainer->setAdded(date('Y-m-d H:i:s'));
                    $orderContainer->setModified(date('Y-m-d H:i:s'));
                    $orderContainer->save();

                } else {

                    $orderContainer = new $fullClass($pdo);
                    $orderContainer->setTitle(UtilsService::generateUniqueHex(24, []));
                    $orderContainer->setCategory(static::STATUS_UNPAID);
                    $orderContainer->setBillingSame(1);
                    $orderContainer->save();

                }

                $this->container->get('session')->set(static::SESSION_ID, $orderContainer->getId());

            }

            if ($oldOrderContainer) {
                $this->reorder($orderContainer, $oldOrderContainer);
            }

            //convert 1/0 to boolean
            $orderContainer->setBillingSame($orderContainer->getBillingSame() ? true : false);
            $orderContainer->setBillingSave($orderContainer->getBillingSave() ? true : false);
            $orderContainer->setShippingSave($orderContainer->getShippingSave() ? true : false);

            $customer = UtilsService::getUser($this->container);
            if (gettype($customer) == 'object') {
                $orderContainer->setCustomerId($customer->getId());
                $orderContainer->setCustomerName($customer->getFirstName() . ' ' . $customer->getLastName());

                if (!$orderContainer->getEmail()) {
                    $orderContainer->setEmail($customer->getTitle());
                }
            }

            $request = Request::createFromGlobals();
            if (strpos($request->getPathInfo(), '/cart') === 0) {
                $orderContainer->update($customer);
            }

            $this->orderContainer = $orderContainer;
        }

        return $this->orderContainer;
    }

    /**
     * @param $newOrderContainer
     * @param $oldOrderContainer
     * @return JsonResponse
     * @throws \Exception
     */
    public function reorder($newOrderContainer, $oldOrderContainer)
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');
        $customer = UtilsService::getUser($this->container);

        $result = [];

        foreach ($oldOrderContainer->objOrderItems() as $oi) {

            $fullClass = ModelService::fullClass($pdo, 'ProductVariant');
            $variant = $fullClass::getById($pdo, $oi->getProductId());
            if ($variant || ($variant && $variant->getStock() == 0)) {
                $product = $variant->objProduct();

                $stockInCart = 0;
                $fullClass = ModelService::fullClass($pdo, 'OrderItem');
                $orderItem = new $fullClass($pdo);
                $orderItem->setTitle($product->objTitle() . ' - ' . $variant->getTitle());
                $orderItem->setSku($variant->getSku());
                $orderItem->setOrderId($newOrderContainer->getId());
                $orderItem->setProductId($variant->getId());
                $orderItem->setPrice($variant->objPrice($customer));
                $orderItem->setWeight($variant->getWeight());
                $orderItem->setQuantity(0);

                $orderItems = $newOrderContainer->objOrderItems();
                foreach ($orderItems as $itm) {
                    if ($itm->getProductId() == $variant->getId()) {
                        $orderItem = $itm;
                        $stockInCart = $itm->getQuantity();
                    }
                }

                if ($variant->getStock() < ($oi->getQuantity() + $stockInCart)) {
                    $result[] = [
                        'title' => $oi->getTitle(),
                        'message' => 'The product does not have enough stock',
                    ];

                    $orderItem->setQuantity($variant->getStock());
                } else {
                    $orderItem->setQuantity($oi->getQuantity() + $orderItem->getQuantity());
                }

                $orderItem->save();

            } else {
                $result[] = [
                    'title' => $oi->getTitle(),
                    'message' => 'The product is not availble any more',
                ];
            }

        }

        return new JsonResponse($result);
    }
}