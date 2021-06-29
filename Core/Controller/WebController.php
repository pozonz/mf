<?php

namespace MillenniumFalcon\Core\Controller;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Cart\Service\CartService;
use MillenniumFalcon\Cart\ControllerTraits\CartAjaxTrait;
use MillenniumFalcon\Cart\ControllerTraits\CartPageTrait;
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
        CartPageTrait,
        WebCoreAssetTrait,
        WebCoreTrait

        ;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * WebController constructor.
     * @param Connection $connection
     * @param KernelInterface $kernel
     * @param CartService $cartService
     * @param Environment $environment
     */
    public function __construct(Connection $connection, KernelInterface $kernel, CartService $cartService)
    {
        $this->connection = $connection;
        $this->kernel = $kernel;
        $this->cartService = $cartService;
    }
}