<?php

namespace MillenniumFalcon\Controller;

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