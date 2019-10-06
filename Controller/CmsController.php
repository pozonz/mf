<?php

namespace MillenniumFalcon\Controller;

use MillenniumFalcon\Core\RouterController;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CmsController extends RouterController
{
    use CmsOrmTrait,
        CmsModelTrait,
        CmsRestFileTrait,
        CmsRestProductTrait,
        CmsRestTrait,
        CmsCartAccountTrait,
        CmsCartAccountGoogleTrait,
        CmsTrait;

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        $dir = $this->container->getParameter('kernel.project_dir') . '/vendor/pozoltd/millennium-falcon/Resources/views';
        $loader = $this->container->get('twig')->getLoader();
        $loader->addPath($dir);
    }
}