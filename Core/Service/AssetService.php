<?php

namespace MillenniumFalcon\Core\Service;

use BlueM\Tree;
use BlueM\Tree\Serializer\HierarchicalTreeJsonSerializer;
use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\ORM\_Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\MimeTypes;

class AssetService
{
    const FOLDER_OPEN_MAX_LIMIT = 10;
    protected Connection $connection;
    protected static string $uploadPath  = __DIR__ . '/../../../../../uploads/';
    protected static string $templateFilePath = __DIR__ . '/../../Resources/files/';
    protected static string $imageCachePath = __DIR__ . '/../../../../../cache/image/';

    public function __construct(
        Connection $connection,
    ) {
        $this->connection = $connection;
    }


    /**
     * @param $folderRef
     */
    public function getGallery($folderRef, $options = [])
    {
        $fullClass = ModelService::fullClass($this->connection, 'Asset');
        $folder = $fullClass::data($this->connection, array(
            'whereSql' => 'm.isFolder = 1 AND m.title = ?',
            'params' => [$folderRef],
            'limit' => 1,
            'oneOrNull' => 1,
        ));
        if (!$folder) {
            $folder = $fullClass::data($this->connection, array(
                'whereSql' => 'm.isFolder = 1 AND m.title = ?',
                'params' => [$folderRef],
                'limit' => 1,
                'oneOrNull' => 1,
            ));
        }

        if ($folder) {
            return $fullClass::data($this->connection, array_merge([
                'whereSql' => '(m.isFolder != 1 OR m.isFolder IS NULL) AND m.parentId = ?',
                'params' => [$folder->getId()]
            ], $options));
        }

        return [];
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
            'buildwarningcallback' => function () {
            },
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

        $allowedMimeTypes = [
            'image/png',
            'image/jpeg',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation  ',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/pdf',
            'audio/mpeg',
            'audio/wav',
            'video/x-msvideo',
            'video/mp4',
            'video/mpeg',
        ];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new \Exception('Mime type now allowed.');
        }

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
    static public function processUploadedFileWithAsset(Connection $pdo, UploadedFile $file, $orm): JsonResponse
    {
        static::removeFile($orm);
        static::removeCaches($pdo, $orm);

        $originalName = $file->getClientOriginalName();

        // do not trust this fucker, it's a bad time. determine the extension from the file type
        // $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $ee = $file->getExtension();

        $type = $file->getMimeType();
        $ext = current(MimeTypes::getDefault()->getExtensions($type)) ?? 'dat';

        $orm->setFileName($originalName);
        $orm->setFileType($file->getMimeType());
        $orm->setFileSize($file->getSize());
        $orm->setFileExtension($ext);
        $orm->setIsImage(0);
        $orm->setWidth(null);
        $orm->setHeight(null);

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
            $command = getenv('CONVERT_CMD') . ' ' . escapeshellarg($chkFile) . ' -auto-orient ' . escapeshellarg($fnlFile);
            static::generateOutput($command);
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
    static public function removeAssetOrms($pdo, $asset): void
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
    static public function removeFile($asset): void
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
    static public function removeAssetBinary($pdo, $asset): void
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
    static public function removeCaches($pdo, $asset): void
    {
        $fullClass = ModelService::fullClass($pdo, 'AssetSize');
        $assetSizes = $fullClass::data($pdo);
        foreach ($assetSizes as $assetSize) {
            static::removeCache($asset, $assetSize);
        }
    }

    /**
     * @param $pdo
     * @param $asset
     * @throws \Exception
     */
    static public function removeCachesByAssetSize($pdo, $assetSize): void
    {
        $fullClass = ModelService::fullClass($pdo, 'Asset');
        $assets = $fullClass::data($pdo, [
            'whereSql' => 'm.isFolder != 1 OR m.isFolder IS NULL',
        ]);
        foreach ($assets as $asset) {
            static::removeCache($asset, $assetSize);
        }
    }

    /**
     * @param $asset
     * @param $assetSize
     */
    static public function removeCache($asset, $assetSize): void
    {
        $ext = $asset->getFileExtension();

        $cachedFolder = AssetService::getImageCachePath();
        $cachedKey = AssetService::getCacheKey($asset, $assetSize->getCode());
        $cachedFile = "{$cachedFolder}{$cachedKey}.{$ext}";
        if (file_exists($cachedFile)) {
            unlink($cachedFile);
        }

        $cachedFile = "{$cachedFolder}{$cachedKey}.{$ext}.txt";

        if (file_exists($cachedFile)) {
            unlink($cachedFile);
        }

        if (strtolower($ext) == 'gif') {
            $ext = "jpg";
        }

        $cachedFile = "{$cachedFolder}{$cachedKey}.{$ext}.webp";
        if (file_exists($cachedFile)) {
            unlink($cachedFile);
        }

        $cachedFile = "{$cachedFolder}{$cachedKey}.{$ext}.webp.txt";
        if (file_exists($cachedFile)) {
            unlink($cachedFile);
        }
    }

    /**
     * @param $asset
     * @param $assetSize
     * @return string
     */
    static public function getCacheKey($asset, $assetSizeCode): string
    {
        return "{$asset->getCode()}-{$assetSizeCode}";
    }

    static public function getUploadPath(): string
    {
        return static::$uploadPath;
    }

    static public function setUploadPath(string $uploadPath): void
    {
        static::$uploadPath = $uploadPath;
    }

    static public function getImageCachePath(): string
    {
        return static::$imageCachePath;
    }

    static public function setImageCachePath(string $imageCachePath): void
    {
        static::$imageCachePath = $imageCachePath;
    }

    static public function getTemplateFilePath(): string
    {
        return static::$templateFilePath;
    }

    static public function setTemplateFilePath(string $templateFilePath): void
    {
        static::$templateFilePath = $templateFilePath;
    }
}
