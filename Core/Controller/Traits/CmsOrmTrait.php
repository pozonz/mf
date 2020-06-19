<?php

namespace MillenniumFalcon\Core\Controller\Traits;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Form\Builder\OrmForm;
use MillenniumFalcon\Core\Form\Builder\OrmProductsForm;
use MillenniumFalcon\Core\Form\Builder\SearchProduct;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Exception\RedirectException;
use MillenniumFalcon\Core\Service\AssetService;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\UtilsService;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use function ZendTest\Code\Reflection\TestAsset\function1;

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

            $total = $fullClass::data($pdo, array(
                "count" => 1,
            ));
            $params['total'] = $total['count'];

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
            $params['total'] = $total['count'];
            $params['totalPages'] = ceil($total['count'] / $model->getNumberPerPage());
            $params['url'] = $request->getPathInfo() . "?sort=$sort&order=$order";
            $params['urlNoSort'] = $request->getPathInfo();
            $params['pageNum'] = $pageNum;
            $params['sort'] = $sort;
            $params['order'] = $order;

        } elseif ($model->getListType() == 2) {
            $nodes = $fullClass::data($pdo, array(
                "select" => 'm.id AS id, m.parentId AS parent, m.title, m.closed, m.status',
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
     * @route("/manage/pages/orms/Page/{ormId}/version/{versionUuid}")
     * @return Response
     */
    public function page($ormId, $versionUuid = null)
    {
        $className = 'Page';
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $orm = $this->_orm($pdo, $className, $ormId);
        if ($versionUuid) {
            $orm = $orm->getByVersionUuid($versionUuid);
        }
        return $this->_ormPageWithForm($pdo, $className, $orm, 'OrmPageForm', function () use ($orm) {
            $request = Request::createFromGlobals();
            throw new RedirectException('/manage/pages/orms/Page/' . $orm->getId());
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
     * @route("/manage/current-user")
     * @return Response
     */
    public function user()
    {
        $request = Request::createFromGlobals();
        if ($request->get('fragment') == 1 && $_SERVER['APP_ENV'] == 'dev') {
            $this->container->get('profiler')->disable();
        }

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $cmsUser = UtilsService::getUser($this->container);;

        $className = 'User';
        $ormId = $cmsUser->getId();
        $orm = $this->_orm($pdo, $className, $ormId);
        return $this->_ormPageWithForm($pdo, $className, $orm, 'OrmForm', function() {
            throw new RedirectException('/manage/current-user');
        });
    }

    /**
     * @route("/manage/orms/{className}/{ormId}")
     * @route("/manage/admin/orms/{className}/{ormId}")
     * @route("/manage/pages/orms/{className}/{ormId}")
     * @route("/manage/orms/{className}/{ormId}/version/{versionUuid}")
     * @route("/manage/admin/orms/{className}/{ormId}/version/{versionUuid}")
     * @route("/manage/pages/orms/{className}/{ormId}/version/{versionUuid}")
     * @return Response
     */
    public function orm($className, $ormId, $versionUuid = null)
    {
        $request = Request::createFromGlobals();
        if ($request->get('fragment') == 1 && $_SERVER['APP_ENV'] == 'dev') {
            $this->container->get('profiler')->disable();
        }

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $orm = $this->_orm($pdo, $className, $ormId);
        if ($versionUuid) {
            $orm = $orm->getByVersionUuid($versionUuid);
        }
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
    protected function _orm($pdo, $className, $ormId)
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
    protected function _ormPageWithForm($pdo, $className, $orm, $formClass = 'OrmForm', $callback = null)
    {
        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', $className);
        $params = $this->prepareParams();

        $request = Request::createFromGlobals();
        $returnUrl = $request->get('returnUrl') ?: null;

        $fullFormClass = "MillenniumFalcon\\Core\\Form\\Builder\\{$formClass}";
        $form = $this->container->get('form.factory')->create($fullFormClass, $orm, array(
            'model' => $model,
            'orm' => $orm,
            'pdo' => $pdo,
        ));

        $params['fragmentSubmitted'] = 0;
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $submitButtonValue = $request->get('submit');

            if ($submitButtonValue == 'Preview') {
                $orm->savePreview();
                throw new RedirectException($orm->getFrontendUrl() . "?__preview_{$model->getClassName()}=" . $orm->getVersionUuid());
            }

            $isNew = $orm->getId() ? 0 : 1;
            $this->convertDateValue($orm, $model);

            $user = UtilsService::getUser($this->container);
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
            if ($submitButtonValue == 'Apply' || $submitButtonValue == 'Restore') {
                throw new RedirectException($baseUrl . $orm->getId() . '?returnUrl=' . urlencode($returnUrl));
            } else if ($submitButtonValue == 'Save') {
                throw new RedirectException($returnUrl);
            } else if ($submitButtonValue == 'Save changes') {
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