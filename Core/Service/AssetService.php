<?php

namespace MillenniumFalcon\Core\Service;

use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\Orm\_Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AssetService
{
    const FOLDER_OPEN_MAX_LIMIT = 10;

    /**
     * DbService constructor.
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function __construct(\Doctrine\DBAL\Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return \Pz\Router\InterfaceNode
     */
    public function getRoot() {
        return static::getFolderRoot($this->connection->getWrappedConnection(), 0);
    }

    /**
     * @param $currentFolderId
     * @return \Pz\Router\InterfaceNode
     */
    static public function getFolderRoot($pdo, $currentFolderId)
    {
        $childrenCount = array();
        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $data = $fullClass::data($pdo, array('whereSql' => 'm.isFolder = 1'));
        foreach ($data as $itm) {
            if (!isset($childrenCount[$itm->getParentId()])) {
                $childrenCount[$itm->getParentId()] = 0;
            }
            $childrenCount[$itm->getParentId()]++;
        }

        foreach ($data as &$itm) {
            if ($itm->getId() == $currentFolderId) {
                $itm->setStateValue('opened', true);
                $itm->setStateValue('selected', true);
            } else {
                if (isset($childrenCount[$itm->getId()]) && $childrenCount[$itm->getId()] <= static::FOLDER_OPEN_MAX_LIMIT) {
                    $itm->setStateValue('opened', true);
                } else {
                    $itm->setStateValue('opened', false);
                }
            }
        }

        $assetRoot = static::getAssetRoot($pdo, $currentFolderId);
        $tree = new Tree($data);
        return $tree->getRootFromNode($assetRoot);
    }

    /**
     * @param $pdo
     * @param $currentFolderId
     * @return mixed
     * @throws \Exception
     */
    static public function getAssetRoot($pdo, $currentFolderId) {
        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $assetRoot = new $fullClass($pdo);
        $assetRoot->setTitle('Home');
        $assetRoot->setId('0');
        $assetRoot->setParentId(null);
        $assetRoot->setState(array('opened' => true, 'selected' => $currentFolderId == "0" ? true : false));
        return $assetRoot;
    }

    /**
     * @param \PDO $pdo
     * @param UploadedFile $file
     * @return JsonResponse
     * @throws \Exception
     */
    static public function processUploadedFile(\PDO $pdo, UploadedFile $file)
    {
        $request = Request::createFromGlobals();
        $parentId = $request->request->get('parentId') ?: 0;

        $originalName = $file->getClientOriginalName();

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
        $orm->setTitle($originalName);
        $orm->setIsFolder(0);
        $orm->setParentId($parentId);
        $orm->setRank($min);
        $orm->save();

        return static::processUploadedFileWithAsset($pdo, $file, $orm);
    }

    /**
     * @param \PDO $pdo
     * @param UploadedFile $file
     * @param $orm
     * @return JsonResponse
     */
    static public function processUploadedFileWithAsset(\PDO $pdo, UploadedFile $file, $orm)
    {
        static::removeFile($orm);
        static::removeCaches($pdo, $orm);

        $originalName = $file->getClientOriginalName();
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);

        $orm->setFileName($originalName);
        $orm->setFileType($file->getMimeType());
        $orm->setFileSize($file->getSize());
        $orm->setFileExtension($file->getClientOriginalExtension());
        $orm->setIsImage(0);
        $orm->setWidth(null);
        $orm->setHeight(null);

        $file->move(AssetService::getImageCachePath());
        $tmpFile = AssetService::getImageCachePath() . $file->getFilename();
        $chkFile = $tmpFile . '.' . $ext;

        rename($tmpFile, $chkFile);
        $info = getimagesize($chkFile);
        if ($info !== false) {
            list($x, $y) = $info;
            $orm->setIsImage(1);
            $orm->setWidth($x);
            $orm->setHeight($y);
        }

        $fnlFile = AssetService::getUploadPath() . $orm->getId() . '.' . $ext;
        if ($orm->getIsImage() == 1) {
            $command = getenv('CONVERT_CMD') . ' ' . $chkFile . ' -auto-orient ' . $fnlFile;
            static::generateOutput($command);
            unlink($chkFile);
        } else {
            rename($chkFile, $fnlFile);
        }

        $orm->setFileLocation($orm->getId() . '.' . $ext);
        $orm->save();

        return new JsonResponse(array(
            'status' => 1,
            'orm' => $orm,
        ));
    }

    /**
     * @param $command
     * @param string $in
     * @param null $out
     * @return int
     */
    static public function generateOutput($command, &$in = '', &$out = null)
    {
        $logFolder = AssetService::getImageCachePath();
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("file", $logFolder . 'error-output.txt', 'a') // stderr is a file to write to
        );

        $returnValue = -999;

        $process = proc_open($command, $descriptorspec, $pipes);
        if (is_resource($process)) {

            fwrite($pipes[0], $in);
            fclose($pipes[0]);

            $out = "";
            //read the output
            while (!feof($pipes[1])) {
                $out .= fgets($pipes[1], 4096);
            }
            fclose($pipes[1]);
            $returnValue = proc_close($process);
        }

        return $returnValue;
    }

    /**
     * @param $pdo
     * @param $asset
     * @throws \Exception
     */
    static public function removeAssetOrms($pdo, $asset) {
        $fullClass = ModelService::fullClass($pdo, 'AssetOrm');
        $assetOrms = $fullClass::data($pdo, array(
            'whereSql' => 'm.title = ?',
            'params' => array($asset->getId()),
        ));
        foreach ($assetOrms as $assetOrm) {
            $assetOrm->delete();
        }
    }

    /**
     * @param $asset
     */
    static public function removeFile($asset) {
        $link = static::getUploadPath() . $asset->getFileLocation();
        if (file_exists($link) && is_file($link)) {
            unlink($link);
        }
    }

    /**
     * @param $pdo
     * @param $asset
     * @throws \Exception
     */
    static public function removeCaches($pdo, $asset) {
        $fullClass = ModelService::fullClass($pdo, 'AssetSize');
        $assetSizes = $fullClass::data($pdo);
        foreach ($assetSizes as $assetSize) {
            static::removeCache($asset, $assetSize);
        }
    }

    /**
     * @param $asset
     * @param $assetSize
     */
    static public function removeCache($asset, $assetSize) {
        $cachedFolder = AssetService::getImageCachePath();
        $cachedKey = AssetService::getCacheKey($asset, $assetSize);
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
     * @param $asset
     * @param $assetSize
     * @return string
     */
    static public function getCacheKey($asset, $assetSize) {
        return "{$asset->getCode()}-{$assetSize->getCode()}-{$asset->getId()}-{$assetSize->getId()}";
    }

    /**
     * @return string
     */
    static public function getUploadPath() {
        return __DIR__ . '/../../../../../uploads/';
    }

    /**
     * @return string
     */
    static public function getImageCachePath() {
        return __DIR__ . '/../../../../../cache/image/';
    }
}