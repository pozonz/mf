<?php

namespace MillenniumFalcon\Core\Controller\Traits\Cms\Core;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Db;
use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Form\Builder\Orm;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Twig\Extension;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

trait CmsCoreRestPageTrait
{
    /**
     * @route("/manage/rest/cat/count", methods={"GET"})
     * @return Response
     */
    public function cmsRestCatCount()
    {
        $fullClass = ModelService::fullClass($this->connection, 'PageCategory');
        $pageCategories = $fullClass::active($this->connection);

        $fullClass = ModelService::fullClass($this->connection, 'Page');
        $pages = $fullClass::data($this->connection);

        $result = array();
        foreach ($pageCategories as $pageCategory) {
            $result["cat{$pageCategory->getId()}"] = 0;
            foreach ($pages as $page) {
                $category = (array)json_decode($page->getCategory());
                if (!$category) {
                    $category = array();
                }
                if (in_array($pageCategory->getId(), $category)) {
                    $result["cat{$pageCategory->getId()}"]++;
                }
            }
        }

        $result["cat0"] = 0;
        foreach ($pages as $page) {
            $category = (array)json_decode($page->getCategory());
            if (gettype($category) == 'array' && (in_array(0, $category) || !count($category))) {
                $result["cat0"]++;
            } elseif (!$category) {
                $result["cat0"]++;
            }
        }
        return new JsonResponse($result);
    }

    /**
     * @route("/manage/rest/pages/sort", methods={"POST"})
     * @return Response
     */
    public function cmsRestPagesSort(Request $request)
    {
        $cat = $request->get('cat');
        $data = (array)json_decode($request->get('data'));

        $fullClass = ModelService::fullClass($this->connection, 'Page');
        foreach ($data as $itm) {
            $orm = $fullClass::getById($this->connection, $itm->id);

            $category = $orm->getCategory() ? (array)json_decode($orm->getCategory()) : array();
            if (!in_array($cat, $category)) {
                $category[] = $cat;
            }

            $categoryRank = $orm->getCategoryRank() ? (array)json_decode($orm->getCategoryRank()) : array();
            $categoryParent = $orm->getCategoryParent() ? (array)json_decode($orm->getCategoryParent()) : array();

            $categoryRank["cat{$cat}"] = $itm->rank;
            $categoryParent["cat{$cat}"] = $itm->parentId ?: null;

            $orm->setCategory(json_encode($category));
            $orm->setCategoryRank(json_encode($categoryRank));
            $orm->setCategoryParent(json_encode($categoryParent));
            $orm->save(true);
        }

        return new Response('OK');
    }

    /**
     * @route("/manage/rest/page/change", methods={"POST"})
     * @return Response
     */
    public function cmsRestPageChange(Request $request)
    {
        $id = $request->get('id');
        $oldCat = $request->get('oldCat');
        $newCat = $request->get('newCat') ?: 0;

        $fullClass = ModelService::fullClass($this->connection, 'Page');
        $pageTree = Extension::nestablePges($fullClass::data($this->connection), $oldCat);
        $pageNode = $pageTree->getNodeById($id);
        $nodes = $pageNode->getDescendantsAndSelf();

        $fullClass = ModelService::fullClass($this->connection, 'Page');
        foreach ($nodes as $node) {
            $orm = $fullClass::getById($this->connection, $node->getId());

            $category = $orm->getCategory() ? (array)json_decode($orm->getCategory()) : array();
            $category = array_filter($category, function ($itm) use ($oldCat) {
                return $oldCat != $itm;
            });
            if ($newCat != 0) {
                $category[] = $newCat;
            }

            $categoryRank = $orm->getCategoryRank() ? (array)json_decode($orm->getCategoryRank()) : array();
            $categoryParent = $orm->getCategoryParent() ? (array)json_decode($orm->getCategoryParent()) : array();

            $categoryRank["cat{$newCat}"] = $orm->getId() == $id ? 0 : $categoryRank["cat{$oldCat}"];
            $categoryParent["cat{$newCat}"] = $orm->getId() == $id ? null : $categoryParent["cat{$oldCat}"];

            unset($categoryRank["cat{$oldCat}"]);
            unset($categoryParent["cat{$oldCat}"]);

            $orm->setCategory(json_encode($category));
            $orm->setCategoryRank(json_encode($categoryRank));
            $orm->setCategoryParent(json_encode($categoryParent));
            $orm->save(true);
        }

        return new Response('OK');
    }

    /**
     * @route("/manage/rest/page/closed", methods={"POST"})
     * @return Response
     */
    public function cmsRestPageClosed(Request $request)
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $cat = $request->get('cat');
        $closed = $request->get('closed') ?: 0;

        $fullClass = ModelService::fullClass($this->connection, 'Page');
        $orm = $fullClass::getById($this->connection, $id);
        if (!$orm) {
            throw new NotFoundHttpException();
        }

        $categoryClosed = $orm->getCategoryClosed() ? (array)json_decode($orm->getCategoryClosed()) : array();
        $categoryClosed["cat{$cat}"] = $closed;
        $orm->setCategoryClosed(json_encode($categoryClosed));
        $orm->save(true);

        return new Response('OK');
    }
}
