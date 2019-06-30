<?php

namespace MillenniumFalcon\Controller;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Db;
use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Form\Builder\Orm;
use MillenniumFalcon\Core\Nestable\PageNode;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Orm\DataGroup;
use MillenniumFalcon\Core\Orm\User;
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
        $params = $this->getParams($requestUri);

        //Check permission
        $params['verticalMenuRoot'] = $params['node']->getTopAncestor($params['root']);
        if (!$params['verticalMenuRoot']) {
            throw new NotFoundHttpException();
        }
        $params['verticalMenuItems'] = $params['verticalMenuRoot']->getChildren();
        return $params;
    }

    /**
     * @return array
     */
    protected function getNodes()
    {
        /** @var User $cmsUser */
        $cmsUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $accessibleSections = $cmsUser->objAccessibleSections();

        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $nodes = [];
        $nodes[] = new PageNode(uniqid(), null, 0, 2, 'Login', '/manage/login', 'cms/login.html.twig');

        /** @var DataGroup[] $dataGroups */
        $dataGroups = DataGroup::active($pdo);
        foreach ($dataGroups as $dataGroupIdx => $dataGroup) {
            if (!in_array($dataGroup->getId(), $accessibleSections)) {
                continue;
            }

            $dataGroupNodeId = uniqid();
            $nodes[] = new PageNode(
                $dataGroup->getBuiltInSectionCode() ?: $dataGroupNodeId,
                null,
                $dataGroupIdx,
                1,
                $dataGroup->getTitle(),
                $dataGroup->getBuiltInSection() == 1 ? '/manage/' . $dataGroup->getBuiltInSectionCode() : '/manage/section/' . $dataGroup->getId(),
                $dataGroup->getBuiltInSection() == 1 ?  $dataGroup->getBuiltInSectionTemplate() : 'cms/admin.html.twig',
                $dataGroup->getIcon()
            );

            if ($dataGroup->getBuiltInSection() != 1) {
                /** @var _Model[] $models */
                $models = _Model::active($pdo, array(
                    'whereSql' => 'm.dataGroups LIKE ? AND m.dataType = 0',
                    'params' => array('%"' . $dataGroup->getId() . '"%'),
                ));
                if (count($models)) {
                    $data = array();
                    $data['Data'] = null;
                    foreach ($models as $model) {
                        $data[$model->getTitle()] = array(
                            'class' => $model->getClassName(),
                            'children' => array(),
                        );
                    }
                    $nodes = array_merge($nodes, static::appendModelsToParent($pdo, $dataGroupNodeId, $data, '/manage/orms/'));
                }
            }
        }

        $fullClass = ModelService::fullClass($pdo, 'PageCategory');
        $categories = $fullClass::active($pdo);

        $count = 0;
        $fullClass = ModelService::fullClass($pdo, 'Page');
        $pages = $fullClass::active($pdo);
        foreach ($categories as $catIdx => $catItm) {
            $catId = uniqid();
            $nodes[] = new PageNode($catId, 'pages', $count + $catIdx, 1, $catItm->getTitle());

            $pageRoot = Extension::nestablePges($pages, $catItm->getId());
            static::appendPagesToParent($pdo, $pageRoot, 'pages', $nodes, $count);
            $count += count($pageRoot->getChildren());
        }

        //Admin: set up page builder
        $nodes = array_merge($nodes, static::appendModelsToParent($pdo, 'admin', array(
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

        //Admin: set up model builder
        $nodes[] = new PageNode(9992, 'admin', 3, 1, 'Model Builder', '/manage/admin/model-builder', 'cms/models/models.html.twig');
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

        //Admin: set up the rest
        $nodes = array_merge($nodes, static::appendModelsToParent($pdo, 'admin', array(
            'Image Sizes' => array(
                'class' => 'AssetSize',
                'children' => array(),
            ),
            'Admin' => null,
            'Users' => array(
                'class' => 'User',
                'children' => array(),
            ),
            'Sections' => array(
                'class' => 'DataGroup',
                'children' => array(),
            ),
        ), '/manage/admin/orms/', 20));

        /** @var _Model[] $models */
        $models = _Model::active($pdo, array(
            'whereSql' => 'm.dataType = 1',
            'params' => array(),
        ));

        if (count($models)) {
            $data = array();
            $data['Data'] = null;
            foreach ($models as $model) {
                $data[$model->getTitle()] = array(
                    'class' => $model->getClassName(),
                    'children' => array(),
                );
            }
            $nodes = array_merge($nodes, static::appendModelsToParent($pdo, 'admin', $data, '/manage/orms/', 30));
        }

        return $nodes;

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

                foreach ($pageItm->getChildren() as $subpageIdx => $subpageItm) {
                    $subpageId = uniqid();
                    $nodes[] = new PageNode($subpageId, $pageId, $subpageIdx + 1, 1, $subpageItm->getTitle(), '/manage/orms/Page/' . $subpageItm->getId(), 'cms/orms/orm-custom-page.html.twig');
                    $nodes[] = new PageNode(uniqid(), $subpageId, $subpageIdx + 2, 1, $subpageItm->getTitle(), '/manage/orms/Page/' . $subpageItm->getId(), 'cms/orms/orm-custom-page.html.twig');
                    $count++;
                }
            }
        }

    }

    /**
     * @param $pdo
     * @param $pageNode
     * @param $parentId
     * @param $nodes
     * @param int $count
     */
    static public function appendPagesToParent($pdo, $pageNode, $parentId, &$nodes, $count = 0) {
        if (method_exists($pageNode, 'getHideFromCMSNav')) {
            if ($pageNode->getHideFromCMSNav() == 1) {
                return;
            }
            $pageId = uniqid();
            $nodes[] = new PageNode($pageId, $parentId, $count + 1, 1, $pageNode->getTitle(), '/manage/pages/orms/Page/' . $pageNode->getId(), 'cms/orms/orm-custom-page.html.twig');
        } else {
            $pageId = $parentId;
        }

        foreach ($pageNode->getChildren() as $pageIdx => $pageItm) {
            static::appendPagesToParent($pdo, $pageItm, $pageId, $nodes, $count + $pageIdx);
        }

        if (method_exists($pageNode, 'getAttachedModels')) {
            if ($pageNode->getAttachedModels()) {
                $attachedModels = json_decode($pageNode->getAttachedModels());
                if (count($attachedModels)) {
                    $models = array_map(function ($itm) use ($pdo) {
                        return _Model::getById($pdo, $itm);
                    }, $attachedModels);
                    $data = array();
                    foreach ($models as $model) {
                        $data[$model->getTitle()] = array(
                            'class' => $model->getClassName(),
                            'children' => array(),
                        );
                    }
                    $nodes = array_merge($nodes, static::appendModelsToParent($pdo, $pageId, $data, '/manage/pages/orms/', 30));
                }
            }
        }

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