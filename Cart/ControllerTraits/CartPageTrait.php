<?php

namespace MillenniumFalcon\Cart\ControllerTraits;

use MillenniumFalcon\Cart\Form\CheckoutAccountForm;
use MillenniumFalcon\Cart\Form\CheckoutPaymentForm;
use MillenniumFalcon\Cart\Form\CheckoutShippingForm;
use MillenniumFalcon\Core\SymfonyKernel\RedirectException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use MillenniumFalcon\Core\Service\ModelService;
use Twig\Environment;

trait CartPageTrait
{
    /**
     * @route("/cart")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function displayCart(Request $request)
    {
        $fullClass = ModelService::fullClass($this->connection, 'Page');
        $page = new $fullClass($this->connection);
        $page->setTitle('Cart');
        return $this->render('cart.twig', [
            'urlParams' => null,
            'urlFragments' => null,
            'theNode' => [
                'extraInfo' => $page,
            ],
            'theDataGroup' => null,
            'rootNodes' => null,
        ]);
    }

    /**
     * @route("/checkout")
     * @param Request $request
     * @return RedirectResponse
     */
    public function checkout(Request $request)
    {
        $cart = $this->cartService->getCart();
        if ($cart->getCategory() == $this->cartService->getStatusNew()) {
            $cart->setCategory($this->cartService->getStatusCreated());
            $cart->setSubmitted(1);
            $cart->setSubmittedDate(date('Y-m-d H:i:s'));
            $cart->save();
        }

        $cart->save();

        return new RedirectResponse("/checkout/account?id={$cart->getTitle()}");
    }

