<?php

namespace MillenniumFalcon\Core\Controller\Traits\Cms\Core;

use MillenniumFalcon\Core\Form\Builder\OrmForm;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Exception\RedirectException;
use MillenniumFalcon\Core\Service\AssetService;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\UtilsService;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

trait CmsCoreOrmTrait
{
    /**
     * @route("/manage/pages/orms/Page/{ormId}")
     * @route("/manage/pages/orms/Page/{ormId}/version/{versionUuid}")
     * @return Response
     */
    public function page(Request $request, $ormId, $versionUuid = null)
    {
        $className = 'Page';
        $orm = $this->_orm($request, $className, $ormId);
        if ($versionUuid) {
            $orm = $orm->getByVersionUuid($versionUuid);
        }
        return $this->_ormPageWithForm($request, $className, $orm, 'OrmPageForm', function () use ($orm) {
            throw new RedirectException('/manage/pages/orms/Page/' . $orm->getId());
        });
    }

    /**
     * @route("/manage/orms/Asset/{ormId}")
     * @route("/manage/admin/orms/Asset/{ormId}")
     * @route("/manage/pages/orms/Asset/{ormId}")
     * @return Response
     */
    public function asset(Request $request, $ormId)
    {
        $className = 'Asset';

        $orm = $this->_orm($request, $className, $ormId);
        return $this->_ormPageWithForm($request, $className, $orm, 'OrmAssetForm', function(Form $form, $orm) {
            $uploadedFile = $form['file']->getData();
            if ($uploadedFile) {
                AssetService::processUploadedFileWithAsset($this->connection, $uploadedFile, $orm);
            }
        });
    }

    /**
     * @route("/manage/current-user")
     * @return Response
     */
    public function user(Request $request)
    {
        if ($request->get('fragment') == 1 && $_SERVER['APP_ENV'] == 'dev') {
            $this->container->get('profiler')->disable();
        }

        $cmsUser = UtilsService::getUser($this->container);;

        $className = 'User';
        $ormId = $cmsUser->getId();
        $orm = $this->_orm($request, $className, $ormId);
        return $this->_ormPageWithForm($request, $className, $orm, 'OrmForm', function() {
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
    public function orm(Request $request, $className, $ormId, $versionUuid = null)
    {
        if ($request->get('fragment') == 1 && $_SERVER['APP_ENV'] == 'dev') {
            $this->container->get('profiler')->disable();
        }

        $orm = $this->_orm($request, $className, $ormId);
        if ($versionUuid) {
            $orm = $orm->getByVersionUuid($versionUuid);
        }
        return $this->_ormPageWithForm($request, $className, $orm);
    }

    /**
     * @route("/manage/orms/{className}/copy/{ormId}")
     * @route("/manage/admin/orms/{className}/copy/{ormId}")
     * @route("/manage/pages/orms/{className}/copy/{ormId}")
     * @return Response
     */
    public function copyOrm(Request $request, $className, $ormId)
    {
        $orm = $this->_orm($request, $className, $ormId);
        $orm->setUniqid(uniqid());
        $orm->setId(null);
        return $this->_ormPageWithForm($request, $className, $orm);
    }

    /**
     * @param Request $request
     * @param $className
     * @param $ormId
     * @return mixed
     * @throws \Exception
     */
    protected function _orm(Request $request, $className, $ormId)
    {
        $fullClass = ModelService::fullClass($this->connection, $className);
        $orm = $fullClass::getById($this->connection, $ormId);
        if (!$orm) {
            $orm = new $fullClass($this->connection);

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
     * @param Request $request
     * @param $className
     * @param $orm
     * @param string $formClass
     * @param null $callback
     * @return mixed
     * @throws RedirectException
     */
    protected function _ormPageWithForm(Request $request, $className, $orm, $formClass = 'OrmForm', $callback = null)
    {
        $model = _Model::getByField($this->connection, 'className', $className);
        $params = $this->getCmsTemplateParams($request);

        $returnUrl = $request->get('returnUrl') ?: null;

        $fullFormClass = "MillenniumFalcon\\Core\\Form\\Builder\\{$formClass}";
        $form = $this->container->get('form.factory')->create($fullFormClass, $orm, array(
            'model' => $model,
            'orm' => $orm,
            'pdo' => $this->connection,
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
            $this->_convertDateValue($orm, $model);

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

            $baseUrl = str_replace('copy/', '', $params['theNode']->getUrl());
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
        return $this->render($params['theNode']->template, $params);
    }

    /**
     * @param $orm
     * @param $model
     */
    private function _convertDateValue($orm, $model, $formats = [
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