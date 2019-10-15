<?php

namespace MillenniumFalcon\Core\Service;

use Symfony\Component\DependencyInjection\Container;
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
            if (!$orderContainer || $orderContainer->getCategory() != static::STATUS_UNPAID) {
                $orderContainer = new $fullClass($pdo);
                $orderContainer->setTitle(UtilsService::generateUniqueHex(24, []));
                $orderContainer->setCategory(static::STATUS_UNPAID);
                $orderContainer->setBillingSame(1);
                $orderContainer->save();
                $this->container->get('session')->set(static::SESSION_ID, $orderContainer->getId());
            }

            //convert 1/0 to boolean
            $orderContainer->setBillingSame($orderContainer->getBillingSame() ? true : false);
            $orderContainer->setBillingSave($orderContainer->getBillingSave() ? true : false);
            $orderContainer->setShippingSave($orderContainer->getShippingSave() ? true : false);

            $customer = $this->container->get('security.token_storage')->getToken()->getUser();
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
}