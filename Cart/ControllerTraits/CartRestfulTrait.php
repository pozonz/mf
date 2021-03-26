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

trait CartRestfulTrait
{
    /**
     * @Route("/cart/post/cart-item/add")
     * @param Request $request
     * @param Environment $environment
     * @return JsonResponse
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function addToCart(Request $request, Environment $environment)
    {
        $id = $request->get('id');
        $qty = $request->get('qty');
        $isOutOfStock = 0;
        $outOfStockMessage = $this->outOfStockMessage ?? '';

        $cart = $this->cartService->getCart();

        $productVariantFullClass = ModelService::fullClass($this->connection, 'ProductVariant');
        $cartItemFullClass = ModelService::fullClass($this->connection, 'OrderItem');

        $variant = $productVariantFullClass::getById($this->connection, $id);
        if (!$variant || !$variant->getStatus()) {
            throw new NotFoundHttpException('Variant not found');
        }

        $product = $variant->objProduct();
        if (!$product || !$product->getStatus()) {
            throw new NotFoundHttpException('Product not found');
        }

        $exist = false;
        $cartItems = $cart->objOrderItems();
        foreach ($cartItems as $itm) {
            if ($itm->getProductId() == $variant->getId()) {
                $exist = true;

                if (!$variant->getStockEnabled() || $variant->getStock() >= ($itm->getQuantity() + $qty)) {
                    $itm->setQuantity($itm->getQuantity() + $qty);
                    $itm->save();
                } else {
                    $isOutOfStock = 1;
                    $outOfStockMessage = str_replace('{{productName}}', $product->getTitle(), $outOfStockMessage);
                    $outOfStockMessage = str_replace('{{variantName}}', $variant->getTitle(), $outOfStockMessage);
                    $outOfStockMessage = str_replace('{{stock}}', $variant->getStock(), $outOfStockMessage);
                    $outOfStockMessage = str_replace('{{qty}}', $itm->getQuantity() + $qty, $outOfStockMessage);
                    $outOfStockMessage = str_replace('{{extra}}', " ({$itm->getQuantity()} item" . ($itm->getQuantity() == 1 ? ' is' : 's are') . " already in the cart)", $outOfStockMessage);
                }
            }
        }

        if (!$exist) {
            if (!$variant->getStockEnabled() || $variant->getStock() >= $qty) {
                $cartItem = new $cartItemFullClass($this->connection);
                $cartItem->setTitle($product->getTitle() . ' - ' . $variant->getTitle());
                $cartItem->setSku($variant->getSku());
                $cartItem->setOrderId($cart->getId());
                $cartItem->setProductId($variant->getId());
                $cartItem->setQuantity($qty);
                $this->cartService->setCustomOrderItem($cartItem, $variant);
                $cartItem->save();
            } else {
                $isOutOfStock = 1;
                $outOfStockMessage = str_replace('{{productName}}', $product->getTitle(), $outOfStockMessage);
                $outOfStockMessage = str_replace('{{variantName}}', $variant->getTitle(), $outOfStockMessage);
                $outOfStockMessage = str_replace('{{stock}}', $variant->getStock(), $outOfStockMessage);
                $outOfStockMessage = str_replace('{{qty}}', $itm->getQuantity() + $qty, $outOfStockMessage);
                $outOfStockMessage = str_replace('{{extra}}', "", $outOfStockMessage);
            }
        }

        $fullClass = ModelService::fullClass($this->connection, 'Order');
        $cart = $fullClass::getById($this->connection, $cart->getId());
        $this->cartService->updateOrder($cart);

        return new JsonResponse([
            'isOutOfStock' => $isOutOfStock,
            'outOfStockMessage' => $outOfStockMessage,
            'cart' => $cart,
            'miniCartHtml' => $environment->render('includes/cart-mini.twig', [
                'cart' => $cart,
            ]),
        ]);
    }

    /**
     * @Route("/cart/post/cart-item/delete")
     * @param Request $request
     * @param Environment $environment
     * @return JsonResponse
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function deleteOrderItem(Request $request, Environment $environment)
    {
        $id = $request->get('id');

        $cart = $this->cartService->getCart();

        $cartItems = $cart->objOrderItems();
        foreach ($cartItems as $itm) {
            if ($itm->getId() == $id) {
                $itm->delete();
            }
        }

        $fullClass = ModelService::fullClass($this->connection, 'Order');
        $cart = $fullClass::getById($this->connection, $cart->getId());
        $this->cartService->updateOrder($cart);

        return new JsonResponse([
            'cart' => $cart,
            'miniCartHtml' => $environment->render('includes/cart-mini.twig', [
                'cart' => $cart,
            ]),
            'miniCartSubtotalHtml' => $environment->render('includes/cart-mini-subtotal.twig', [
                'cart' => $cart,
            ]),
            'cartHtml' => $environment->render('includes/cart.twig', [
                'cart' => $cart,
            ]),
            'cartSubtotalHtml' => $environment->render('includes/cart-subtotal.twig', [
                'cart' => $cart,
            ]),
        ]);
    }

    /**
     * @Route("/cart/post/cart-item/qty")
     * @param Request $request
     * @param Environment $environment
     * @return JsonResponse
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function changeOrderItemQty(Request $request, Environment $environment)
    {
        $id = $request->get('id');
        $qty = $request->get('qty');
        $isOutOfStock = 0;
        $outOfStockMessage = $this->outOfStockMessage ?? '';

        $cart = $this->cartService->getCart();

        $cartItems = $cart->objOrderItems();
        foreach ($cartItems as $itm) {
            if ($itm->getId() == $id) {
                $variant = $itm->objVariant();
                if (!$variant || !$variant->getStatus()) {
                    throw new NotFoundHttpException('Variant not found');
                }

                $product = $variant->objProduct();
                if (!$product || !$product->getStatus()) {
                    throw new NotFoundHttpException('Product not found');
                }

                if (!$variant->getStockEnabled() || $variant->getStock() >= $qty) {
                    $itm->setQuantity($qty);
                    $itm->save();
                } else {
                    $isOutOfStock = 1;
                    $outOfStockMessage = str_replace('{{productName}}', $product->getTitle(), $outOfStockMessage);
                    $outOfStockMessage = str_replace('{{variantName}}', $variant->getTitle(), $outOfStockMessage);
                    $outOfStockMessage = str_replace('{{stock}}', $variant->getStock(), $outOfStockMessage);
                    $outOfStockMessage = str_replace('{{qty}}', $qty, $outOfStockMessage);
                    $outOfStockMessage = str_replace('{{extra}}', "", $outOfStockMessage);
                }
            }
        }

        $fullClass = ModelService::fullClass($this->connection, 'Order');
        $cart = $fullClass::getById($this->connection, $cart->getId());
        $this->cartService->updateOrder($cart);

        return new JsonResponse([
            'isOutOfStock' => $isOutOfStock,
            'outOfStockMessage' => $outOfStockMessage,
            'cart' => $cart,
            'miniCartHtml' => $environment->render('includes/cart-mini.twig', [
                'cart' => $cart,
            ]),
            'miniCartSubtotalHtml' => $environment->render('includes/cart-mini-subtotal.twig', [
                'cart' => $cart,
            ]),
            'cartHtml' => $environment->render('includes/cart.twig', [
                'cart' => $cart,
            ]),
            'cartSubtotalHtml' => $environment->render('includes/cart-subtotal.twig', [
                'cart' => $cart,
            ]),
        ]);
    }

    /**
     * @Route("/checkout/post/order/promo-code")
     * @param Request $request
     * @param Environment $environment
     * @return JsonResponse
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function applyPromoCode(Request $request, Environment $environment)
    {
        $code = $request->get('code');

        $order = $this->getOrderByRequest($request);
        $order->setPromoCode($code);
        $order->setPayToken(null);
        $order->setPaySecret(null);
        $order->setHummRequestQuery(null);
        $order->save();

        $order = $this->getOrderByRequest($request);
        $this->cartService->updateOrder($order);

        $this->initialiasePaymentGateways($request, $order);

        return new JsonResponse([
            'order' => $order,
            'cartSubtotalHtml' => $environment->render('includes/cart-subtotal.twig', [
                'cart' => $order,
            ]),
            'checkoutSidebarSubtotalHtml' => $environment->render('includes/checkout-sidebar-subtotal.twig', [
                'order' => $order,
            ]),
            'checkoutPaymentMethodsHtml' => $environment->render('includes/checkout-payment-methods.twig', [
                'gateways' => $this->cartService->getGatewayClasses(),
                'order' => $order,
            ]),
        ]);
    }

    /**
     * @Route("/checkout/post/order/is-pickup")
     * @param Request $request
     * @param Environment $environment
     * @return JsonResponse
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function setIsPickup(Request $request, Environment $environment)
    {
        $pickup = $request->get('pickup');

        $order = $this->getOrderByRequest($request);
        $order->setIsPickup($pickup);
        $order->save();

        $order = $this->getOrderByRequest($request);
        $this->cartService->updateOrder($order);

        return new JsonResponse([
            'order' => $order,
            'checkoutSidebarSubtotalHtml' => $environment->render('includes/checkout-sidebar-subtotal.twig', [
                'order' => $order,
            ]),
        ]);
    }

    /**
     * @Route("/checkout/post/order/delivery-option")
     * @param Request $request
     * @param Environment $environment
     * @return JsonResponse
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function chooseDeliveryOption(Request $request, Environment $environment)
    {
        $shipping = $request->get('shipping');

        $order = $this->getOrderByRequest($request);
        $order->setShippingId($shipping);
        $order->save();

        $order = $this->getOrderByRequest($request);
        $this->cartService->updateOrder($order);

        return new JsonResponse([
            'order' => $order,
            'checkoutSidebarSubtotalHtml' => $environment->render('includes/checkout-sidebar-subtotal.twig', [
                'order' => $order,
            ]),
        ]);
    }

    /**
     * @Route("/checkout/post/order/change-pay-type")
     * @param Request $request
     * @return JsonResponse
     * @throws RedirectException
     */
    public function changeOrderPayType(Request $request)
    {
        $type = $request->get('type');

        $order = $this->getOrderByRequest($request);
        if (!$order) {
            throw new NotFoundHttpException();
        }

        $order->setPayType($type);
        $order->save();

        return new JsonResponse([
            'order' => $order,
        ]);
    }

    /**
     * @Route("/checkout/post/order/send-to-payment-gateway")
     * @param Request $request
     * @return JsonResponse
     * @throws RedirectException
     */
    public function sendToPaymentGateway(Request $request)
    {
        $type = $request->get('type');
        $note = $request->get('note');
        $order = $this->getOrderByRequest($request);
        if (!$order) {
            throw new NotFoundHttpException();
        }

        $order->setCategory($this->cartService->getStatusGatewaySent());
        $order->setGatewaySent(1);
        $order->setGatewaySentDate(date('Y-m-d H:i:s'));
        $order->setPayType($type);
        $order->setNote($note);
        $order->save();

        return new JsonResponse([
            'order' => $order,
        ]);
    }
}