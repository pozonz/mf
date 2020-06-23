<?php

namespace MillenniumFalcon\Core\Controller\Traits;

use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Tree\RawData;
use MillenniumFalcon\Core\ORM\_Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

trait CmsCoreTrait
{
    /**
     * @var array
     */
    protected $models = [];

    /**
     * @route("/manage/{page}", requirements={"page" = ".*"})
     * @param Request $request
     * @return mixed
     */
    public function manage(Request $request)
    {
        $params = $this->getCmsTemplateParams($request);
        return $this->render($params['theNode']->template, $params);
    }

    /**
     * @return mixed
     */
    public function getCmsTemplateParams($request)
    {
        $params = $this->getTemplateParams($request);

        //Check permission
//        if (!$params['verticalMenuRoot']) {
//            throw new NotFoundHttpException();
//        }
//        $params['verticalMenuItems'] = $params['verticalMenuRoot']->getChildren();

        return $params;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getNodes()
    {
        $data = _Model::data($this->connection);
        foreach ($data as $itm) {
            $this->models[$itm->getClassName()] = $itm;
        }

        $nodes = [];
        $fullClass = ModelService::fullClass($this->connection, 'DataGroup');
        $dataGroups = $fullClass::active($this->connection);

        $nodes = array_merge($nodes, array_map(function ($itm) {
            return (array)new RawData([
                'id' => $this->_getClass($itm) . $itm->getId(),
                'parent' => null,
                'title' => $itm->getTitle(),
                'url' => '/manage/' . ($itm->getBuiltInSection() ? $itm->getBuiltInSectionCode() : 'section/' . $itm->getId()),
                'template' => $itm->getBuiltInSection() ? str_replace('.html.twig', '.twig', $itm->getBuiltInSectionTemplate()) : 'cms/admin.twig',
                'status' => 1,
                'icon' => $itm->getIcon(),
            ]);
        }, $dataGroups));

        foreach ($dataGroups as $dataGroup) {
            if ($dataGroup->getTitle() == 'Pages') {
                $nodes = $this->_getDataGroupNodesForPages($nodes, $dataGroup);
            } else if ($dataGroup->getTitle() == 'Admin') {
                $nodes = $this->_getDataGroupNodesForAdmin($nodes, $dataGroup);
            } else {
                $nodes = $this->_getDataGroupNodes($nodes, $dataGroup);
            }
        }
        return $nodes;
    }

    /**
     * @param $nodes
     * @param $dataGroup
     * @return array
     * @throws \ReflectionException
     */
    private function _getDataGroupNodesForPages($nodes, $dataGroup)
    {
        $dataGroupClass = $this->_getClass($dataGroup);

        $fullClass = ModelService::fullClass($this->connection, 'PageCategory');
        $pageCategories = $fullClass::active($this->connection);
        foreach ($pageCategories as $pageCategory) {
            $nodes[] = (array)new RawData([
                'id' => $this->_getClass($pageCategory) . $pageCategory->getId(),
                'parent' => $dataGroupClass . $dataGroup->getId(),
                'title' => $pageCategory->getTitle(),
                'status' => 1,
            ]);

            $fullClass = ModelService::fullClass($this->connection, 'Page');
            $pages = $fullClass::active($this->connection, [
                'whereSql' => 'm.category LIKE ? AND (m.hideFromCMSNav IS NULL OR m.hideFromCMSNav != 1)',
                'params' => ['%' . $pageCategory->getId() . '%'],
            ]);
            $nodes = array_merge($nodes, array_map(function ($itm) use ($dataGroup, $dataGroupClass, $pageCategory) {
                $categoryParent = (object)json_decode($itm->getCategoryParent() ?: '[]');
                $categoryParentAttr = "cat{$pageCategory->getId()}";
                $parentId = isset($categoryParent->{$categoryParentAttr}) ? $this->_getClass($itm) . $categoryParent->{$categoryParentAttr} : $dataGroupClass . $dataGroup->getId();
                return (array)new RawData([
                    'id' => $this->_getClass($itm) . $itm->getId(),
                    'parent' => $parentId,
                    'title' => $itm->getTitle(),
                    'url' => "/manage/pages/orms/{$this->_getClass($itm)}/{$itm->getId()}",
                    'template' => 'cms/orms/orm-custom-page.html.twig',
                    'status' => 1,
                ]);
            }, $pages));
        }

        return $nodes;
    }

    /**
     * @param $nodes
     * @param $dataGroup
     * @return array
     * @throws \ReflectionException
     */
    private function _getDataGroupNodesForAdmin($nodes, $dataGroup)
    {
        $dataGroupClass = $this->_getClass($dataGroup);

        $nodes[] = (array)new RawData([
            'id' => 'adminTools',
            'parent' => $dataGroupClass . $dataGroup->getId(),
            'title' => 'Tools',
            'status' => 1,
        ]);

        $nodes[] = (array)new RawData([
            'id' => 'pageBuilder',
            'parent' => $dataGroupClass . $dataGroup->getId(),
            'title' => 'Webpage builder',
            'url' => "/manage/admin/orms/Page",
            'template' => 'cms/orms/orms-custom-page.twig',
            'status' => 1,
        ]);
        $nodes = $this->_addModelDetailToParent($nodes, 'pageBuilder', 'Page');
        $nodes = $this->_addModelListingToParent($nodes, 'pageBuilder', 'PageCategory');
        $nodes = $this->_addModelListingToParent($nodes, 'pageBuilder', 'PageTemplate');

        $nodes[] = (array)new RawData([
            'id' => 'modelBuilder',
            'parent' => $dataGroupClass . $dataGroup->getId(),
            'title' => 'Model builder',
            'url' => "/manage/admin/model-builder",
            'template' => 'cms/models/models.twig',
            'status' => 1,
        ]);
        $nodes[] = (array)new RawData([
            'id' => 'modelBuilderDetail',
            'parent' => 'modelBuilder',
            'title' => 'Model detail',
            'url' => "/manage/admin/model-builder/",
            'template' => 'cms/models/model.twig',
            'allowExtra' => 1,
            'maxParams' => 1,
            'status' => 2,
        ]);
        $nodes[] = (array)new RawData([
            'id' => 'modelBuilderCopy',
            'parent' => 'modelBuilder',
            'title' => 'Model copy',
            'url' => "/manage/admin/model-builder/copy/",
            'template' => 'cms/models/model.twig',
            'allowExtra' => 1,
            'maxParams' => 1,
            'status' => 2,
        ]);
        $nodes = $this->_addModelListingToParent($nodes, 'modelBuilder', 'FragmentBlock');
        $nodes = $this->_addModelListingToParent($nodes, 'modelBuilder', 'FragmentTag');
        $nodes = $this->_addModelListingToParent($nodes, 'modelBuilder', 'FragmentDefault');

        $nodes = $this->_addModelListingToParent($nodes, $dataGroupClass . $dataGroup->getId(), 'AssetSize');
        $nodes = $this->_addModelListingToParent($nodes, $dataGroupClass . $dataGroup->getId(), 'FormDescriptor');

        $nodes[] = (array)new RawData([
            'id' => 'adminAdmin',
            'parent' => $dataGroupClass . $dataGroup->getId(),
            'title' => 'Admin',
            'status' => 1,
        ]);
        $nodes = $this->_addModelListingToParent($nodes, $dataGroupClass . $dataGroup->getId(), 'User');
        $nodes = $this->_addModelListingToParent($nodes, $dataGroupClass . $dataGroup->getId(), 'DataGroup');

        $nodes = $this->_getDataGroupNodes($nodes, $dataGroup);
        return $nodes;
    }

    /**
     * @param $nodes
     * @param $dataGroup
     * @return mixed
     * @throws \ReflectionException
     */
    private function _getDataGroupNodes($nodes, $dataGroup)
    {
        $dataGroupClass = $this->_getClass($dataGroup);

        $nodes[] = (array)new RawData([
            'id' => "data{$dataGroup->getId()}",
            'parent' => $dataGroupClass . $dataGroup->getId(),
            'title' => 'Data',
            'status' => 1,
        ]);

        foreach ($this->models as $model) {
            $modelDataGroups = json_decode($model->getDataGroups() ?: '[]');
            if (in_array($dataGroup->getId(), $modelDataGroups)) {
                $nodes = $this->_addModelListingToParent($nodes, $dataGroupClass . $dataGroup->getId(), $model->getClassName());
            }
        }

        return $nodes;
    }


    /**
     * @param $nodes
     * @param $parentId
     * @param $modelClassName
     * @return mixed
     * @throws \Exception
     */
    private function _addModelListingToParent($nodes, $parentId, $modelClassName)
    {
        $model = $this->models[$modelClassName] ?? null;

        $ormsListTwig = array(
            0 => 'cms/orms/orms-dragdrop.twig',
            1 => 'cms/orms/orms-pagination.twig',
            2 => 'cms/orms/orms-tree.twig',
        );

        $fullClass = ModelService::fullClass($this->connection, $modelClassName);
        $nodes[] = (array)new RawData([
            'id' => $modelClassName,
            'parent' => $parentId,
            'title' => $model->getTitle(),
            'url' => "/manage/admin/orms/{$modelClassName}",
            'template' => $fullClass::getCmsOrmsTwig() ?: $ormsListTwig[$model->getListType()],
            'status' => 1,
            'allowExtra' => 1,
            'maxParams' => 1,
        ]);
        return $this->_addModelDetailToParent($nodes, $modelClassName, $modelClassName);
    }

    /**
     * @param $nodes
     * @param $parentId
     * @param $modelClassName
     * @return mixed
     * @throws \Exception
     */
    private function _addModelDetailToParent($nodes, $parentId, $modelClassName)
    {
        $fullClass = ModelService::fullClass($this->connection, $modelClassName);
        $nodes[] = (array)new RawData([
            'id' => "{$modelClassName}Detail",
            'parent' => $parentId,
            'title' => "{$modelClassName} detail",
            'url' => "/manage/admin/orms/{$modelClassName}/",
            'template' => $fullClass::getCmsOrmTwig(),
            'status' => 2,
            'allowExtra' => 1,
            'maxParams' => 1,
        ]);

        $nodes[] = (array)new RawData([
            'id' => "{$modelClassName}Copy",
            'parent' => $parentId,
            'title' => "{$modelClassName} copy",
            'url' => "/manage/admin/orms/{$modelClassName}/copy/",
            'template' => $fullClass::getCmsOrmTwig(),
            'status' => 2,
            'allowExtra' => 1,
            'maxParams' => 1,
        ]);
        return $nodes;
    }

    /**
     * @param $obj
     * @return string
     * @throws \ReflectionException
     */
    private function _getClass($obj)
    {
        $rc = new \ReflectionClass($obj);
        return $rc->getShortName();
    }
}