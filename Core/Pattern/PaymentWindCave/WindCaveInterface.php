<?php

namespace MillenniumFalcon\Core\Pattern\PaymentWindCave;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;

interface WindCaveInterface
{
    public function getAmountInput();

    public function getEmail();

    public function getMerchantReference();

    public function getBillingId();

    public function getTxnId();

    public function getFinaliseUrl(Request $request);

    public function getPaymentToken();

    public function setPaymentStatus($paymentStatus);

    public function setPaymentToken($paymentToken);

    public function setPaymentGatewayRequest($paymentGatewayRequest);

    public function setPaymentGatewayResponse($paymentGatewayResponse);

    public function setPaymentStatusRequest($paymentStatusRequest);

    public function setPaymentStatusResponse($paymentStatusResponse);

    public function save($doNotSaveVersion = false, $options = []);

    static public function getByField(Connection $pdo, $field, $value);
}