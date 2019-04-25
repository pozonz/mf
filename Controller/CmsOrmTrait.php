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

        $request = Request::createFromGlobals();

        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', 'Page');

        $categories = PageCategory::active($pdo);
        $cat = $request->get('cat') || $request->get('cat') === '0' ? $request->get('cat') : $categories[0]->getId();

        $params = $this->prepareParams();
        $params['categories'] = $categories;
        $params['cat'] = $cat;
        $params['ormModel'] = $model;
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

        $params['ormModel'] = $model;
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

            $baseUrl = str_replace('copy/', '', $params['node']->getUrl());
            if ($request->get('submit') == 'Apply') {
                throw new RedirectException($baseUrl . $orm->getId() . '?returnUrl=' . urlencode($returnUrl));
            } else if ($request->get('submit') == 'Save') {
                throw new RedirectException($returnUrl);
            }
        }

        $params['returnUrl'] = $returnUrl;
        $params['form'] = $form->createView();
        $params['ormModel'] = $model;
        $params['orm'] = $orm;
        return $this->render($params['node']->getTemplate(), $params);
    }
}