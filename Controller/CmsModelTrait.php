<?php

namespace MillenniumFalcon\Controller;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Db;
use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Form\Builder\Orm;
use MillenniumFalcon\Core\Nestable\PageNode;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Orm\AssetSize;
use MillenniumFalcon\Core\Orm\DataGroup;
use MillenniumFalcon\Core\Orm\PageCategory;
use MillenniumFalcon\Core\Orm\PageTemplate;
use MillenniumFalcon\Core\Orm\User;
use MillenniumFalcon\Core\Redirect\RedirectException;
use MillenniumFalcon\Core\Router;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;

trait CmsModelTrait
{
    /**
     * @route("/manage/admin/model-builder/{modelId}")
     * @return Response
     */
    public function model($modelId)
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

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
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

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
        /** @var DataGroup[] $result */
        $result = DataGroup::active($pdo);
        foreach ($result as $itm) {
            $dataGroups[$itm->getTitle()] = $itm->getId();
        }

        $columns = array_keys(_Model::getFields());
        $form = $this->container->get('form.factory')->create(Model::class, $model, array(
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
            $this->setGenereatedFile($model);
            $this->setCustomFile($model);

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

    /**
     * @param _Model $orm
     */
    private function setGenereatedFile(_Model $orm)
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        $pdo = $connection->getWrappedConnection();

        $myClass = get_class($orm);
        $fieldChoices = $myClass::getFieldChoices();
        $columnsJson = json_decode($orm->getColumnsJson());
        $fields = array_map(function ($value) use ($fieldChoices) {
            $fieldChoice = $fieldChoices[$value->column];
            return <<<EOD
    /**
     * #pz {$fieldChoice}
     */
    private \${$value->field};
    
EOD;
        }, $columnsJson);

        $methods = array_map(function ($value) {
            $ucfirst = ucfirst($value->field);
            return <<<EOD
    /**
     * @return mixed
     */
    public function get{$ucfirst}()
    {
        return \$this->{$value->field};
    }
    
    /**
     * @param mixed {$value->field}
     */
    public function set{$ucfirst}(\${$value->field})
    {
        \$this->{$value->field} = \${$value->field};
    }
    
EOD;
        }, $columnsJson);

        $generated_file = $orm->getListType() == 2 ? 'orm_generated_node.txt' : 'orm_generated.txt';
        $str = file_get_contents($this->container->getParameter('kernel.project_dir') . '/vendor/pozoltd/millennium-falcon/Resources/files/' . $generated_file);
        $str = str_replace('{time}', date('Y-m-d H:i:s'), $str);
        $str = str_replace('{namespace}', $orm->getNamespace() . '\\Generated', $str);
        $str = str_replace('{classname}', $orm->getClassName(), $str);
        $str = str_replace('{fields}', join("\n", $fields), $str);
        $str = str_replace('{methods}', join("\n", $methods), $str);

        $path = $this->container->getParameter('kernel.project_dir') . ($orm->getModelType() == 0 ? '/src/Orm' : '/vendor/pozoltd/millennium-falcon/Core/Orm') . '/Generated/';

        $file = $path . '../CmsConfig/' . $orm->getClassName() . '.json';
        $dir = dirname($file);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($file, _Model::encodedModel($orm));

        $file = $path . $orm->getClassName() . '.php';
        $dir = dirname($file);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($file, $str);
    }

    /**
     * @param _Model $orm
     */
    private function setCustomFile(_Model $orm)
    {
        $path = $this->container->getParameter('kernel.project_dir') . ($orm->getModelType() == 0 ? '/src/Orm' : '/vendor/pozoltd/millennium-falcon/Core/Orm') . '/';

        $file = $path . 'Traits/' . $orm->getClassName() . 'Trait.php';
        if (!file_exists($file)) {
            $str = file_get_contents($this->container->getParameter('kernel.project_dir') . '/vendor/pozoltd/millennium-falcon/Resources/files/orm_custom_trait.txt');
            $str = str_replace('{time}', date('Y-m-d H:i:s'), $str);
            $str = str_replace('{namespace}', $orm->getNamespace(), $str);
            $str = str_replace('{classname}', $orm->getClassName(), $str);

            $dir = dirname($file);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($file, $str);
        }

        $file = $path . $orm->getClassName() . '.php';
        if (!file_exists($file)) {
            $custom_file = 'orm_custom.txt';
            $str = file_get_contents($this->container->getParameter('kernel.project_dir') . '/vendor/pozoltd/millennium-falcon/Resources/files/' . $custom_file);
            $str = str_replace('{time}', date('Y-m-d H:i:s'), $str);
            $str = str_replace('{namespace}', $orm->getNamespace(), $str);
            $str = str_replace('{classname}', $orm->getClassName(), $str);

            $dir = dirname($file);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($file, $str);
        }
    }
}