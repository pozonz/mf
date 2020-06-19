<?php

namespace MillenniumFalcon\Core\Controller\Traits;

use BlueM\Tree\Node;
use Cocur\Slugify\Slugify;

use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Twig\Extension;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

trait CmsRestProductTrait
{
    /**
     * @route("/manage/rest/product-categories")
     * @return Response
     */
    public function cmsProductCategories()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $fullClassCategory = ModelService::fullClass($pdo, 'ProductCategory');
        $fullClassProduct = ModelService::fullClass($pdo, 'Product');

        $categories = [];
        $result = $fullClassCategory::data($pdo);
        foreach ($result as $itm) {
            $categories[$itm->getId()] = $itm;
        }

        $tree = new \BlueM\Tree($fullClassCategory::data($pdo, [
            "select" => 'm.id AS id, m.parentId AS parent, m.title, m.count',
            "sort" => 'm.rank',
            "order" => 'ASC',
            "orm" => 0,
        ]), [
            'rootId' => null,
        ]);

        foreach ($tree->getRootNodes() as $node) {
            $this->getProductSubtotal($node, $categories, $fullClassProduct, $pdo);
        }

        return new JsonResponse(1);
    }

    /**
     * @param Node $node
     * @param $categories
     * @param $fullClassProduct
     * @param $pdo
     */
    public function getProductSubtotal(Node $node, $categories, $fullClassProduct, $pdo)
    {
        if (isset($categories[$node->getId()])) {

            $result = $node->getDescendantsAndSelf();
            $sqlComponents = array_map(function ($itm) {
                return 'm.categories LIKE ?';
            }, $result);
            $sql = implode(' OR ', $sqlComponents);

            $params = array_map(function ($itm) {
                return '%"' . $itm->getId() . '"%';
            }, $result);

            $total = $fullClassProduct::data($pdo, [
                'whereSql' => $sql,
                'params' => $params,
                'count' => 1,
            ]);

            $orm = $categories[$node->getId()];
            $orm->setCount($total['count']);
            $orm->save();

            foreach ($node->getChildren() as $child) {
                $this->getProductSubtotal($child, $categories, $fullClassProduct, $pdo);
            }
        }
    }

    /**
     * @route("/manage/rest/product-variants")
     * @return Response
     */
    public function cmsProductVariants()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

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