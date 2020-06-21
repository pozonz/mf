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
        $conn = $this->container->get('doctrine.dbal.default_connection');
        $nodes = [];

        $fullClass = ModelService::fullClass($conn, 'DataGroup');
        $dataGroups = $fullClass::active($conn);

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
        $conn = $this->container->get('doctrine.dbal.default_connection');

        $fullClass = ModelService::fullClass($conn, 'PageCategory');
        $pageCategories = $fullClass::active($conn);
        foreach ($pageCategories as $pageCategory) {
            $nodes[] = (array)new RawData([
                'id' => $this->_getClass($pageCategory) . $pageCategory->getId(),
                'parent' => $this->_getClass($dataGroup) . $dataGroup->getId(),
                'title' => $pageCategory->getTitle(),
                'status' => 1,
            ]);

            $fullClass = ModelService::fullClass($conn, 'Page');
            $pages = $fullClass::active($conn, [
                'whereSql' => 'm.category LIKE ? AND (m.hideFromCMSNav IS NULL OR m.hideFromCMSNav != 1)',
                'params' => ['%' . $pageCategory->getId() . '%'],
            ]);
            $nodes = array_merge($nodes, array_map(function ($itm) use ($dataGroup, $pageCategory) {
                $categoryParent = (object)json_decode($itm->getCategoryParent() ?: '[]');
                $categoryParentAttr = "cat{$pageCategory->getId()}";
                $parentId = isset($categoryParent->{$categoryParentAttr}) ? $this->_getClass($itm) . $categoryParent->{$categoryParentAttr} : $this->_getClass($dataGroup) . $dataGroup->getId();
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
        $conn = $this->container->get('doctrine.dbal.default_connection');

        $nodes[] = (array)new RawData([
            'id' => uniqid(),
            'parent' => $this->_getClass($dataGroup) . $dataGroup->getId(),
            'title' => 'Tools',
            'status' => 1,
        ]);

        $nodes[] = (array)new RawData([
            'id' => 'model',
            'parent' => $this->_getClass($dataGroup) . $dataGroup->getId(),
            'title' => 'Model builder',
            'url' => "/manage/admin/model-builder",
            'template' => 'cms/models/models.twig',
            'status' => 1,
        ]);

        $nodes[] = (array)new RawData([
            'id' => 'modelDetail',
            'parent' => 'model',
            'title' => 'Model detail',
            'url' => "/manage/admin/model-builder/",
            'template' => 'cms/models/model.twig',
            'allowExtra' => 1,
            'maxParams' => 1,
            'status' => 2,
        ]);

        $nodes[] = (array)new RawData([
            'id' => 'modelCopy',
            'parent' => 'model',
            'title' => 'Model copy',
            'url' => "/manage/admin/model-builder/copy/",
            'template' => 'cms/models/model.twig',
            'allowExtra' => 1,
            'maxParams' => 1,
            'status' => 2,
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