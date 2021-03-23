<?php

namespace MillenniumFalcon\Cart\Payment;

use MillenniumFalcon\Cart\Service\CartService;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Db\Traits\BaseReflectionTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractGateway
{
    use BaseReflectionTrait;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * PaymentInterface constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection, CartService $cartService)
    {
        $this->connection = $connection;
        $this->cartService = $cartService;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        $rc = static::getReflectionClass();
        return str_replace('Gateway', '', $rc->getShortName());
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return 'Pay by ' . $this->getId();
    }

    /**
     * @return mixed
     */
    abstract public function getOrder(Request $request);

    /**
     * @return mixed
     */
    public function initialise(Request $request, $order)
    {

    }

    /**
     * @param Request $request
     * @param $order
     * @return false
     */
    public function retrieveRedirectUrl(Request $request, $order)
    {
        return false;
    }

    /**
     * @param $order
     * @return mixed
     */
    abstract public function finalise(Request $request, $order);

    /**
     * @return int
     */
    public function getInstalment()
    {
        return 1;
    }

    /**
     * @return null
     */
    public function getFrequency()
    {
        return null;
    }

    /**
     * @param $order
     * @param $type
     * @param $url
     * @param $request
     * @param $response
     * @param $status
     * @param $seconds
     */
    protected function addToOrderLog($order, $type, $url, $request, $response, $status, $seconds)
    {
        $sections = $order->getLogs() ? json_decode($order->getLogs()) : [];
        if (!count($sections)) {
            $sections = $this->cartService->getLogBlankSections($sections);
        }
        $sections[0]->blocks[] = $this->cartService->getLogBlock(
            $type,
            $url,
            $request,
            $response,
            $status,
            $seconds
        );

        $order->setLogs(json_encode($sections));
        $order->save();
    }

    /**
     * @param $order
     * @param $status
     * @return RedirectResponse
     */
    protected function finaliseOrderAndRedirect($order, $status)
    {
        if ($status == 1) {
            $order->setPayStatus(1);
            $order->setCategory($this->cartService->getStatusAccepted());
            $order->save();
            return new RedirectResponse('/checkout/accepted?id=' . $order->getTitle());

        } else {
            $order->setPayStatus(0);
            $order->setCategory($this->cartService->getStatusDeclined());
            $order->save();
            return new RedirectResponse('/checkout/declined?id=' . $order->getTitle());
        }
    }
}