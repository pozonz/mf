<?php

namespace MillenniumFalcon\Controller;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Db;
use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Form\Builder\Orm;
use MillenniumFalcon\Core\Nestable\PageNode;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Redirect\RedirectException;
use MillenniumFalcon\Core\Router;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Twig\Extension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

trait CmsTrait
{
    /**
     * @route("/manage/login")
     */
    public function login(AuthenticationUtils $authenticationUtils)
    {
        $params = $this->prepareParams();

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render($params['node']->getTemplate(), array_merge($params, array(
            'last_username' => $lastUsername,
            'error' => $error,
        )));
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
    protected function getNodes()
    {
        /** @var \PDO $pdo */
        $pdo = $this->connection->getWrappedConnection();

        $nodes = [];
        $nodes[] = new PageNode(uniqid(), null, 0, 2, 'Login', '/manage/login', 'cms/login.html.twig');

        //Set up major nav
        $nodes[] = new PageNode(1, null, 0, 1, 'Pages', '/manage/pages', 'cms/pages.html.twig', 'cms_viewmode_cms');

        $fullClass = ModelService::fullClass($pdo, 'PageCategory');
        $categories = $fullClass::active($pdo);

        $count = 0;
        $fullClass = ModelService::fullClass($pdo, 'Page');
        $pages = $fullClass::active($pdo);
        foreach ($categories as $catIdx => $catItm) {
            $catId = uniqid();
            $nodes[] = new PageNode($catId, 1, $count + $catIdx + 1, 1, $catItm->getTitle());

            $pageRoot = Extension::nestablePges($pages, $catItm->getId());
            foreach ($pageRoot->getChildren() as $pageIdx => $pageItm) {
                $pageId = uniqid();
                $nodes[] = new PageNode($pageId, 1, $pageIdx + 2, 1, $pageItm->getTitle(), '/manage/orms/Page/' . $pageItm->getId(), 'cms/orms/orm-custom-page.html.twig');
                $count++;
            }
        }

//        $nodes[] = new PageNode(uniqid(), 1, 0, 1, 'Pages');

//        $fullClass = ModelService::fullClass($pdo, 'Page');
//        $orms = $fullClass::active($pdo);
//        $tree = new Tree($orms);
//        $root = $tree->getRoot();
//        var_dump($root);exit;
//        foreach ($root->getChildren() as $idx => $itm) {
//            /** @var _Model[] $models */
//            $models = _Model::active($pdo, array(
//                'whereSql' => 'm.dataGroups LIKE ? AND m.dataType = 0',
//                'params' => array('%"' . $itm->getId() . '"%'),
//            ));
//
//            $id = uniqid();
//            $nodes[] = new PageNode($id, 1, $idx + 1, 1, $itm->getTitle(), '/manage/orms/Page/' . $itm->getId(), 'cms/orms/orm-custom-page.html.twig');
//
////            $data = array();
////            $data['Data'] = null;
////            foreach ($models as $model) {
////                $data[$model->getTitle()] = array(
////                    'class' => $model->getClassName(),
////                    'children' => array(),
////                );
////            }
////            $nodes = array_merge($nodes, static::appendModelsToParent($pdo, $id, $data, '/manage/orms/'));
//        }

        //Set up custom partitions
        $fullClass = ModelService::fullClass($pdo, 'DataGroup');
        $orms = $fullClass::active($pdo);
        foreach ($orms as $idx => $itm) {
            /** @var _Model[] $models */
            $models = _Model::active($pdo, array(
                'whereSql' => 'm.dataGroups LIKE ? AND m.dataType = 0',
                'params' => array('%"' . $itm->getId() . '"%'),
            ));

            $id = uniqid();
            $nodes[] = new PageNode($id, null, $idx + 1, 1, $itm->getTitle(), '/manage/section/' . $itm->getId(), 'cms/admin.html.twig', $itm->getIcon());

            $data = array();
            $data['Data'] = null;
            foreach ($models as $model) {
                $data[$model->getTitle()] = array(
                    'class' => $model->getClassName(),
                    'children' => array(),
                );
            }
            $nodes = array_merge($nodes, static::appendModelsToParent($pdo, $id, $data, '/manage/orms/'));
        }

        //Set up assets
        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $nodes[] = new PageNode(998, null, 998, 1, 'Assets', '/manage/files', $fullClass::getCmsOrmsTwig(), 'cms_viewmode_asset');
        $nodes[] = new PageNode(9981, 998, 1, 2, 'Assets', '/manage/orms/Asset/', $fullClass::getCmsOrmTwig(), null, 1, 1);

        //Set up admin
        $nodes[] = new PageNode(999, null, 999, 1, 'Admin', '/manage/admin', 'cms/admin.html.twig', 'cms_viewmode_admin');
        $nodes = array_merge($nodes, static::appendModelsToParent($pdo, 999, array(
            'TOOLS' => null,
            'Webpage Builder' => array(
                'class' => 'Page',
                'children' => array(
                    'Manage Templates' => array(
                        'class' => 'PageTemplate',
                        'children' => array(),
                    ),
                    'Manage Categories' => array(
                        'class' => 'PageCategory',
                        'children' => array(),
                    ),
                ),
            ),
        ), '/manage/admin/orms/'));

        //Set up model builder in admin
        $nodes[] = new PageNode(9992, 999, 3, 1, 'Model Builder', '/manage/admin/model-builder', 'cms/models/models.html.twig');
        $nodes[] = new PageNode(99921, 9992, 1, 2, 'Model', '/manage/admin/model-builder/', 'cms/models/model.html.twig', null, 1, 1);
        $nodes[] = new PageNode(99922, 9992, 2, 2, 'Model', '/manage/admin/model-builder/copy/', 'cms/models/model.html.twig', null, 1, 1);
        $nodes = array_merge($nodes, static::appendModelsToParent($pdo, 9992, array(
            'Content Blocks' => array(
                'class' => 'FragmentBlock',
                'children' => array(),
            ),
            'Content Block Tags' => array(
                'class' => 'FragmentTag',
                'children' => array(),
            ),
            'Content Block Defaults' => array(
                'class' => 'FragmentDefault',
                'children' => array(),
            ),
        ), '/manage/admin/orms/', 10));

        //Set up rest in admin
        $nodes = array_merge($nodes, static::appendModelsToParent($pdo, 999, array(
            'Image Sizes' => array(
                'class' => 'AssetSize',
                'children' => array(),
            ),
            'Admin' => null,
            'Users' => array(
                'class' => 'User',
                'children' => array(),
            ),
            'Partitions' => array(
                'class' => 'DataGroup',
                'children' => array(),
            ),
        ), '/manage/admin/orms/', 10));
        return $nodes;
    }

    /**
     * @param $pdo
     * @param $parentId
     * @param array $data
     * @param $baseUrl
     * @param int $start
     * @return array
     */
    static public function appendModelsToParent($pdo, $parentId, $data = array(), $baseUrl, $start = 1)
    {
        $ormsListTwig = array(
            0 => 'cms/orms/orms-dragdrop.html.twig',
            1 => 'cms/orms/orms-pagination.html.twig',
            2 => 'cms/orms/orms-tree.html.twig',
        );

        $ormDefaultTwig = 'cms/orms/orm.html.twig';

        $nodes = array();
        $count = $start;
        foreach ($data as $idx => $itm) {
            if ($itm === null) {
                $nodes[] = new PageNode(uniqid(), $parentId, $count, 1, $idx);
            } else {
                $className = $itm['class'];
                $children = $itm['children'];

                $fullClass = ModelService::fullClass($pdo, $className);
                $model = _Model::getByField($pdo, 'className', $className);
                $ormsTwig = $fullClass::getCmsOrmsTwig();
                if (!$ormsTwig) {
                    $ormsTwig = $ormsListTwig[$model->getListType()];
                }
                $ormTwig = $fullClass::getCmsOrmTwig();
                if (!$ormTwig) {
                    $ormTwig = $ormDefaultTwig;
                }

                $modelNodeId = uniqid();
                $nodes[] = new PageNode($modelNodeId, $parentId, $count, 1, $idx, $baseUrl . $className, $ormsTwig);
                $nodes[] = new PageNode(uniqid(), $modelNodeId, 1, 2, '', $baseUrl . $className . '/', $ormTwig, null, 1, 1);
                $nodes[] = new PageNode(uniqid(), $modelNodeId, 2, 2, '', $baseUrl . $className . '/copy/', $ormTwig, null, 1, 1);

                if (count($children)) {
                    $nodes = array_merge($nodes, static::appendModelsToParent($pdo, $modelNodeId, $children, $baseUrl, 3));
                }

            }
            $count++;
        }
        return $nodes;
    }
}