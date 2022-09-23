<?php

namespace MillenniumFalcon\Core\Controller;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Controller\Traits\Cms\Core\CmsCoreProductTrait;
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
use Symfony\Component\Security\Core\Security;

class CmsController extends RouterController
{
    use CmsCoreProductTrait,
        CmsInstallTrait,
        CmsCoreLoginTrait,
        CmsCoreModelTrait,
        CmsCoreOrmsTrait,
        CmsCoreOrmTrait,
        CmsCoreRestTrait,
        CmsCoreRestFileTrait,
        CmsCoreRestPageTrait,
        CmsCoreRestProductTrait,
        CmsCoreTrait;


    protected Connection $connection;
    protected KernelInterface $kernel;
    protected Security $security;

    /**
     * CmsController constructor.
     * @param Connection $connection
     * @param KernelInterface $kernel
     */
    public function __construct(Connection $connection, KernelInterface $kernel, Security $security)
    {
        $this->connection = $connection;
        $this->kernel = $kernel;
        $this->security = $security;
    }
}
