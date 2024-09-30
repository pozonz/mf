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
        WebCoreAssetTrait,
        WebCoreTrait;

    public function __construct(
        readonly protected Connection $connection,
        readonly protected Environment $environment,
        readonly protected KernelInterface $kernel,
    ) {}
}
