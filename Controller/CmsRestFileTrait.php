<?php

namespace MillenniumFalcon\Controller;

use MillenniumFalcon\Core\Asset\AssetController;
use MillenniumFalcon\Core\Nestable\AssetNode;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\UtilsService;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

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

        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

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
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

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
        $request = Request::createFromGlobals();
        $currentFolderId = $request->get('currentFolderId') ?: 0;
        $this->container->get('session')->set('currentFolderId', $currentFolderId);

        $root = $this->getFolderRoot($currentFolderId);

        return new JsonResponse(array(
            'folders' => $root,
        ));
    }

    /**
     * @route("/manage/rest/asset/folders/file/select")
     * @return Response
     */
    public function assetAjaxFoldersFileSelect()
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

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

    /**
     * @route("/manage/rest/asset/nav")
     * @return Response
     */
    public function assetAjaxNav()
    {
        $request = Request::createFromGlobals();
        $currentFolderId = $request->get('currentFolderId') ?: 0;
        $this->container->get('session')->set('currentFolderId', $currentFolderId);

        $root = $this->getFolderRoot($currentFolderId);
        $path = $root->path($currentFolderId);

        return new JsonResponse(array(
            'currentFolder' => end($path),
            'path' => $path,
        ));
    }

    /**
     * @route("/manage/rest/asset/files/add/folder")
     * @return Response
     */
    public function assetAjaxAddFolder()
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

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
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

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

        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

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
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

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

        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $orm = $fullClass::getById($pdo, $id);
        if ($orm) {
            $this->deleteFolder($pdo, $orm);
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

        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $orm = $fullClass::getById($pdo, $id);
        if (!$orm) {
            throw new NotFoundHttpException();
        }
        if (file_exists($this->getUploadedPath() . $orm->getFileLocation())) {
            unlink($this->getUploadedPath() . $orm->getFileLocation());
        }
        $orm->delete();
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/asset/files/upload")
     * @return Response
     */
    public function assetAjaxUpload()
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $request = Request::createFromGlobals();
        $file = $files = $request->files->get('file');
        if ($file) {
            return $this->processUploadedFile($pdo, $file);
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
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

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

        $this->removeCache($asset, $assetSize);

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
     * @param $asset
     * @param $assetSize
     */
    private function removeCache($asset, $assetSize) {
        $cachedFolder = AssetController::getImageCachePath();
        $cachedKey = AssetController::getCacheKey($asset, $assetSize);
        $cachedFile =  "{$cachedFolder}{$cachedKey}.{$asset->getFileExtension()}";
        if (file_exists($cachedFile)) {
            unlink($cachedFile);
        }
        $cachedFile = "{$cachedFolder}webp-{$cachedKey}.webp";
        if (file_exists($cachedFile)) {
            unlink($cachedFile);
        }
    }

    /**
     * @param \PDO $pdo
     * @param UploadedFile $file
     * @return JsonResponse
     */
    private function processUploadedFile(\PDO $pdo, UploadedFile $file)
    {
        $request = Request::createFromGlobals();

        $originalName = $file->getClientOriginalName();
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);

        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $rank = $fullClass::data($pdo, array(
            'select' => 'MIN(m.rank) AS min',
            'orm' => 0,
            'whereSql' => 'm.parentId = ?',
            'params' => array($request->get('parentId')),
            'oneOrNull' => 1,
        ));
        $min = $rank['min'] - 1;

        $orm = new $fullClass($pdo);
        $orm->setIsFolder(0);
        $orm->setParentId($request->request->get('parentId'));
        $orm->setRank($min);
        $orm->setTitle($originalName);
        $orm->setFileName($originalName);
        $orm->setFileType($file->getMimeType());
        $orm->setFileSize($file->getSize());
        $orm->setFileExtension($file->getClientOriginalExtension());
        $orm->save();

        $file->move(AssetController::getUploadPath());
        if (file_exists(AssetController::getUploadPath() . $file->getFilename())) {
            rename(AssetController::getUploadPath() . $file->getFilename(), AssetController::getUploadPath() . $orm->getId() . '.' . $ext);
        }

        $info = getimagesize(AssetController::getUploadPath() . $orm->getId() . '.' . $ext);
        if ($info === false) {
            $orm->setIsImage(0);
        } else {
            list($x, $y) = $info;
            $orm->setIsImage(1);
            $orm->setWidth($x);
            $orm->setHeight($y);
        }

        $orm->setFileLocation($orm->getId() . '.' . $ext);
        $orm->save();

        return new JsonResponse(array(
            'status' => 1,
            'orm' => $orm,
        ));
    }

    /**
     * @param \PDO $pdo
     * @param $orm
     */
    private function deleteFolder(\PDO $pdo, $orm)
    {
        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $children = $fullClass::data($pdo, array(
            'whereSql' => 'm.parentId = ?',
            'params' => array($orm->getId())
        ));
        foreach ($children as $itm) {
            $this->deleteFolder($pdo, $itm);
        }
        if (!$orm->getIsFolder()) {
            if (file_exists(AssetController::getUploadPath() . $orm->getFileLocation())) {
                unlink(AssetController::getUploadPath() . $orm->getFileLocation());
            }
        }
        $orm->delete();
    }

    /**
     * @param $currentFolderId
     * @return \Pz\Router\InterfaceNode
     */
    private function getFolderRoot($currentFolderId)
    {
        $folderOpenMaxLimit = 10;

        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $baseurl = '/pz/files?currentFolderId=';
        $childrenCount = array();
        $nodes = array();

        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $data = $fullClass::data($pdo, array('whereSql' => 'm.isFolder = 1'));
        foreach ($data as $itm) {
            if (!isset($childrenCount[$itm->getParentId()])) {
                $childrenCount[$itm->getParentId()] = 0;
            }
            $childrenCount[$itm->getParentId()]++;

            $nodes[] = new AssetNode($itm->getId(), $itm->getParentId() ?: 0, $itm->getRank(), 1, $itm->getTitle() ?: 'Home', $baseurl . $itm->getId(), array('opened' => true, 'selected' => $currentFolderId == $itm->getId()));
        }

        /** @var AssetNode[] $nodes */
        foreach ($nodes as &$itm) {
            if (isset($childrenCount[$itm->getId()]) && $childrenCount[$itm->getId()] >= $folderOpenMaxLimit && $itm->getId() != $currentFolderId) {
                $itm->setStateValue('opened', false);
            }
        }
        $tree = new Tree($nodes);
        $root = $tree->getRootFromNode(new AssetNode("0", null, 0, 1, 'Home', $baseurl . 0, array('opened' => true, 'selected' => $currentFolderId == "0" ? true : false)));

        return $root;
    }
}