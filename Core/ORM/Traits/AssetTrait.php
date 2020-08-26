<?php

namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\AssetService;
use MillenniumFalcon\Core\Service\UtilsService;

trait AssetTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo)
    {

    }

    /**
     * @return array
     */
    public function getFolderPath()
    {
        $path = [];
        $parent = $this;
        do {
            $path[] = $parent;
            $parent = static::getById($this->getPdo(), $parent->getParentId());
        } while ($parent);
        $path[] = [
            'id' => 0,
            'title' => 'Home',
        ];
        $path = array_reverse($path);
        return $path;
    }

    /**
     * @param bool $doNotSaveVersion
     * @param array $options
     * @return mixed|null
     */
    public function save($doNotSaveVersion = false, $options = [])
    {
        if (!$this->getCode()) {
            do {
                $code = UtilsService::generateHex(4);
                $orm = static::getByField($this->getPdo(), 'code', $code);
            } while ($orm);
            $this->setCode($code);
        }
        return parent::save($doNotSaveVersion, $options);
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        AssetService::removeAssetOrms($this->getPdo(), $this);
        AssetService::removeCaches($this->getPdo(), $this);

        if ($this->getIsFolder()) {
            $children = $this->getChildAssets();
            foreach ($children as $itm) {
                $itm->delete();
            }
        } else {
            AssetService::removeFile($this);
            AssetService::removeAssetBinary($this->getPdo(), $this);
        }

        return parent::delete();
    }

    /**
     * @return array|null
     */
    public function getChildAssets()
    {
        return static::data($this->getPdo(), array(
            'whereSql' => 'm.parentId = ?',
            'params' => array($this->getId())
        ));
    }

    /**
     * @return string
     */
    public function formattedSize()
    {
        $fileSize = $this->getFileSize();
        if ($fileSize > 1000000000000) {
            return number_format($fileSize / 1000000000000, 2);
        } elseif ($fileSize > 1000000000) {
            return number_format($fileSize / 1000000000, 2);
        } elseif ($fileSize > 1000000) {
            return number_format($fileSize / 1000000, 2);
        } elseif ($fileSize > 1000) {
            return number_format($fileSize / 1000, 0);
        } else {
            return $fileSize;
        }
    }

    /**
     * @return string
     */
    public function formattedSizeUnit()
    {
        $fileSize = $this->getFileSize();
        if ($fileSize > 1000000000000) {
            return 'TB';
        } elseif ($fileSize > 1000000000) {
            return 'GB';
        } elseif ($fileSize > 1000000) {
            return 'MB';
        } elseif ($fileSize > 1000) {
            return 'KB';
        } else {
            return 'B';
        }
    }
    
    /**
     * @return mixed
     */
    static public function getCmsOrmsTwig()
    {
        return 'cms/files/files.twig';
    }

    /**
     * @return mixed
     */
    static public function getCmsOrmTwig()
    {
        return 'cms/files/file.twig';
    }
}