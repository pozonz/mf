<?php

namespace MillenniumFalcon\Controller;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Db;
use MillenniumFalcon\Core\Exception\KernelExceptionListener;
use MillenniumFalcon\Core\Exception\RedirectException;
use MillenniumFalcon\Core\Form\Builder\AccountAddress;
use MillenniumFalcon\Core\Form\Builder\AccountForgetForm;
use MillenniumFalcon\Core\Form\Builder\AccountPassword;
use MillenniumFalcon\Core\Form\Builder\AccountProfile;
use MillenniumFalcon\Core\Form\Builder\AccountRegisterForm;
use MillenniumFalcon\Core\Form\Builder\CartForm;
use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Form\Builder\Orm;
use MillenniumFalcon\Core\Nestable\PageNode;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Orm\CustomerAddress;
use MillenniumFalcon\Core\Service\CartService;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\UtilsService;
use MillenniumFalcon\Core\Twig\Extension;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

require_once (__DIR__ . '/../PxPay_Curl.inc.php');

trait WebCartTrait
{
    /**
     * @route("/cart")
     * @return mixed
     */
    public function displayCart(CartService $cartService)
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $orderContainer = $cartService->getOrderContainer();

        $formFactory = $this->container->get('form.factory');
        $form = $formFactory->create(CartForm::class, $orderContainer, array(
            'pdo' => $pdo,
        ));

        $request = Request::createFromGlobals();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $customer = UtilsService::getUser($this->container);;
            $orderContainer->update($customer);

