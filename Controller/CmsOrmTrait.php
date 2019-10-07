<?php

namespace MillenniumFalcon\Controller;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Form\Builder\OrmForm;
use MillenniumFalcon\Core\Form\Builder\SearchProduct;
use MillenniumFalcon\Core\Form\Builder\SearchProductForm;
use MillenniumFalcon\Core\Nestable\FastTree;
use MillenniumFalcon\Core\Nestable\Node;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Exception\RedirectException;
use MillenniumFalcon\Core\Service\AssetService;
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

trait CmsOrmTrait
{

    /**
     * @route("/manage/admin/orms/Page")
     * @return Response
     */
    public function pages()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();

        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', 'Page');

        $fullClass = ModelService::fullClass($pdo, 'PageCategory');
        $categories = $fullClass::active($pdo);
        $cat = $request->get('cat') || $request->get('cat') === '0' ? $request->get('cat') : (count($categories) == 0 ? 0 : $categories[0]->getId());

        $params = $this->prepareParams();
        $params['categories'] = $categories;
        $params['cat'] = $cat;
        $params['ormModel'] = $model;
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

        $request = Request::createFromGlobals();
        $search = $request->get('search');
        if ($search) {
            $obj->category = $search['category'] ?? null;
            $obj->keywords = $search['keywords'] ?? null;

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
                $sql .= ($sql ? ' AND ' : '') . "MATCH (m.title, m.subtitle, m.brand, m.type, m.sku, m.description, m.content) AGAINST (? IN NATURAL LANGUAGE MODE)";
                $params = array_merge($params, [
                    '*' . $obj->keywords . '*'
                ]);
            }

//            var_dump($sql, $params);exit;
            $extraUrl = http_build_query([
                'search[category]' => $obj->category,
                'search[keywords]' => $obj->keywords,
            ]);
        }


        $form = $this->container->get('form.factory')->create(SearchProductForm::class, $obj, [
            'categories' => $tree->getRootNodes(),
        ]);
        $request = Request::createFromGlobals();
        $form->handleRequest($request);


        $pageNum = $request->get('pageNum') ?: 1;
        $limit = $model->getNumberPerPage();
        $sort = $request->get('sort') ?: $model->getDefaultSortBy();
        $order = $request->get('order') ?: ($model->getDefaultOrder() == 0 ? 'ASC' : 'DESC');

        $fullClass = ModelService::fullClass($pdo, $className);
        $orms = $fullClass::data($pdo, array(
            "whereSql" => $sql,
            "params" => $params,
            "page" => $pageNum,
            "limit" => $limit,
            "sort" => $sort,
            "order" => $order,
//            "debug" => 1,
        ));
        $total = $fullClass::data($pdo, array(
            "whereSql" => $sql,
            "params" => $params,
            "count" => 1,
        ));

        $params = $this->prepareParams();
        $params['search'] = $search;
        $params['formView'] = $form->createView();
        $params['totalPages'] = ceil($total['count'] / $limit);
        $params['url'] = $request->getPathInfo() . "?sort=$sort&order=$order" . ($extraUrl ? '&' . $extraUrl : '');
        $params['pageNum'] = $pageNum;
        $params['sort'] = $sort;
        $params['order'] = $order;

        $params['ormModel'] = $model;
        $params['orms'] = $orms;
        return $this->render($params['node']->getTemplate(), $params);
    }

    /**
     * @route("/manage/orms/{className}")
     * @route("/manage/admin/orms/{className}")
     * @route("/manage/pages/orms/{className}")
     * @return Response
     */
    public function orms($className)
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', $className);
        $fullClass = $model->getNamespace() . '\\' . $model->getClassName();

        $params = $this->prepareParams();
        if ($model->getListType() == 0) {
            $orms = $fullClass::data($pdo);

        } elseif ($model->getListType() == 1) {
            $request = Request::createFromGlobals();
            $pageNum = $request->get('pageNum') ?: 1;
            $sort = $request->get('sort') ?: $model->getDefaultSortBy();
            $order = $request->get('order') ?: ($model->getDefaultOrder() == 0 ? 'ASC' : 'DESC');

            $orms = $fullClass::data($pdo, array(
                "page" => $pageNum,
                "limit" => $model->getNumberPerPage(),
                "sort" => $sort,
                "order" => $order,
            ));

            $total = $fullClass::data($pdo, array(
                "count" => 1,
            ));
            $params['totalPages'] = ceil($total['count'] / $model->getNumberPerPage());
            $params['url'] = $request->getPathInfo() . "?sort=$sort&order=$order";
            $params['pageNum'] = $pageNum;
            $params['sort'] = $sort;
            $params['order'] = $order;

        } elseif ($model->getListType() == 2) {
            $nodes = $fullClass::data($pdo, array(
                "select" => 'm.id AS id, m.parentId AS parent, m.title, m.closed, m.status, m.count AS extraInfo',
                "sort" => 'm.rank',
                "order" => 'ASC',
                "orm" => 0,
            ));

            $tree = new \BlueM\Tree($nodes, ['rootId' => null]);
            $orms = $tree->getRootNodes();
        }

        $params['ormModel'] = $model;
        $params['orms'] = $orms;
        return $this->render($params['node']->getTemplate(), $params);
    }

    /**
     * @route("/manage/pages/orms/Page/{ormId}")
     * @return Response
     */
    public function page($ormId)
    {
        $className = 'Page';
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $orm = $this->_orm($pdo, $className, $ormId);
        return $this->_ormPageWithForm($pdo, $className, $orm, 'OrmPageForm', function () {
            $request = Request::createFromGlobals();
            throw new RedirectException($request->getUri());
        });
    }

    /**
     * @route("/manage/orms/Asset/{ormId}")
     * @route("/manage/admin/orms/Asset/{ormId}")
     * @route("/manage/pages/orms/Asset/{ormId}")
     * @return Response
     */
    public function asset($ormId)
    {
        $className = 'Asset';

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $orm = $this->_orm($pdo, $className, $ormId);
        return $this->_ormPageWithForm($pdo, $className, $orm, 'OrmAssetForm', function(Form $form, $orm) use ($pdo) {
            $uploadedFile = $form['file']->getData();
            if ($uploadedFile) {
                AssetService::processUploadedFileWithAsset($pdo, $uploadedFile, $orm);
            }
        });
    }

    /**
     * @route("/manage/orms/{className}/{ormId}")
     * @route("/manage/admin/orms/{className}/{ormId}")
     * @route("/manage/pages/orms/{className}/{ormId}")
     * @return Response
     */
    public function orm($className, $ormId)
    {
        $request = Request::createFromGlobals();
        if ($request->get('fragment') == 1) {
            $this->container->get('profiler')->disable();
        }

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $orm = $this->_orm($pdo, $className, $ormId);
        return $this->_ormPageWithForm($pdo, $className, $orm);
    }

    /**
     * @route("/manage/orms/{className}/copy/{ormId}")
     * @route("/manage/admin/orms/{className}/copy/{ormId}")
     * @route("/manage/pages/orms/{className}/copy/{ormId}")
     * @return Response
     */
    public function copyOrm($className, $ormId)
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $orm = $this->_orm($pdo, $className, $ormId);
        $orm->setUniqid(uniqid());
        $orm->setId(null);
        return $this->_ormPageWithForm($pdo, $className, $orm);
    }

    /**
     * @param $pdo
     * @param $className
     * @param $orm
     * @param string $formClass
     * @return mixed
     * @throws RedirectException
     */
    private function _orm($pdo, $className, $ormId)
    {
        $request = Request::createFromGlobals();

        $fullClass = ModelService::fullClass($pdo, $className);
        $orm = $fullClass::getById($pdo, $ormId);
        if (!$orm) {
            $orm = new $fullClass($pdo);

            $fields = array_keys($fullClass::getFields());
            foreach ($fields as $field) {
                $value = $request->get($field);
                if ($value) {
                    $method = 'set' . ucfirst($field);
                    $orm->$method($value);
                }
            }
        }
        return $orm;
    }

    /**
     * @param $pdo
     * @param $className
     * @param $orm
     * @param string $formClass
     * @return mixed
     * @throws RedirectException
     */
    private function _ormPageWithForm($pdo, $className, $orm, $formClass = 'OrmForm', $callback = null)
    {
        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', $className);
        $params = $this->prepareParams();

        $request = Request::createFromGlobals();
        $returnUrl = $request->get('returnUrl') ?: '/manage/orms/' . $model->getClassName();

        $fullFormClass = "MillenniumFalcon\\Core\\Form\\Builder\\{$formClass}";
        $form = $this->container->get('form.factory')->create($fullFormClass, $orm, array(
            'model' => $model,
            'orm' => $orm,
            'pdo' => $pdo,
        ));

        $params['fragmentSubmitted'] = 0;
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $orm->getId() ? 0 : 1;
            $this->convertDateValue($orm, $model);

            $user = $this->container->get('security.token_storage')->getToken()->getUser();
            $orm->setLastEditedBy($user->getId());
            $orm->save();

            if ($isNew) {
                $orm->setRank($orm->getId());
                $orm->save();
            }

            if ($callback) {
                call_user_func($callback, $form, $orm);
            }

            $baseUrl = str_replace('copy/', '', $params['node']->getUrl());
            if ($request->get('submit') == 'Apply') {
                throw new RedirectException($baseUrl . $orm->getId() . '?returnUrl=' . urlencode($returnUrl));
            } else if ($request->get('submit') == 'Save') {
                throw new RedirectException($returnUrl);
            } else if ($request->get('submit') == 'Save changes') {
                $params['fragmentSubmitted'] = 1;
            }
        }

        $params['returnUrl'] = $returnUrl;
        $params['form'] = $form->createView();
        $params['ormModel'] = $model;
        $params['orm'] = $orm;
        return $this->render($params['node']->getTemplate(), $params);
    }

    /**
     * @param $orm
     * @param $model
     */
    private function convertDateValue($orm, $model, $formats = [
        '\\MillenniumFalcon\\Core\Form\\Type\\DatePicker' => 'Y-m-d H:i:s',
        '\\MillenniumFalcon\\Core\Form\\Type\\DateTimePicker' => 'Y-m-d H:i:s',
    ])
    {
        $objColumnJson = json_decode($model->getColumnsJson());
        foreach ($objColumnJson as $columnJson) {
            if (isset($formats[$columnJson->widget])) {
                $getMethod = 'get' . ucfirst($columnJson->field);
                $dateStr = $orm->$getMethod();
                if ($dateStr) {
                    $format = $formats[$columnJson->widget];
                    $setMethod = 'set' . ucfirst($columnJson->field);
                    $orm->$setMethod(date($format, strtotime($dateStr)));
                }
            }
        }

        $objPresetDataMap = _Model::presetDataMap;
        foreach ($objPresetDataMap as $presetDataMap) {
            foreach ($presetDataMap as $idx => $val) {
                if (isset($formats[$val])) {
                    $getMethod = 'get' . ucfirst($idx);
                    $dateStr = $orm->$getMethod();
                    if ($dateStr) {
                        $format = $formats[$val];
                        $setMethod = 'set' . ucfirst($idx);
                        $orm->$setMethod(date($format, strtotime($dateStr)));
                    }
                }
            }
        }
    }
}