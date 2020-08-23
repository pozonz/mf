<?php

namespace MillenniumFalcon\Core\Controller;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Controller\Traits\Web\Cart\WebCartAjaxTrait;
use MillenniumFalcon\Core\Controller\Traits\Web\Cart\WebCartPageTrait;
use MillenniumFalcon\Core\Controller\Traits\Web\Core\WebCoreAssetTrait;
use MillenniumFalcon\Core\Controller\Traits\Web\Core\WebCoreTrait;
use MillenniumFalcon\Core\RouterController;
use MillenniumFalcon\Core\Service\CartService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;

class WebController extends RouterController
{
    use
        WebCartAjaxTrait,
        WebCartPageTrait,
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