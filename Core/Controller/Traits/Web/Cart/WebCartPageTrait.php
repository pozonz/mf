<?php

namespace MillenniumFalcon\Core\Controller\Traits\Web\Cart;

use BlueM\Tree;
use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Form\Builder\OrmForm;
use MillenniumFalcon\Core\Form\Builder\OrmProductsForm;
use MillenniumFalcon\Core\Form\Builder\OrmShippingOptionMethodForm;
use MillenniumFalcon\Core\Form\Builder\SearchProduct;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\ORM\Page;
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

trait WebCartPageTrait
{
    /**
     * @Route("/cart")
     * @param Request $request
     * @return JsonResponse
     */
    public function displayCart(Request $request)
    {
        $page = new Page($this->connection);
        return $this->render('cart/cart-display.twig', [
            'theNode' => new Tree\Node(uniqid(), uniqid(), [
                'extraInfo' => $page,
            ]),
        ]);
    }
}