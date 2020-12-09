<?php

namespace MillenniumFalcon\Core\Controller\Traits\Web\Cart;

use BlueM\Tree;
use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Form\Builder\OrmForm;
use MillenniumFalcon\Core\Form\Builder\OrmShippingOptionMethodForm;
use MillenniumFalcon\Core\Form\Builder\SearchProduct;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Service\CartService;
use MillenniumFalcon\Core\SymfonyKernel\RedirectException;
use MillenniumFalcon\Core\Service\AssetService;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\UtilsService;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

trait WebCartAjaxTrait
{
    /**
     * @Route("/cart/order/item/add")
     * @param Request $request
     * @return JsonResponse
     */
    public function addToCart(Request $request)
    {
        $id = $request->get('id');
        $qty = $request->get('qty');

        $customer = $this->cartService->getCustomer();
        $order = $this->cartService->getOrder();

        $productVariantClassName = CartService::getProductVariantClassName();
        $productClassName = CartService::getProductClassName();

        $productVariantFullClass = ModelService::fullClass($this->connection, $productVariantClassName);
        $productFullClass = ModelService::fullClass($this->connection, $productClassName);
        $orderItemFullClass = ModelService::fullClass($this->connection, 'OrderItem');

        $variant = $productVariantFullClass::getById($this->connection, $id);
        if (!$variant || !$variant->getStatus()) {
            return new JsonResponse([
                'order' => $order,
            ]);
        }

        $product = $variant->objProduct();
        if ((!$product || !$product->getStatus()) && $productFullClass) {
            return new JsonResponse([
                'order' => $order,
            ]);
        }

        $exist = false;
        $orderItems = $order->objOrderItems();
        foreach ($orderItems as $itm) {
            if ($itm->getProductId() == $variant->getId()) {
                $itm->setQuantity($itm->getQuantity() + $qty);
                $itm->save();
                $exist = true;
            }
        }

        if (!$exist) {
            $orderItem = new $orderItemFullClass($this->connection);
            $orderItem->setTitle($variant->objTitle());
            $orderItem->setSku($variant->getSku());
            $orderItem->setOrderId($order->getId());
            $orderItem->setProductId($variant->getId());
            $orderItem->setQuantity($qty);
            $orderItem->save();
        }

        $order->update($customer);

        return new JsonResponse([
            'order' => $order,
        ]);
    }

    /**
     * @Route("/cart/order/item/qty")
     * @param Request $request
     * @return JsonResponse
     */
    public function changeOrderItemQty(Request $request)
    {
        $id = $request->get('id');
        $qty = $request->get('qty');

        $customer = $this->cartService->getCustomer();
        $order = $this->cartService->getOrder();

        $orderItems = $order->objOrderItems();
        foreach ($orderItems as $itm) {
            if ($itm->getId() == $id) {
                $itm->setQuantity($qty);
                $itm->save();
            }
        }

        $order->update($customer);

        return new JsonResponse([
            'order' => $order,
        ]);
    }

    /**
     * @Route("/cart/order/item/delete")
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteOrderItem(Request $request)
    {
        $id = $request->get('id');

        $customer = $this->cartService->getCustomer();
        $order = $this->cartService->getOrder();

        $orderItems = $order->objOrderItems();
        foreach ($orderItems as $itm) {
            if ($itm->getId() == $id) {
                $itm->delete();
            }
        }

        $order->update($customer);

        return new JsonResponse([
            'order' => $order,
        ]);
    }

    /**
     * @Route("/cart/order/clear")
     * @param Request $request
     * @return JsonResponse
     */
    public function clearOrder(Request $request)
    {
        $customer = $this->cartService->getCustomer();
        $order = $this->cartService->getOrder();

        $orderItems = $order->objOrderItems();
        foreach ($orderItems as $itm) {
            $itm->delete();
        }

        $order->update($customer);

        return new JsonResponse([
            'order' => $order,
        ]);
    }
}