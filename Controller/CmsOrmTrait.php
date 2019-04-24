<?php

namespace MillenniumFalcon\Controller;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Db;
use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Form\Builder\Orm;
use MillenniumFalcon\Core\Nestable\PageNode;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Orm\AssetSize;
use MillenniumFalcon\Core\Orm\DataGroup;
use MillenniumFalcon\Core\Orm\Page;
use MillenniumFalcon\Core\Orm\PageCategory;
use MillenniumFalcon\Core\Orm\PageTemplate;
use MillenniumFalcon\Core\Orm\User;
use MillenniumFalcon\Core\Redirect\RedirectException;
use MillenniumFalcon\Core\Router;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;

trait CmsOrmTrait
{
    /**
     * @route("/manage/orms/Page")
     * @route("/manage/admin/orms/Page")
     * @return Response
     */
    public function pages()
    {

        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', 'Page');
        $fullClass = $model->getNamespace() . '\\' . $model->getClassName();

        $categories = PageCategory::active($pdo);

        $request = Request::createFromGlobals();
        $category = $request->get('cat') || $request->get('cat') === 0 ? $request->get('cat') : $categories[0]->getId();
//                    {% set cat = app.request.get('cat') or app.request.get('cat') is same as('0') ? app.request.get('cat') : categories.0.id %}

        $pages = Page::data($pdo);
        $nodes = array();
        foreach ($pages as $page) {
            $category = $page->getCategory() ? json_decode($page->getCategory()) : [];
            if (!in_array($cat, $category) && !($cat == 0 && count($category) == 0)) {
                continue;
            }
            $categoryParent = (array)json_decode($page->getCategoryParent());
            $categoryRank = (array)json_decode($page->getCategoryRank());
            $categoryClosed = (array)json_decode($page->getCategoryClosed());

            $categoryParentValue = isset($categoryParent["cat$cat"]) ? $categoryParent["cat$cat"] : 0;
            $categoryRankValue = isset($categoryRank["cat$cat"]) ? $categoryRank["cat$cat"] : 0;
            $categoryClosedValue = isset($categoryClosed["cat$cat"]) ? $categoryClosed["cat$cat"] : 0;

            $page->setParentId($categoryParentValue);
            $page->setRank($categoryRankValue);
            $page->setClosed($categoryClosedValue);

            $nodes[] =$page;
        }

        $tree = new Tree($nodes);
        $pageRoot = $tree->getRoot();

        $params = $this->prepareParams();

//        var_dump($params);exit;
//        var_dump($model->getListType());exit;

        $params['pageRoot'] = $pageRoot;
        $params['categories'] = $categories;
        $params['category'] = $category;
        $params['model'] = $model;
        return $this->render($params['node']->getTemplate(), $params);
    }

    /**
     * @route("/manage/orms/{className}")
     * @route("/manage/admin/orms/{className}")
     * @return Response
     */
    public function orms($className)
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

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
            $params['totalPages'] = ceil($total['count'] /  $model->getNumberPerPage());
            $params['url'] = $request->getPathInfo() . "?sort=$sort&order=$order";
            $params['pageNum'] = $pageNum;
            $params['sort'] = $sort;
            $params['order'] = $order;
        } elseif ($model->getListType() == 2) {


        }

        $params['model'] = $model;
        $params['orms'] = $orms;
        return $this->render($params['node']->getTemplate(), $params);
    }

    /**
     * @route("/manage/orms/{className}/{ormId}")
     * @route("/manage/admin/orms/{className}/{ormId}")
     * @return Response
     */
    public function orm($className, $ormId)
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', $className);
        $fullClass = $model->getNamespace() . '\\' . $model->getClassName();
        $orm = $fullClass::getById($pdo, $ormId);
        if (!$orm) {
            $orm = new $fullClass($pdo);
        }

        return $this->_orm($pdo, $model, $orm);
    }

    /**
     * @route("/manage/orms/{className}/copy/{ormId}")
     * @route("/manage/admin/orms/{className}/copy/{ormId}")
     * @return Response
     */
    public function copyOrm($className, $ormId)
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', $className);
        $fullClass = $model->getNamespace() . '\\' . $model->getClassName();
        $orm = $fullClass::getById($pdo, $ormId);
        if (!$orm) {
            $orm = new $fullClass($pdo);
        }

        $orm->setId(null);
        return $this->_orm($pdo, $model, $orm);
    }

    /**
     * @param $model
     * @param $orm
     * @return mixed
     * @throws RedirectException
     */
    private function _orm($pdo, $model, $orm)
    {
        $params = $this->prepareParams();

        $request = Request::createFromGlobals();
        $returnUrl = $request->get('returnUrl') ?: '/manage/orms/' . $model->getClassName();

        $form = $this->container->get('form.factory')->create(Orm::class, $orm, array(
            'model' => $model,
            'orm' => $orm,
            'pdo' => $pdo,
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $orm->getId() ? 0 : 1;
            $orm->save();

            $baseUrl = '/manage/orms/' . $model->getClassName();
            if ($request->get('submit') == 'Apply') {
                throw new RedirectException($baseUrl . '/' . $orm->getId());
            } else if ($request->get('submit') == 'Save') {
                throw new RedirectException($returnUrl);
            }
        }

        $params['returnUrl'] = $returnUrl;
        $params['form'] = $form->createView();
        $params['model'] = $model;
        $params['orm'] = $orm;
        return $this->render($params['node']->getTemplate(), $params);
    }
}