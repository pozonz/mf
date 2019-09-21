<?php

namespace MillenniumFalcon\Controller;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Db;
use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Form\Builder\Orm;
use MillenniumFalcon\Core\Nestable\PageNode;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Twig\Extension;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

trait CmsRestProductTrait
{
    /**
     * @route("/manage/rest/product-variants")
     * @return Response
     */
    public function cmsProductVariants()
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $request = Request::createFromGlobals();
        $productUniqid = $request->get('productUniqid');
        $fullClass = ModelService::fullClass($pdo, 'ProductVariant');
        $data = $fullClass::data($pdo, [
            'whereSql' => 'm.productUniqid = ?',
            'params' => [$productUniqid],
        ]);
        return new JsonResponse($data);
    }
}