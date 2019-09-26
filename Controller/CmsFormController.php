<?php

namespace MillenniumFalcon\Controller;

use MillenniumFalcon\Core\Form\Builder\OrmForm;
use MillenniumFalcon\Core\Form\Builder\OrmProductVariantForm;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Orm\ProductVariant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class CmsFormController extends AbstractController
{
    /**
     * @return Response
     * @throws RedirectException
     */
    function productVariant($productUniqid, $variantId = null)
    {
        $pdo = $this->container->get('doctrine')->getConnection();

        $request = Request::createFromGlobals();

        /** @var ProductVariant $orm */
        $orm = ProductVariant::getById($pdo, $variantId);
        if (!$orm) {
            $orm = new ProductVariant($pdo);
            $orm->setProductUniqid($productUniqid);
        }

        $model = $orm->getModel();

        /** @var FormFactory $formFactory */
        $formFactory = $this->container->get('form.factory');
        /** @var FormBuilder $formBuiler */
        $formBuiler = $formFactory->createBuilder(OrmForm::class, $orm, [
            'model' => $model,
            'orm' => $orm,
            'pdo' => $pdo,
        ]);

        /** @var Form $form */
        $form = $formBuiler->getForm();
        $form->handleRequest($request);

        $submitted = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $isNewOrm = $orm->getId() ? 0 : 1;
            $orm->save();

            if ($isNewOrm) {
                $orm->setRank($orm->getId());
                $orm->save();
            }

            $submitted = true;
        }

        $templateOptions = [
            'url' => $request->getRequestUri(),
            'form' => $form->createView(),
            'orm' => $orm,
            'submitted' => $submitted,
            'ormModel' => $model,
        ];
        return $this->render('cms/orms/forms/form-product-variant.html.twig', $templateOptions);
    }
}