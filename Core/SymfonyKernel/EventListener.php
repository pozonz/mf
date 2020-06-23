<?php

namespace MillenniumFalcon\Core\SymfonyKernel;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Controller\WebController;
use MillenniumFalcon\Core\Exception\RedirectException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;


class EventListener
{
    const LAST_URI = '__lastUrl';

    const ALLOWED_URIS = [
        '/cart',
    ];

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection, Environment $environment)
    {
        $this->connection = $connection;
        $this->environment = $environment;

    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelController(RequestEvent $event)
    {
        /** @var Request $request */
        $request = $event->getRequest();
        $requestUri = $request->getRequestUri();
        $pathInfo = $request->getPathInfo();
        $session = $event->getRequest()->getSession();
        if (in_array($pathInfo, static::ALLOWED_URIS)) {
            $session->set(static::LAST_URI, $requestUri);
        }
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof NotFoundHttpException) {
            $event->setResponse(new Response($this->environment->render('404.twig')));
        } else if ($exception instanceof RedirectException) {
            $event->setResponse(new RedirectResponse($exception->getUrl()));
        }
    }
}
