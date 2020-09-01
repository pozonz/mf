<?php

namespace MillenniumFalcon\Core\Service;

use BlueM\Tree;
use BlueM\Tree\Serializer\HierarchicalTreeJsonSerializer;
use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\ORM\_Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AssetService
{
    const FOLDER_OPEN_MAX_LIMIT = 10;

    /**
     * AssetService constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return \Pz\Router\InterfaceNode
     */
    public function getRoot()
    {
        return static::getFolderRoot($this->connection, 0);
    }

    /**
     * @param $currentFolderId
     * @return \Pz\Router\InterfaceNode
     */
    static public function getFolderRoot($pdo, $currentFolderId)
    {
        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $data = $fullClass::data($pdo, array(
            "select" => 'm.id AS id, m.parentId AS parent, m.title AS text',
            'whereSql' => 'm.isFolder = 1',
            "sort" => 'm.rank',
            "order" => 'ASC',
            "orm" => 0,
        ));
        foreach ($data as &$itm) {
            if ($itm['id'] == $currentFolderId) {
                $itm['state'] = [
                    'opened' => true,
                    'selected' => true,
                ];
            }
        }

        $tree = new Tree($data, [
            'rootId' => 0,
            'jsonserializer' => new HierarchicalTreeJsonSerializer(),
            'buildwarningcallback' => function () {},
        ]);

        $assetRoot = static::getAssetFolderRoot();
        $assetRoot->children = $tree->jsonSerialize();
        return $assetRoot;
    }

    /**
     * @return \stdClass
     */
    static public function getAssetFolderRoot()
    {
        $assetRoot = new \stdClass();
        $assetRoot->id = '0';
        $assetRoot->title = 'Home';
        $assetRoot->text = 'Home';
        $assetRoot->closed = 0;
        $assetRoot->status = 1;
        $assetRoot->state = [
            'opened' => true,
            'selected' => false,
        ];
        return $assetRoot;
    }

    /**
     * @param $pdo
     * @param $currentFolderId
     * @return mixed
     * @throws \Exception
     */
    static public function getAssetRoot($pdo, $currentFolderId)
    {
        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $assetRoot = new $fullClass($pdo);
        $assetRoot->setTitle('Home');
        $assetRoot->setId('0');
        $assetRoot->setParentId(null);
        $assetRoot->setState(array('opened' => true, 'selected' => $currentFolderId == "0" ? true : false));
        return $assetRoot;
    }

    /**
     * @param Connection $pdo
     * @param UploadedFile $file
     * @return JsonResponse
     * @throws \Exception
     */
    static public function processUploadedFile(Connection $pdo, UploadedFile $file)
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
     * @param Connection $pdo
     * @param UploadedFile $file
     * @param $orm
     * @return JsonResponse
     */
    static public function processUploadedFileWithAsset(Connection $pdo, UploadedFile $file, $orm)
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

//        $file->move(AssetService::getImageCachePath());
//        $tmpFile = AssetService::getImageCachePath() . $file->getFilename();
//        $chkFile = $tmpFile . '.' . $ext;
//        rename($tmpFile, $chkFile);

        $chkFile = $file->getPathName();

        $info = getimagesize($chkFile);
        if ($info !== false) {
            list($x, $y) = $info;
            $orm->setIsImage(1);
            $orm->setWidth($x);
            $orm->setHeight($y);
        }

        $uploadedDir = static::getUploadPath();
        if (!file_exists($uploadedDir)) {
            mkdir($uploadedDir, 0777, true);
        }

        $fnlFile = $uploadedDir . $orm->getId() . '.' . $ext;
        if ($orm->getIsImage() == 1) {
            $command = getenv('CONVERT_CMD') . ' "' . $chkFile . '" -auto-orient ' . $fnlFile;
            static::generateOutput($command);
//            unlink($chkFile);
        } else {
            copy($chkFile, $fnlFile);
        }

        $orm->setFileLocation($orm->getId() . '.' . $ext);
        $orm->save();

        $SAVE_ASSETS_TO_DB = getenv('SAVE_ASSETS_TO_DB');
        if ($SAVE_ASSETS_TO_DB) {
            $fileLocation = $uploadedDir . $orm->getFileLocation();
            if (file_exists($fileLocation)) {
                $content = file_get_contents($fileLocation);

                $assetBinaryFullClass = ModelService::fullClass($pdo, 'AssetBinary');
                $assetBinary = $assetBinaryFullClass::getByField($pdo, 'title', $orm->getId());
                if (!$assetBinary) {
                    $assetBinary = new $assetBinaryFullClass($pdo);
                    $assetBinary->setTitle($orm->getId());
                }
                $assetBinary->setContent($content);
                $assetBinary->save();

                static::removeFile($orm);
                static::removeCaches($pdo, $orm);
            }
        }

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
    static public function removeAssetOrms($pdo, $asset)
    {
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
    static public function removeFile($asset)
    {
        $link = static::getUploadPath() . $asset->getFileLocation();
        if (file_exists($link) && is_file($link)) {
            unlink($link);
        }
    }

    /**
     * @param $pdo
     * @param $asset
     */
    static public function removeAssetBinary($pdo, $asset)
    {
        $SAVE_ASSETS_TO_DB = getenv('SAVE_ASSETS_TO_DB');
        if ($SAVE_ASSETS_TO_DB) {
            $assetBinaryFullClass = ModelService::fullClass($pdo, 'AssetBinary');
            $assetBinary = $assetBinaryFullClass::getByField($pdo, 'title', $asset->getId());
            if ($assetBinary) {
                $assetBinary->delete();
            }
        }
    }

    /**
     * @param $pdo
     * @param $asset
     * @throws \Exception
     */
    static public function removeCaches($pdo, $asset)
    {
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
    static public function removeCache($asset, $assetSize)
    {
        $cachedFolder = AssetService::getImageCachePath();
        $cachedKey = AssetService::getCacheKey($asset, $assetSize->getCode());
        $cachedFile = "{$cachedFolder}{$cachedKey}.{$asset->getFileExtension()}";
        if (file_exists($cachedFile)) {
            unlink($cachedFile);
        }
        $cachedFile = "{$cachedFolder}{$cachedKey}.webp";
        if (file_exists($cachedFile)) {
            unlink($cachedFile);
        }
    }

    /**
     * @param $asset
     * @param $assetSize
     * @return string
     */
    static public function getCacheKey($asset, $assetSizeCode)
    {
        return "{$asset->getCode()}-{$assetSizeCode}";
    }

    /**
     * @return string
     */
    static public function getUploadPath()
    {
        return __DIR__ . '/../../../../../uploads/';
    }

    /**
     * @return string
     */
    static public function getImageCachePath()
    {
        return __DIR__ . '/../../../../../cache/image/';
    }

    /**
     * @return string
     */
    static public function getTemplateFilePath()
    {
        return __DIR__ . '/../../Resources/files/';
    }
}