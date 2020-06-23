<?php

namespace MillenniumFalcon\Core\Controller;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Controller\Traits\Web\Core\WebCoreAssetTrait;
use MillenniumFalcon\Core\RouterController;
use Symfony\Component\HttpKernel\KernelInterface;

class WebController extends RouterController
{
    use WebCoreAssetTrait
//        WebCartTrait,
//        WebCartFormTrait,
//        WebCartRestTrait,
//        WebCartAccountTrait,
//        WebCartAccountGoogleTrait,
//        WebCartAccountFacebookTrait,
//        WebTrait
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
     * WebController constructor.
     * @param Connection $connection
     * @param KernelInterface $kernel
     */
    public function __construct(Connection $connection, KernelInterface $kernel)
    {
        $this->connection = $connection;
        $this->kernel = $kernel;
    }

    public function getNodes()
    {
        return [];
    }
}