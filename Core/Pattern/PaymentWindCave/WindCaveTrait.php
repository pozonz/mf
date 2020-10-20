<?php

namespace MillenniumFalcon\Core\Pattern\PaymentWindCave;

use MillenniumFalcon\Core\SymfonyKernel\RedirectException;

trait WindCaveTrait
{
    /**
     * @throws RedirectException
     */
    public function validateGatewayValid()
    {
        if ($this->getPaymentStatus() == 2) {
            throw new RedirectException($this->getFinaliseUrl());
        }

        if ($this->getPaymentToken()) {
            throw new RedirectException($this->getPaymentToken());
        }
    }

    /**
     * @param $failedUrl
     * @throws RedirectException
     */
    public function validatePaymentConfirmed($failedUrl)
    {
        if ($this->getPaymentStatus() != 2) {
            throw new RedirectException($failedUrl);
        }
    }

    /**
     * @param $confirmedUrl
     * @throws RedirectException
     */
    public function validatePaymentFailed($confirmedUrl)
    {
        if ($this->getPaymentStatus() == 2) {
            throw new RedirectException($confirmedUrl);
        }
    }
}