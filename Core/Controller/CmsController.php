<?php

namespace MillenniumFalcon\Core\Controller;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Controller\Traits\CmsCoreModelTrait;
use MillenniumFalcon\Core\Controller\Traits\CmsCoreOrmsTrait;
use MillenniumFalcon\Core\Controller\Traits\CmsCoreOrmTrait;
use MillenniumFalcon\Core\Controller\Traits\CmsCoreRestPageTrait;
use MillenniumFalcon\Core\Controller\Traits\CmsCoreRestTrait;
use MillenniumFalcon\Core\Controller\Traits\CmsInstallTrait;
use MillenniumFalcon\Core\Controller\Traits\CmsCoreLoginTrait;
use MillenniumFalcon\Core\Controller\Traits\CmsCoreTrait;
use MillenniumFalcon\Core\Controller\Traits\CmsOrmCartTrait;
use MillenniumFalcon\Core\Controller\Traits\CmsRestFileTrait;
use MillenniumFalcon\Core\Controller\Traits\CmsRestProductTrait;
use MillenniumFalcon\Core\RouterController;
use Symfony\Component\HttpKernel\KernelInterface;

class CmsController extends RouterController
{
    use
        CmsInstallTrait,
        CmsCoreLoginTrait,
        CmsCoreModelTrait,
        CmsCoreOrmsTrait,
        CmsCoreOrmTrait,

        CmsCoreRestTrait,
        CmsCoreRestPageTrait,

        CmsRestFileTrait,

//        CmsOrmCartTrait,
//        CmsRestProductTrait,

        CmsCoreTrait;

    /**
     * CmsController constructor.
     * @param Connection $connection
     * @param KernelInterface $kernel
     */
    public function __construct(Connection $connection, KernelInterface $kernel)
    {
        $this->connection = $connection;
        $this->kernel = $kernel;
    }
}