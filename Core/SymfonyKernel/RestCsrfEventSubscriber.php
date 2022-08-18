<?php

namespace MillenniumFalcon\Core\SymfonyKernel;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class RestCsrfEventSubscriber implements EventSubscriberInterface
{
    protected CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onRequest'],
        ];
    }

    public function onRequest(RequestEvent  $event)
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/manage/rest')) {
            return;
        }

        if ($this->isRequestTokenValid($request)) {
            return;
        }

        throw new AccessDeniedHttpException();
    }

    protected function isRequestTokenValid(Request $request): bool
    {
        $token = $request->headers->get('session-rest-token');
        $isValid = $this->csrfTokenManager->isTokenValid(new CsrfToken('session-rest-token', $token));
        return $isValid;
    }

}
