<?php

namespace MillenniumFalcon\Controller;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Db;
use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Form\Builder\Orm;
use MillenniumFalcon\Core\Nestable\PageNode;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Orm\Asset;
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
    protected function getNodes()
    {
        /** @var \PDO $pdo */
        $pdo = $this->connection->getWrappedConnection();

        $nodes = [];
        //Set up major nav
        $nodes[] = new PageNode(uniqid(), null, 0, 1, 'Pages', '/manage/pages', 'cms/pages.html.twig', 'cms_viewmode_cms');

        //Set up custom partitions
        $orms = DataGroup::active($pdo);
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
        $nodes[] = new PageNode(998, null, 998, 1, 'Assets', '/manage/files', 'cms/files/files.html.twig', 'cms_viewmode_asset');
        $nodes[] = new PageNode(9981, 998, 1, 2, 'Assets', '/manage/orms/Asset/', 'cms/orms/orm.html.twig', null, 1, 1);

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

                /** @var _Model $model */
                $model = _Model::getByField($pdo, 'className', $className);
                $fullClass = $model->getNamespace() . '\\' . $model->getClassName();

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