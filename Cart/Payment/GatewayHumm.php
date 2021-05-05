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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class GatewayHumm extends AbstractGateway
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function getOrder(Request $request)
    {
        $token = $request->get('x_reference');
        $fullClass = ModelService::fullClass($this->connection, 'Order');
        return $fullClass::getByField($this->connection, 'title', $token);
    }

    /**
     * @param Request $request
     * @param $order
     * @return false|mixed|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function initialise(Request $request, $order)
    {
        $start = time();
        $query = [
            "x_account_id" => getenv('HUMM_ACCOUNT'),
            "x_amount" => $order->getTotal(),
            "x_currency" => "NZD",
            "x_reference" => $order->getTitle(),
            "x_shop_country" => "NZ",
            "x_shop_name" => getenv('HUMM_SHOP'),
            "x_url_callback" => $request->getSchemeAndHttpHost() . '/checkout/finalise',
            "x_url_cancel" => $request->getSchemeAndHttpHost() . '/checkout/finalise',
            "x_url_complete" => $request->getSchemeAndHttpHost() . '/checkout/finalise',
        ];
        $query['x_signature'] = $this->humm_sign_without_key($query);

        $end = time();
        $seconds = $end - $start;
        $this->addToOrderLog(
            $order,
            $this->getId() . ' - ' . __FUNCTION__,
            '',
            json_encode($query, JSON_PRETTY_PRINT),
            '',
            1,
            $seconds
        );

        $order->setHummRequestQuery(json_encode($query));
        $order->save();
    }

    /**
     * @param $order
     * @return RedirectResponse
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function finalise(Request $request, $order)
    {
        $start = time();
        $query = [
            'x_account_id' => $request->get('x_account_id'),
            'x_reference' => $request->get('x_reference'),
            'x_currency' => $request->get('x_currency'),
            'x_test' => $request->get('x_test'),
            'x_amount' => $request->get('x_amount'),
            'x_timestamp' => $request->get('x_timestamp'),
            'x_result' => $request->get('x_result'),
            'x_gateway_reference' => $request->get('x_gateway_reference'),
            'x_purchase_number' => $request->get('x_purchase_number'),
        ];
        $x_signature = $request->get('x_signature');

        $status = $query['x_result'] == 'completed' ? 1 : 0;

        $end = time();
        $seconds = $end - $start;
        $this->addToOrderLog(
            $order,
            $this->getId() . ' - ' . __FUNCTION__,
            '',
            json_encode($query, JSON_PRETTY_PRINT),
            '',
            $status,
            $seconds
        );

        if ($this->humm_sign_without_key($query) != $x_signature) {
            throw new BadRequestHttpException();
        }

        return $this->finaliseOrderAndRedirect($order, $status);
    }

    /**
     * @return int
     */
    public function getInstalment()
    {
        return 5;
    }

    /**
     * @return null
     */
    public function getFrequency()
    {
        return 'fortnightly';
    }

    /**
     * @param $query
     * @return string|string[]
     */
    protected function humm_sign_without_key($query)
    {
        return $this->humm_sign($query, getenv('HUMM_KEY'));
    }

    /**
     * @param $query
     * @param $api_key
     * @return string|string[]
     */
    protected function humm_sign($query, $api_key)
    {
        $clear_text = '';
        ksort($query);
        foreach ($query as $key => $value) {
            if (substr($key, 0, 2) === "x_" && $key !== "x_signature") {
                $clear_text .= $key . $value;
            }
        }
        $hash = hash_hmac("sha256", $clear_text, $api_key);
        return str_replace('-', '', $hash);
    }
}