<?php

namespace MillenniumFalcon\Core\Orm\Generated;

use MillenniumFalcon\Core\Orm;

class Asset extends Orm
{
    /**
     * @var array
     */
    private $children = array();

    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $description;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $isFolder;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $fileName;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $fileType;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $fileSize;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $fileLocation;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $parentId;
    
    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }
    
    /**
     * @param mixed title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }
    
    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }
    
    /**
     * @param mixed description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
    
    /**
     * @return mixed
     */
    public function getIsFolder()
    {
        return $this->isFolder;
    }
    
    /**
     * @param mixed isFolder
     */
    public function setIsFolder($isFolder)
    {
        $this->isFolder = $isFolder;
    }
    
    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->fileName;
    }
    
    /**
     * @param mixed fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }
    
    /**
     * @return mixed
     */
    public function getFileType()
    {
        return $this->fileType;
    }
    
    /**
     * @param mixed fileType
     */
    public function setFileType($fileType)
    {
        $this->fileType = $fileType;
    }
    
    /**
     * @return mixed
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }
    
    /**
     * @param mixed fileSize
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;
    }
    
    /**
     * @return mixed
     */
    public function getFileLocation()
    {
        return $this->fileLocation;
    }
    
    /**
     * @param mixed fileLocation
     */
    public function setFileLocation($fileLocation)
    {
        $this->fileLocation = $fileLocation;
    }
    
    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parentId;
    }
    
    /**
     * @param mixed parentId
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    }
    
}