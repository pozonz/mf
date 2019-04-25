<?php

namespace MillenniumFalcon\Controller;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Db;
use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Form\Builder\Orm;
use MillenniumFalcon\Core\Nestable\PageNode;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Orm\AssetSize;
use MillenniumFalcon\Core\Orm\DataGroup;
use MillenniumFalcon\Core\Orm\Page;
use MillenniumFalcon\Core\Orm\PageCategory;
use MillenniumFalcon\Core\Orm\PageTemplate;
use MillenniumFalcon\Core\Orm\User;
use MillenniumFalcon\Core\Redirect\RedirectException;
use MillenniumFalcon\Core\Router;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Twig\Extension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;

trait CmsRestTrait
{
    /**
     * @route("/manage/rest/column/sort")
     * @return Response
     */
    public function cmsRestColumnSort()
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $request = Request::createFromGlobals();
        $data = json_decode($request->get('data'));
        $className = $request->get('className');

        $fullClassName = ModelService::fullClassName($className);
        foreach ($data as $idx => $itm) {
            $orm = $fullClassName::getById($pdo, $itm);
            if ($orm) {
                $orm->setRank($idx);
                $orm->save();
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
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $request = Request::createFromGlobals();
        $data = json_decode($request->get('data'));
        $className = $request->get('model');

        $fullClassName = ModelService::fullClassName($className);
        foreach ($data as $idx => $itm) {
            $orm = $fullClassName::getById($pdo, $itm->id);
            if ($orm) {
                $orm->setRank($itm->rank);
                $orm->setParentId($itm->parentId);
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
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $closed = $request->get('closed') ?: 0;
        $className = $request->get('model');

        $fullClassName = ModelService::fullClassName($className);
        $orm = $fullClassName::getById($pdo, $id);
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
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $request = Request::createFromGlobals();
        $status = $request->get('status');
        $id = $request->get('id');
        $className = $request->get('className');

        $fullClassName = ModelService::fullClassName($className);
        $orm = $fullClassName::getById($pdo, $id);
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
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $request = Request::createFromGlobals();
        $status = $request->get('status');
        $id = $request->get('id');
        $className = $request->get('className');

        $fullClassName = ModelService::fullClassName($className);
        $orm = $fullClassName::getById($pdo, $id);
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
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $pageCategories = PageCategory::active($pdo);
        $pages = Page::data($pdo);

        $result = array();
        foreach ($pageCategories as $pageCategory) {
            $result["cat{$pageCategory->getId()}"] = 0;
            foreach ($pages as $page) {
                $category = json_decode($page->getCategory());
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
            $category = json_decode($page->getCategory());
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
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $request = Request::createFromGlobals();
        $cat = $request->get('cat');
        $data = (array)json_decode($request->get('data'));

        foreach ($data as $itm) {
            /** @var Page $orm */
            $orm = Page::getById($pdo, $itm->id);

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
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $oldCat = $request->get('oldCat');
        $newCat = $request->get('newCat') ?: 0;

        $root = Extension::nestablePges(Page::data($pdo), $oldCat);
        $nodes = Tree::getChildrenAndSelfAsArray($root, $id);
        foreach ($nodes as $node) {
            /** @var Page $orm */
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
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $request = Request::createFromGlobals();
        $id = $request->get('id');
        $cat = $request->get('cat');
        $closed = $request->get('closed') ?: 0;

        /** @var Page $orm */
        $orm = Page::getById($pdo, $id);
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