<?php

namespace MillenniumFalcon\Controller;

use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Nestable\PageNode;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Orm\DataGroup;
use MillenniumFalcon\Core\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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

        $orm = _Model::getById($pdo, $modelId);


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
//            if ($model->getModelType() == 0) {
//                $model->setNamespace('Web\\Orm');
//            } else {
//                $model->setNamespace('Pz\\Orm');
//            }
//            $this->setGenereatedFile($model);
//            $this->setCustomFile($model);
//            $model->save();
//
//            $model->setRank($model->getId() - 1);
//            $model->save();
//
//            $baseUrl = "/pz/admin/models/" . ($model->getModelType() == 0 ? 'customised' : 'built-in');
//            $redirectUrl = "$baseUrl/sync/{$model->getId()}?returnUrl=";
//            if ($request->get('submit') == 'Apply') {
//                $url = $request->getPathInfo();
//                $url = rtrim($url, '/');
//                if (count(explode('/', $url)) < 7) {
//                    $url .= '/' . $model->getId();
//                }
//                throw new RedirectException($redirectUrl . urlencode($url), 301);
//            } else if ($request->get('submit') == 'Save') {
//                throw new RedirectException($redirectUrl . urlencode($baseUrl), 301);
//            }
        }

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
}