<?php

namespace MillenniumFalcon\Core\Exception;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Controller\WebController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class KernelExceptionListener extends WebController
{
    /**
     * @var Connection
     */
    protected $connection;

    const LAST_URI = '__lastUrl';

    const ALLOWED_URIS = [
        '/cart',
    ];

    /**
     * KernelExceptionListener constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
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
        $params = null;

        try {
            $params = $this->getTemplateParams($request);
        } catch (NotFoundHttpException $ex) {
        } catch (RedirectException $ex) {
        }

        $session = $event->getRequest()->getSession();
        if ($params || in_array($pathInfo, static::ALLOWED_URIS)) {
            $session->set(static::LAST_URI, $requestUri);
        }
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $e = $event->getException();

        //For custom redirect exception
        do {
            if ($e instanceof NotFoundHttpException) {
                $event->setResponse($this->render('404.twig', [
                    'node' => null,
                ]));
            }

            //do we have a redirect exception?
            if ($e instanceof RedirectException) {
                $event->setResponse(new RedirectResponse($e->getUrl(), $e->getStatusCode()));
                return;
            }

            //this could be bad..
            if ($e instanceof \HttpException && $event->getException() !== $e) {
                $event->setException($e);
                return;
            }

        } while (null !== $e = $e->getPrevious());
    }
}
