<?php

namespace MillenniumFalcon\Controller;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Db;
use MillenniumFalcon\Core\Exception\KernelExceptionListener;
use MillenniumFalcon\Core\Exception\RedirectException;
use MillenniumFalcon\Core\Form\Builder\CustomerRegisterForm;
use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Form\Builder\Orm;
use MillenniumFalcon\Core\Nestable\PageNode;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Orm\Customer;
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
        return $this->render('cms/cart/account-member-addresses.html.twig', array(
            'node' => null,
        ));
    }

    /**
     * @route("/account/address")
     * @return mixed
     */
    public function accountAddress()
    {
        return $this->render('cms/cart/account-member-address.html.twig', array(
            'node' => null,
        ));
    }

    /**
     * @route("/account/profile")
     * @return mixed
     */
    public function accountProfile()
    {
        return $this->render('cms/cart/account-member-profile.html.twig', array(
            'node' => null,
        ));
    }

    /**
     * @route("/account/password")
     * @return mixed
     */
    public function accountPassword()
    {
        return $this->render('cms/cart/account-member-password.html.twig', array(
            'node' => null,
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
        $form = $this->container->get('form.factory')->create(CustomerRegisterForm::class, $orm, [
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

//        $orm->setResetToken(null);
//        $orm->setIsActivated(1);
        $orm->setStatus(1);
        $orm->save();

        $tokenStorage = $this->container->get('security.token_storage');
        $token = new UsernamePasswordToken($orm, $orm->getPassword(), "public", $orm->getRoles());
        $tokenStorage->setToken($token);
        $this->get('session')->set('_security_account', serialize($token));
        return new RedirectResponse('\account\dashboard');
    }

    /**
     * @route("/account/after_login")
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
        return $this->render('cms/cart/account-login-forget.html.twig', array(
            'node' => null,
        ));
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