    /**
     * @route("/checkout/account")
     * @param Request $request
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws RedirectException
     */
    public function setAccountForCart(Request $request)
    {
        $order = $this->getOrderByRequest($request);
        $this->cartService->updateOrder($order);

        $customer = $this->cartService->getCustomer();
        if ($customer) {
            return new RedirectResponse("/checkout/shipping?id={$order->getTitle()}");
        }

        $form = $this->container->get('form.factory')->create(CheckoutAccountForm::class, $order, [
            'request' => $request,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $order->save();
            return new RedirectResponse("/checkout/shipping?id={$order->getTitle()}");
        }

        $fullClass = ModelService::fullClass($this->connection, 'Page');
        $page = new $fullClass($this->connection);
        $page->setTitle('Checkout');
        return $this->render('checkout-account.twig', [
            'urlParams' => null,
            'urlFragments' => null,
            'theNode' => [
                'extraInfo' => $page,
            ],
            'theDataGroup' => null,
            'rootNodes' => null,
            'order' => $order,
            'formView' => $form->createView(),
        ]);
    }

    /**
     * @route("/checkout/shipping")
     * @param Request $request
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws RedirectException
     */
    public function setShippingForCart(Request $request)
    {
        $order = $this->getOrderByRequest($request);
        $this->cartService->updateOrder($order);

        $form = $this->container->get('form.factory')->create(CheckoutShippingForm::class, $order, [
            'request' => $request,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->save();
            return new RedirectResponse("/checkout/payment?id={$order->getTitle()}");
        }

        $fullClass = ModelService::fullClass($this->connection, 'Page');
        $page = new $fullClass($this->connection);
        $page->setTitle('Checkout');
        return $this->render('checkout-shipping.twig', [
            'urlParams' => null,
            'urlFragments' => null,
            'theNode' => [
                'extraInfo' => $page,
            ],
            'theDataGroup' => null,
            'rootNodes' => null,
            'order' => $order,
            'formView' => $form->createView(),
        ]);
    }

    /**
     * @route("/checkout/payment")
     * @param Request $request
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws RedirectException
     */
    public function setPaymentForCart(Request $request)
    {
        $order = $this->getOrderByRequest($request);
        $this->cartService->updateOrder($order);

        $this->initialiasePaymentGateways($request, $order);

        $form = $this->container->get('form.factory')->create(CheckoutPaymentForm::class, $order);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $order->save();

            $gatewayClass = $this->cartService->getGatewayClass($order->getPayType());
            if (!$gatewayClass) {
                throw new NotFoundHttpException();
            }
            $redirectUrl = $gatewayClass->retrieveRedirectUrl($request, $order);
            if ($redirectUrl) {
                return new RedirectResponse($redirectUrl);
            }
        }

        $fullClass = ModelService::fullClass($this->connection, 'Page');
        $page = new $fullClass($this->connection);
        $page->setTitle('Checkout');
        return $this->render('checkout-payment.twig', [
            'urlParams' => null,
            'urlFragments' => null,
            'theNode' => [
                'extraInfo' => $page,
            ],
            'theDataGroup' => null,
            'rootNodes' => null,
            'order' => $order,
            'gateways' => $this->cartService->getGatewayClasses(),
            'formView' => $form->createView(),
        ]);
    }

    /**
     * @route("/checkout/finalise")
     * @param Request $request
     * @return mixed
     */
    public function finaliseCart(Request $request)
    {
        $order = null;
        $gatewayClasses = $this->cartService->getGatewayClasses();
        foreach ($gatewayClasses as $gatewayClass) {
            $order = $gatewayClass->getOrder($request);
            if ($order) {
                break;
            }
        }

        if (!$order) {
            throw new NotFoundHttpException();
        }

        $gatewayClass = $this->cartService->getGatewayClass($order->getPayType());
        return $gatewayClass->finalise($request, $order);
    }

    /**
     * @route("/checkout/accepted")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function displayCartAccepted(Request $request)
    {
        $orderTitle = $request->get('id');
        $fullClass = ModelService::fullClass($this->connection, 'Order');
        $order = $fullClass::getByField($this->connection, 'title', $orderTitle);
        if (!$order) {
            throw new NotFoundHttpException();
        }

        $fullClass = ModelService::fullClass($this->connection, 'Page');
        $page = new $fullClass($this->connection);
        $page->setTitle('Confirmation');
        return $this->render('checkout-confirm.twig', [
            'urlParams' => null,
            'urlFragments' => null,
            'theNode' => [
                'extraInfo' => $page,
            ],
            'theDataGroup' => null,
            'rootNodes' => null,
            'order' => $order,
        ]);
    }

    /**
     * @route("/checkout/declined")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function displayCartDeclined(Request $request)
    {
        $orderTitle = $request->get('id');
        $fullClass = ModelService::fullClass($this->connection, 'Order');
        $order = $fullClass::getByField($this->connection, 'title', $orderTitle);
        if (!$order) {
            throw new NotFoundHttpException();
        }

        $fullClass = ModelService::fullClass($this->connection, 'Page');
        $page = new $fullClass($this->connection);
        $page->setTitle('Confirmation');
        return $this->render('checkout-declined.twig', [
            'urlParams' => null,
            'urlFragments' => null,
            'theNode' => [
                'extraInfo' => $page,
            ],
            'theDataGroup' => null,
            'rootNodes' => null,
            'order' => $order,
        ]);
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws RedirectException
     */
    protected function getOrderByRequest(Request $request)
    {
        $orderTitle = $request->get('id');
        $fullClass = ModelService::fullClass($this->connection, 'Order');
        $order = $fullClass::getByField($this->connection, 'title', $orderTitle);
        if (!$order) {
            throw new RedirectException("/checkout");
        }
        if ($order->getCategory() == $this->cartService->getStatusAccepted()) {
            throw new RedirectException("/checkout");
        }
        $order = $this->cartService->setBooleanValues($order);
        if (!count($order->objOrderItems())) {
            throw new RedirectException("/");
        }

        return $order;
    }

    /**
     * @param $request
     * @param $order
     */
    protected function initialiasePaymentGateways($request, $order)
    {
        $gatewayClasses = $this->cartService->getGatewayClasses();
        foreach ($gatewayClasses as $idx => $gatewayClass) {
            if ($idx == 0 && !$order->getPayType()) {
                $order->setPayType($gatewayClass->getId());
            }
            $gatewayClass->initialise($request, $order);
        }
    }
}