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

trait ShopRestfulTrait
{
    /**
     * @route("/product/variant/price")
     * @param Request $request
     * @return JsonResponse
     * @throws RedirectException
     */
    public function productPrice(Request $request)
    {
        $uniqid = $request->get('uniqid');
        $fullClass = ModelService::fullClass($this->connection, 'ProductVariant');
        $variant = $fullClass::getByField($this->connection, 'uniqid', $uniqid);

        if (!$variant) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse([
            'html' => $this->environment->render('/cart/includes/product-price.twig', [
                'product' => $variant->objProduct(),
                'variant' => $variant,
            ]),
        ]);
    }
}
