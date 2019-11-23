<?php

namespace MillenniumFalcon\Controller;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Form\Builder\ModelForm;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Exception\RedirectException;
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

trait CmsModelTrait
{
    /**
     * @route("/manage/admin/model-builder/{modelId}")
     * @return Response
     */
    public function model($modelId)
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $model = _Model::getById($pdo, $modelId);
        if (!$model) {
            $model = new _Model($pdo);
        }

        return $this->_model($pdo, $model);
    }

    /**
     * @route("/manage/admin/model-builder/copy/{modelId}")
     * @return Response
     */
    public function copyModel($modelId)
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $model = _Model::getById($pdo, $modelId);
        if (!$model) {
            $model = new _Model($pdo);
        }

        $model->setId(null);
        return $this->_model($pdo, $model);
    }

    /**
     * @param $pdo
     * @param $model
     * @return mixed
     * @throws RedirectException
     */
    private function _model($pdo, $model) {
        $params = $this->prepareParams();
        $dataGroups = array();

        $fullClass = ModelService::fullClass($pdo, 'DataGroup');
        $result = $fullClass::active($pdo);
        foreach ($result as $itm) {
            $dataGroups[$itm->getTitle()] = $itm->getId();
        }

        $columns = array_keys(_Model::getFields());
        $form = $this->container->get('form.factory')->create(ModelForm::class, $model, array(
            'defaultSortByOptions' => array_combine($columns, $columns),
            'dataGroups' => $dataGroups,
        ));

        $request = Request::createFromGlobals();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($model->getModelType() == 0) {
                $model->setNamespace('App\\Orm');
            } else {
                $model->setNamespace('MillenniumFalcon\\Core\\Orm');
            }
            _Model::setGenereatedFile($model, $this->container);
            _Model::setCustomFile($model, $this->container);

            $fullClassname = $model->getNamespace() . '\\' . $model->getClassName();

            if (!$model->getId()) {
                $model->setRank(_Model::lastRank($pdo));
            }
            $model->save();

            $fullClassname::sync($pdo);

            $baseUrl = '/manage/admin/model-builder';
            if ($request->get('submit') == 'Apply') {
                throw new RedirectException($baseUrl . '/' . $model->getId(), 301);
            } else if ($request->get('submit') == 'Save') {
                throw new RedirectException($baseUrl, 301);
            }
        }

        $params['form'] = $form->createView();
        $params['ormModel'] = $model;
        return $this->render($params['node']->getTemplate(), $params);
    }
}