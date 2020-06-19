<?php

namespace MillenniumFalcon\Core\Controller;

use MillenniumFalcon\Core\Controller\Traits\CmsModelTrait;
use MillenniumFalcon\Core\Controller\Traits\CmsOrmCartTrait;
use MillenniumFalcon\Core\Controller\Traits\CmsOrmTrait;
use MillenniumFalcon\Core\Controller\Traits\CmsRestFileTrait;
use MillenniumFalcon\Core\Controller\Traits\CmsRestProductTrait;
use MillenniumFalcon\Core\Controller\Traits\CmsRestTrait;
use MillenniumFalcon\Core\Controller\Traits\CmsTrait;
use MillenniumFalcon\Core\RouterController;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CmsController extends RouterController
{
    use CmsOrmCartTrait,
        CmsOrmTrait,
        CmsModelTrait,
        CmsRestFileTrait,
        CmsRestProductTrait,
        CmsRestTrait,
        CmsTrait;

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        $dir = $this->container->getParameter('kernel.project_dir') . '/vendor/pozoltd/millennium-falcon/Resources/views';
        $loader = $this->container->get('twig')->getLoader();
        $loader->addPath($dir);
    }
}