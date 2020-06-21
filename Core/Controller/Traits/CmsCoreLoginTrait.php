<?php

namespace MillenniumFalcon\Core\Controller\Traits;

use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Routing\Annotation\Route;

trait CmsCoreLoginTrait
{
    /**
     * @Route("/manage/login")
     * @param AuthenticationUtils $authenticationUtils
     * @return mixed
     */
    public function login(Request $request, AuthenticationUtils $authenticationUtils)
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('cms/login.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * @Route("/manage/after_login")
     * @param AuthenticationUtils $authenticationUtils
     * @return RedirectResponse
     * @throws \Exception
     */
    public function afterLogin(AuthenticationUtils $authenticationUtils)
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');
        $fullClass = ModelService::fullClass($pdo, 'DataGroup');
        $orm = $fullClass::active($pdo, [
            'limit' => 1,
            'oneOrNull' => 1,
        ]);
        if (!$orm) {
            return new RedirectResponse('/manage/pages');
        }
        return new RedirectResponse($orm->getBuiltInSection() ? "/manage/{$orm->getBuiltInSectionCode()}" : "/manage/section/{$orm->getId()}");
    }
}