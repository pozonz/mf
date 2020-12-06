<?php

namespace MillenniumFalcon\Core\SymfonyKernel;

use BlueM\Tree\Node;
use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Controller\WebController;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\SymfonyKernel\RedirectException;
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

        if (
            strpos($requestUri, '/manage') === 0
            || strpos($requestUri, '/install') === 0
            || strpos($requestUri, '/import') === 0
            || strpos($requestUri, "/_fragment") === 0
        ) {
            return;
        }

        $redirectRequired = false;
        if (strtolower($requestUri) !== $requestUri) {
            $requestUri = strtolower($requestUri);
            $redirectRequired = true;
        }

        if ($redirectRequired) {
            $event->setResponse(new RedirectResponse($requestUri));
        }

        $fullClass = ModelService::fullClass($this->connection, 'Redirect');
        $redirect = $fullClass::getByField($this->connection, 'title', $pathInfo);
        if ($redirect && $redirect->getStatus() == 1) {
            return $event->setResponse(new RedirectResponse($redirect->getTo()));
        }
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if (!($exception instanceof RedirectException) && !($exception instanceof NotFoundHttpException)) {
            $exception = $exception->getPrevious();
        }

        if ($exception instanceof RedirectException) {
            $event->setResponse(
                new RedirectResponse($exception->getUrl())
            );
            return;
        }

        if ($exception instanceof NotFoundHttpException) {
            $fullClass = ModelService::fullClass($this->connection, 'Page');

            $page404Id = getenv('PAGE_404_ID');
            if ($page404Id) {
                $page = $fullClass::getById($this->connection, $page404Id);
                if ($page) {
                    $event->setResponse(
                        new Response(
                            $this->environment->render($page->objPageTemplate()->getFilename(), [
                                'theNode' => new Node(uniqid(), uniqid(), [
                                    'extraInfo' => $page,
                                ])
                            ])
                        )
                    );
                }
            }
        }
    }
}
