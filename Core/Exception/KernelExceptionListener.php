<?php

namespace MillenniumFalcon\Core\Exception;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Controller\WebController;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\UtilsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class KernelExceptionListener extends WebController
{
    const LAST_URI = '__lastUrl';
    const ALLOWED_URIS = [
        '/cart',
    ];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function onKernelController(RequestEvent $event)
    {
        $request = $event->getRequest();
        $path = rtrim($request->getPathInfo(), '/');
        $uri = $request->getRequestUri();

        $pdo = $this->container->get('doctrine')->getConnection();

        $params = null;
        try {
            $params = $this->getParams($path);
        } catch (NotFoundHttpException $ex) {
        } catch (RedirectException $ex) {
        }

        $session = $event->getRequest()->getSession();
        if ($params || in_array($path, static::ALLOWED_URIS)) {
            $session->set(static::LAST_URI, $uri);
        }
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $e = $event->getException();

        //For custom redirect exception
        do {
            if ($e instanceof NotFoundHttpException) {
                $event->setResponse($this->render('404.html.twig', [
                    'node' => null,
                ]));
            }

            //do we have a redirect exception?
            if ($e instanceof RedirectException) {
                $event->setResponse(new RedirectResponse($e->getUrl(), $e->getStatusCode()));
                return;
            }

            //this could be bad..
            if ($e instanceof HttpException && $event->getException() !== $e) {
                $event->setException($e);
                return;
            }

        } while (null !== $e = $e->getPrevious());
    }
}
