<?php

namespace MillenniumFalcon\Controller;


use MillenniumFalcon\Core\Form\Builder\CartAddItemForm;
use MillenniumFalcon\Core\Service\CartService;
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\HttpFoundation\Request;

trait CmsCartFormTrait
{
    /**
     * @param $variantId
     * @param $url
     * @param CartService $cartService
     * @return mixed
     * @throws \Exception
     */
    public function addToCart($variantId, $url, CartService $cartService)
    {
        $quantity = 1;

        $pdo = $this->container->get('doctrine.dbal.default_connection');
        $orderContainer = $cartService->getOrderContainer();

        $fullClass = ModelService::fullClass($pdo, 'ProductVariant');
        $variant  = $fullClass::getById($pdo, $variantId);
        $product = $variant->objProduct();

        $stockInCart = 0;
        $fullClass = ModelService::fullClass($pdo, 'OrderItem');
        $orderItem = new $fullClass($pdo);
        $orderItem->setTitle($product->objTitle() . ' - ' . $variant->getTitle());
        $orderItem->setOrderId($orderContainer->getId());
        $orderItem->setProductId($variant->getId());
        $orderItem->setWeight($variant->getWeight());
        $orderItem->setQuantity($quantity);

        $orderItems = $orderContainer->objOrderItems();
        foreach ($orderItems as $itm) {
            if ($itm->getProductId() == $variant->getId()) {
                $orderItem = $itm;
                $stockInCart = $orderItem->getQuantity();
                $orderItem->setQuantity($quantity);
            }
        }
        $form = $this->container->get('form.factory')->create(CartAddItemForm::class, $orderItem, array(
            'maxQuantity' => $variant->getStock() - $stockInCart,
        ));

        $request = Request::createFromGlobals();
        $form->handleRequest($request);
        $submitted = 0;
        if ($form->isSubmitted() && $form->isValid()) {
            $orderItem->setQuantity($orderItem->getQuantity() + $stockInCart);
            $submitted = 1;

            $customer = $this->container->get('security.token_storage')->getToken()->getUser();
            $orderContainer->update($customer);

            $orderItem->setQuantity($quantity);
            $form = $this->container->get('form.factory')->create(CartAddItemForm::class, $orderItem, array(
                'maxQuantity' => $variant->getStock(),
            ));
        }

        $params = [];
        $params['url'] = $url;
        $params['product'] = $product;
        $params['variant'] = $variant;
        $params['submitted'] = $submitted;
        $params['formView'] = $form->createView();
        return $this->render('cms/cart/forms/add-to-cart.html.twig', $params);
    }
}