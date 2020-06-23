<?php

namespace MillenniumFalcon\Core\Controller\Traits;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Db;
use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Form\Builder\Orm;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Twig\Extension;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

trait CmsCoreRestTrait
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
                $orm->save(true);
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
                $orm->save(true);
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
        $orm->save(true);

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
            $orm->save(true);
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
}