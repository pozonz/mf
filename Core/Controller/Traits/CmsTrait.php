<?php

namespace MillenniumFalcon\Core\Controller\Traits;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Db;
use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Form\Builder\Orm;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Exception\RedirectException;
use MillenniumFalcon\Core\Router;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\UtilsService;
use MillenniumFalcon\Core\Twig\Extension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
     * @route("/manage/after_login")
     */
    public function afterLogin(AuthenticationUtils $authenticationUtils)
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');
        $fullClass = ModelService::fullClass($pdo, 'DataGroup');
        $orm = $fullClass::active($pdo, [
            'limit' => 1,
            'oneOrNull' => 1,
        ]);
        if (!$orm) {
            return new RedirectResponse('/manage/pages');
        }
        return new RedirectResponse($orm->getBuiltInSection() ? "/manage/{$orm->getBuiltInSectionCode()}" : "/manage/section/{$orm->getId()}");
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
    protected function prepareParams()
    {
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
     * @throws \Exception
     */
    public function getNodes()
    {
//        $cmsUser = UtilsService::getUser($this->container);;
//        if (!$cmsUser || gettype($cmsUser) == 'string') {
//            $accessibleSections = [];
//        } else {
//            $accessibleSections = $cmsUser->objAccessibleSections();
//        }
//
//        $pdo = $this->container->get('doctrine.dbal.default_connection');
//
//        /** @var _Model[] $result */
//        $result = _Model::active($pdo);
//        /** @var _Model[] $modelMapData */
//        $modelMapData = [];
//        foreach ($result as $itm) {
//            $modelMapData[$itm->getClassName()] = $itm;
//        }
//
//        $nodes = [];
//        $nodes[] = new PageNode(uniqid(), null, 0, 2, 'Login', '/manage/login', 'cms/login.html.twig');
//
//        $fullClass = ModelService::fullClass($pdo, 'DataGroup');
//        $dataGroups = $fullClass::active($pdo);
//        foreach ($dataGroups as $dataGroupIdx => $dataGroup) {
//            if (!in_array($dataGroup->getId(), $accessibleSections)) {
//                continue;
//            }
//
//            $dataGroupNodeId = uniqid();
//            $nodes[] = new PageNode(
//                $dataGroup->getBuiltInSectionCode() ?: $dataGroupNodeId,
//                null,
//                $dataGroupIdx,
//                1,
//                $dataGroup->getTitle(),
//                $dataGroup->getBuiltInSection() == 1 ? '/manage/' . $dataGroup->getBuiltInSectionCode() : '/manage/section/' . $dataGroup->getId(),
//                $dataGroup->getBuiltInSection() == 1 ?  $dataGroup->getBuiltInSectionTemplate() : 'cms/admin.html.twig',
//                $dataGroup->getIcon()
//            );
//
//            if ($dataGroup->getLoadFromConfig()) {
//                $data = json_decode($dataGroup->getConfig());
//                $nodes = array_merge($nodes, static::appendModelsToParent($pdo, $dataGroupNodeId, $data, '/manage/orms/'));
//            } else {
//                if ($dataGroup->getTitle() == 'Files') {
//                    $nodes[] = new PageNode(uniqid(), $dataGroup->getBuiltInSectionCode(), 0, 2, 'Asset', '/manage/orms/Asset/', 'cms/files/file.html.twig', null, 1, 1);
//                } else if ($dataGroup->getBuiltInSection() != 1) {
//                    /** @var _Model[] $models */
//                    $models = array_filter($modelMapData, function ($itm) use ($dataGroup) {
//                        return (strpos($itm->getDataGroups(), '"' . $dataGroup->getId() . '"') !== false && $itm->getDataType() == 0) ? 1 : 0;
//                    });
//                    if (count($models)) {
//                        $data = array();
//                        $data['Data'] = null;
//                        foreach ($models as $model) {
//                            $data[$model->getTitle()] = array(
//                                'model' => $model,
//                                'children' => array(),
//                            );
//                        }
//                        $nodes = array_merge($nodes, static::appendModelsToParent($pdo, $dataGroupNodeId, $data, '/manage/orms/'));
//                    }
//                }
//            }
//        }
//
//        $fullClass = ModelService::fullClass($pdo, 'PageCategory');
//        $categories = $fullClass::active($pdo);
//
//        $count = 0;
//        $fullClass = ModelService::fullClass($pdo, 'Page');
//        $pages = $fullClass::active($pdo);
//        foreach ($categories as $catIdx => $catItm) {
//            $catId = uniqid();
//            $nodes[] = new PageNode($catId, 'pages', $count + $catIdx, 1, $catItm->getTitle());
//
//            $pageRoot = Extension::nestablePges($pages, $catItm->getId());
//            static::appendPagesToParent($pdo, $pageRoot, 'pages', $nodes, $count + $catIdx);
//            $count += count($pageRoot->getChildren());
//        }
//
//        //Admin: set up page builder
//        $nodes = array_merge($nodes, static::appendModelsToParent($pdo, 'admin', array(
//            'TOOLS' => null,
//            'Webpage Builder' => array(
//                'model' => $modelMapData['Page'],
//                'children' => array(
//                    'Manage Templates' => array(
//                        'model' => $modelMapData['PageTemplate'],
//                        'children' => array(),
//                    ),
//                    'Manage Categories' => array(
//                        'model' => $modelMapData['PageCategory'],
//                        'children' => array(),
//                    ),
//                ),
//            ),
//        ), '/manage/admin/orms/'));
//
//        //Admin: set up model builder
//        $nodes[] = new PageNode(9992, 'admin', 3, 1, 'Model Builder', '/manage/admin/model-builder', 'cms/models/models.html.twig');
//        $nodes[] = new PageNode(99921, 9992, 1, 2, 'Model', '/manage/admin/model-builder/', 'cms/models/model.html.twig', null, 1, 1);
//        $nodes[] = new PageNode(99922, 9992, 2, 2, 'Model', '/manage/admin/model-builder/copy/', 'cms/models/model.html.twig', null, 1, 1);
//        $nodes = array_merge($nodes, static::appendModelsToParent($pdo, 9992, array(
//            'Content Blocks' => array(
//                'model' => $modelMapData['FragmentBlock'],
//                'children' => array(),
//            ),
//            'Content Block Tags' => array(
//                'model' => $modelMapData['FragmentTag'],
//                'children' => array(),
//            ),
//            'Content Block Defaults' => array(
//                'model' => $modelMapData['FragmentDefault'],
//                'children' => array(),
//            ),
//        ), '/manage/admin/orms/', 10));
//
//        //Admin: set up the rest
//        $nodes = array_merge($nodes, static::appendModelsToParent($pdo, 'admin', array(
//            'Image Sizes' => array(
//                'model' => $modelMapData['AssetSize'],
//                'children' => array(),
//            ),
//            'Form Builder' => array(
//                'model' => $modelMapData['FormDescriptor'],
//                'children' => array(),
//            ),
//            'Admin' => null,
//            'Users' => array(
//                'model' => $modelMapData['User'],
//                'children' => array(),
//            ),
//            'CMS Sections' => array(
//                'model' => $modelMapData['DataGroup'],
//                'children' => array(),
//            ),
//        ), '/manage/admin/orms/', 20));
//
//        /** @var _Model[] $models */
//        $models = array_filter($modelMapData, function ($itm) use ($dataGroup) {
//            return ($itm->getDataType() == 1) ? 1 : 0;
//        });
//
//        if (count($models)) {
//            $data = array();
//            $data['Data'] = null;
//            foreach ($models as $model) {
//                $data[$model->getTitle()] = array(
//                    'model' => $model,
//                    'children' => array(),
//                );
//            }
//            $nodes = array_merge($nodes, static::appendModelsToParent($pdo, 'admin', $data, '/manage/orms/', 30));
//        }
//
//        $nodes[] = new PageNode(uniqid(), null, null, null, 'Manage Account', '/manage/current-user', 'cms/orms/orm.html.twig');
//
//        return $nodes;
        return [];
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
            $nodes[] = new PageNode($pageId, $parentId, $count + 1, 1, $pageNode->getTitle(), '/manage/pages/orms/Page/' . $pageNode->getId(), 'cms/orms/orm-custom-page.html.twig', '', 1, 2);
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
                    $models = array_filter($models);
                    $data = array();
                    foreach ($models as $model) {
                        $data[$model->getTitle()] = array(
                            'model' => $model,
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
                $modelNodeId = uniqid();

                $itm = (array)$itm;
                if (isset($itm['single']) && $itm['single']) {
                    $nodes[] = new PageNode($modelNodeId, $parentId, $count, 1, $idx, $itm['url'], $itm['twig']);

                    if (isset($itm['model'])) {
                        $model = $itm['model'];
                        if (gettype($model) == 'string') {
                            $model = _Model::getByField($pdo, 'className', $model);
                        }
                        $className = $model->getClassName();

                        $fullClass = ModelService::fullClass($pdo, $className);
                        $ormsTwig = $fullClass::getCmsOrmsTwig();
                        if (!$ormsTwig) {
                            $ormsTwig = $ormsListTwig[$model->getListType()];
                        }
                        $ormTwig = $fullClass::getCmsOrmTwig();
                        if (!$ormTwig) {
                            $ormTwig = $ormDefaultTwig;
                        }

                        $nodes[] = new PageNode(uniqid(), $modelNodeId, 1, 2, '', $itm['url'] . '/'  . $className . '/', $ormTwig, null, 1, 3);
                        $nodes[] = new PageNode(uniqid(), $modelNodeId, 2, 2, '', $itm['url'] . '/'  . $className . '/copy/', $ormTwig, null, 1, 1);
                    }

                } else {
                    $model = $itm['model'];
                    if (gettype($model) == 'string') {
                        $model = _Model::getByField($pdo, 'className', $model);
                    }
                    $className = $model->getClassName();

                    $fullClass = ModelService::fullClass($pdo, $className);
                    $ormsTwig = $fullClass::getCmsOrmsTwig();
                    if (!$ormsTwig) {
                        $ormsTwig = $ormsListTwig[$model->getListType()];
                    }
                    $ormTwig = $fullClass::getCmsOrmTwig();
                    if (!$ormTwig) {
                        $ormTwig = $ormDefaultTwig;
                    }

                    $nodes[] = new PageNode($modelNodeId, $parentId, $count, $itm['status'] ?? 1, $idx, $baseUrl . $className, $ormsTwig);
                    $nodes[] = new PageNode(uniqid(), $modelNodeId, 1, 2, '', $baseUrl . $className . '/', $ormTwig, null, 1, 3);
                    $nodes[] = new PageNode(uniqid(), $modelNodeId, 2, 2, '', $baseUrl . $className . '/copy/', $ormTwig, null, 1, 1);
                }

                $children = (array)$itm['children'];
                if (count($children)) {
                    $nodes = array_merge($nodes, static::appendModelsToParent($pdo, $modelNodeId, $children, $baseUrl, 3));
                }
            }
            $count++;
        }
        return $nodes;
    }
}