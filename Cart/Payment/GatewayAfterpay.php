<?php

namespace MillenniumFalcon\Cart\Payment;

use MillenniumFalcon\Cart\Payment\PaymentInterface;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use MillenniumFalcon\Core\Service\ModelService;
use Ramsey\Uuid\Uuid;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class GatewayAfterpay extends AbstractGateway
{
    protected $info;

    /**
     * PaymentInterface constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection, $cartService)
    {
        parent::__construct($connection, $cartService);

        $fullClass = ModelService::fullClass($this->connection, 'PaymentInstallmentInfo');
        if ($fullClass) {
            $this->info = $fullClass::getActiveByTitle($this->connection, $this->getId());
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getOrder(Request $request)
    {
        $token = $request->get('orderToken');
        $fullClass = ModelService::fullClass($this->connection, 'Order');
        return $fullClass::getByField($this->connection, 'payToken', $token);
    }

    /**
     * @param Request $request
     * @param $order
     * @return false|mixed|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function retrieveRedirectUrl(Request $request, $order)
    {
        $start = time();
        $authorization = base64_encode(($_ENV['AFTERPAY_MID'] ?? false) . ':' . ($_ENV['AFTERPAY_MKEY'] ?? false));
        $query = [
            RequestOptions::JSON => [
                "totalAmount" => [
                    "amount" => $order->getTotal(),
                    "currency" => 'NZD'
                ],
                "consumer" => [
                    "phoneNumber" => $order->getShippingPhone(),
                    "givenNames" => $order->getShippingFirstname(),
                    "surname" => $order->getShippingLastname(),
                    "email" => $order->getEmail(),
                ],
                "shipping" => [
                    "name" => substr($order->getShippingFirstname() . ' ' . $order->getShippingLastname(), 0, 255),
                    "line1" => substr($order->getShippingAddress(), 0, 128),
                    "line2" => substr($order->getShippingAddress2(), 0, 128),
                    "state" => substr($order->getShippingCity(), 0, 128),
                    "postcode" => substr($order->getShippingPostcode(), 0, 128),
                    "countryCode" => substr($order->getShippingCountry(), 0, 128),
                    "phoneNumber" => substr($order->getShippingPhone(), 0, 128),
                ],
                "billing" => [
                    "name" => substr($order->getBillingSame() ? $order->getShippingFirstname() . ' ' . $order->getShippingLastname() : $order->getShippingFirstname() . ' ' . $order->getShippingLastname(), 0, 255),
                    "line1" => substr($order->getBillingSame() ? $order->getShippingAddress() : $order->getShippingAddress(), 0, 128),
                    "line2" => substr($order->getBillingSame() ? $order->getShippingAddress2() : $order->getShippingAddress2(), 0, 128),
                    "state" => substr($order->getBillingSame() ? $order->getShippingCity() : $order->getShippingCity(), 0, 128),
                    "postcode" => substr($order->getBillingSame() ? $order->getShippingPostcode() : $order->getShippingPostcode(), 0, 128),
                    "countryCode" => substr($order->getBillingSame() ? $order->getShippingCountry() : $order->getShippingPhone(), 0, 128),
                    "phoneNumber" => substr($order->getBillingSame() ? $order->getShippingPhone() : $order->getShippingPhone(), 0, 128),
                ],
                "merchant" => [
                    "redirectConfirmUrl" => $request->getSchemeAndHttpHost() . '/checkout/finalise',
                    "redirectCancelUrl" => $request->getSchemeAndHttpHost() . '/checkout/finalise',
                ],
                "merchantReference" => $order->getTitle(),
            ],
            'headers' => [
                "User-Agent" => "MyAfterpayModule/1.0.0 (Custom E-Commerce Platform/1.0.0; PHP/7.3; Merchant/" . ($_ENV['AFTERPAY_MID'] ?? false) . ') ' . $request->getSchemeAndHttpHost(),
                "Accept" => "application/json",
                "Content-Type" => "application/json",
                "Authorization" => "Basic " . $authorization
            ],
        ];
        $url = '/v1/orders';

        try {
            $client = $this->getClient();
            $response = $client->request('POST', $url, $query);
            $result = $response->getBody()->getContents();
            $jsonData = json_decode($result);

        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }

        $end = time();
        $seconds = $end - $start;
        $this->addToOrderLog(
            $order,
            $this->getId() . ' - ' . __FUNCTION__,
            $url,
            json_encode($query, JSON_PRETTY_PRINT),
            $result,
            1,
            $seconds
        );

        $token = null;
        if (isset($jsonData) && gettype($jsonData) == 'object' && isset($jsonData->token)) {
            $token = $jsonData->token;
        }

        $order->setCategory($this->cartService->STATUS_GATEWAY_SENT);
        $order->setGatewaySent(1);
        $order->setGatewaySentDate(date('Y-m-d H:i:s'));
        $order->setPayToken($token);
        $order->setPaySecret(null);
        $order->save();

        return null;
    }

    /**
     * @param $order
     * @return RedirectResponse
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function finalise(Request $request, $order)
    {
        $start = time();
        $authorization = base64_encode(($_ENV['AFTERPAY_MID'] ?? false) . ':' . ($_ENV['AFTERPAY_MKEY'] ?? false));
        $query = [
            RequestOptions::JSON => [
                "token" => $order->getPayToken(),
            ],
            'headers' => [
                "User-Agent" => "MyAfterpayModule/1.0.0 (Custom E-Commerce Platform/1.0.0; PHP/7.3; Merchant/" . ($_ENV['AFTERPAY_MID'] ?? false) . ') ' . $request->getSchemeAndHttpHost(),
                "Accept" => "application/json",
                "Content-Type" => "application/json",
                "Authorization" => "Basic " . $authorization
            ],
        ];
        $url = '/v1/payments/capture';

        try {
            $client = $this->getClient();
            $response = $client->request('POST', $url, $query);
            $result = $response->getBody()->getContents();
            $jsonData = json_decode($result);

        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }

        $end = time();
        $seconds = $end - $start;
        $this->addToOrderLog(
            $order,
            $this->getId() . ' - ' . __FUNCTION__,
            $url,
            json_encode($query, JSON_PRETTY_PRINT),
            $result,
            1,
            $seconds
        );

        $status = null;
        if (isset($jsonData) && gettype($jsonData) == 'object' && isset($jsonData->status)) {
            $status = $jsonData->status === 'APPROVED' ? 1 : 0;
        }

        return $this->finaliseOrderAndRedirect($order, $status);
    }

    /**
     * @return int
     */
    public function getInstalment()
    {
        return $this->info ? $this->info->getInstallments() : 4;
    }

    /**
     * @return null
     */
    public function getFrequency()
    {
        return $this->info ? $this->info->getShortdescription() : 'fortnightly payments of';
    }

    public function getImage()
    {
        return $this->info ? $this->info->getImage() : null;
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        return new Client([
            'base_uri' => ($_ENV['AFTERPAY_ENDPOINT'] ?? false),
        ]);
    }
}
