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
        $nodes[] = new PageNode(9998, 999, 8, 1, 'CMS Partitions', '/manage/admin/orms/DataGroup', DataGroup::getCmsOrmsTwig());
        $nodes[] = new PageNode(99981, 9998, 1, 2, 'CMS Partition', '/manage/admin/orms/DataGroup/', DataGroup::getCmsOrmTwig(), null, 1, 1);

        return $nodes;
    }
}