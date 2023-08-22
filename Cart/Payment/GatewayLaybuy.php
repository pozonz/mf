<?php

namespace MillenniumFalcon\Cart\Payment;

use MillenniumFalcon\Cart\Payment\PaymentInterface;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use MillenniumFalcon\Core\Service\ModelService;
use Ramsey\Uuid\Uuid;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class GatewayLaybuy extends AbstractGateway
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function getOrder(Request $request)
    {
        $token = $request->get('token');
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
        $query = [
            "amount" => $order->getTotal(),
            "currency" => "NZD",
            "returnUrl" => $request->getSchemeAndHttpHost() . '/checkout/finalise',
            "merchantReference" => $order->getTitle(),
            "customer" => [
                "firstName" => $order->getShippingFirstName(),
                "lastName" => $order->getShippingLastName(),
                "email" => $order->getEmail(),
            ]
        ];
        if ($order->getShippingPhone()) {
            $query['customer']['phone'] = $order->getShippingPhone();
        }

        if ($order->getIsPickup()) {
            $query['customer']['firstName'] = $order->getPickupFirstName();
            $query['customer']['lastName'] = $order->getPickupLastName();
            $query['customer']['email'] = $order->getEmail();
            if ($order->getPickupPhone()) {
                $query['customer']['phone'] = $order->getPickupPhone();
            }
        }

        $url = '/order/create';

        try {
            $client = $this->getClient();
            $response = $client->request('POST', $url, [
                'json' => $query
            ]);
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
        $token = null;
        $paymentUrl = null;
        if (isset($jsonData) && gettype($jsonData) == 'object' && isset($jsonData->result)) {
            $status = $jsonData->result ?? null;
            $token = $jsonData->token ?? null;
            $paymentUrl = $jsonData->paymentUrl ?? null;
            $status = 'SUCCESS' == $status ? 1 : 0;
        }

        $order->setCategory($this->cartService->STATUS_GATEWAY_SENT);
        $order->setGatewaySent(1);
        $order->setGatewaySentDate(date('Y-m-d H:i:s'));
        $order->setPayToken($token);
        $order->setPaySecret(null);
        $order->save();

        return $paymentUrl;
    }

    /**
     * @param Request $request
     * @param $order
     * @return mixed|RedirectResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function finalise(Request $request, $order)
    {
        $start = time();
        $query = [
            'json' => [
                'token' => $order->getPayToken(),
                'amount' => $order->getTotal(),
                'currency' => 'NZD'
            ]
        ];
        $url = "/order/confirm";

        try {
            $client = $this->getClient();
            $response = $client->request('POST', $url, $query);
            $result = $response->getBody()->getContents();
            $jsonData = json_decode($result);

        } catch (\Exception $e) {
            $result = $e->getMessage();
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
        if (isset($jsonData) && gettype($jsonData) == 'object' && isset($jsonData->result)) {
            $status = $jsonData->result ?? null;
            $status = 'SUCCESS' == $status ? 1 : 0;
        }

        return $this->finaliseOrderAndRedirect($order, $status);
    }

    /**
     * @return int
     */
    public function getInstalment()
    {
        return 6;
    }

    /**
     * @return null
     */
    public function getFrequency()
    {
        return 'weekly';
    }

    /**
     * @return Client
     */
    private function getClient()
    {
        return new Client([
            'base_uri' => $_ENV['LAYBUY_ENDPOINT'] ?? null,
            'auth' => [
                $_ENV['LAYBUY_USERNAME'] ?? null,
                $_ENV['LAYBUY_KEY'] ?? null
            ]
        ]);
    }
}