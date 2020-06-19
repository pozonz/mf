<?php

namespace MillenniumFalcon\Core\Controller\Traits;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Db;
use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Form\Builder\Orm;
use MillenniumFalcon\Core\Nestable\PageNode;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Twig\Extension;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

trait CmsRestTrait
{
    /**
     * @route("/manage/rest/version/delete")
     * @return Response
     */
    public function cmsRestVersionDelete()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $className = $request->get('className');

        $fullClass = ModelService::fullClass($pdo, $className);
        $version = $fullClass::data($pdo, [
            'whereSql' => 'm.id = ?',
            'params' => [$id],
            'limit' => 1,
            'oneOrNull' => 1,
            'includePreviousVersion' => 1,
        ]);
        $version->delete();

        return new Response('OK');
    }

    /**
     * @route("/manage/rest/column/sort")
     * @return Response
     */
    public function cmsRestColumnSort()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $data = json_decode($request->get('data'));
        $className = $request->get('className');

        $fullClass = ModelService::fullClass($pdo, $className);
        foreach ($data as $idx => $itm) {
            $orm = $fullClass::getById($pdo, $itm);
            if ($orm) {
                $orm->setRank($idx);
                $orm->save();
                if ($className == '_Model') {
                    $fullClass::setGenereatedFile($orm, $this->container);
                }
            }
        }
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/nestable/sort")
     * @return Response
     */
    public function cmsRestNestableSort()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $data = json_decode($request->get('data'));
        $className = $request->get('model');

        $fullClass = ModelService::fullClass($pdo, $className);
        foreach ($data as $idx => $itm) {
            $orm = $fullClass::getById($pdo, $itm->id);
            if ($orm) {
                $orm->setRank($itm->rank);
                $orm->setParentId($itm->parentId ?: null);
                $orm->save();
            }
        }
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/nestable/closed")
     * @return Response
     */
    public function cmsRestNestableClosed()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $closed = $request->get('closed') ?: 0;
        $className = $request->get('model');

        $fullClass = ModelService::fullClass($pdo, $className);
        $orm = $fullClass::getById($pdo, $id);
        if (!$orm) {
            throw new NotFoundHttpException();
        }

        $orm->setClosed($closed);
        $orm->save();

        return new Response('OK');
    }

    /**
     * @route("/manage/rest/status")
     * @return Response
     */
    public function cmsRestStatus()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $status = $request->get('status');
        $id = $request->get('id');
        $className = $request->get('className');

        $fullClass = ModelService::fullClass($pdo, $className);
        $orm = $fullClass::getById($pdo, $id);
        if ($orm) {
            $orm->setStatus($status);
            $orm->save();
        }
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/delete")
     * @return Response
     */
    public function cmsRestDelete()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $status = $request->get('status');
        $id = $request->get('id');
        $className = $request->get('className');

        $fullClass = ModelService::fullClass($pdo, $className);
        $orm = $fullClass::getById($pdo, $id);
        if ($orm) {
            $orm->delete();
        }
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/cat/count")
     * @return Response
     */
    public function cmsRestCatCount()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $fullClass = ModelService::fullClass($pdo, 'PageCategory');
        $pageCategories = $fullClass::active($pdo);

        $fullClass = ModelService::fullClass($pdo, 'Page');
        $pages = $fullClass::data($pdo);

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
     * @route("/manage/rest/pages/sort")
     * @return Response
     */
    public function cmsRestPagesSort()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $cat = $request->get('cat');
        $data = (array)json_decode($request->get('data'));

        $fullClass = ModelService::fullClass($pdo, 'Page');
        foreach ($data as $itm) {
            $orm = $fullClass::getById($pdo, $itm->id);

            $category = $orm->getCategory() ? (array)json_decode($orm->getCategory()) : array();
            if (!in_array($cat, $category)) {
                $category[] = $cat;
            }

            $categoryRank = $orm->getCategoryRank() ? (array)json_decode($orm->getCategoryRank()) : array();
            $categoryParent = $orm->getCategoryParent() ? (array)json_decode($orm->getCategoryParent()) : array();

            $categoryRank["cat{$cat}"] = $itm->rank;
            $categoryParent["cat{$cat}"] = $itm->parentId;

            $orm->setCategory(json_encode($category));
            $orm->setCategoryRank(json_encode($categoryRank));
            $orm->setCategoryParent(json_encode($categoryParent));
            $orm->save();
        }

        return new Response('OK');
    }

    /**
     * @route("/manage/rest/page/change")
     * @return Response
     */
    public function cmsRestPageChange()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $oldCat = $request->get('oldCat');
        $newCat = $request->get('newCat') ?: 0;

        $fullClass = ModelService::fullClass($pdo, 'Page');
        $root = Extension::nestablePges($fullClass::data($pdo), $oldCat);
        $nodes = Tree::getChildrenAndSelfAsArray($root, $id);
        foreach ($nodes as $node) {
            $orm = $node;

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
            $categoryParent["cat{$newCat}"] = $orm->getId() == $id ? 0 : $categoryParent["cat{$oldCat}"];

            unset($categoryRank["cat{$oldCat}"]);
            unset($categoryParent["cat{$oldCat}"]);

            $orm->setCategory(json_encode($category));
            $orm->setCategoryRank(json_encode($categoryRank));
            $orm->setCategoryParent(json_encode($categoryParent));
            $orm->save();
        }

        return new Response('OK');
    }

    /**
     * @route("/manage/rest/page/closed")
     * @return Response
     */
    public function cmsRestPageClosed()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $cat = $request->get('cat');
        $closed = $request->get('closed') ?: 0;

        $fullClass = ModelService::fullClass($pdo, 'Page');
        $orm = $fullClass::getById($pdo, $id);
        if (!$orm) {
            throw new NotFoundHttpException();
        }

        $categoryClosed = $orm->getCategoryClosed() ? (array)json_decode($orm->getCategoryClosed()) : array();
        $categoryClosed["cat{$cat}"] = $closed;
        $orm->setCategoryClosed(json_encode($categoryClosed));
        $orm->save();

        return new Response('OK');
    }
}