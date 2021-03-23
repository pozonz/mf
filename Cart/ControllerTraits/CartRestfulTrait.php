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

        $customer = $this->cartService->getCustomer();
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
                $itm->setQuantity($itm->getQuantity() + $qty);
                $itm->save();
                $exist = true;
            }
        }

        if (!$exist) {
            $cartItem = new $cartItemFullClass($this->connection);
            $cartItem->setTitle($product->getTitle() . ' - ' . $variant->getTitle());
            $cartItem->setSku($variant->getSku());
            $cartItem->setOrderId($cart->getId());
            $cartItem->setProductId($variant->getId());
            $cartItem->setQuantity($qty);
            $cartItem->save();
        }

        $fullClass = ModelService::fullClass($this->connection, 'Order');
        $cart = $fullClass::getById($this->connection, $cart->getId());
        $cart->update($customer);

        return new JsonResponse([
            'cart' => $cart,
            'miniCartHtml' => $environment->render('cms/cart/includes/cart-mini.twig', [
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

        $customer = $this->cartService->getCustomer();
        $cart = $this->cartService->getCart();

        $cartItems = $cart->objOrderItems();
        foreach ($cartItems as $itm) {
            if ($itm->getId() == $id) {
                $itm->delete();
            }
        }

        $fullClass = ModelService::fullClass($this->connection, 'Order');
        $cart = $fullClass::getById($this->connection, $cart->getId());
        $cart->update($customer);

        return new JsonResponse([
            'cart' => $cart,
            'miniCartHtml' => $environment->render('cms/cart/includes/cart-mini.twig', [
                'cart' => $cart,
            ]),
            'cartPageSubtotalHtml' => $environment->render('cms/cart/includes/cart-page-subtotal.twig', [
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

        $customer = $this->cartService->getCustomer();
        $cart = $this->cartService->getCart();

        $cartItems = $cart->objOrderItems();
        foreach ($cartItems as $itm) {
            if ($itm->getId() == $id) {
                $itm->setQuantity($qty);
                $itm->save();
            }
        }

        $fullClass = ModelService::fullClass($this->connection, 'Order');
        $cart = $fullClass::getById($this->connection, $cart->getId());
        $cart->update($customer);

        return new JsonResponse([
            'cart' => $cart,
            'cartPageSubtotalHtml' => $environment->render('cms/cart/includes/cart-page-subtotal.twig', [
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

        $customer = $this->cartService->getCustomer();
        $order = $this->getOrderByRequest($request);

        $order->setPromoCode($code);
        $order->save();

        $order = $this->getOrderByRequest($request);
        $order->update($customer);

        return new JsonResponse([
            'order' => $order,
            'cartPageSubtotalHtml' => $environment->render('cms/cart/includes/cart-page-subtotal.twig', [
                'cart' => $order,
            ]),
            'cartSidebarSubtotalHtml' => $environment->render('cms/cart/includes/cart-sidebar-subtotal.twig', [
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