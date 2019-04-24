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
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;

trait CmsTrait
{
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
    private function prepareParams()
    {
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

        $orms = DataGroup::active($pdo);
        foreach ($orms as $idx => $itm) {
            $id = $idx + 2;
            $nodes[] = new PageNode($id, null, $id, 1, $itm->getTitle(), '/manage/section/' . $itm->getId(), 'cms/files.html.twig', $itm->getIcon());
            $nodes[] = new PageNode($id . 0, $id, 0, 1, 'DATA');

            /** @var _Model[] $models */
            $models = _Model::active($pdo, array(
                'whereSql' => 'm.dataGroups LIKE ? AND m.dataType = 0',
                'params' => array('%"' . $itm->getId() . '"%'),
            ));
            $data = [];
            foreach ($models as $model) {
                $data[$model->getTitle()] = $model->getClassName();
            }
            $nodes = array_merge($nodes, static::appendModelsToParent($pdo, $id, $data, '/manage/orms/'));
        }

        $nodes[] = new PageNode(998, null, 998, 1, 'Assets', '/manage/files', 'cms/files.html.twig', 'cms_viewmode_asset');
        $nodes[] = new PageNode(999, null, 999, 1, 'Admin', '/manage/admin', 'cms/admin.html.twig', 'cms_viewmode_admin');

        $nodes[] = new PageNode(9991, 999, 1, 1, 'TOOLS');
        $nodes = array_merge($nodes, static::appendModelsToParent($pdo, 999, array(
            'Webpage Builder' => 'Page',
        ), '/manage/admin/orms/', 2));

//        $nodes[] = new PageNode(9992, 999, 2, 1, 'Webpage Builder', '/manage/admin/web-page-builder', 'cms/admin/pages.html.twig');
        $nodes = array_merge($nodes, static::appendModelsToParent($pdo, 9992, array(
            'Manage Templates' => 'PageTemplate',
            'Manage Categories' => 'PageCategory',
        ), '/manage/admin/orms/'));

        $nodes[] = new PageNode(9993, 999, 3, 1, 'Model Builder', '/manage/admin/model-builder', 'cms/admin/models.html.twig');
        $nodes[] = new PageNode(99931, 9993, 1, 2, 'Model', '/manage/admin/model-builder/', 'cms/admin/model.html.twig', null, 1, 1);
        $nodes[] = new PageNode(99932, 9993, 2, 2, 'Model', '/manage/admin/model-builder/copy/', 'cms/admin/model.html.twig', null, 1, 1);

//        $nodes[] = new PageNode(9994, 999, 4, 1, 'Form Descriptors', '/manage/admin/form-builder', 'cms/admin/forms.html.twig');

        $nodes = array_merge($nodes, static::appendModelsToParent($pdo, 999, array(
            'Image Sizes' => 'AssetSize',
        ), '/manage/admin/orms/', 5));

//        $nodes[] = new PageNode(9995, 999, 5, 1, 'Image Sizes', '/manage/admin/orms/AssetSize', AssetSize::getCmsOrmsTwig());
//        $nodes[] = new PageNode(99951, 9995, 1, 2, 'Image Size', '/manage/admin/orms/AssetSize/', AssetSize::getCmsOrmTwig(), null, 1, 1);

        $nodes[] = new PageNode(9996, 999, 6, 1, 'ADMIN');
        $nodes = array_merge($nodes, static::appendModelsToParent($pdo, 999, array(
            'Users' => 'User',
            'CMS Partitions' => 'DataGroup',
        ), '/manage/admin/orms/', 7));

//        var_dump($nodes);exit;
        return $nodes;
    }

    static public function appendModelsToParent($pdo, $parentId, $data = array(), $baseUrl, $start = 1)
    {
        $ormsListTwig = array(
            0 => 'cms/orms-dragdrop.html.twig',
            1 => 'cms/orms-pagination.html.twig',
            2 => 'cms/orms-tree.html.twig',
        );

        $ormDefaultTwig = 'cms/orm.html.twig';

        $nodes = array();
        $count = $start;
        foreach ($data as $idx => $itm) {
            /** @var _Model $model */
            $model = _Model::getByField($pdo, 'className', $itm);
            $fullClass = $model->getNamespace() . '\\' . $model->getClassName();

            $ormsTwig = $fullClass::getCmsOrmsTwig();
            if (!$ormsTwig) {
                $ormsTwig = $ormsListTwig[$model->getListType()];
            }
            $ormTwig = $fullClass::getCmsOrmTwig();
            if (!$ormTwig) {
                $ormTwig = $ormDefaultTwig;
            }

            $modelNodeId = $parentId . $count;
            $nodes[] = new PageNode($modelNodeId, $parentId, $count, 1, $idx, $baseUrl . $itm, $ormsTwig);
            $nodes[] = new PageNode($modelNodeId . 1, $modelNodeId, 1, 2, '', $baseUrl . $itm . '/', $ormTwig, null, 1, 1);
            $nodes[] = new PageNode($modelNodeId . 2, $modelNodeId, 2, 2, '', $baseUrl . $itm . '/copy/', $ormTwig, null, 1, 1);

            $count++;
        }
        return $nodes;
    }


}