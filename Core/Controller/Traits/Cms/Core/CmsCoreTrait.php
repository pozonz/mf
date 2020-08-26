<?php

namespace MillenniumFalcon\Core\Controller\Traits\Cms\Core;

use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Tree\RawData;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Tree\TreeUtils;
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

        $theDataGroup = TreeUtils::ancestor($params['theNode']);
        $params['theDataGroup'] = $theDataGroup;
        $params['rootNodes'] = $this->_tree->getRootNodes();

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
    public function getRawData()
    {
        $data = _Model::active($this->connection);
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
            } else if ($dataGroup->getTitle() == 'Files') {
                $nodes = $this->_addModelDetailToParent($nodes, $this->_getClass($dataGroup) . $dataGroup->getId(), 'Asset');
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
            $toBeMergedNodes = [];
            $fullClass = ModelService::fullClass($this->connection, 'Page');
            $pages = $fullClass::active($this->connection, [
                'whereSql' => 'm.category LIKE ? AND (m.hideFromCMSNav IS NULL OR m.hideFromCMSNav != 1)',
                'params' => ['%' . $pageCategory->getId() . '%'],
            ]);

            $startRank = -1;
            $toBeSorted = [];
            $catAttr = 'cat' . $pageCategory->getId();

            foreach ($pages as $page) {
                $jsonRank = json_decode($page->getCategoryRank() ?: '[]');
                $rank = $jsonRank->$catAttr ?? $startRank;
                $toBeSorted[$rank] = $page;
                $startRank--;
            }
            ksort($toBeSorted);
            $pages = array_values($toBeSorted);

            $toBeMergedNodes = array_merge($toBeMergedNodes, array_map(function ($itm) use ($dataGroup, $dataGroupClass, $pageCategory, $fullClass) {
                $categoryParent = (object)json_decode($itm->getCategoryParent() ?: '[]');
                $categoryParentAttr = "cat{$pageCategory->getId()}";
                $parentId = isset($categoryParent->{$categoryParentAttr}) ? $this->_getClass($itm) . $categoryParent->{$categoryParentAttr} : $dataGroupClass . $dataGroup->getId();
                return (array)new RawData([
                    'id' => $this->_getClass($itm) . $itm->getId(),
                    'parent' => $parentId,
                    'title' => $itm->getTitle(),
                    'url' => "/manage/pages/orms/{$this->_getClass($itm)}/{$itm->getId()}",
                    'template' => $fullClass::getCmsOrmTwig(),
                    'status' => 1,
                    'allowExtra' => 1,
                    'maxParams' => 2,
                ]);
            }, $pages));

            foreach ($pages as $itm) {
                $attachedModelIds = json_decode($itm->getAttachedModels() ?: '[]');
                foreach ($attachedModelIds as $attachedModelId) {
                    $attachedModel = _Model::getById($this->connection, $attachedModelId);
                    $toBeMergedNodes = $this->_addModelListingToParent($toBeMergedNodes, $this->_getClass($itm) . $itm->getId(), $attachedModel->getClassname(), '/manage/pages');
                }
            }

            if (count($toBeMergedNodes)) {
                $nodes[] = (array)new RawData([
                    'id' => $this->_getClass($pageCategory) . $pageCategory->getId(),
                    'parent' => $dataGroupClass . $dataGroup->getId(),
                    'title' => $pageCategory->getTitle(),
                    'status' => 1,
                ]);
                $nodes = array_merge($nodes, $toBeMergedNodes);
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
        $nodes = $this->_addModelDetailToParent($nodes, 'pageBuilder', 'Page', '/manage/admin');
        $nodes = $this->_addModelListingToParent($nodes, 'pageBuilder', 'PageCategory', '/manage/admin');
        $nodes = $this->_addModelListingToParent($nodes, 'pageBuilder', 'PageTemplate', '/manage/admin');

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
        $nodes = $this->_addModelListingToParent($nodes, 'modelBuilder', 'FragmentBlock', '/manage/admin');
        $nodes = $this->_addModelListingToParent($nodes, 'modelBuilder', 'FragmentTag', '/manage/admin');
        $nodes = $this->_addModelListingToParent($nodes, 'modelBuilder', 'FragmentDefault', '/manage/admin');

        $nodes = $this->_addModelListingToParent($nodes, $dataGroupClass . $dataGroup->getId(), 'AssetSize', '/manage/admin');
        $nodes = $this->_addModelListingToParent($nodes, $dataGroupClass . $dataGroup->getId(), 'FormDescriptor', '/manage/admin');

        $nodes[] = (array)new RawData([
            'id' => 'adminAdmin',
            'parent' => $dataGroupClass . $dataGroup->getId(),
            'title' => 'Admin',
            'status' => 1,
        ]);
        $nodes = $this->_addModelListingToParent($nodes, $dataGroupClass . $dataGroup->getId(), 'User', '/manage/admin');
        $nodes = $this->_addModelListingToParent($nodes, $dataGroupClass . $dataGroup->getId(), 'DataGroup', '/manage/admin');

        $nodes = $this->_getDataGroupNodes($nodes, $dataGroup, '/manage/admin');
        return $nodes;
    }

    /**
     * @param $nodes
     * @param $dataGroup
     * @return mixed
     * @throws \ReflectionException
     */
    private function _getDataGroupNodes($nodes, $dataGroup, $baseUrl = '/manage')
    {
        $dataGroupClass = $this->_getClass($dataGroup);

        $toBeMergedNodes = [];

        foreach ($this->models as $model) {
            $modelDataGroups = json_decode($model->getDataGroups() ?: '[]');
            if (in_array($dataGroup->getId(), $modelDataGroups)) {
                $toBeMergedNodes = $this->_addModelListingToParent($toBeMergedNodes, $dataGroupClass . $dataGroup->getId(), $model->getClassName(), $baseUrl);
            }
        }

        if (count($toBeMergedNodes)) {
            $nodes[] = (array)new RawData([
                'id' => "data{$dataGroup->getId()}",
                'parent' => $dataGroupClass . $dataGroup->getId(),
                'title' => 'Data',
                'status' => 1,
            ]);
            $nodes = array_merge($nodes, $toBeMergedNodes);
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
    private function _addModelListingToParent($nodes, $parentId, $modelClassName, $baseUrl = '/manage')
    {
        $model = $this->models[$modelClassName] ?? null;

        $ormsListTwig = array(
            0 => 'cms/orms/orms-dragdrop.twig',
            1 => 'cms/orms/orms-pagination.twig',
            2 => 'cms/orms/orms-tree.twig',
        );

        $modelId = $parentId . $modelClassName;
        $fullClass = ModelService::fullClass($this->connection, $modelClassName);
        $nodes[] = (array)new RawData([
            'id' => $modelId,
            'parent' => $parentId,
            'title' => $model->getTitle(),
            'url' => "{$baseUrl}/orms/{$modelClassName}",
            'template' => $fullClass::getCmsOrmsTwig() ?: $ormsListTwig[$model->getListType()],
            'status' => $model->getDataType() == 3 ? 3 : 1,
            'allowExtra' => 1,
            'maxParams' => 1,
        ]);
        return $this->_addModelDetailToParent($nodes, $modelId, $modelClassName, $baseUrl);
    }

    /**
     * @param $nodes
     * @param $parentId
     * @param $modelClassName
     * @return mixed
     * @throws \Exception
     */
    private function _addModelDetailToParent($nodes, $parentId, $modelClassName, $baseUrl = '/manage')
    {
        $fullClass = ModelService::fullClass($this->connection, $modelClassName);
        $nodes[] = (array)new RawData([
            'id' => "{$parentId}{$modelClassName}Detail",
            'parent' => $parentId,
            'title' => "{$modelClassName} detail",
            'url' => "{$baseUrl}/orms/{$modelClassName}/",
            'template' => $fullClass::getCmsOrmTwig(),
            'status' => 2,
            'allowExtra' => 1,
            'maxParams' => 3,
        ]);

        $nodes[] = (array)new RawData([
            'id' => "{$parentId}{$modelClassName}Copy",
            'parent' => $parentId,
            'title' => "{$modelClassName} copy",
            'url' => "{$baseUrl}/orms/{$modelClassName}/copy/",
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