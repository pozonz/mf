<?php

namespace MillenniumFalcon\Cart\ControllerTraits;

use MillenniumFalcon\Cart\Form\CheckoutAccountForm;
use MillenniumFalcon\Cart\Form\CheckoutPaymentForm;
use MillenniumFalcon\Cart\Form\CheckoutShippingForm;
use MillenniumFalcon\Core\SymfonyKernel\RedirectException;
use PhpParser\Node\Expr\BinaryOp\Mod;
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
     * @route("/products/filter/shop")
     * @route("/products/filter/shop/{categories}", requirements={"categories" = ".*"})
     * @param Request $request
     * @return JsonResponse
     * @throws RedirectException
     */
    public function productsFilter(Request $request, $categories = null)
    {
        $category = null;
        if ($categories) {
            $categories = explode('/', $categories);
            $category = array_pop($categories);
        }
        $params = $this->filterProductResult($request, $category);
        return new JsonResponse([
            'productHtml' => $this->environment->render('/cart/includes/product-results.twig', $params),
            'brandHtml' => $this->environment->render('/cart/includes/product-brands.twig', $params),
            'total' => $params['total'],
        ]);
    }

    /**
     * @Route("/cart/get")
     * @param Request $request
     * @param Environment $environment
     * @return JsonResponse
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getCart(Request $request, Environment $environment)
    {
        $cart = $this->cartService->getCart();

        return new JsonResponse([
            'cart' => $cart,
            'miniCartHtml' => $environment->render('/cart/cart-mini.twig', [
                'cart' => $cart,
            ]),
        ]);
    }

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
                /** @var OrderItem $cartItem */
                $cartItem = new $cartItemFullClass($this->connection);
                $cartItem->setTitle($product->getTitle() . ($variant->getTitle() ? ' - ' . $variant->getTitle() : ''));
                $cartItem->setProductName($product->getTitle());
                $cartItem->setVariantName($variant->getTitle());
                if ($product->objBrand()) {
                    $cartItem->setBrandName($product->objBrand()->getTitle());
                }
                $cartItem->setSku($variant->getSku());
                $cartItem->setOrderId($cart->getId());
                $cartItem->setProductId($variant->getId());
                $cartItem->setQuantity($qty);
                $cartItem->setImage($product->objThumbnail()->getId());
                $cartItem->save();
            } else {
                $isOutOfStock = 1;
                $outOfStockMessage = str_replace('{{productName}}', $product->getTitle(), $outOfStockMessage);
                $outOfStockMessage = str_replace('{{variantName}}', $variant->getTitle(), $outOfStockMessage);
                $outOfStockMessage = str_replace('{{stock}}', $variant->getStock(), $outOfStockMessage);
                $outOfStockMessage = str_replace('{{qty}}', $qty, $outOfStockMessage);
                $outOfStockMessage = str_replace('{{extra}}', "", $outOfStockMessage);
            }
        }

        $fullClass = ModelService::fullClass($this->connection, 'Order');
        $cart = $fullClass::getById($this->connection, $cart->getId());
        $this->cartService->updateCart($cart);

        return new JsonResponse([
            'isOutOfStock' => $isOutOfStock,
            'outOfStockMessage' => $outOfStockMessage,
            'cart' => $cart,
            'miniCartHtml' => $environment->render('/cart/cart-mini.twig', [
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
        $this->cartService->updateCart($cart);

        return new JsonResponse([
            'cart' => $cart,
            'miniCartHtml' => $environment->render('/cart/cart-mini.twig', [
                'cart' => $cart,
            ]),
            'miniCartSubtotalHtml' => $environment->render('/cart/includes/cart-mini-subtotal.twig', [
                'cart' => $cart,
            ]),
            'cartSubtotalHtml' => $environment->render('/cart/includes/cart-subtotal.twig', [
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
        $stock = 0;

        $cart = $this->cartService->getCart();

        $cartItem = null;
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

                $stock = $variant->getStock();

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

                $cartItem = $itm;
            }
        }

        $fullClass = ModelService::fullClass($this->connection, 'Order');
        $cart = $fullClass::getById($this->connection, $cart->getId());
        $this->cartService->updateCart($cart);

        return new JsonResponse([
            'isOutOfStock' => $isOutOfStock,
            'outOfStockMessage' => $outOfStockMessage,
            'stock' => $stock,
            'cart' => $cart,
            'miniCartHtml' => $environment->render('/cart/cart-mini.twig', [
                'cart' => $cart,
            ]),
            'miniCartSubtotalHtml' => $environment->render('/cart/includes/cart-mini-subtotal.twig', [
                'cart' => $cart,
            ]),
            'cartSubtotalHtml' => $environment->render('/cart/includes/cart-subtotal.twig', [
                'cart' => $cart,
            ]),
            'cartItemSubtotalHtml' => $environment->render('/cart/includes/cart-item-subtotal.twig', [
                'itm' => $cartItem,
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

        $cart = $this->cartService->getCart();
        $cart->setPromoCode($code);
        $cart->save();

        $cart = $this->cartService->getCart();

        return new JsonResponse([
            'orderTotalFormatted' => number_format($cart->getTotal(), 2),
            'cart' => $cart,
            'checkoutSidebarSubtotalHtml' => $environment->render('/cart/includes/checkout-sidebar-subtotal.twig', [
                'cart' => $cart,
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
        $id = $request->get('id');
        $order = $this->cartService->getOrderById($id);
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
     * @param Environment $environment
     * @return JsonResponse
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function sendToPaymentGateway(Request $request, Environment $environment)
    {
        $type = $request->get('type');
        $note = $request->get('note');
        $id = $request->get('id');
        $order = $this->cartService->getOrderById($id);
        if (!$order) {
            throw new NotFoundHttpException();
        }

        $order->setCategory($this->cartService->STATUS_GATEWAY_SENT);
        $order->setGatewaySent(1);
        $order->setGatewaySentDate(date('Y-m-d H:i:s'));
        $order->setPayType($type);
        $order->setNote($note);
        $order->save();

        return new JsonResponse([
            'order' => $order,
            'checkoutSidebarHtml' => $environment->render('/cart/includes/checkout-sidebar.twig', [
                'cart' => $order,
            ]),
        ]);
    }

    /**
     * @Route("/checkout/post/order/shipping")
     * @param Request $request
     * @return JsonResponse
     */
    public function updateShippingOptions(Request $request)
    {
        $address = $request->get('address');
        $city = $request->get('city');
        $country = $request->get('country');
        $region = $request->get('region');
        $postcode = $request->get('postcode');
        $pickup = $request->get('pickup');

        $cart = $this->cartService->getCart();
        $cart->setShippingAddress($address);
        $cart->setShippingCity($city);
        $cart->setShippingState($region);
        $cart->setShippingCountry($country);
        $cart->setShippingPostcode($postcode);
        $cart->setIsPickup($pickup);
        $cart->save();

        $this->cartService->updateCart($cart);

        $regions = $this->cartService->getDeliverableRegions($cart);
        $deliveryOptions = $this->cartService->getDeliveryOptions($cart);
        return new JsonResponse([
            'shippingPriceMode' => getenv('SHIPPING_PRICE_MODE') ?? 1,
            'cart' => $cart,
            'regions' => $regions,
            'deliveryOptions' => $deliveryOptions,
            'checkoutSidebarSubtotalHtml' => $this->environment->render('/cart/includes/checkout-sidebar-subtotal.twig', [
                'cart' => $cart,
            ]),
        ]);
    }

    /**
     * @Route("/checkout/post/order/delivery")
     * @param Request $request
     * @return JsonResponse
     */
    public function updateDeliveryOption(Request $request)
    {
        $shipping = $request->get('shipping');

        $cart = $this->cartService->getCart();
        $cart->setShippingId($shipping);
        $cart->save();

        $this->cartService->updateCart($cart);

        $regions = $this->cartService->getDeliverableRegions($cart);
        $deliveryOptions = $this->cartService->getDeliveryOptions($cart);
        return new JsonResponse([
            'regions' => $regions,
            'deliveryOptions' => $deliveryOptions,
            'checkoutSidebarSubtotalHtml' => $this->environment->render('/cart/includes/checkout-sidebar-subtotal.twig', [
                'cart' => $cart,
            ]),
        ]);
    }
}
