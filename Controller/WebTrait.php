<?php

namespace MillenniumFalcon\Controller;

use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

trait WebTrait
{
    /**
     * @route("/{page}", requirements={"page" = ".*"})
     * @return Response
     */
    public function web()
    {
        $request = Request::createFromGlobals();
        $path = rtrim($request->getPathInfo(), '/');
        $params = $this->getParams($path);
        return $this->render($params['node']->getTemplate(), $params);
    }

    /**
     * @return array
     */
    public function getNodes()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $fullClass = ModelService::fullClass($pdo, 'Page');
        return $fullClass::data($pdo);
    }
}