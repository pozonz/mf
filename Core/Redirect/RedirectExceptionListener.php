<?php

namespace MillenniumFalcon\Core\Redirect;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RedirectExceptionListener
{

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        //check for either a redirect exception, or a redirect

        // if we have a twig exception, or some form of redirect exception, unpack
        // and handle accordingly..

        $e = $event->getException();
        do {
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