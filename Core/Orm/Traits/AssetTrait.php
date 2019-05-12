<?php
//Last updated: 2019-04-30 11:33:32
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Orm\AssetOrm;
use MillenniumFalcon\Core\Service\AssetService;
use MillenniumFalcon\Core\Service\UtilsService;

trait AssetTrait
{
    /**
     * @param bool $doubleCheckExistence
     * @return mixed
     */
    public function save($doubleCheckExistence = false) {
        if (!$this->getId()) {
            do {
                $code = UtilsService::generateHex(4);
                $orm = static::getByField($this->getPdo(), 'code', $code);
            } while($orm);
            $this->setCode($code);
        }
        return parent::save($doubleCheckExistence);
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        AssetService::removeAssetOrms($this->getPdo(), $this);
        AssetService::removeCaches($this->getPdo(), $this);

        if ($this->getIsFolder()) {
            $children = $this->getChildren();
            foreach ($children as $itm) {
                $itm->delete();
            }
        } else {
            AssetService::removeFile($this);
        }

        return parent::delete();
    }

    /**
     * @return array|null
     */
    public function getChildren() {
        return static::data($this->getPdo(), array(
            'whereSql' => 'm.parentId = ?',
            'params' => array($this->getId())
        ));
    }

    /**
     * @return string
     */
    public function formattedSize() {
        $fileSize = $this->getFileSize();
        if ($fileSize > 1000000000000) {
            return number_format($fileSize / 1000000000000, 2);
        } elseif ($fileSize > 1000000000) {
            return number_format($fileSize / 1000000000, 2);
        } elseif ($fileSize > 1000000) {
            return number_format($fileSize / 1000000, 2);
        } elseif ($fileSize > 1000) {
            return number_format($fileSize / 1000, 0);
        }
    }

    /**
     * @return string
     */
    public function formattedSizeUnit() {
        $fileSize = $this->getFileSize();
        if ($fileSize > 1000000000000) {
            return 'TB';
        } elseif ($fileSize > 1000000000) {
            return 'GB';
        } elseif ($fileSize > 1000000) {
            return 'MB';
        } elseif ($fileSize > 1000) {
            return 'KB';
        }
    }

    /**
     * @return mixed
     */
    static public function getCmsOrmsTwig() {
        return 'cms/files/files.html.twig';
    }

    /**
     * @return mixed
     */
    static public function getCmsOrmTwig() {
        return 'cms/files/file.html.twig';
    }
}