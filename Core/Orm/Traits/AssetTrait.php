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
     * @return \stdClass
     */
    public function jsonSerialize()
    {
        $fields = array_keys(static::getFields());

        $obj = new \stdClass();
        foreach ($fields as $field) {
            $getMethod = "get" . ucfirst($field);
            $obj->{$field} = $this->$getMethod();
        }
        $obj->text = $this->getText();
        $obj->state = $this->getState();
        $obj->children = $this->getChildren();
        return $obj;
    }

    /**
     * @param bool $doubleCheckExistence
     * @return mixed
     */
    public function save($doubleCheckExistence = false)
    {
        if (!$this->getId()) {
            do {
                $code = UtilsService::generateHex(4);
                $orm = static::getByField($this->getPdo(), 'code', $code);
            } while ($orm);
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
            $children = $this->getChildAssets();
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
    public function getChildAssets()
    {
        return static::data($this->getPdo(), array(
            'whereSql' => 'm.parentId = ?',
            'params' => array($this->getId())
        ));
    }

    /**
     * @return mixed
     */
    static public function getCmsOrmsTwig()
    {
        return 'cms/files/files.html.twig';
    }

    /**
     * @return mixed
     */
    static public function getCmsOrmTwig()
    {
        return 'cms/files/file.html.twig';
    }
}