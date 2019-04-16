<?php

namespace MillenniumFalcon\Core\Orm\Generated;

use MillenniumFalcon\Core\Orm;

/**
 * Class _Model
 * @package Web\Orm
 */
class _Model extends Orm
{
    /**
     * #pz varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL
     */
    private $title;

    /**
     * #pz varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $className;

    /**
     * #pz varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $namespace;

    /**
     * #pz tinyint(1) DEFAULT NULL
     */
    private $modelType;

    /**
     * #pz tinyint(1) DEFAULT NULL
     */
    private $dataType;

    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $dataGroups;

    /**
     * #pz tinyint(1) DEFAULT NULL
     */
    private $listType;

    /**
     * #pz smallint(6) DEFAULT NULL
     */
    private $numberPerPage;

    /**
     * #pz varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $defaultSortBy;

    /**
     * #pz tinyint(1) DEFAULT NULL
     */
    private $defaultOrder;

    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $columnsJson;

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param mixed $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * @return mixed
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param mixed $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * @return mixed
     */
    public function getModelType()
    {
        return $this->modelType;
    }

    /**
     * @param mixed $modelType
     */
    public function setModelType($modelType)
    {
        $this->modelType = $modelType;
    }

    /**
     * @return mixed
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * @param mixed $dataType
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }

    /**
     * @return mixed
     */
    public function getDataGroups()
    {
        return $this->dataGroups;
    }

    /**
     * @param mixed $dataGroups
     */
    public function setDataGroups($dataGroups)
    {
        $this->dataGroups = $dataGroups;
    }

    /**
     * @return mixed
     */
    public function getListType()
    {
        return $this->listType;
    }

    /**
     * @param mixed $listType
     */
    public function setListType($listType)
    {
        $this->listType = $listType;
    }

    /**
     * @return mixed
     */
    public function getNumberPerPage()
    {
        return $this->numberPerPage;
    }

    /**
     * @param mixed $numberPerPage
     */
    public function setNumberPerPage($numberPerPage)
    {
        $this->numberPerPage = $numberPerPage;
    }

    /**
     * @return mixed
     */
    public function getDefaultSortBy()
    {
        return $this->defaultSortBy;
    }

    /**
     * @param mixed $defaultSortBy
     */
    public function setDefaultSortBy($defaultSortBy)
    {
        $this->defaultSortBy = $defaultSortBy;
    }

    /**
     * @return mixed
     */
    public function getDefaultOrder()
    {
        return $this->defaultOrder;
    }

    /**
     * @param mixed $defaultOrder
     */
    public function setDefaultOrder($defaultOrder)
    {
        $this->defaultOrder = $defaultOrder;
    }

    /**
     * @return mixed
     */
    public function getColumnsJson()
    {
        return $this->columnsJson;
    }

    /**
     * @param mixed $columnsJson
     */
    public function setColumnsJson($columnsJson)
    {
        $this->columnsJson = $columnsJson;
    }

    /**
     * @return null
     */
    public static function getEncodedModel()
    {
        return null;
    }
}