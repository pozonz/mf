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
     * @route("/shop")
     * @param Request $request
     * @return mixed
     */
    public function shop(Request $request)
    {
        $params = $this->getTemplateParams($request);

        $fullClass = ModelService::fullClass($this->connection, 'Product');
        $params['orms'] = $fullClass::data($this->connection, [
            'sort' => 'pageRank',
            'order' => 'DESC',
        ]);
        return $this->render('/cart/products.twig', $params);
    }

    /**
     * @route("/product/{slug}")
     * @param Request $request
     * @return mixed
     */
    public function product(Request $request, $slug)
    {
        $params = $this->getTemplateParams($request);

        $fullClass = ModelService::fullClass($this->connection, 'Product');
        $params['orm'] = $fullClass::getBySlug($this->connection, $slug);
        return $this->render('/cart/product.twig', $params);
    }

    /**
     * @route("/cart")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function displayCart(Request $request)
    {
        $cart = $this->cartService->getCart();

        $params = $this->getTemplateParamsByUrl('/cart');
        $params['cart'] = $cart;
        return $this->render('/cart/cart.twig', $params);
    }

    /**
     * @route("/checkout")
     * @param Request $request
     * @return RedirectResponse
     */
    public function checkout(Request $request)
    {
//        $cart = $this->cartService->getCart();
//        if ($cart->getCategory() == $this->cartService->getStatusNew()) {
//            $cart->setCategory($this->cartService->getStatusCreated());
//            $cart->setSubmitted(1);
//            $cart->setSubmittedDate(date('Y-m-d H:i:s'));
//            $cart->save();
//        }
//
//        $cart->save();

        return new RedirectResponse("/checkout/account");
    }

    /**
     * @route("/checkout/account")
     * @param Request $request
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws RedirectException
     */
    public function setAccountForCart(Request $request)
    {
        return new RedirectResponse("/checkout/shipping");
    }

    /**
     * @route("/checkout/shipping")
     * @param Request $request
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws RedirectException
     */
    public function setShippingForCart(Request $request)
    {
        $cart = $this->cartService->getCart();
        $form = $this->container->get('form.factory')->create(CheckoutShippingForm::class, $cart, [
            'request' => $request,
            'connection' => $this->connection,
            'cartService' => $this->cartService,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($cart->getCategory() == $this->cartService->STATUS_NEW) {
                $cart->setCategory($this->cartService->STATUS_CREATED);
                $cart->setSubmitted(1);
                $cart->setSubmittedDate(date('Y-m-d H:i:s'));
                $cart->save();
                return new RedirectResponse("/checkout/payment?id={$cart->getTitle()}");
            }
        }

        $params = $this->getTemplateParamsByUrl('/cart');
        $params['formView'] = $form->createView();
        $params['cart'] = $cart;
        return $this->render('/cart/checkout-shipping.twig', $params);
    }

    /**
     * @route("/checkout/payment")
     * @param Request $request
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws RedirectException
     */
    public function setPaymentForCart(Request $request)
    {
        $id = $request->get('id');
        $order = $this->cartService->getOrderById($id);
        if (!$order) {
            throw new RedirectException("/checkout");
        }
        if ($order->getCategory() == $this->cartService->STATUS_ACCEPTED) {
            throw new RedirectException("/checkout");
        }
        if (!count($order->objOrderItems())) {
            throw new RedirectException("/");
        }

//        $order = $this->cartService->setBooleanValues($order);
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

        $params = $this->getTemplateParamsByUrl('/cart');
        $params['formView'] = $form->createView();
        $params['order'] = $order;
        $params['gateways'] = $this->cartService->getGatewayClasses();
        return $this->render('/cart/checkout-payment.twig', $params);
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
            return new RedirectResponse('/checkout');
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
        $id = $request->get('id');
        $order = $this->cartService->getOrderById($id);
        if (!$order) {
            throw new NotFoundHttpException();
        }

        $params = $this->getTemplateParamsByUrl('/cart');
        $params['order'] = $order;
        return $this->render('/cart/checkout-confirm.twig', $params);
    }

    /**
     * @route("/checkout/declined")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function displayCartDeclined(Request $request)
    {
        $id = $request->get('id');
        $order = $this->cartService->getOrderById($id);
        if (!$order) {
            throw new NotFoundHttpException();
        }

        $params = $this->getTemplateParamsByUrl('/cart');
        $params['order'] = $order;
        return $this->render('/cart/checkout-declined.twig', $params);
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