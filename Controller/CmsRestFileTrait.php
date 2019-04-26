<?php

namespace MillenniumFalcon\Controller;


use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Orm\Asset;
use MillenniumFalcon\Core\Orm\AssetOrm;
use MillenniumFalcon\Core\Orm\Page;
use MillenniumFalcon\Core\Orm\PageCategory;
use MillenniumFalcon\Core\Nestable\AssetNode;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\Twig\Extension;
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
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $request = Request::createFromGlobals();
        $modelName = $request->get('modelName');
        $attributeName = $request->get('attributeName');
        $ormId = $request->get('ormId');
        $ids = json_decode($request->get('ids'));
        foreach ($ids as $idx => $id) {

            /** @var AssetOrm $orm */
            $orm = AssetOrm::data($pdo, array(
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

        $request = Request::createFromGlobals();
        $modelName = $request->get('modelName');
        $attributeName = $request->get('attributeName');
        $ormId = $request->get('ormId');
        if ($modelName && $attributeName && $ormId) {

            /** @var AssetOrm[] $result */
            $result = AssetOrm::data($pdo, array(
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

        if ($keyword) {
            $data = Asset::data($pdo, array(
                'whereSql' => 'm.isFolder = 0 AND m.title LIKE ?',
                'params' => array("%$keyword%"),
            ));
        } else {
            $data = Asset::data($pdo, array(
                'whereSql' => 'm.isFolder = 0 AND m.parentId = ?',
                'params' => array($currentFolderId),
            ));
        }

        $modelName = $request->get('modelName');
        $attributeName = $request->get('attributeName');
        $ormId = $request->get('ormId');
        if ($modelName && $attributeName && $ormId) {
            $assetOrmMap = array();
            /** @var AssetOrm[] $result */
            $result = AssetOrm::data($pdo, array(
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

        if ($addOrDelete == 0) {
            foreach ($ids as $id) {
                $assetOrms = AssetOrm::data($pdo, array(
                    'whereSql' => 'm.title = ? AND m.modelName = ? AND m.attributeName = ? AND ormId = ?',
                    'params' => array($id, $modelName, $attributeName, $ormId),
                ));
                foreach ($assetOrms as $assetOrm) {
                    $assetOrm->delete();
                }
            }
        } elseif ($addOrDelete == 1) {

            foreach ($ids as $id) {
                $assetOrm = AssetOrm::data($pdo, array(
                    'whereSql' => 'm.title = ? AND m.modelName = ? AND m.attributeName = ? AND ormId = ?',
                    'params' => array($id, $modelName, $attributeName, $ormId),
                    'oneOrNull' => 1,
                ));
                if (!$assetOrm) {
                    $assetOrm = new AssetOrm($pdo);
                    $assetOrm->setTitle($id);
                    $assetOrm->setModelName($modelName);
                    $assetOrm->setAttributeName($attributeName);
                    $assetOrm->setOrmId($ormId);
                    $assetOrm->setMyRank(999);
                    $assetOrm->save();
                }
            }
        }

        elseif ($addOrDelete == 2) {

            $assetOrms = AssetOrm::data($pdo, array(
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

        $rank = Asset::data($pdo, array(
            'select' => 'MAX(m.rank) AS max',
            'orm' => 0,
            'whereSql' => 'm.parentId = ?',
            'params' => array($request->get('__parentId')),
            'oneOrNull' => 1,
        ));
        $max = ($rank['max'] ?: 0) + 1;

        $orm = new Asset($pdo);
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

        /** @var Asset $orm */
        $orm = Asset::getById($pdo, $request->get('id'));
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

        $request = Request::createFromGlobals();
        $data = json_decode($request->get('data'));
        foreach ($data as $itm) {
            /** @var Asset $orm */
            $orm = Asset::getById($pdo, $itm->id);
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

        /** @var Asset $orm */
        $orm = Asset::getById($pdo, $request->get('id'));
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

        $orm = Asset::getById($pdo, $id);
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

        /** @var Asset $orm */
        $orm = Asset::getById($pdo, $id);
        if (!$orm) {
            throw new NotFoundHttpException();
        }
        if (file_exists($this->container->getParameter('kernel.project_dir') . '/uploads/' . $orm->getFileLocation())) {
            unlink($this->container->getParameter('kernel.project_dir') . '/uploads/' . $orm->getFileLocation());
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

        $files = $request->files->get('files');
        if ($files && is_array($files) && count($files) > 0) {
            $originalName = $files[0]->getClientOriginalName();
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);

            $rank = Asset::data($pdo, array(
                'select' => 'MIN(m.rank) AS min',
                'orm' => 0,
                'whereSql' => 'm.parentId = ?',
                'params' => array($request->get('parentId')),
                'oneOrNull' => 1,
            ));
            $min = $rank['min'] - 1;

            $orm = new Asset($pdo);
            $orm->setIsFolder(0);
            $orm->setParentId($request->get('parentId'));
            $orm->setRank($min);
            $orm->setTitle($originalName);
            $orm->setFileName($originalName);
            $orm->save();

            require_once $this->container->getParameter('kernel.project_dir') . '/vendor/blueimp/jquery-file-upload/server/php/UploadHandler.php';
            $uploader = new \UploadHandler(array(
                'upload_dir' => $this->container->getParameter('kernel.project_dir') . '/uploads/',
                'image_versions' => array()
            ), false);
            $_SERVER['HTTP_CONTENT_DISPOSITION'] = $orm->getId();
            $result = $uploader->post(false);

            $orm->setFileLocation($orm->getId() . '.' . $ext);
            $orm->setFileType($result['files'][0]->type);
            $orm->setFileSize($result['files'][0]->size);
            $orm->save();

            if (file_exists($this->container->getParameter('kernel.project_dir') . '/uploads/' . $result['files'][0]->name)) {
                rename($this->container->getParameter('kernel.project_dir') . '/uploads/' . $result['files'][0]->name, dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/' . $orm->getId() . '.' . $ext);
            }

            return new JsonResponse($orm);
        }
        return new Response(json_encode(array(
            'failed'
        )));
    }

    /**
     * @param \PDO $pdo
     * @param Asset $orm
     */
    private function deleteFolder(\PDO $pdo, Asset $orm)
    {
        /** @var Asset[] $children */
        $children = Asset::data($pdo, array(
            'whereSql' => 'm.parentId = ?',
            'params' => array($orm->getId())
        ));
        foreach ($children as $itm) {
            $this->deleteFolder($pdo, $itm);
        }
        if (!$orm->getIsFolder()) {
            if (file_exists($this->container->getParameter('kernel.project_dir') . '/uploads/' . $orm->getFileLocation())) {
                unlink($this->container->getParameter('kernel.project_dir') . '/uploads/' . $orm->getFileLocation());
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

        /** @var Asset[] $data */
        $data = Asset::data($pdo, array('whereSql' => 'm.isFolder = 1'));
        foreach ($data as $itm) {
            if (!isset($childrenCount[$itm->getParentId()])) {
                $childrenCount[$itm->getParentId()] = 0;
            }
            $childrenCount[$itm->getParentId()]++;

            $nodes[] = new AssetNode($itm->getId(), $itm->getParentId() ?: 0, $itm->getRank(), 1,$itm->getTitle() ?: 'Home', $baseurl . $itm->getId(), array('opened' => true, 'selected' => $currentFolderId == $itm->getId()));
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