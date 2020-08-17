<?php

namespace MillenniumFalcon\Core\Controller\Traits\Cms\Core;

use MillenniumFalcon\Core\Asset\AssetController;
use MillenniumFalcon\Core\Service\AssetService;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\UtilsService;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;


trait CmsCoreRestFileTrait
{
    /**
     * @route("/manage/rest/asset/file/current-folder")
     * @return Response
     */
    public function assetAjaxFilesCurrentFolderSet(Request $request)
    {
        $modelName = $request->get('modelName');
        $attributeName = $request->get('attributeName');
        $ormId = $request->get('ormId');
        $currentAssetId = $request->get('currentAssetId') ?: 0;

        $currentFolderId = 0;

        if ($modelName && $attributeName && $ormId) {
            $fullClass = ModelService::fullClass($this->connection, 'AssetOrm');
            $assetOrm = $fullClass::data($this->connection, array(
                'whereSql' => 'm.modelName = ? AND m.attributeName = ? AND ormId = ?',
                'params' => array($modelName, $attributeName, $ormId),
                'limit' => 1,
                'oneOrNull' => 1,
            ));

            if ($assetOrm) {
                $fullClass = ModelService::fullClass($this->connection, 'Asset');
                $asset = $fullClass::getById($this->connection, $assetOrm->getTitle());
                if ($asset) {
                    $currentFolderId = $asset->getParentId();
                }
            }
        }

        if ($currentAssetId) {
            $fullClass = ModelService::fullClass($this->connection, 'Asset');
            $asset = $fullClass::getById($this->connection, $currentAssetId);
            if (!$asset) {
                $asset = $fullClass::getByField($this->connection, 'code', $currentAssetId);
            }
            if ($asset) {
                $currentFolderId = $asset->getParentId();
            }
        }

        $this->container->get('session')->set('currentFolderId', $currentFolderId);

        return new JsonResponse(array(
            'currentFolderId' => $currentFolderId,
        ));
    }

    /**
     * @route("/manage/rest/asset/files/chosen/rank")
     * @return Response
     */
    public function assetAjaxFilesChosenRank(Request $request)
    {
        $fullClass = ModelService::fullClass($this->connection, 'AssetOrm');
        $modelName = $request->get('modelName');
        $attributeName = $request->get('attributeName');
        $ormId = $request->get('ormId');
        $ids = json_decode($request->get('ids'));
        foreach ($ids as $idx => $id) {
            $orm = $fullClass::data($this->connection, array(
                'whereSql' => 'm.title = ? AND m.modelName = ? AND m.attributeName = ? AND ormId = ?',
                'params' => array($id, $modelName, $attributeName, $ormId),
                'oneOrNull' => 1,
            ));
            if ($orm) {
                $orm->setMyRank($idx);
                $orm->save(true);
            }
        }

        return new JsonResponse($ids);
    }

