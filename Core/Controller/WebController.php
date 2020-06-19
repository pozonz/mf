<?php

namespace MillenniumFalcon\Core\Controller;

use MillenniumFalcon\Core\Controller\Traits\WebAssetTrait;
use MillenniumFalcon\Core\Controller\Traits\WebCartAccountFacebookTrait;
use MillenniumFalcon\Core\Controller\Traits\WebCartAccountGoogleTrait;
use MillenniumFalcon\Core\Controller\Traits\WebCartAccountTrait;
use MillenniumFalcon\Core\Controller\Traits\WebCartFormTrait;
use MillenniumFalcon\Core\Controller\Traits\WebCartRestTrait;
use MillenniumFalcon\Core\Controller\Traits\WebCartTrait;
use MillenniumFalcon\Core\Controller\Traits\WebTrait;

use MillenniumFalcon\Core\RouterController;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WebController extends RouterController
{
    use WebCartTrait,
        WebCartFormTrait,
        WebCartRestTrait,
        WebCartAccountTrait,
        WebCartAccountGoogleTrait,
        WebCartAccountFacebookTrait,
        WebAssetTrait,
        WebTrait;

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        $dir = $this->container->getParameter('kernel.project_dir') . '/vendor/pozoltd/millennium-falcon/Resources/views';
        $loader = $this->container->get('twig')->getLoader();
        $loader->addPath($dir);
    }
}