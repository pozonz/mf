<?php

namespace MillenniumFalcon\Controller;

use MillenniumFalcon\Core\Nestable\PageNode;
use MillenniumFalcon\Core\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CmsController extends Router
{
    /**
     * @Route("/cms")
     */
    public function index()
    {
        $request = Request::createFromGlobals();
        $requestUri = rtrim($request->getPathInfo(), '/');
        $params = $this->getParams($requestUri);
        return $this->render($params['node']->getTemplate(), $params);
    }

    public function getNodes()
    {
        return [
            new PageNode(1, null, 0, 1, 'Pages', '/cms', 'pz/pages.twig', 'fa fa-sitemap'),
        ];
    }
}