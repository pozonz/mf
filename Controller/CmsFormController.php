<?php

namespace MillenniumFalcon\Controller;

use MillenniumFalcon\Core\Form\Builder\OrmProductVariantForm;
use MillenniumFalcon\Core\Orm\ProductVariant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;


class CmsFormController extends AbstractController
{
    /**
     * @return Response
     * @throws RedirectException
     */
    function productVariant($url, $productUniqid, $variantId = null)
    {
        $pdo = $this->container->get('doctrine')->getConnection()->getWrappedConnection();

        $request = Request::createFromGlobals();

        /** @var ProductVariant $orm */
        $orm = ProductVariant::getById($pdo, $variantId);
        if (!$orm) {
            $orm = new ProductVariant($pdo);
            $orm->setProductUniqid($productUniqid);
        }

        /** @var FormFactory $formFactory */
        $formFactory = $this->container->get('form.factory');
        /** @var FormBuilder $formBuiler */
        $formBuiler = $formFactory->createBuilder(OrmProductVariantForm::class, $orm, [
            'orm' => $orm,
        ]);

        /** @var Form $form */
        $form = $formBuiler->getForm();
        $form->handleRequest($request);

        $submitted = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $orm->save();

            $orm->setRank($orm->getId());
            $orm->save();

            $submitted = true;
        }

        $templateOptions = [
            'form' => $form->createView(),
            'url' => $url,
            'orm' => $orm,
            'submitted' => $submitted,
        ];
        return $this->render('cms/orms/forms/form-product-variant.html.twig', $templateOptions);
    }
}