    /**
     * @route("/manage/rest/asset/files/chosen")
     * @return Response
     */
    public function assetAjaxFilesChosen(Request $request)
    {
        $data = array();

        $fullClass = ModelService::fullClass($this->connection, 'AssetOrm');
        $modelName = $request->get('modelName');
        $attributeName = $request->get('attributeName');
        $ormId = $request->get('ormId');
        if ($modelName && $attributeName && $ormId) {

            $result = $fullClass::data($this->connection, array(
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
    public function assetAjaxFiles(Request $request)
    {
        $keyword = $request->get('keyword') ?: '';
        $currentFolderId = $request->get('currentFolderId') ?: 0;

        $this->container->get('session')->set('currentFolderId', $currentFolderId);
        $fullClass = ModelService::fullClass($this->connection, 'Asset');
        if ($keyword) {
            $data = $fullClass::data($this->connection, array(
                'whereSql' => 'm.isFolder = 0 AND m.title LIKE ?',
                'params' => array("%$keyword%"),
            ));
        } else {
            $data = $fullClass::data($this->connection, array(
                'whereSql' => 'm.isFolder = 0 AND m.parentId = ?',
                'params' => array($currentFolderId),
            ));
        }

        $fullClass = ModelService::fullClass($this->connection, 'AssetOrm');
        $modelName = $request->get('modelName');
        $attributeName = $request->get('attributeName');
        $ormId = $request->get('ormId');
        if ($modelName && $attributeName && $ormId) {
            $assetOrmMap = array();
            $result = $fullClass::data($this->connection, array(
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
    public function assetAjaxFolders(Request $request)
    {
        $currentFolderId = $request->get('currentFolderId') ?: 0;

        $this->container->get('session')->set('currentFolderId', $currentFolderId);
        return new JsonResponse(array(
            'folders' => AssetService::getFolderRoot($this->connection, $currentFolderId),
        ));
    }

    /**
     * @route("/manage/rest/asset/nav")
     * @return Response
     */
    public function assetAjaxNav(Request $request)
    {
        $currentFolderId = $request->get('currentFolderId') ?: 0;

        $fullClass = ModelService::fullClass($this->connection, 'Asset');
        $orm = $fullClass::getById($this->connection, $currentFolderId);
        if (!$orm) {
            $path[] = [
                'id' => 0,
                'title' => 'Home',
            ];
        } else {
            $path = $orm->getFolderPath();
        }
        
        $this->container->get('session')->set('currentFolderId', $currentFolderId);
        return new JsonResponse([
            'currentFolder' => end($path),
            'path' => $path,
        ]);
    }

    /**
     * @route("/manage/rest/asset/files/add/folder")
     * @return Response
     */
    public function assetAjaxAddFolder(Request $request)
    {
        $title = $request->get('title');
        $parentId = $request->get('parentId');

        $fullClass = ModelService::fullClass($this->connection, 'Asset');
        $rank = $fullClass::data($this->connection, array(
            'select' => 'MAX(m.rank) AS max',
            'orm' => 0,
            'whereSql' => 'm.parentId = ?',
            'params' => array($parentId),
            'oneOrNull' => 1,
        ));
        $max = ($rank['max'] ?: 0) + 1;

        $orm = new $fullClass($this->connection);
        $orm->setTitle($title);
        $orm->setParentId($parentId);
        $orm->setRank($max);
        $orm->setIsFolder(1);
        $orm->save(true);
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/asset/files/edit/folder")
     * @return Response
     */
    public function assetAjaxEditFolder(Request $request)
    {
        $fullClass = ModelService::fullClass($this->connection, 'Asset');
        $orm = $fullClass::getById($this->connection, $request->get('id'));
        if (!$orm) {
            throw new NotFoundHttpException();
        }

        $orm->setTitle($request->get('title'));
        $orm->save(true);
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/asset/folders/update")
     * @return Response
     */
    public function assetAjaxFoldersUpdate(Request $request)
    {
        $fullClass = ModelService::fullClass($this->connection, 'Asset');
        $data = json_decode($request->get('data'));
        foreach ($data as $itm) {
            $orm = $fullClass::getById($this->connection, $itm->id);
            $orm->setParentId($itm->parentId);
            $orm->setRank($itm->rank);
            $orm->save(true);
        }
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/asset/file/move")
     * @return Response
     */
    public function assetAjaxFileMove(Request $request)
    {
        $data = json_decode($request->get('data'));

        $fullClass = ModelService::fullClass($this->connection, 'Asset');
        $orm = $fullClass::getById($this->connection, $request->get('id'));
        $orm->setParentId($request->get('parentId'));
        $orm->save(true);
        return new Response('OK');

    }

    /**
     * @route("/manage/rest/asset/files/delete/folder")
     * @return Response
     */
    public function assetAjaxDeleteFolder(Request $request)
    {
        $id = $request->get('id');

        $fullClass = ModelService::fullClass($this->connection, 'Asset');
        $orm = $fullClass::getById($this->connection, $id);
        if ($orm) {
            $orm->delete();
        }

        return new Response('OK');
    }

    /**
     * @route("/manage/rest/asset/files/delete/file")
     * @return Response
     */
    public function assetAjaxDeleteFile(Request $request)
    {
        $id = $request->get('id');

        $fullClass = ModelService::fullClass($this->connection, 'Asset');
        $orm = $fullClass::getById($this->connection, $id);
        if ($orm) {
            $orm->delete();
        }
        return new Response('OK');
    }

    /**
     * @route("/manage/rest/asset/files/get/file")
     * @return Response
     */
    public function assetAjaxGetFile(Request $request)
    {
        $id = $request->get('id');

        $fullClass = ModelService::fullClass($this->connection, 'Asset');

        $orm = $fullClass::getByField($this->connection, 'code', $id);
        if (!$orm) {
            $orm = $fullClass::getById($this->connection, $id);
        }
        return new JsonResponse($orm);
    }

    /**
     * @route("/manage/rest/asset/files/upload")
     * @return Response
     */
    public function assetAjaxUpload(Request $request)
    {
        $file = $files = $request->files->get('file');
        if ($file) {
            return AssetService::processUploadedFile($this->connection, $file);
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
     * @route("/manage/rest/asset/file/size")
     * @return Response
     */
    public function assetAjaxImageSize(Request $request)
    {
        $assetId = $request->get('code');
        $assetSize = $request->get('size');

        $fullClass = ModelService::fullClass($this->connection, 'Asset');
        $asset = $fullClass::getById($this->connection, $assetId);
        if (!$asset) {
            $asset = $fullClass::getByField($this->connection, 'code', $assetId);
        }
        if (!$asset) {
            return new JsonResponse([
                'id' => null,
                'width' => null,
                'height' => null,
                'size' => null,
            ]);
        }

        return new JsonResponse([
            'id' => $asset->getId(),
            'width' => $asset->getWidth(),
            'height' => $asset->getHeight(),
            'size' => $assetSize,
        ]);

    }

    /**
     * @route("/manage/rest/asset/file/crop")
     * @return Response
     */
    public function assetAjaxImageCrop(Request $request)
    {
        $x = $request->get('x');
        $y = $request->get('y');
        $width = $request->get('width');
        $height = $request->get('height');
        $assetId = $request->get('assetId');
        $assetSizeId = $request->get('assetSizeId');

        $fullClass = ModelService::fullClass($this->connection, 'Asset');
        $asset = $fullClass::getById($this->connection, $assetId);
        if (!$asset) {
            $asset = $fullClass::getByField($this->connection, 'code', $assetId);
        }
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        $fullClass = ModelService::fullClass($this->connection, 'AssetSize');
        $assetSize = $fullClass::getById($this->connection, $assetSizeId);
        if (!$assetSize) {
            throw new NotFoundHttpException();
        }

        $fullClass = ModelService::fullClass($this->connection, 'AssetCrop');
        $orm = $fullClass::data($this->connection, array(
            'whereSql' => 'm.assetId = ? AND m.assetSizeId = ?',
            'params' => array($asset->getId(), $assetSizeId),
            'limit' => 1,
            'oneOrNull' => 1,
        ));
        if (!$orm) {
            $orm = new $fullClass($this->connection);
            $orm->setTitle(($asset ? $asset->getCode() : '') . ' - ' . $assetSize->getTitle());
        }

        AssetService::removeCache($asset, $assetSize);

        $orm->setX($x);
        $orm->setY($y);
        $orm->setWidth($width);
        $orm->setHeight($height);
        $orm->setAssetId($asset->getId());
        $orm->setAssetSizeId($assetSizeId);
        $orm->save(true);
        return new Response('OK');

    }

    /**
     * @route("/manage/rest/asset/folders/file/select")
     * @return Response
     */
    public function assetAjaxFoldersFileSelect(Request $request)
    {
        $addOrDelete = $request->get('addOrDelete') ?: 0;
        $ids = $request->get('id');

        $modelName = $request->get('modelName');
        $ormId = $request->get('ormId');
        $attributeName = $request->get('attributeName');

        $fullClass = ModelService::fullClass($this->connection, 'AssetOrm');
        if ($addOrDelete == 0) {
            foreach ($ids as $id) {
                $assetOrms = $fullClass::data($this->connection, array(
                    'whereSql' => 'm.title = ? AND m.modelName = ? AND m.attributeName = ? AND ormId = ?',
                    'params' => array($id, $modelName, $attributeName, $ormId),
                ));
                foreach ($assetOrms as $assetOrm) {
                    $assetOrm->delete();
                }
            }
        } elseif ($addOrDelete == 1) {

            foreach ($ids as $id) {
                $assetOrm = $fullClass::data($this->connection, array(
                    'whereSql' => 'm.title = ? AND m.modelName = ? AND m.attributeName = ? AND ormId = ?',
                    'params' => array($id, $modelName, $attributeName, $ormId),
                    'oneOrNull' => 1,
                ));
                if (!$assetOrm) {
                    $assetOrm = new $fullClass($this->connection);
                    $assetOrm->setTitle($id);
                    $assetOrm->setModelName($modelName);
                    $assetOrm->setAttributeName($attributeName);
                    $assetOrm->setOrmId($ormId);
                    $assetOrm->setMyRank(999);
                    $assetOrm->save(true);
                }
            }
        } elseif ($addOrDelete == 2) {

            $assetOrms = $fullClass::data($this->connection, array(
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