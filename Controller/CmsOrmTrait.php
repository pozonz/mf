<?php

namespace MillenniumFalcon\Controller;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Form\Builder\OrmForm;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Redirect\RedirectException;
use MillenniumFalcon\Core\Service\AssetService;
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

trait CmsOrmTrait
{

    /**
     * @route("/manage/orms/Asset/{ormId}")
     * @route("/manage/admin/orms/Asset/{ormId}")
     * @return Response
     */
    public function asset($ormId)
    {
        $className = 'Asset';

        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $orm = $this->_orm($pdo, $className, $ormId);
        return $this->_ormPageWithForm($pdo, $className, $orm, 'OrmAssetForm', function(Form $form, $orm) use ($pdo) {
            $uploadedFile = $form['file']->getData();
            if ($uploadedFile) {
                AssetService::processUploadedFileWithAsset($pdo, $uploadedFile, $orm);
            }
        });
    }

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

        $fullClass = ModelService::fullClass($pdo, 'PageCategory');
        $categories = $fullClass::active($pdo);
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
            $params['totalPages'] = ceil($total['count'] / $model->getNumberPerPage());
            $params['url'] = $request->getPathInfo() . "?sort=$sort&order=$order";
            $params['pageNum'] = $pageNum;
            $params['sort'] = $sort;
            $params['order'] = $order;

        } elseif ($model->getListType() == 2) {
            //TODO: implement tree

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

        $orm = $this->_orm($pdo, $className, $ormId);
        return $this->_ormPageWithForm($pdo, $className, $orm);
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

        $orm = $this->_orm($pdo, $className, $ormId);
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
        $fullClass = ModelService::fullClass($pdo, $className);
        $orm = $fullClass::getById($pdo, $ormId);
        if (!$orm) {
            $orm = new $fullClass($pdo);
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

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $orm->getId() ? 0 : 1;
            $orm->save();

            if ($callback) {
               call_user_func($callback, $form, $orm);
            }

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