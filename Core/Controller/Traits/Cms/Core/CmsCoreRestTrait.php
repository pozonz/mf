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

trait CmsCoreRestTrait
{
    /**
     * @route("/manage/rest/model/note", methods={"POST"})
     * @return Response
     */
    public function cmsRestModelNote(Request $request)
    {
        $className = $request->get('className');
        $note = $request->get('note');

        $fullClass = ModelService::fullClass($this->connection, 'ModelNote');
        $orm = $fullClass::getByField($this->connection, 'title', $className);
        if (!$orm) {
            $orm = new $fullClass($this->connection);
            $orm->setTitle($className);
        }

        $orm->setNote($note);
        $orm->save();

        return new Response('OK');
    }

    /**
     * @route("/manage/rest/version/delete", methods={"DELETE"})
     * @return Response
     */
    public function cmsRestVersionDelete(Request $request)
    {
        $id = $request->get('id');
        $className = $request->get('className');

        $fullClass = ModelService::fullClass($this->connection, $className);
        $version = $fullClass::data($this->connection, [
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
     * @route("/manage/rest/column/sort", methods={"POST"})
     * @return Response
     */
    public function cmsRestColumnSort(Request $request)
    {
        $data = json_decode($request->get('data'));
        $className = $request->get('className');

        $fullClass = ModelService::fullClass($this->connection, $className);
        foreach ($data as $idx => $itm) {
            $orm = $fullClass::getById($this->connection, $itm);
            if ($orm) {
                $orm->setRank($idx);
                $orm->save(1, [
                    'doNotUpdateModified' => 1,
                ]);
                if ($className == '_Model' && ($orm->getIsBuiltIn() != 1 || getenv('ALLOW_CHANGE_BUILTIN') == 1)) {
                    $fullClass::setGenereatedFile($orm, $this->kernel);
                }
            }
        }
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/nestable/sort", methods={"POST"})
     * @return Response
     */
    public function cmsRestNestableSort(Request $request)
    {
        $data = json_decode($request->get('data'));
        $className = $request->get('model');

        $fullClass = ModelService::fullClass($this->connection, $className);
        foreach ($data as $idx => $itm) {
            $orm = $fullClass::getById($this->connection, $itm->id);
            if ($orm) {
                $orm->setRank($itm->rank);
                $orm->setParentId($itm->parentId ?: null);
                $orm->save(true);
            }
        }
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/nestable/closed", methods={"POST"})
     * @return Response
     */
    public function cmsRestNestableClosed(Request $request)
    {
        $id = $request->get('id');
        $closed = $request->get('closed') ?: 0;
        $className = $request->get('model');

        $fullClass = ModelService::fullClass($this->connection, $className);
        $orm = $fullClass::getById($this->connection, $id);
        if (!$orm) {
            throw new NotFoundHttpException();
        }

        $orm->setClosed($closed);
        $orm->save(true);

        return new Response('OK');
    }

    /**
     * @route("/manage/rest/status", methods={"POST"})
     * @return Response
     */
    public function cmsRestStatus(Request $request)
    {
        $status = $request->get('status');
        $id = $request->get('id');
        $className = $request->get('className');

        $fullClass = ModelService::fullClass($this->connection, $className);
        $orm = $fullClass::getById($this->connection, $id);
        if ($orm) {
            $orm->setStatus($status);
            $orm->save(true);
        }
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/delete", methods={"DELETE"})
     * @return Response
     */
    public function cmsRestDelete(Request $request)
    {
        $status = $request->get('status');
        $id = $request->get('id');
        $className = $request->get('className');

        $fullClass = ModelService::fullClass($this->connection, $className);
        $orm = $fullClass::getById($this->connection, $id);
        if ($orm) {
            $orm->delete();
        }
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/shipping/regions", methods={"GET"})
     * @return Response
     */
    public function cmsRestShippingRegions(Request $request)
    {
        $zone = $request->get('zone');

        $fullClass = ModelService::fullClass($this->connection, 'ShippingZone');
        $orms = $fullClass::active($this->connection, [
            'whereSql' => 'm.parentId = ?',
            'params' => [$zone],
            'sort' => 'm.title'
        ]);
        return new JsonResponse($orms);
    }
}
