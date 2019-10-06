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
use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Form\Builder\Orm;
use MillenniumFalcon\Core\Nestable\PageNode;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\Orm\_Model;
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

trait CmsCartAccountTrait
{
    /**
     * @route("/account/dashboard")
     * @return mixed
     */
    public function accountDashboard()
    {
        return $this->render('cms/cart/account-member-dashboard.html.twig', array(
            'node' => null,
        ));
    }

    /**
     * @route("/account/orders")
     * @return mixed
     */
    public function accountOrders()
    {
        return $this->render('cms/cart/account-member-orders.html.twig', array(
            'node' => null,
        ));
    }

    /**
     * @route("/account/order")
     * @return mixed
     */
    public function accountOrder()
    {
        return $this->render('cms/cart/account-member-order.html.twig', array(
            'node' => null,
        ));
    }

    /**
     * @route("/account/addresses")
     * @return mixed
     */
    public function accountAddresses()
    {
        $orm = $this->container->get('security.token_storage')->getToken()->getUser();

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $fullClass = ModelService::fullClass($pdo, 'CustomerAddress');
        $customerAddresses = $fullClass::active($pdo, array(
            'whereSql' => 'm.customerId = ?',
            'params' => array($orm->getId()),
        ));

        return $this->render('cms/cart/account-member-addresses.html.twig', array(
            'node' => null,
            'customerAddresses' => $customerAddresses,
        ));
    }

    /**
     * @route("/account/address/{id}")
     * @return mixed
     */
    public function accountAddress($id)
    {
        $orm = $this->container->get('security.token_storage')->getToken()->getUser();

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $fullClass = ModelService::fullClass($pdo, 'CustomerAddress');
        $customerAddress = new $fullClass($pdo);
        $customerAddress->setCustomerId($orm->getId());
        if ($id && $id !== 'new') {
            $customerAddress = $fullClass::getByField($pdo, 'uniqid', $id);
            if (!$customerAddress) {
                throw new NotFoundHttpException();
            }
            if ($customerAddress->getCustomerId() != $orm->getId()) {
                throw new NotFoundHttpException();
            }
        }

        //convert 1/0 to boolean
        $customerAddress->setPrimaryAddress($customerAddress->getPrimaryAddress() ? true : false);

        /** @var FormFactory $formFactory */
        $formFactory = $this->container->get('form.factory');
        /** @var Form $form */
        $form = $formFactory->create(AccountAddress::class, $customerAddress, [
            'pdo' => $pdo,
        ]);

        $request = Request::createFromGlobals();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $customerAddress->setPrimaryAddress($customerAddress->getPrimaryAddress() ? 1 : 0);

            $customerAddresses = $fullClass::active($pdo, array(
                'whereSql' => 'm.customerId = ? AND m.uniqid != ?',
                'params' => array($orm->getId(), $id),
            ));
            $hasPrimaryAddress = 0;
            foreach ($customerAddresses as $itm) {
                if ($itm->getPrimaryAddress() == 1) {
                    $hasPrimaryAddress = 1;
                }
            }

            if ($request->get('submit') == 'Save') {
                if ($customerAddress->getPrimaryAddress() == 1) {

                    foreach ($customerAddresses as $itm) {
                        if ($itm->getPrimaryAddress() == 1) {
                            $itm->setPrimaryAddress(0);
                            $itm->save();
                        }
                    }

                } else {

                    if (!$hasPrimaryAddress) {
                        $customerAddress->setPrimaryAddress(1);
                    }
                }

                $customerAddress->save();

            } elseif ($request->get('submit') == 'Delete') {
                $customerAddress->delete();
                if (count($customerAddresses) > 0 && !$hasPrimaryAddress) {
                    $customerAddresses[0]->setPrimaryAddress(1);
                    $customerAddresses[0]->save();
                }
            }

            return new RedirectResponse('/account/addresses');
        }

        $formView = $form->createView();

