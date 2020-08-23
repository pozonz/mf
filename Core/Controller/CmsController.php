<?php

namespace MillenniumFalcon\Core\Controller;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Controller\Traits\Cms\Install\CmsInstallTrait;
use MillenniumFalcon\Core\Controller\Traits\Cms\Core\CmsCoreModelTrait;
use MillenniumFalcon\Core\Controller\Traits\Cms\Core\CmsCoreOrmsTrait;
use MillenniumFalcon\Core\Controller\Traits\Cms\Core\CmsCoreOrmTrait;
use MillenniumFalcon\Core\Controller\Traits\Cms\Core\CmsCoreRestFileTrait;
use MillenniumFalcon\Core\Controller\Traits\Cms\Core\CmsCoreRestPageTrait;
use MillenniumFalcon\Core\Controller\Traits\Cms\Core\CmsCoreRestProductTrait;
use MillenniumFalcon\Core\Controller\Traits\Cms\Core\CmsCoreRestTrait;
use MillenniumFalcon\Core\Controller\Traits\Cms\Core\CmsCoreLoginTrait;
use MillenniumFalcon\Core\Controller\Traits\Cms\Core\CmsCoreTrait;
use MillenniumFalcon\Core\Controller\Traits\Cms\Core\CmsOrmCartTrait;
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
        CmsCoreRestFileTrait,
        CmsCoreRestPageTrait,
        CmsCoreRestProductTrait,
        CmsCoreTrait
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