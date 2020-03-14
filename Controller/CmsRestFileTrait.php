<?php

namespace MillenniumFalcon\Controller;

use MillenniumFalcon\Core\Asset\AssetController;
use MillenniumFalcon\Core\Nestable\AssetNode;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\Service\AssetService;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\UtilsService;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;


trait CmsRestFileTrait
{
    /**
     * @route("/manage/rest/asset/files/chosen/rank")
     * @return Response
     */
    public function assetAjaxFilesChosenRank()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $fullClass = ModelService::fullClass($pdo, 'AssetOrm');
        $request = Request::createFromGlobals();
        $modelName = $request->get('modelName');
        $attributeName = $request->get('attributeName');
        $ormId = $request->get('ormId');
        $ids = json_decode($request->get('ids'));
        foreach ($ids as $idx => $id) {
            $orm = $fullClass::data($pdo, array(
                'whereSql' => 'm.title = ? AND m.modelName = ? AND m.attributeName = ? AND ormId = ?',
                'params' => array($id, $modelName, $attributeName, $ormId),
                'oneOrNull' => 1,
            ));
            if ($orm) {
                $orm->setMyRank($idx);
                $orm->save();
            }
        }

        return new JsonResponse($ids);
    }

    /**
     * @route("/manage/rest/asset/files/chosen")
     * @return Response
     */
    public function assetAjaxFilesChosen()
    {
        $data = array();

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $fullClass = ModelService::fullClass($pdo, 'AssetOrm');
        $request = Request::createFromGlobals();
        $modelName = $request->get('modelName');
        $attributeName = $request->get('attributeName');
        $ormId = $request->get('ormId');
        if ($modelName && $attributeName && $ormId) {

            $result = $fullClass::data($pdo, array(
                'whereSql' => 'm.modelName = ? AND m.attributeName = ? AND ormId = ?',
                'params' => array($modelName, $attributeName, $ormId),
                'sort' => 'm.myRank',
            ));

            foreach ($result as $itm) {
                $data[] = $itm->objAsset();
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @route("/manage/rest/asset/files")
     * @return Response
     */
    public function assetAjaxFiles()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();

        $keyword = $request->get('keyword') ?: '';
        $currentFolderId = $request->get('currentFolderId') ?: 0;

        $this->container->get('session')->set('currentFolderId', $currentFolderId);
        $fullClass = ModelService::fullClass($pdo, 'Asset');
        if ($keyword) {
            $data = $fullClass::data($pdo, array(
                'whereSql' => 'm.isFolder = 0 AND m.title LIKE ?',
                'params' => array("%$keyword%"),
            ));
        } else {
            $data = $fullClass::data($pdo, array(
                'whereSql' => 'm.isFolder = 0 AND m.parentId = ?',
                'params' => array($currentFolderId),
            ));
        }

        $fullClass = ModelService::fullClass($pdo, 'AssetOrm');
        $modelName = $request->get('modelName');
        $attributeName = $request->get('attributeName');
        $ormId = $request->get('ormId');
        if ($modelName && $attributeName && $ormId) {
            $assetOrmMap = array();
            $result = $fullClass::data($pdo, array(
                'whereSql' => 'm.modelName = ? AND m.attributeName = ? AND ormId = ?',
                'params' => array($modelName, $attributeName, $ormId),
            ));
            foreach ($result as $itm) {
                $assetOrmMap[$itm->getTitle()] = 1;
            }

            foreach ($data as &$itm) {
                $itm = json_decode(json_encode($itm));
                $itm->_selected = isset($assetOrmMap[$itm->id]) ? 1 : 0;
            }
        }


        return new JsonResponse(array(
            'files' => $data,
        ));
    }

    /**
     * @route("/manage/rest/asset/folders")
     * @return Response
     */
    public function assetAjaxFolders()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $currentFolderId = $request->get('currentFolderId') ?: 0;

        $this->container->get('session')->set('currentFolderId', $currentFolderId);
        return new JsonResponse(array(
            'folders' => AssetService::getFolderRoot($pdo, $currentFolderId),
        ));
    }

    /**
     * @route("/manage/rest/asset/nav")
     * @return Response
     */
    public function assetAjaxNav()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $currentFolderId = $request->get('currentFolderId') ?: 0;

        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $orm = $fullClass::getById($pdo, $currentFolderId);

        $this->container->get('session')->set('currentFolderId', $currentFolderId);
        if ($currentFolderId == 0) {
            $path = [];
        } else {
            if ($orm) {
                $path = $orm->getFolderPath();
            } else {
                $path = [];
            }
        }

        return new JsonResponse([
            'currentFolder' => end($path),
            'path' => $path,
        ]);
    }

    /**
     * @route("/manage/rest/asset/files/add/folder")
     * @return Response
     */
    public function assetAjaxAddFolder()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $title = $request->get('title');
        $parentId = $request->get('parentId');

        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $rank = $fullClass::data($pdo, array(
            'select' => 'MAX(m.rank) AS max',
            'orm' => 0,
            'whereSql' => 'm.parentId = ?',
            'params' => array($parentId),
            'oneOrNull' => 1,
        ));
        $max = ($rank['max'] ?: 0) + 1;

        $orm = new $fullClass($pdo);
        $orm->setTitle($title);
        $orm->setParentId($parentId);
        $orm->setRank($max);
        $orm->setIsFolder(1);
        $orm->save();
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/asset/files/edit/folder")
     * @return Response
     */
    public function assetAjaxEditFolder()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();

        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $orm = $fullClass::getById($pdo, $request->get('id'));
        if (!$orm) {
            throw new NotFoundHttpException();
        }

        $orm->setTitle($request->get('title'));
        $orm->save();
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/asset/folders/update")
     * @return Response
     */
    public function assetAjaxFoldersUpdate()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $request = Request::createFromGlobals();
        $data = json_decode($request->get('data'));
        foreach ($data as $itm) {
            $orm = $fullClass::getById($pdo, $itm->id);
            $orm->setParentId($itm->parentId);
            $orm->setRank($itm->rank);
            $orm->save();
        }
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/asset/file/move")
     * @return Response
     */
    public function assetAjaxFileMove()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $data = json_decode($request->get('data'));

        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $orm = $fullClass::getById($pdo, $request->get('id'));
        $orm->setParentId($request->get('parentId'));
        $orm->save();
        return new Response('OK');

    }

    /**
     * @route("/manage/rest/asset/files/delete/folder")
     * @return Response
     */
    public function assetAjaxDeleteFolder()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $orm = $fullClass::getById($pdo, $id);
        if ($orm) {
            $orm->delete();
        }

        return new Response('OK');
    }

    /**
     * @route("/manage/rest/asset/files/delete/file")
     * @return Response
     */
    public function assetAjaxDeleteFile()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $orm = $fullClass::getById($pdo, $id);
        if ($orm) {
            $orm->delete();
        }
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/asset/files/get/file")
     * @return Response
     */
    public function assetAjaxGetFile()
    {
        $request = Request::createFromGlobals();
        $id = $request->get('id');

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $orm = $fullClass::getById($pdo, $id);
        return new JsonResponse($orm);
    }

    /**
     * @route("/manage/rest/asset/files/upload")
     * @return Response
     */
    public function assetAjaxUpload()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $file = $files = $request->files->get('file');
        if ($file) {
            return AssetService::processUploadedFile($pdo, $file);
        }

        return new JsonResponse(array(
            'status' => 0,
            'orm' => array(
                'title' => 'Error Occurred',
                'code' => 'Oops'
            ),
        ));
    }

    /**
     * @route("/manage/rest/asset/file/crop")
     * @return Response
     */
    public function assetAjaxImageCrop()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $x = $request->get('x');
        $y = $request->get('y');
        $width = $request->get('width');
        $height = $request->get('height');
        $assetId = $request->get('assetId');
        $assetSizeId = $request->get('assetSizeId');

        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $asset = $fullClass::getById($pdo, $assetId);
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        $fullClass = ModelService::fullClass($pdo, 'AssetSize');
        $assetSize = $fullClass::getById($pdo, $assetSizeId);
        if (!$assetSize) {
            throw new NotFoundHttpException();
        }

        $fullClass = ModelService::fullClass($pdo, 'AssetCrop');
        $orm = $fullClass::data($pdo, array(
            'whereSql' => 'm.assetId = ? AND m.assetSizeId = ?',
            'params' => array($assetId, $assetSizeId),
            'limit' => 1,
            'oneOrNull' => 1,
        ));
        if (!$orm) {
            $orm = new $fullClass($pdo);
            $orm->setTitle(($asset ? $asset->getCode() : '') . ' - ' . $assetSize->getTitle());
        }

        AssetService::removeCache($asset, $assetSize);

        $orm->setX($x);
        $orm->setY($y);
        $orm->setWidth($width);
        $orm->setHeight($height);
        $orm->setAssetId($assetId);
        $orm->setAssetSizeId($assetSizeId);
        $orm->save();
        return new Response('OK');

    }

    /**
     * @route("/manage/rest/asset/folders/file/select")
     * @return Response
     */
    public function assetAjaxFoldersFileSelect()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $request = Request::createFromGlobals();
        $addOrDelete = $request->get('addOrDelete') ?: 0;
        $ids = $request->get('id');

        $modelName = $request->get('modelName');
        $ormId = $request->get('ormId');
        $attributeName = $request->get('attributeName');

        $fullClass = ModelService::fullClass($pdo, 'AssetOrm');
        if ($addOrDelete == 0) {
            foreach ($ids as $id) {
                $assetOrms = $fullClass::data($pdo, array(
                    'whereSql' => 'm.title = ? AND m.modelName = ? AND m.attributeName = ? AND ormId = ?',
                    'params' => array($id, $modelName, $attributeName, $ormId),
                ));
                foreach ($assetOrms as $assetOrm) {
                    $assetOrm->delete();
                }
            }
        } elseif ($addOrDelete == 1) {

            foreach ($ids as $id) {
                $assetOrm = $fullClass::data($pdo, array(
                    'whereSql' => 'm.title = ? AND m.modelName = ? AND m.attributeName = ? AND ormId = ?',
                    'params' => array($id, $modelName, $attributeName, $ormId),
                    'oneOrNull' => 1,
                ));
                if (!$assetOrm) {
                    $assetOrm = new $fullClass($pdo);
                    $assetOrm->setTitle($id);
                    $assetOrm->setModelName($modelName);
                    $assetOrm->setAttributeName($attributeName);
                    $assetOrm->setOrmId($ormId);
                    $assetOrm->setMyRank(999);
                    $assetOrm->save();
                }
            }
        } elseif ($addOrDelete == 2) {

            $assetOrms = $fullClass::data($pdo, array(
                'whereSql' => 'm.modelName = ? AND m.attributeName = ? AND ormId = ?',
                'params' => array($modelName, $attributeName, $ormId),
//                'debug' => 1,
            ));

            foreach ($assetOrms as $assetOrm) {
                $assetOrm->delete();
            }
        }

        return new JsonResponse($ids);
    }
}