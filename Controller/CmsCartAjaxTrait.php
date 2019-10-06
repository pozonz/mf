<?php

namespace Pz\Controller;

use Omnipay\Common\CreditCard;
use Omnipay\Common\GatewayFactory;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

trait CmsCartAjaxTrait
{
    /**
     * @route("/xhr/cart/item/add/{id}/{quantity}")
     * @return Response
     */
    public function xhrAddOrderItem($id, $quantity)
    {
        $orderContainer = $this->cartService->addOrderItem($id, $quantity);
        return new JsonResponse($orderContainer);
    }

    /**
     * @route("/xhr/cart/order")
     * @return Response
     */
    public function xhrGetOrder()
    {
        $orderContainer = $this->cartService->getOrderContainer();
        return new JsonResponse($orderContainer);
    }

    /**
     * @route("/xhr/cart/item/qty")
     * @return Response
     */
    public function xhrChangeItemQty()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $qty = $request->get('qty');

        $orderContainer = $this->cartService->getOrderContainer();
        $pendingItems = $orderContainer->getPendingItems();
        foreach ($pendingItems as $pendingItem) {
            if ($pendingItem->getUniqid() == $id) {
                $pendingItem->setQuantity($qty);
                $pendingItem->setSubtotal($pendingItem->getPrice() * $pendingItem->getQuantity());
                $pendingItem->setTotalWeight($pendingItem->getWeight() * $pendingItem->getQuantity());
            }
        }
        $orderContainer->update();
        return new JsonResponse($orderContainer);
    }

    /**
     * @route("/xhr/cart/order/address/update")
     * @return Response
     */
    public function xhrUpdateAddress()
    {
        $request = Request::createFromGlobals();
        $o = json_decode($request->get('order'));

        $orderContainer = $this->cartService->getOrderContainer();
        foreach ($o as $idx => $itm) {
            if (strpos($idx, 'shipping') == -1 && strpos($idx, 'billing') == -1) {
                continue;
            }
            $method = 'set' . ucfirst($idx);
            $orderContainer->$method($itm);
        }
        $orderContainer->update();
        return new JsonResponse($orderContainer);
    }

    /**
     * @route("/xhr/cart/order/delivery/update")
     * @return Response
     */
    public function xhrUpdateDeliveryOption()
    {
        $request = Request::createFromGlobals();
        $deliverOptionId = $request->get('id');

        $orderContainer = $this->cartService->getOrderContainer();
        $orderContainer->setDeliveryOptionId($deliverOptionId);
        $orderContainer->update();
        return new JsonResponse($orderContainer);
    }

    /**
     * @route("/xhr/cart/item/delete")
     * @return Response
     */
    public function xhrDeleteItem()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');

        $orderContainer = $this->cartService->getOrderContainer();
        $pendingItems = $orderContainer->getPendingItems();
        foreach ($pendingItems as $idx => $pendingItem) {
            if ($pendingItem->getUniqid() == $id) {
                array_splice($pendingItems, $idx, 1);
                $orderContainer->setPendingItems($pendingItems);
                $pendingItem->delete();
                break;
            }
        }
        $orderContainer->update();
        return new JsonResponse($orderContainer);
    }

    /**
     * @route("/xhr/cart/promo/apply")
     * @return Response
     */
    public function xhrApplyPromoCode()
    {
        $request = Request::createFromGlobals();
        $code = $request->get('code');

        $orderContainer = $this->cartService->getOrderContainer();
        $orderContainer->setPromoCode($code);
        $orderContainer->update();
        return new JsonResponse($orderContainer);
    }
}