<?php

namespace MillenniumFalcon\Core\Service;

use Symfony\Component\DependencyInjection\Container;
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

    protected $orderContainer;

    /**
     * Shop constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

}