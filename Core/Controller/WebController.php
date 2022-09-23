<?php

namespace MillenniumFalcon\Core\Controller;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Cart\Service\CartService;
use MillenniumFalcon\Cart\ControllerTraits\ShopPageTrait;
use MillenniumFalcon\Cart\ControllerTraits\CartPageTrait;
use MillenniumFalcon\Cart\ControllerTraits\CartRestfulTrait;
use MillenniumFalcon\Core\Controller\Traits\Web\Core\WebCoreAssetTrait;
use MillenniumFalcon\Core\Controller\Traits\Web\Core\WebCoreTrait;
use MillenniumFalcon\Core\RouterController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;

class WebController extends RouterController
{
    const AB_TEST_TOKEN_NAME = '_abt';

    use
        ShopPageTrait,
        CartPageTrait,
        CartRestfulTrait,
        WebCoreAssetTrait,
        WebCoreTrait;

    protected Connection $connection;
    protected KernelInterface $kernel;
    protected CartService $cartService;

    /**
     * WebController constructor.
     * @param Connection $connection
     * @param KernelInterface $kernel
     * @param Environment $environment
     * @param CartService $cartService
     */
    public function __construct(Connection $connection, KernelInterface $kernel, Environment $environment, CartService $cartService)
    {
        $this->connection = $connection;
        $this->kernel = $kernel;
        $this->environment = $environment;
        $this->cartService = $cartService;
    }
}
