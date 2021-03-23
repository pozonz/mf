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
     */
    public function displayCart()
    {
        $fullClass = ModelService::fullClass($this->connection, 'Page');
        $page = new $fullClass($this->connection);
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
     */
    public function checkout()
    {
        $cart = $this->cartService->getCart();
        if ($cart->getCategory() == $this->cartService->getStatusNew()) {
            $cart->setCategory($this->cartService->getStatusCreated());
            $cart->setSubmitted(1);
            $cart->setSubmittedDate(date('Y-m-d H:i:s'));
            $cart->save();
        }

        //Test
        $cart->setTotal(100);
        $cart->setEmail('weida@gravitate.co.nz');
        $cart->setShippingFirstName('Weida');
        $cart->setShippingLastName('Xue');
//        $cart->setShippingPhone('021123456');
//        $cart->setShippingAddress('123 Queen Street');
//        $cart->setShippingPostcode('2016');
//        $cart->setShippingCity('Auckland');
//        $cart->setShippingCountry('NZ');


        $cart->save();

        return new RedirectResponse("/checkout/account?id={$cart->getTitle()}");
    }

    /**
     * @route("/checkout/account")
     */
    public function setAccountForCart(Request $request)
    {
        $order = $this->getOrderByRequest($request);
//        return new RedirectResponse("/checkout/shipping?id={$order->getTitle()}");

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
     */
    public function setShippingForCart(Request $request)
    {
        $order = $this->getOrderByRequest($request);

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
     */
    public function setPaymentForCart(Request $request, Environment $environment)
    {
        $order = $this->getOrderByRequest($request);

        $gatewayClasses = $this->cartService->getGatewayClasses();
        foreach ($gatewayClasses as $idx => $gatewayClass) {
            if ($idx == 0 && !$order->getPayType()) {
                $order->setPayType($gatewayClass->getId());
            }
            $gatewayClass->initialise($request, $order);
        }

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
        $order = $this->cartService->setBooleanValues($order);

        return $order;
    }
}