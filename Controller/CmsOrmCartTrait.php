<?php

namespace MillenniumFalcon\Controller;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Form\Builder\OrmForm;
use MillenniumFalcon\Core\Form\Builder\OrmProductsForm;
use MillenniumFalcon\Core\Form\Builder\OrmShippingOptionMethodForm;
use MillenniumFalcon\Core\Form\Builder\SearchProduct;
use MillenniumFalcon\Core\Nestable\FastTree;
use MillenniumFalcon\Core\Nestable\Node;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Exception\RedirectException;
use MillenniumFalcon\Core\Service\AssetService;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\UtilsService;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

trait CmsOrmCartTrait
{
    /**
     * @route("/manage/shop/Dashboard")
     * @return Response
     */
    public function dashboard()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');
        $request = Request::createFromGlobals();

        $fullClass = ModelService::fullClass($pdo, 'Product');
        $params = $this->prepareParams();
        $params['tables'] = [
            [
                'title' => 'Product Summary',
                'rows' => [
                    [
                        'title' => 'All products',
                        'link' => '/manage/orms/Product',
                        'value' => $fullClass::data($pdo, [
                            'count' => 1,
                        ])['count'],
                    ],
                    [
                        'title' => 'In stock',
                        'link' => '/manage/orms/Product',
                        'value' => $fullClass::data($pdo, [
                            'whereSql' => '((m.outOfStock = 0 OR m.outOfStock IS NULL) AND (m.lowStock = 0 OR m.lowStock IS NULL))',
                            'count' => 1,
                        ])['count'],
                    ],
                    [
                        'title' => 'Low stock',
                        'link' => '/manage/orms/Product',
                        'value' => $fullClass::data($pdo, [
                            'whereSql' => '(m.lowStock = 1)',
                            'count' => 1,
                        ])['count'],
                    ],
                    [
                        'title' => 'Out of stock',
                        'link' => '/manage/orms/Product',
                        'value' => $fullClass::data($pdo, [
                            'whereSql' => '(m.outOfStock = 1)',
                            'count' => 1,
                        ])['count'],
                    ],
                ],
            ]
        ];
        return $this->render($params['node']->getTemplate(), $params);
    }

    /**
     * @route("/manage/shop/ShippingOptionMethod")
     * @return Response
     */
    public function shippingOptionMethod()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');
        $request = Request::createFromGlobals();

        $fullClass = ModelService::fullClass($pdo, 'ShippingOptionMethod');
        $methods = [];
        $result = $fullClass::data($pdo);

        $obj = new \stdClass();
        $obj->method = $result[0]->getClassName();
        foreach ($result as $itm) {
            $methods[$itm->getTitle()] = $itm->getClassName();
            if ($itm->getSelected() == 1) {
                $obj->className = $itm->getClassName();
            }
        }

        $form = $this->container->get('form.factory')->create(OrmShippingOptionMethodForm::class, $obj, [
            'methods' => $methods,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($result as $itm) {
                $itm->setSelected(0);
                $itm->save();
            }
            $orm = $fullClass::getByField($pdo, 'className', $obj->className);
            $orm->setSelected(1);
            $orm->save();
        }

        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', $obj->className);
        $fullClass = $model->getNamespace() . '\\' . $model->getClassName();

        $params = $this->prepareParams();
        $orms = $fullClass::data($pdo);

        $params['formView'] = $form->createView();
        $params['obj'] = $obj;

        $params['ormModel'] = $model;
        $params['orms'] = $orms;
        return $this->render($params['node']->getTemplate(), $params);
    }

    /**
     * @route("/manage/orms/ProductCategory")
     * @return Response
     */
    public function productCategories()
    {
        $className = 'ProductCategory';

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', $className);
        $fullClass = $model->getNamespace() . '\\' . $model->getClassName();

        $params = $this->prepareParams();
        $nodes = $fullClass::data($pdo, array(
            "select" => 'm.id AS id, m.parentId AS parent, m.title, m.closed, m.status, m.count AS extraInfo',
            "sort" => 'm.rank',
            "order" => 'ASC',
            "orm" => 0,
        ));

        $tree = new \BlueM\Tree($nodes, ['rootId' => null]);
        $orms = $tree->getRootNodes();

        $params['ormModel'] = $model;
        $params['orms'] = $orms;
        return $this->render($params['node']->getTemplate(), $params);
    }

    /**
     * @route("/manage/orms/Product")
     * @return Response
     */
    public function products()
    {
        $className = 'Product';
        $pdo = $this->container->get('doctrine.dbal.default_connection');
        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', $className);

        $fullClass = ModelService::fullClass($pdo, 'ProductCategory');
        $tree = new \BlueM\Tree($fullClass::data($pdo, [
            "whereSql" => 'm.count > 0',
            "select" => 'm.id AS id, m.parentId AS parent, CONCAT(m.title, " (", m.count , ")") AS title',
            "sort" => 'm.rank',
            "order" => 'ASC',
            "orm" => 0,
        ]), [
            'rootId' => null,
        ]);

        $sql = '';
        $params = [];
        $extraUrl = '';

        $obj = new \stdClass();
        $obj->category = null;
        $obj->keywords = null;
        $obj->stock = null;

        $request = Request::createFromGlobals();
        $search = $request->get('search');
        if ($search) {
            $obj->category = $search['category'] ?? null;
            $obj->keywords = $search['keywords'] ?? null;
            $obj->stock = $search['stock'] ?? null;

            if ($obj->category) {
                $node = $tree->getNodeById($obj->category);
                $result = $node->getDescendantsAndSelf();
                $sqlComponents = array_map(function ($itm) {
                    return 'm.categories LIKE ?';
                }, $result);
                $sql = implode(' OR ', $sqlComponents);

                $params = array_map(function ($itm) {
                    return '%"' . $itm->getId() . '"%';
                }, $result);
            }

            if ($obj->keywords) {
                $sql = ($sql ? "($sql) AND " : '') . "(MATCH (m.content) AGAINST (? IN Boolean MODE))";
                $params = array_merge($params, [
                    '*' . $obj->keywords . '*'
                ]);
            }

            if ($obj->stock == 1) {
                $sql = ($sql ? "($sql) AND " : '') . "((m.outOfStock = 0 OR m.outOfStock IS NULL) AND (m.lowStock = 0 OR m.lowStock IS NULL))";
            } else if ($obj->stock == 2) {
                $sql = ($sql ? "($sql) AND " : '') . "(m.lowStock = 1)";
            } else if ($obj->stock == 3) {
                $sql = ($sql ? "($sql) AND " : '') . "(m.outOfStock = 1)";
            }

//            var_dump($sql, $params);exit;
            $extraUrl = http_build_query([
                'search[category]' => $obj->category,
                'search[keywords]' => $obj->keywords,
            ]);
        }


        $form = $this->container->get('form.factory')->create(OrmProductsForm::class, $obj, [
            'categories' => $tree->getRootNodes(),
        ]);
        $request = Request::createFromGlobals();
        $form->handleRequest($request);


        $pageNum = $request->get('pageNum') ?: 1;
        $limit = $model->getNumberPerPage();
        $sort = $request->get('sort') ?: 'pageRank';
        $order = $request->get('order') ?: 'DESC';

        $fullClass = ModelService::fullClass($pdo, $className);

        if ($obj->keywords) {
            $orms = $fullClass::data($pdo, array(
                'select' => 'm.*, MATCH (m.content) AGAINST (? IN Boolean MODE) as relevance',
                "whereSql" => $sql,
                'params' => array_merge(['*' . $obj->keywords . '*'], $params),
                "page" => $pageNum,
                "limit" => $limit,
                'sort' => 'relevance',
                'order' => 'DESC',
//            "debug" => 1,
            ));
        } else {
            $orms = $fullClass::data($pdo, array(
                "whereSql" => $sql,
                'params' => $params,
                "page" => $pageNum,
                "limit" => $limit,
                'sort' => $sort,
                'order' => $order,
//            "debug" => 1,
            ));
        }

        $total = $fullClass::data($pdo, array(
            "whereSql" => $sql,
            "params" => $params,
            "count" => 1,
        ));

        $params = $this->prepareParams();
        $params['search'] = $search;
        $params['formView'] = $form->createView();
        $params['total'] = $total['count'];
        $params['totalPages'] = ceil($total['count'] / $limit);
        $params['url'] = $request->getPathInfo() . "?sort=$sort&order=$order" . ($extraUrl ? '&' . $extraUrl : '');
        $params['urlNoSort'] = $request->getPathInfo();
        $params['pageNum'] = $pageNum;
        $params['sort'] = $sort;
        $params['order'] = $order;

        $params['ormModel'] = $model;
        $params['orms'] = $orms;
        return $this->render($params['node']->getTemplate(), $params);
    }

    /**
     * @route("/manage/orms/Order")
     * @return Response
     */
    public function orders()
    {
        $className = 'Order';

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', $className);
        $fullClass = $model->getNamespace() . '\\' . $model->getClassName();

        $params = $this->prepareParams();

        $request = Request::createFromGlobals();
        $pageNum = $request->get('pageNum') ?: 1;
        $sort = $request->get('sort') ?: $model->getDefaultSortBy();
        $order = $request->get('order') ?: ($model->getDefaultOrder() == 0 ? 'ASC' : 'DESC');

        $orms = $fullClass::data($pdo, array(
            "whereSql" => 'm.submitted = 1',
            "page" => $pageNum,
            "limit" => $model->getNumberPerPage(),
            "sort" => $sort,
            "order" => $order,
        ));

        $total = $fullClass::data($pdo, array(
            "whereSql" => 'm.category > 0',
            "count" => 1,
        ));

        $params['total'] = $total['count'];
        $params['totalPages'] = ceil($total['count'] / $model->getNumberPerPage());
        $params['url'] = $request->getPathInfo() . "?sort=$sort&order=$order";
        $params['urlNoSort'] = $request->getPathInfo();
        $params['pageNum'] = $pageNum;
        $params['sort'] = $sort;
        $params['order'] = $order;

        $params['ormModel'] = $model;
        $params['orms'] = $orms;
        return $this->render($params['node']->getTemplate(), $params);
    }
}