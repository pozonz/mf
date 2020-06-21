<?php

namespace MillenniumFalcon\Core\Controller\Traits;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Form\Builder\ModelForm;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Exception\RedirectException;
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

trait CmsCoreModelTrait
{
    /**
     * @route("/manage/admin/model-builder/{modelId}")
     * @return Response
     */
    public function model(Request $request, $modelId)
    {
        $model = _Model::getById($this->connection, $modelId);
        if (!$model) {
            $model = new _Model($this->connection);
        }

        return $this->_model($request, $model);
    }

    /**
     * @route("/manage/admin/model-builder/copy/{modelId}")
     * @return Response
     */
    public function copyModel(Request $request, $modelId)
    {
        $model = _Model::getById($this->connection, $modelId);
        if (!$model) {
            $model = new _Model($this->connection);
        }

        $model->setId(null);
        return $this->_model($request, $model);
    }

    /**
     * @param Request $request
     * @param $model
     * @return mixed
     * @throws RedirectException
     */
    private function _model(Request $request, $model) {
        $params = $this->getCmsTemplateParams($request);
        $dataGroups = array();

        $fullClass = ModelService::fullClass($this->connection, 'DataGroup');
        $result = $fullClass::active($this->connection);
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
                $model->setNamespace('App\\ORM');
            } else {
                $model->setNamespace('MillenniumFalcon\\Core\\ORM');
            }
            _Model::setGenereatedFile($model, $this->container);
            _Model::setCustomFile($model, $this->container);

            $fullClassname = $model->getNamespace() . '\\' . $model->getClassName();

            if (!$model->getId()) {
                $model->setRank(_Model::lastRank($this->connection));
            }
            $model->save();

            $fullClassname::sync($this->connection);

            $baseUrl = '/manage/admin/model-builder';
            if ($request->get('submit') == 'Apply') {
                throw new RedirectException($baseUrl . '/' . $model->getId(), 301);
            } else if ($request->get('submit') == 'Save') {
                throw new RedirectException($baseUrl, 301);
            }
        }

        $params['form'] = $form->createView();
        $params['ormModel'] = $model;

        return $this->render($params['theNode']->template, $params);
    }
}