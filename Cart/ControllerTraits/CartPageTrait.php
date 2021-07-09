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
     * @route("/shop/{categories}", requirements={"categories" = ".*"})
     * @param Request $request
     * @return mixed
     */
    public function shop(Request $request, $categories = null)
    {
        $category = null;
        if ($categories) {
            $categories = explode('/', $categories);
            $category = array_pop($categories);
        }
        $params = array_merge($this->getTemplateParamsByUrl('/shop'), $this->filterProductResult($request, $category));
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

    /**
     * @param Request $request
     * @return array
     * @throws RedirectException
     */
    protected function filterProductResult(Request $request, $category = null)
    {
        $limit = 100;
        $limit = min($request->get('limit') ?: 20, $limit);
        $productCategorySlug = $category ?? $request->get('category');
        $productBrandSlug = $request->get('brand');
        $productKeyword = $request->get('keyword');
        $pageNum = $request->get('pageNum') ?: 1;
        $sortby = $request->get('sortby');
        $sort = 'CAST(m.pageRank AS UNSIGNED)';
        $order = 'DESC';

        if ($sortby == 'price-high-to-low') {
            $sort = 'CAST(m.price AS UNSIGNED)';
            $order = 'DESC';
        } elseif ($sortby == 'price-low-to-high') {
            $sort = 'CAST(m.price AS UNSIGNED)';
            $order = 'ASC';
        } elseif ($sortby == 'newest') {
            $sort = 'm.added';
            $order = 'DESC';
        } elseif ($sortby == 'oldest') {
            $sort = 'm.added';
            $order = 'ASC';
        }

        $productCategoryFullClass = ModelService::fullClass($this->connection, 'ProductCategory');
        $productBrandFullClass = ModelService::fullClass($this->connection, 'ProductBrand');
        $productFullClass = ModelService::fullClass($this->connection, 'Product');

        $brands = $productBrandFullClass::active($this->connection);
        $categories = new \BlueM\Tree($productCategoryFullClass::active($this->connection, [
            "select" => 'm.id AS id, m.parentId AS parent, m.title, m.slug, m.status',
            "sort" => 'm.rank',
            "order" => 'ASC',
            "orm" => 0,
        ]), [
            'rootId' => null,
        ]);

        $selectedProductCategory = $productCategoryFullClass::getBySlug($this->connection, $productCategorySlug);
        if ($selectedProductCategory) {
            $selectedProductCategory = $categories->getNodeById($selectedProductCategory->getId());
        }
        $selectedProductBrand = $productBrandFullClass::getBySlug($this->connection, $productBrandSlug);

        $whereSql = '';
        $params = [];

        if ($selectedProductCategory) {
            $descendants = $selectedProductCategory->getDescendants();
            $selectedProductCategoryIds = array_merge([$selectedProductCategory->get('id')], array_map(function ($itm) {
                return $itm->getId();
            }, $descendants));

            $s = array_map(function ($itm) {
                return "m.categories LIKE ?";
            }, $selectedProductCategoryIds);
            $p = array_map(function ($itm) {
                return '%"' . $itm . '"%';
            }, $selectedProductCategoryIds);
            $whereSql .= ($whereSql ? ' AND ' : '') . '(' . implode(' OR ', $s) . ')';
            $params = array_merge($params, $p);
        }

        if ($selectedProductBrand) {
            $whereSql .= ($whereSql ? ' AND ' : '') . '(m.brand = ?)';
            $params = array_merge($params, [$selectedProductBrand->getId()]);
        }

        if ($productKeyword) {
            $whereSql .= ($whereSql ? ' AND ' : '') . '(m.title LIKE ? OR m.sku LIKE ? OR m.description LIKE ?)';
            $params = array_merge($params, ['%' . $productKeyword . '%', '%' . $productKeyword . '%', '%' . $productKeyword . '%']);
        }

        $products = $productFullClass::active($this->connection, [
            'whereSql' => $whereSql,
            'params' => $params,
            'page' => $pageNum,
            'limit' => $limit,
            'sort' => $sort,
            'order' => $order,
            'debug' => 0,
        ]);

        $total = $productFullClass::active($this->connection, [
            'whereSql' => $whereSql,
            'params' => $params,
            'count' => 1
        ]);

        $pageTotal = ceil($total['count'] / $limit);

        return [
            'orms' => $products,
            'brands' => $brands,
            'categories' => $categories,
            'selectedProductCategory' => $selectedProductCategory,
            'selectedProductBrand' => $selectedProductBrand,
            'productKeyword' => $productKeyword,
            'pageNum' => $pageNum,
            'pageTotal' => $pageTotal,
            'total' => $total,
            'sortby' => $sortby,
        ];
    }
}