            return new RedirectResponse('/cart/review');
        }

        $formView = $form->createView();
        return $this->render('cms/cart/cart-display.html.twig', array(
            'node' => null,
            'formView' => $formView,
        ));
    }

    /**
     * @route("/cart/review")
     * @return mixed
     */
    public function reviewCart(CartService $cartService)
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $orderContainer = $cartService->getOrderContainer();

        $formFactory = $this->container->get('form.factory');
        $form = $formFactory->create(CartForm::class, $orderContainer, array(
            'pdo' => $pdo,
        ));

        $request = Request::createFromGlobals();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $request->get($form->getName());

            if ($orderContainer->getCustomerId()) {
                $customer = $orderContainer->objCustomer();
                $fullClass = ModelService::fullClass($pdo, 'CustomerAddress');
                if ($orderContainer->getBillingSave()) {
                    $customerAddress = new $fullClass($pdo);
                    $customerAddress->setTitle('');
                    $customerAddress->setCustomerId($customer->getId());
                    $customerAddress->setAddress($orderContainer->getBillingAddress());
                    $customerAddress->setAddress2($orderContainer->getBillingAddress2());
                    $customerAddress->setState($orderContainer->getBillingState());
                    $customerAddress->setCity($orderContainer->getBillingCity());
                    $customerAddress->setPostcode($orderContainer->getBillingPostcode());
                    $customerAddress->setCountry($orderContainer->getBillingCountry());
                    $customerAddress->setFirstname($orderContainer->getBillingFirstname());
                    $customerAddress->setLastname($orderContainer->getBillingLastname());
                    $customerAddress->setPhone($orderContainer->getBillingPhone());
                    $customerAddress->setPrimaryAddress(count($customer->objAddresses()) ? 0 : 1);
                    $customerAddress->save();
                }

                if ($orderContainer->getShippingSave()) {
                    $customerAddress = new $fullClass($pdo);
                    $customerAddress->setTitle('');
                    $customerAddress->setCustomerId($customer->getId());
                    $customerAddress->setAddress($orderContainer->getShippingAddress());
                    $customerAddress->setAddress2($orderContainer->getShippingAddress2());
                    $customerAddress->setState($orderContainer->getShippingState());
                    $customerAddress->setCity($orderContainer->getShippingCity());
                    $customerAddress->setPostcode($orderContainer->getShippingPostcode());
                    $customerAddress->setCountry($orderContainer->getShippingCountry());
                    $customerAddress->setFirstname($orderContainer->getShippingFirstname());
                    $customerAddress->setLastname($orderContainer->getShippingLastname());
                    $customerAddress->setPhone($orderContainer->getShippingPhone());
                    $customerAddress->setPrimaryAddress(count($customer->objAddresses()) ? 0 : 1);
                    $customerAddress->save();
                }
            }

            if ($data['action'] == 'dps') {
                $returnUrl = $request->getSchemeAndHttpHost() . '/cart/dps/finalise';
                $uniqid = $orderContainer->getId() . "_" . substr(time() . "", -5);

                $request = new \PxPayRequest();
                $request->setEnableAddBillCard(1) ;
                $request->setAmountInput($orderContainer->getTotal()) ;
                $request->setTxnData1($uniqid);
                $request->setTxnType('Purchase');
                $request->setCurrencyInput('NZD');
                $request->setBillingId($orderContainer->getId());
                $request->setMerchantReference($uniqid); # fill this with your order number
                $request->setEmailAddress($orderContainer->getEmail());
                $request->setUrlFail($returnUrl);
                $request->setUrlSuccess($returnUrl);
                $request->setTxnId($uniqid);
                $pxaccess = $this->getDpsGateway();
                $requestString = $pxaccess->makeRequest($request);

                $orderContainer->setSubmitted(1);
                $orderContainer->setSubmittedDate(date('Y-m-d H:i:s'));

                $orderContainer->setCategory(CartService::STATUS_SUBMITTED);
                $orderContainer->setPayRequest(print_r($request, true));
                $orderContainer->setPayToken($uniqid);
                $orderContainer->setPayDate(date('Y-m-d H:i:s'));
                $orderContainer->save();

                $pxaccess = null;
                $pxXml = $request->toXml();
                $response = new \MifMessage($requestString);
                $dpsUrl = $response->get_element_text("URI");
                $valid = $response->get_attribute("valid");

                if ($valid) {
                    return new RedirectResponse($dpsUrl);
                }
            }
        }

        $formView = $form->createView();
        return $this->render('cms/cart/cart-display-review.html.twig', array(
            'node' => null,
            'formView' => $formView,
        ));
    }

    /**
     * @route("/cart/dps/finalise")
     * @return Response
     */
    public function dpsFinaliseOrder()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();

        $pxaccess = $this->getDpsGateway();
        $pxResponse = $pxaccess->getResponse($request->get('result'));

        $fullClass = ModelService::fullClass($pdo, 'Order');
        $orderContainer = $fullClass::getByField($pdo, 'payToken', $pxResponse->getTxnData1());

        if (!$orderContainer) {
            throw new NotFoundHttpException();
        }

        if ($orderContainer->getCategory() != CartService::STATUS_SUCCESS) {

            $orderContainer->setCategory($pxResponse->getSuccess() ? CartService::STATUS_SUCCESS : CartService::STATUS_UNPAID);
            $orderContainer->setPayStatus($orderContainer->getCategory());
            $orderContainer->setPayResponse(print_r($pxResponse, true));
            $orderContainer->save();

            if ($orderContainer->getCategory() == CartService::STATUS_SUCCESS) {

                $messageBody = $this->container->get('twig')->render("cms/cart/emails/invoice.twig", array(
                    'orderContainer' => $orderContainer,
                ));
                $orderContainer->setEmailContent($messageBody);
                $orderContainer->save();

                $message = (new \Swift_Message())
                    ->setSubject('Invoice #' . $orderContainer->getUniqid())
                    ->setFrom(array(getenv('EMAIL_FROM')))
                    ->setTo(array($orderContainer->getEmail()))
                    ->setBcc(array(getenv('EMAIL_BCC'), getenv('EMAIL_BCC_ORDER')))
                    ->setBody($messageBody, 'text/html');
                $this->container->get('mailer')->send($message);

                //Update stock
                $orderItems = $orderContainer->objOrderItems();
                foreach ($orderItems as $orderItem) {
                    $productVariant = $orderItem->objProductVariant();
                    $stock = $productVariant->getStock();
                    $productVariant->setStock($stock - $orderItem->getQuantity());
                    $productVariant->save();
                }

                $this->container->get('session')->set(CartService::SESSION_ID, null);
                return new RedirectResponse('/cart/payment/success?id=' . $orderContainer->getUniqid());

            } else {

                $orderContainer->setCategory(CartService::STATUS_UNPAID);
                $orderContainer->setPayStatus($orderContainer->getCategory());
                $orderContainer->save();
                $this->container->get('session')->set(CartService::SESSION_ID, $orderContainer->getId());
                return new RedirectResponse('/cart/payment/failed?id=' . $orderContainer->getUniqid());

            }

        } else if ($orderContainer->getCategory() == CartService::STATUS_SUCCESS) {

            $this->container->get('session')->set(CartService::SESSION_ID, null);
            return new RedirectResponse('/cart/payment/failed?id=' . $orderContainer->getUniqid());

        }
    }

    /**
     * @route("/cart/payment/success")
     * @return mixed
     */
    public function showCartSuccess()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $fullClass = ModelService::fullClass($pdo, 'Order');
        $orderContainer = $fullClass::getByField($pdo, 'uniqid', $id);

        if (!$orderContainer) {
            throw new NotFoundHttpException();
        }

        if ($orderContainer->getCategory() != CartService::STATUS_SUCCESS) {
            return new RedirectResponse('/cart/payment/failed?id=' . $orderContainer->getUniqid());
        }

        return $this->render('cms/cart/cart-payment-success.html.twig', array(
            'node' => null,
            'myOrderContainer' => $orderContainer,
        ));
    }

    /**
     * @route("/cart/payment/failed")
     * @return mixed
     */
    public function showCartFailed()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $fullClass = ModelService::fullClass($pdo, 'Order');
        $orderContainer = $fullClass::getByField($pdo, 'uniqid', $id);

        if (!$orderContainer) {
            throw new NotFoundHttpException();
        }

        if ($orderContainer->getCategory() == CartService::STATUS_SUCCESS) {
            return new RedirectResponse('/cart/payment/success?id=' . $orderContainer->getUniqid());
        }

        return $this->render('cms/cart/cart-payment-failed.html.twig', array(
            'node' => null,
            'myOrderContainer' => $orderContainer,
        ));
    }

    /**
     * @return \PxPay_Curl
     */
    public function getDpsGateway(){
        return new \PxPay_Curl(getenv('PX_ACCESS_URL'), getenv('PX_ACCESS_USERID'), getenv('PX_ACCESS_KEY'));;
    }

}