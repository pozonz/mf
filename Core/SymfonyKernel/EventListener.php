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
    const LAST_URL = '__lastUrl';

    const REDIRET_IGNORED_CONTAINED_PARTS = [
        '/manage',
        '/install',
        '/import',
        '/_fragment',
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
        $queryString = $request->getQueryString();

        //redirect to small case
        if (!$this->isContainPart($pathInfo, static::REDIRET_IGNORED_CONTAINED_PARTS, [ 'isAtStartOnly' => 1, ])) {
            if (strtolower($pathInfo) !== $pathInfo) {
                $newPathInfo = strtolower($pathInfo);
                $event->setResponse(new RedirectResponse($newPathInfo . ($queryString ? '?' . $queryString : '')));
                return;
            }
        }

        //redirect for cms setup
        $fullClass = ModelService::fullClass($this->connection, 'Redirect');
        $redirect = $fullClass::getByField($this->connection, 'title', $pathInfo);
        if ($redirect && $redirect->getStatus() == 1) {
            return $event->setResponse(new RedirectResponse($redirect->getTo()));
        }
    }

    /**
     * @param GetResponseForExceptionEvent $event
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
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
            $request = $event->getRequest();
            $pathInfo = $request->getPathInfo();
            if (
                strpos($pathInfo, '/manage') === 0
            ) {
                $event->setResponse(
                    new RedirectResponse('/manage/after-login')
                );
                return;
            }

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

    /**
     * @param $toBeCompared
     * @param $parts
     * @return bool
     */
    protected function isContainPart($toBeCompared, $parts, $options = [])
    {
        $isAtStartOnly = $options['isAtStartOnly'] ?? 0;

        foreach ($parts as $itm) {
            if ($isAtStartOnly) {
                if (strpos($toBeCompared, $itm) === 0) {
                    return true;
                }
            } else {
                if (strpos($toBeCompared, $itm) !== false) {
                    return true;
                }
            }

        }
        return false;
    }
}
