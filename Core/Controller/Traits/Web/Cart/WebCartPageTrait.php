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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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
        $cartDisplayFormClass = getenv('CART_DISPLAY_FORM_CLASS');

        $customer = $this->cartService->getCustomer();
        $order = $this->cartService->getOrder();

        $formFactory = $this->container->get('form.factory');
        $form = $formFactory->create($cartDisplayFormClass, $order, [
            'connection' => $this->connection,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (count($order->objOrderItems()) > 0) {
                $order->update($customer);
                return new RedirectResponse('/cart/review');
            }
        }
        $formView = $form->createView();

        $page = new Page($this->connection);
        return $this->render('cart/cart-display.twig', [
            'theNode' => new Tree\Node(uniqid(), uniqid(), [
                'extraInfo' => $page,
            ]),
            'formView' => $formView,
        ]);
    }

    /**
     * @Route("/cart/review")
     * @param Request $request
     * @param \Swift_Mailer $mailer
     * @param SessionInterface $session
     * @return RedirectResponse
     */
    public function reivewCart(Request $request, \Swift_Mailer $mailer, SessionInterface $session)
    {
        $cartReviewFormClass = getenv('CART_REVIEW_FORM_CLASS');

        $customer = $this->cartService->getCustomer();
        $order = $this->cartService->getOrder();

        $formFactory = $this->container->get('form.factory');
        $form = $formFactory->create($cartReviewFormClass, $order, [
            'connection' => $this->connection,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $order->update($customer);
                if ($order->getTotal() == 0 && count($order->objOrderItems()) > 0) {
                    $order->setCategory(CartService::STATUS_SUCCESS);
                    $messageBody = $this->render("cart/email/invoice.twig", [
                        'order' => $order,
                    ])->getContent();
                    $order->setEmailContent($messageBody);
                    $order->save();


                    $message = (new \Swift_Message())
                        ->setSubject('Your order has been received #' . $order->getTitle())
                        ->setFrom('noreply@nzbcf.org.nz')
                        ->setTo([$order->getEmail()])
//                        ->setBcc(array(static::EMAIL_INVOICE_BCC_ADDRESS, static::EMAIL_INVOICE_OWNER_ADDRESS, 'digital@bcf.org.nz', 'breasthealth@bcf.org.nz'))
                        ->setBody($messageBody, 'text/html');

                    $mailer->send($message);

                    $session->set(CartService::SESSION_ID, null);

                    return new RedirectResponse('/cart/success?result=' . $order->getTitle());
                }
            } else {
                return new RedirectResponse('/cart');
            }
        }
        $formView = $form->createView();

        $page = new Page($this->connection);
        return $this->render('cart/cart-review.twig', [
            'theNode' => new Tree\Node(uniqid(), uniqid(), [
                'extraInfo' => $page,
            ]),
            'formView' => $formView,
        ]);
    }

    /**
     * @Route("/cart/success")
     * @param Request $request
     * @return mixed
     */
    public function showCartSuccess(Request $request, SessionInterface $session)
    {
        $result = $request->get('result');

        $fullClass = ModelService::fullClass($this->connection, 'Order');
        $order = $fullClass::getByField($this->connection, 'title', $result);
        if (!$order) {
            $session->set(CartService::SESSION_ID, null);
            return new RedirectResponse('/cart');
        }

        if ($order->getCategory() != CartService::STATUS_SUCCESS) {
            $session->set(CartService::SESSION_ID, $order->getId());
            return new RedirectResponse('/cart');
        }

        $page = new Page($this->connection);
        return $this->render('cart/cart-success.twig', [
            'theNode' => new Tree\Node(uniqid(), uniqid(), [
                'extraInfo' => $page,
            ]),
            'order' => $order
        ]);
    }
}