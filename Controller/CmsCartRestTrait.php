<?php

namespace MillenniumFalcon\Controller;


use MillenniumFalcon\Core\Form\Builder\CartAddItemForm;
use MillenniumFalcon\Core\Service\CartService;
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

trait CmsCartRestTrait
{
    /**
     * @route("/cart/rest/order")
     * @return Response
     */
    public function restOrderContainer(CartService $cartService)
    {
        $orderContainer = $cartService->getOrderContainer();
        $result = new \stdClass();
        $result->status = 1;
        $result->orderContainer = $orderContainer;

        return new JsonResponse($result);
    }

    /**
     * @route("/cart/rest/order-item/qty")
     * @return Response
     */
    public function restOrderItemQty(CartService $cartService)
    {
        $orderContainer = $cartService->getOrderContainer();
        $result = new \stdClass();
        $result->status = 1;
        $result->orderContainer = $orderContainer;

        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $qty = $request->get('qty');

        $orderItems = $orderContainer->objOrderItems();
        foreach ($orderItems as $itm) {
            if ($itm->getUniqid() == $id) {
                $variant = $itm->objProductVariant();
                if ($variant->getStock() >= $qty) {
                    $itm->setQuantity($qty);
                    $itm->save();
                    $customer = $this->container->get('security.token_storage')->getToken()->getUser();
                    $orderContainer->update($customer);
                } else {
                    $result->status = 0;
                    $result->error = "Sorry, we only have {$variant->getStock()} in stock";

                }
            }
        }
        return new JsonResponse($result);
    }

    /**
     * @route("/cart/rest/order-item/delete")
     * @return Response
     */
    public function restOrderItemDelete(CartService $cartService)
    {
        $orderContainer = $cartService->getOrderContainer();
        $result = new \stdClass();
        $result->status = 1;
        $result->orderContainer = $orderContainer;

        $request = Request::createFromGlobals();
        $id = $request->get('id');

        $orderItems = $orderContainer->objOrderItems();
        foreach ($orderItems as $itm) {
            if ($itm->getUniqid() == $id) {
                $itm->delete();
            }
        }
        $orderContainer->clearOrderItemsCache();
        $customer = $this->container->get('security.token_storage')->getToken()->getUser();
        $orderContainer->update($customer);
        return new JsonResponse($result);
    }

    /**
     * @route("/cart/rest/promo/apply")
     * @return Response
     */
    public function restOrderPromoApply(CartService $cartService)
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $orderContainer = $cartService->getOrderContainer();
        $result = new \stdClass();
        $result->status = 1;
        $result->orderContainer = $orderContainer;

        $request = Request::createFromGlobals();
        $code = $request->get('code');

        $fullClass = ModelService::fullClass($pdo, 'PromoCode');
        $promoCode = $fullClass::getByField($pdo, 'code', $code);
        if ($promoCode && $promoCode->isValid()) {
            $orderContainer->setPromoCode($code);
        } else {
            $orderContainer->setPromoCode('');

            $result->status = 0;
            $result->error = "Sorry, the promo code is invalid";
        }

        $customer = $this->container->get('security.token_storage')->getToken()->getUser();
        $orderContainer->update($customer);

        return new JsonResponse($result);
    }

    /**
     * @route("/cart/rest/order/address/update")
     * @return JsonResponse
     */
    public function restOrderUpdateAddress(CartService $cartService)
    {
        $orderContainer = $cartService->getOrderContainer();
        $result = new \stdClass();
        $result->status = 1;
        $result->orderContainer = $orderContainer;

        $request = Request::createFromGlobals();
        $o = json_decode($request->get('order'));

        $orderContainer = $cartService->getOrderContainer();
        foreach ($o as $idx => $itm) {
            if (strpos($idx, 'shipping') == -1 && strpos($idx, 'billing') == -1) {
                continue;
            }
            $method = 'set' . ucfirst($idx);
            $orderContainer->$method($itm);
        }

        $shippingOption = null;
        $shippingOptions = $orderContainer->objShippingOptions();
        foreach ($shippingOptions as $itm) {
            if ($itm->getId() == $orderContainer->getShippingId()) {
                $shippingOption = $itm;
            }
        }

        $orderContainer->setShippingId(null);
        $orderContainer->setShippingTitle(null);
        $orderContainer->setShippingCost(null);

//        if (!$shippingOption) {
//            $orderContainer->setShippingId(null);
//            $orderContainer->setShippingTitle(null);
//            $orderContainer->setShippingCost(null);
//        } else {
//            $orderContainer->setShippingCost($shippingOption->getPrice());
//        }
        $customer = $this->container->get('security.token_storage')->getToken()->getUser();
        $orderContainer->update($customer);
        return new JsonResponse($result);
    }

    /**
     * @route("/cart/rest/order/delivery/update")
     * @return JsonResponse
     */
    public function restUpdateDeliveryOption(CartService $cartService)
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $orderContainer = $cartService->getOrderContainer();
        $result = new \stdClass();
        $result->status = 1;
        $result->orderContainer = $orderContainer;

        $request = Request::createFromGlobals();
        $id = $request->get('id');

        $fullClass = ModelService::fullClass($pdo, 'ShippingOption');
        $shippingOption = $fullClass::getByField($pdo, 'uniqid', $id);
        if ($shippingOption) {
            $shippingOption->calculatePrice($orderContainer);
            $orderContainer->setShippingId($shippingOption->getId());
            $orderContainer->setShippingTitle($shippingOption->getTitle());
            $orderContainer->setShippingCost($shippingOption->getPrice());
            $customer = $this->container->get('security.token_storage')->getToken()->getUser();
            $orderContainer->update($customer);
        }
        return new JsonResponse($result);
    }

}