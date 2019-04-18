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

class CmsController extends Router
{
    /**
     * @route("/manage/admin/model-builder/{modelId}")
     * @return Response
     */
    public function model($modelId)
    {
        $params = $this->prepareParams();

        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $model = _Model::getById($pdo, $modelId);
        if (!$model) {
            $model = new _Model($pdo);
        }

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
        $params = $this->prepareParams();

        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $request = Request::createFromGlobals();
        $pageNum = $request->get('pageNum');

        $fragments = $params['fragments'];
        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', end($fragments));
        $fullClassname = $model->getNamespace() . '\\' . $model->getClassName();
        $orms = $fullClassname::data($pdo, array(
            "page" => $pageNum,
            "limit" => $model->getNumberPerPage(),
            "sort" => $model->getDefaultSortBy(),
            "order" => $model->getDefaultOrder() == 0 ? 'ASC' : 'DESC',
        ));

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
        $params = $this->prepareParams();

        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $fragments = $params['fragments'];
        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', $fragments[count($fragments) - 2]);
        $fullClassname = $model->getNamespace() . '\\' . $model->getClassName();
        $orm = $fullClassname::getById($pdo, $ormId);
        if (!$orm) {
            $orm = new $fullClassname($pdo);
        }

        $request = Request::createFromGlobals();
        $returnUrl = $request->get('returnUrl') ?: '/manage/orms/' . $model->getClassName();

        $form = $this->container->get('form.factory')->create(Orm::class, $orm, array(
            'model' => $model,
            'orm' => $orm,
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

    /**
     * @route("/manage/{page}", requirements={"page" = ".*"})
     * @return Response
     */
    public function index()
    {
        $params = $this->prepareParams();
        return $this->render($params['node']->getTemplate(), $params);
    }

    /**
     * @return array|object
     */
    private function prepareParams() {
        $dir = $this->container->getParameter('kernel.project_dir') . '/vendor/pozoltd/millennium-falcon/Resources/views';
        $loader = $this->container->get('twig')->getLoader();
        $loader->addPath($dir);

        $request = Request::createFromGlobals();
        $requestUri = rtrim($request->getPathInfo(), '/');
        return $this->getParams($requestUri);
    }

    /**
     * @return array
     */
    public function getNodes()
    {
        /** @var \PDO $pdo */
        $pdo = $this->connection->getWrappedConnection();

        $nodes = [];
        $nodes[] = new PageNode(1, null, 0, 1, 'Pages', '/manage/pages', 'cms/pages.html.twig', 'cms_viewmode_cms');
//        $nodes[] = new PageNode(2, null, 1, 1, 'Modules', '/manage/modules', 'cms/modules.html.twig', 'cms_viewmode_sitecodetables');

        $orms = DataGroup::active($pdo);
        foreach ($orms as $idx => $itm) {
            $id = $idx + 2;
            $nodes[] = new PageNode($id, null, $id, 1, $itm->getTitle(), '/manage/section/' . $itm->getId(), 'cms/files.html.twig', $itm->getIcon());

            /** @var _Model[] $models */
            $models = _Model::active($pdo, array(
                'whereSql' => 'm.dataGroups LIKE ? AND m.dataType = 0',
                'params' => array('%"' . $itm->getId() . '"%'),
            ));

            $nodes[] = new PageNode($id . 0, $id, 1, 1, 'Modules');
            foreach ($models as $modelIdx => $model) {
                $modelId = $id . $model->getId();
                $nodes[] = new PageNode($modelId, $id, $modelIdx + 2, 1, $model->getTitle(), "/manage/orms/" . $model->getClassName(), 'cms/orms.html.twig');
                $nodes[] = new PageNode($modelId . 1, $modelId, 1, 0, $model->getTitle(), "/manage/orms/" . $model->getClassName() . '/', 'cms/orm.html.twig', null, 1, 1);
            }
        }

        $nodes[] = new PageNode(998, null, 998, 1, 'Assets', '/manage/files', 'cms/files.html.twig', 'cms_viewmode_asset');
        $nodes[] = new PageNode(999, null, 999, 1, 'Admin', '/manage/admin', 'cms/admin.html.twig', 'cms_viewmode_admin');

        $nodes[] = new PageNode(9991, 999, 1, 1, 'TOOLS');

        $nodes[] = new PageNode(9992, 999, 2, 1, 'Webpage Builder', '/manage/admin/web-page-builder', 'cms/admin/pages.html.twig');
        $nodes[] = new PageNode(99921, 9992, 1, 1, 'Manage Templates', '/manage/admin/orms/PageTemplate', PageTemplate::getCmsOrmsTwig());
        $nodes[] = new PageNode(999211, 99921, 1, 2, 'Manage Template', '/manage/admin/orms/PageTemplate/', PageTemplate::getCmsOrmTwig(), null, 1, 1);
        $nodes[] = new PageNode(99922, 9992, 2, 1, 'Manage Categories', '/manage/admin/orms/PageCategory', PageCategory::getCmsOrmsTwig());
        $nodes[] = new PageNode(999221, 99922, 1, 2, 'Manage Category', '/manage/admin/orms/PageCategory/', PageCategory::getCmsOrmTwig(), null, 1, 1);


        $nodes[] = new PageNode(9993, 999, 3, 1, 'Model Builder', '/manage/admin/model-builder', 'cms/admin/models.html.twig');
        $nodes[] = new PageNode(99931, 9993, 1, 2, 'Model', '/manage/admin/model-builder/', 'cms/admin/model.html.twig', null, 1, 1);

        $nodes[] = new PageNode(9994, 999, 4, 1, 'Form Descriptors', '/manage/admin/form-builder', 'cms/admin/forms.html.twig');

        $nodes[] = new PageNode(9995, 999, 5, 1, 'Image Sizes', '/manage/admin/orms/AssetSize', AssetSize::getCmsOrmsTwig());
        $nodes[] = new PageNode(99951, 9995, 1, 2, 'Image Size', '/manage/admin/orms/AssetSize/', AssetSize::getCmsOrmTwig(), null, 1, 1);

        $nodes[] = new PageNode(9996, 999, 6, 1, 'ADMIN');
        $nodes[] = new PageNode(9997, 999, 7, 1, 'Users', '/manage/admin/orms/User', User::getCmsOrmsTwig());
        $nodes[] = new PageNode(99971, 9997, 1, 2, 'User', '/manage/admin/orms/User/', User::getCmsOrmTwig(), null, 1, 1);
        $nodes[] = new PageNode(9998, 999, 8, 1, 'CMS Sections', '/manage/admin/orms/DataGroup', DataGroup::getCmsOrmsTwig());
        $nodes[] = new PageNode(99981, 9998, 1, 2, 'CMS Section', '/manage/admin/orms/DataGroup/', DataGroup::getCmsOrmTwig(), null, 1, 1);

        return $nodes;
    }
}