<?php

namespace MillenniumFalcon\Cart\Payment;

use MillenniumFalcon\Cart\Payment\PaymentInterface;
use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Service\ModelService;
use Ramsey\Uuid\Uuid;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class GatewayBankTransfer extends AbstractGateway
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function getOrder(Request $request)
    {
        $token = $request->get('____btid');
        $fullClass = ModelService::fullClass($this->connection, 'Order');
        return $fullClass::getByField($this->connection, 'payToken', $token);
    }

    /**
     * @param Request $request
     * @param $order
     * @return mixed|void
     */
    public function initialise(Request $request, $order)
    {
    }

    /**
     * @param Request $request
     * @param $order
     * @return mixed|RedirectResponse
     */
    public function finalise(Request $request, $order)
    {
        if ($order->getCategory() != $this->cartService->STATUS_ACCEPTED && $order->getCategory() != $this->cartService->STATUS_OFFLINE && $order->getPayStatus() != 1) {

            $order->setCategory($this->cartService->STATUS_OFFLINE);
            $order->save();

            $this->cartService->sendEmailInvoice($order);
            $this->cartService->updateStock($order);
            $this->cartService->clearCart();
        }

        return new RedirectResponse('/checkout/accepted?id=' . $order->getTitle());
    }

    /**
     * @param Request $request
     * @param $order
     * @return false
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function retrieveRedirectUrl(Request $request, $order)
    {
        $order->setPayToken(Uuid::uuid4()->toString());
        $order->save();
        return "/checkout/finalise?____btid={$order->getPayToken()}";
    }
}