        return $this->render('cms/cart/account-member-address.html.twig', array(
            'node' => null,
            'formView' => $formView,
            'customerAddress' => $customerAddress,
        ));
    }

    /**
     * @route("/account/profile")
     * @return mixed
     */
    public function accountProfile()
    {
        $orm = $this->container->get('security.token_storage')->getToken()->getUser();

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        /** @var FormFactory $formFactory */
        $formFactory = $this->container->get('form.factory');
        /** @var Form $form */
        $form = $formFactory->create(AccountProfile::class, $orm);

        $submitted = 0;
        $request = Request::createFromGlobals();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $submitted = 1;
            $orm->save();
        }

        $formView = $form->createView();

        return $this->render('cms/cart/account-member-profile.html.twig', array(
            'node' => null,
            'formView' => $formView,
            'submitted' => $submitted,
        ));
    }

    /**
     * @route("/account/ajax/address/delete")
     * @return Response
     */
    public function ajaxAccountAddressDelete()
    {
        $orm = $this->container->get('security.token_storage')->getToken()->getUser();

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $id = $request->get('id');

        $fullClass = ModelService::fullClass($pdo, 'CustomerAddress');
        $customerAddress = $fullClass::getById($pdo, $id);
        if (!$customerAddress) {
            throw new NotFoundHttpException();
        }
        if ($customerAddress->getCustomerId() != $orm->getId()) {
            throw new NotFoundHttpException();
        }

        $customerAddress->delete();
        return new JsonResponse($customerAddress);
    }

    /**
     * @route("/account/ajax/address/primary")
     * @return Response
     */
    public function ajaxAccountAddressPrimary()
    {
        $orm = $this->container->get('security.token_storage')->getToken()->getUser();

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $id = $request->get('id');

        $fullClass = ModelService::fullClass($pdo, 'CustomerAddress');
        $customerAddress = $fullClass::getById($pdo, $id);
        if (!$customerAddress) {
            throw new NotFoundHttpException();
        }
        if ($customerAddress->getCustomerId() != $orm->getId()) {
            throw new NotFoundHttpException();
        }

        $customerAddress->setPrimaryAddress(1);
        $customerAddress->save();

        $fullClass = ModelService::fullClass($pdo, 'CustomerAddress');
        $customerAddresses = $fullClass::active($pdo, array(
            'whereSql' => 'm.customerId = ? AND m.id != ?',
            'params' => array($orm->getId(), $id),
        ));

        foreach ($customerAddresses as $itm) {
            if ($itm->getPrimaryAddress() == 1) {
                $itm->setPrimaryAddress(0);
                $itm->save();
            }
        }

        return new JsonResponse($customerAddress);
    }

    /**
     * @route("/account/password")
     * @return mixed
     */
    public function accountPassword()
    {
        $orm = $this->container->get('security.token_storage')->getToken()->getUser();

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        /** @var FormFactory $formFactory */
        $formFactory = $this->container->get('form.factory');
        /** @var Form $form */
        $form = $formFactory->create(AccountPassword::class, $orm);

        $submitted = 0;
        $request = Request::createFromGlobals();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $submitted = 1;
            $orm->save();

            $returnUrl = $request->get('returnUrl');
            if ($returnUrl) {
                return new RedirectResponse($returnUrl);
            }
        }

        $formView = $form->createView();

        return $this->render('cms/cart/account-member-password.html.twig', array(
            'node' => null,
            'formView' => $formView,
            'submitted' => $submitted,
        ));
    }

    /**
     * @route("/login")
     * @return Response
     */
    public function accountLogin(AuthenticationUtils $authenticationUtils)
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        /** @var Response $response */
        $response = $this->render('cms/cart/account-login.html.twig', array(
            'node' => null,
            'last_username' => $lastUsername,
            'error' => $error,
        ));
        $response->headers->set('Referrer Policy', 'no-referer');
        return $response;
    }

    /**
     * @route("/register")
     * @return mixed
     */
    public function accountRegister()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $fullClass = ModelService::fullClass($pdo, 'Customer');
        $orm = new $fullClass($pdo);
        $form = $this->container->get('form.factory')->create(AccountRegisterForm::class, $orm, [
            'orm' => $orm,
        ]);
        $request = Request::createFromGlobals();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $exist = $fullClass::data($pdo, [
                'whereSql' => 'm.title = ? AND m.isActivated IS NULL',
                'params' => [$orm->getTitle()],
                'limit' => 1,
                'oneOrNull' => 1,
            ]);
            if ($exist) {
                $orm->setId($exist->getId());
            }

            $orm->setSource(CartService::CUSTOMER_WEBSITE);
            $orm->setResetToken(UtilsService::generateUniqueHex(40, []));
            $orm->setResetExpiry(date('Y-m-d', strtotime('+100 years')));
            $orm->save();

            $messageBody = $this->container->get('twig')->render("cms/cart/email/email-activate.twig", array(
                'customer' => $orm,
            ));

            $message = (new \Swift_Message())
                ->setSubject('Activate your account')
                ->setFrom(array(getenv('EMAIL_FROM')))
                ->setTo($orm->getTitle())
                ->setBcc(array(getenv('EMAIL_BCC')))
                ->setBody($messageBody, 'text/html');
            $this->container->get('mailer')->send($message);

            return new RedirectResponse('/activation/required?id=' . $orm->getUniqid());
        }

        $formView = $form->createView();

        return $this->render('cms/cart/account-login-register.html.twig', array(
            'node' => null,
            'formView' => $formView,
        ));
    }

    /**
     * @route("/activation/required")
     * @return Response
     */
    public function accountActivationRequired()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $uniqId = $request->get('id');

        $fullClass = ModelService::fullClass($pdo, 'Customer');
        $orm = $fullClass::getByField($pdo, 'uniqid', $uniqId);
        if (!$orm) {
            throw new NotFoundHttpException();
        }

        return $this->render('cms/cart/account-login-register-confirmation.html.twig', array(
            'node' => null,
            'orm' => $orm,
        ));
    }

    /**
     * @route("/activate/{id}")
     * @return Response
     */
    public function accountActivate($id)
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $fullClass = ModelService::fullClass($pdo, 'Customer');
        $orm = $fullClass::getByField($pdo, 'resetToken', $id);
        if (!$orm) {
            throw new NotFoundHttpException();
        }

        if ($orm->getIsActivated() == 1) {
            throw new NotFoundHttpException();
        }

        $orm->setResetToken(null);
        $orm->setIsActivated(1);
        $orm->setStatus(1);
        $orm->save();

        $tokenStorage = $this->container->get('security.token_storage');
        $token = new UsernamePasswordToken($orm, $orm->getPassword(), "public", $orm->getRoles());
        $tokenStorage->setToken($token);
        $this->get('session')->set('_security_account', serialize($token));
        return new RedirectResponse('\account\dashboard');
    }

    /**
     * @route("/account/after-login")
     * @return Response
     */
    public function accountAfterLogin()
    {
        $session = $this->container->get('session');
        $lastUri = $session->get(KernelExceptionListener::LAST_URI);
        if ($lastUri) {
            return new RedirectResponse($lastUri);
        }
        return new RedirectResponse('\account\dashboard');
    }

    /**
     * @route("/forget-password")
     * @return mixed
     */
    public function accountForgetPassword()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $fullClass = ModelService::fullClass($pdo, 'Customer');

        $form = $this->container->get('form.factory')->create(AccountForgetForm::class, null, [
            'pdo' => $pdo,
        ]);
        $request = Request::createFromGlobals();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $orm = $fullClass::data($pdo, [
                'whereSql' => 'm.title = ? AND m.isActivated = 1',
                'params' => [$data['title']],
                'limit' => 1,
                'oneOrNull' => 1,
            ]);

            $orm->setResetToken(UtilsService::generateUniqueHex(40, []));
            $orm->setResetExpiry(date('Y-m-d', strtotime('+7 days')));
            $orm->save();

            $messageBody = $this->container->get('twig')->render("cms/cart/email/email-forget.twig", array(
                'customer' => $orm,
            ));

            $message = (new \Swift_Message())
                ->setSubject('Reset your password')
                ->setFrom(array(getenv('EMAIL_FROM')))
                ->setTo($orm->getTitle())
                ->setBcc(array(getenv('EMAIL_BCC')))
                ->setBody($messageBody, 'text/html');
            $this->container->get('mailer')->send($message);

            return new RedirectResponse('/forget-password/confirmation?id=' . $orm->getUniqid());
        }

        $formView = $form->createView();

        return $this->render('cms/cart/account-login-forget.html.twig', array(
            'node' => null,
            'formView' => $formView,
        ));
    }

    /**
     * @route("/forget-password/confirmation")
     * @return Response
     */
    public function accountForgetConfirm()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $uniqId = $request->get('id');

        $fullClass = ModelService::fullClass($pdo, 'Customer');
        $orm = $fullClass::getByField($pdo, 'uniqid', $uniqId);
        if (!$orm) {
            throw new NotFoundHttpException();
        }

        return $this->render('cms/cart/account-login-forget-confirmation.html.twig', array(
            'node' => null,
            'orm' => $orm,
        ));
    }

    /**
     * @route("/reset/{token}")
     * @return Response
     */
    public function resetPassword($token)
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');


        $orm = Customer::getByField($pdo, 'resetToken', $token);
        if (!$orm) {
            throw new NotFoundHttpException();
        }

        if (time() >= strtotime($orm->getResetExpiry())) {
            throw new NotFoundHttpException();
        }

        $orm->setResetToken('');
        $orm->save();

        $tokenStorage = $this->container->get('security.token_storage');
        $token = new UsernamePasswordToken($orm, $orm->getPassword(), "public", $orm->getRoles());
        $tokenStorage->setToken($token);
        $this->get('session')->set('_security_account', serialize($token));
        return new RedirectResponse('\account\password');
    }

    /**
     * @route("/cart")
     * @return mixed
     */
    public function displayCart()
    {
        return $this->render('cms/cart/cart-display.html.twig', array(
            'node' => null,
        ));
    }

    /**
     * @route("/cart/review")
     * @return mixed
     */
    public function reviewCart()
    {
        return $this->render('cms/cart/cart-display-review.html.twig', array(
            'node' => null,
        ));
    }

    /**
     * @route("/cart/payment/success")
     * @return mixed
     */
    public function showCartSuccess()
    {
        return $this->render('cms/cart/cart-payment-success.html.twig', array(
            'node' => null,
        ));
    }

    /**
     * @route("/cart/payment/failed")
     * @return mixed
     */
    public function showCartFailed()
    {
        return $this->render('cms/cart/cart-payment-failed.html.twig', array(
            'node' => null,
        ));
    }
}