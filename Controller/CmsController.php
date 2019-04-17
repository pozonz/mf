<?php

namespace MillenniumFalcon\Controller;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Db;
use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Nestable\PageNode;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Orm\DataGroup;
use MillenniumFalcon\Core\Redirect\RedirectException;
use MillenniumFalcon\Core\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CmsController extends Router
{
    /**
     * @route("/manage/admin/model-builder/{ormId}")
     * @return Response
     */
    public function model($ormId = null)
    {
        $params = $this->prepareParams();
        
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $orm = _Model::getById($pdo, $ormId);
        if (!$orm) {
            $orm = new _Model($pdo);
        }

        $dataGroups = array();
        /** @var DataGroup[] $result */
        $result = DataGroup::active($pdo);
        foreach ($result as $itm) {
            $dataGroups[$itm->getTitle()] = $itm->getId();
        }

        $columns = array_keys(_Model::getFields());
        $form = $this->container->get('form.factory')->create(Model::class, $orm, array(
            'defaultSortByOptions' => array_combine($columns, $columns),
            'dataGroups' => $dataGroups,
        ));

        $request = Request::createFromGlobals();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($orm->getModelType() == 0) {
                $orm->setNamespace('Web\\Orm');
            } else {
                $orm->setNamespace('MillenniumFalcon\\Core\\Orm');
            }
            $this->setGenereatedFile($orm);
            $this->setCustomFile($orm);
            $orm->save();

            $fullClassname = $orm->getNamespace() . '\\' . $orm->getClassName();
            $fullClassname::sync($pdo);

//            $orm->setRank($orm->getId() - 1);
//            $orm->save();

            $baseUrl = '/manage/admin/model-builder';
            if ($request->get('submit') == 'Apply') {
                throw new RedirectException($baseUrl . '/' . $orm->getId(), 301);
            } else if ($request->get('submit') == 'Save') {
                throw new RedirectException($baseUrl, 301);
            }
        }

        $params['orm'] = $orm;
        $params['form'] = $form->createView();
        return $this->render($params['node']->getTemplate(), $params);
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
        $nodes = [];
        $nodes[] = new PageNode(1, null, 0, 1, 'Pages', '/manage/pages', 'cms/pages.html.twig', 'cms_viewmode_cms');
        $nodes[] = new PageNode(2, null, 1, 1, 'Modules', '/manage/modules', 'cms/modules.html.twig', 'cms_viewmode_sitecodetables');
        $nodes[] = new PageNode(3, null, 2, 1, 'Assets', '/manage/files', 'cms/files.html.twig', 'cms_viewmode_asset');
        $nodes[] = new PageNode(999, null, 999, 1, 'Admin', '/manage/admin', 'cms/admin.html.twig', 'cms_viewmode_admin');

        $nodes[] = new PageNode(9991, 999, 1, 1, 'TOOLS');
        $nodes[] = new PageNode(9992, 999, 2, 1, 'Web page builder', '/manage/admin/web-page-builder', 'cms/admin/pages.html.twig');
        $nodes[] = new PageNode(9993, 999, 3, 1, 'Model builder', '/manage/admin/model-builder', 'cms/admin/models.html.twig');
        $nodes[] = new PageNode(9994, 999, 4, 1, 'Form builder', '/manage/admin/form-builder', 'cms/admin/forms.html.twig');
        $nodes[] = new PageNode(9995, 999, 5, 1, 'Image descriptors', '/manage/admin/image-descriptors', 'cms/admin/image-descriptors.html.twig');

        $nodes[] = new PageNode(9996, 999, 6, 1, 'ADMIN');
        $nodes[] = new PageNode(9997, 999, 7, 1, 'Users', '/manage/admin/users', 'cms/admin/users.html.twig');
        $nodes[] = new PageNode(9998, 999, 8, 1, 'CMS sections', '/manage/admin/cms-sections', 'cms/admin/cms-sections.html.twig');

        $nodes[] = new PageNode(99921, 9992, 1, 1, 'Manage templates', '/manage/admin/web-page-builder/templates', 'cms/admin/web-page-builder/templates.html.twig');
        $nodes[] = new PageNode(99922, 9992, 2, 1, 'Manage categories', '/manage/admin/web-page-builder/categories', 'cms/admin/web-page-builder/categories.html.twig');

        $nodes[] = new PageNode(99931, 9993, 1, 1, 'Model', '/manage/admin/model-builder/', 'cms/admin/model.html.twig', null, 1, 1);

        return $nodes;
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

        $path = $this->container->getParameter('kernel.project_dir') . ($orm->getModelType() == 0 ? '/src/Web/Orm' : '/vendor/pozoltd/millennium-falcon/Core/Orm') . '/Generated/';

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
        $path = $this->container->getParameter('kernel.project_dir') . ($orm->getModelType() == 0 ? '/src/Web/Orm' : '/vendor/pozoltd/millennium-falcon/Core/Orm') . '/';

        if ($orm->getModelType() == 1) {
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
        }

        $file = $path . $orm->getClassName() . '.php';
        if (!file_exists($file)) {
            $custom_file = $orm->getModelType() == 1 ? 'orm_custom_pz.txt' : 'orm_custom.txt';
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