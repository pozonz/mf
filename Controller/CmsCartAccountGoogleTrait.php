<?php
namespace MillenniumFalcon\Controller;

use Pz\Orm\Customer;
use Pz\Service\CartService;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

trait CmsCartAccountGoogleTrait
{
    /**
     * @route("/google/verify")
     * @return Response
     */
    public function verifyGoogle() {
        $request = Request::createFromGlobals();

        $client = new \Google_Client();
        $client->setClientId(getenv('GOOGLE_ID'));
        $client->setClientSecret(getenv('GOOGLE_SECRET'));
        $client->setIncludeGrantedScopes(true);
        $client->addScope(\Google_Service_Plus::USERINFO_EMAIL);
        $client->addScope(\Google_Service_Plus::USERINFO_PROFILE);
        $client->setRedirectUri(strtok($request->getUri(), '?'));
        $client->setPrompt('select_account');

        $code = $request->get('code');
        if ($code) {
            $client->fetchAccessTokenWithAuthCode($code);
            $access_token = $client->getAccessToken();
        }

        if (!isset($access_token) || !$access_token) {
            $auth_url = $client->createAuthUrl();
            return new RedirectResponse($auth_url);
        } else {

            $client->setAccessToken($access_token);
            $oauth = new \Google_Service_Oauth2($client);
            $userInfo = $oauth->userinfo->get();

            $connection = $this->container->get('doctrine.dbal.default_connection');
            /** @var \PDO $pdo */
            $pdo = $connection->getWrappedConnection();

            $customer = Customer::data($pdo, array(
                'whereSql' => 'm.title = ? AND m.status = 1',
                'params' => array($userInfo->email),
                'oneOrNull' => 1,
            ));

            $redirectUrl = '/account/dashboard';
            if (!$customer) {
                $customer = new Customer($pdo);
                $customer->setTitle($userInfo->email);
                $customer->setFirstname($userInfo->givenName);
                $customer->setLastname($userInfo->familyName);
                $customer->setSource(CartService::CUSTOMER_GOOGLE);
                $customer->setSourceId($userInfo->id);
                $customer->setIsActivated(1);
                $customer->save();
                $redirectUrl = '/account/password?returnUrl=' . urlencode('/cart');
            } else {
                $orderContainer = $this->cartService->getOrderContainer();
                if (count($orderContainer->getPendingItems())) {
                    $redirectUrl = '/account/after_login';
                }
            }



            $tokenStorage = $this->container->get('security.token_storage');
            $token = new UsernamePasswordToken($customer, $customer->getPassword(), "public", $customer->getRoles());
            $tokenStorage->setToken($token);
            $this->get('session')->set('_security_member', serialize($token));
            return new RedirectResponse($redirectUrl);
        }

    }
}