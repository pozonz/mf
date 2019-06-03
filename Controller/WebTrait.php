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
        $requestUri = rtrim($request->getPathInfo(), '/');
        $params = $this->getParams($requestUri);
        return $this->render($params['node']->getTemplate(), $params);
    }

    /**
     * @return array
     */
    public function getNodes()
    {
        /** @var \PDO $pdo */
        $pdo = $this->connection->getWrappedConnection();

        $fullClass = ModelService::fullClass($pdo, 'Page');
        return $fullClass::data($pdo);
    }
}