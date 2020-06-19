<?php

namespace MillenniumFalcon\Core\Db\Traits;

use Doctrine\DBAL\Connection;

trait BaseDefaultTrait
{
    /**
     * @var Connection
     */
    private $pdo;

    /**
     * #pz int(11) NOT NULL AUTO_INCREMENT
     */
    private $id;

    /**
     * #pz varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL
     */
    private $uniqid;

    /**
     * #pz varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL
     */
    private $slug;

    /**
     * #pz int(11) NOT NULL DEFAULT 0
     */
    private $rank;

    /**
     * #pz datetime NULL
     */
    private $added;

    /**
     * #pz datetime NULL
     */
    private $modified;

    /**
     * #pz datetime NULL
     */
    private $publishFrom;

    /**
     * #pz datetime NULL
     */
    private $publishTo;

    /**
     * #pz varchar(256) COLLATE utf8mb4_unicode_ci NULL
     */
    private $metaTitle;

    /**
     * #pz varchar(256) COLLATE utf8mb4_unicode_ci NULL
     */
    private $metaDescription;

    /**
     * #pz varchar(256) COLLATE utf8mb4_unicode_ci NULL
     */
    private $ogTitle;

    /**
     * #pz varchar(256) COLLATE utf8mb4_unicode_ci NULL
     */
    private $ogDescription;

    /**
     * #pz varchar(256) COLLATE utf8mb4_unicode_ci NULL
     */
    private $ogImage;

    /**
     * #pz int(11) NULL DEFAULT 0
     */
    private $lastEditedBy;

    /**
     * #pz tinyint(1) NULL DEFAULT 0
     */
    private $status;

    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $parentId;

    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $closed;

    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $extraInfo;

    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $siteMapUrl;

    /**
     * #pz varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL
     */
    private $versionUuid;

    /**
     * #pz int(11) DEFAULT NULL
     */
    private $versionId;

    /**
     * @return Connection
     */
    public function getPdo(): Connection
    {
        return $this->pdo;
    }

    /**
     * @param Connection $pdo
     */
    public function setPdo(Connection $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getUniqid()
    {
        return $this->uniqid;
    }

    /**
     * @param mixed $uniqid
     */
    public function setUniqid($uniqid)
    {
        $this->uniqid = $uniqid;
    }

    /**
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param mixed $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return mixed
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * @param mixed $rank
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
    }

    /**
     * @return mixed
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @param mixed $added
     */
    public function setAdded($added)
    {
        $this->added = $added;
    }

    /**
     * @return mixed
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @param mixed $modified
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
    }

    /**
     * @return mixed
     */
    public function getPublishFrom()
    {
        return $this->publishFrom;
    }

    /**
     * @param mixed $publishFrom
     */
    public function setPublishFrom($publishFrom)
    {
        $this->publishFrom = $publishFrom;
    }

    /**
     * @return mixed
     */
    public function getPublishTo()
    {
        return $this->publishTo;
    }

    /**
     * @param mixed $publishTo
     */
    public function setPublishTo($publishTo)
    {
        $this->publishTo = $publishTo;
    }

    /**
     * @return mixed
     */
    public function getMetaTitle()
    {
        return $this->metaTitle;
    }

    /**
     * @param mixed $metaTitle
     */
    public function setMetaTitle($metaTitle)
    {
        $this->metaTitle = $metaTitle;
    }

    /**
     * @return mixed
     */
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     * @param mixed $metaDescription
     */
    public function setMetaDescription($metaDescription)
    {
        $this->metaDescription = $metaDescription;
    }

    /**
     * @return mixed
     */
    public function getOgTitle()
    {
        return $this->ogTitle;
    }

    /**
     * @param mixed $ogTitle
     */
    public function setOgTitle($ogTitle)
    {
        $this->ogTitle = $ogTitle;
    }

    /**
     * @return mixed
     */
    public function getOgDescription()
    {
        return $this->ogDescription;
    }

    /**
     * @param mixed $ogDescription
     */
    public function setOgDescription($ogDescription)
    {
        $this->ogDescription = $ogDescription;
    }

    /**
     * @return mixed
     */
    public function getOgImage()
    {
        return $this->ogImage;
    }

    /**
     * @param mixed $ogImage
     */
    public function setOgImage($ogImage)
    {
        $this->ogImage = $ogImage;
    }

    /**
     * @return mixed
     */
    public function getLastEditedBy()
    {
        return $this->lastEditedBy;
    }

    /**
     * @param mixed $lastEditedBy
     */
    public function setLastEditedBy($lastEditedBy)
    {
        $this->lastEditedBy = $lastEditedBy;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param mixed $parentId
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    }

    /**
     * @return mixed
     */
    public function getClosed()
    {
        return $this->closed;
    }

    /**
     * @param mixed $closed
     */
    public function setClosed($closed)
    {
        $this->closed = $closed;
    }

    /**
     * @return mixed
     */
    public function getExtraInfo()
    {
        return $this->extraInfo;
    }

    /**
     * @param mixed $extraInfo
     */
    public function setExtraInfo($extraInfo)
    {
        $this->extraInfo = $extraInfo;
    }

    /**
     * @return mixed
     */
    public function getSiteMapUrl()
    {
        return $this->siteMapUrl;
    }

    /**
     * @param mixed $siteMapUrl
     */
    public function setSiteMapUrl($siteMapUrl)
    {
        $this->siteMapUrl = $siteMapUrl;
    }

    /**
     * @return mixed
     */
    public function getVersionUuid()
    {
        return $this->versionUuid;
    }

    /**
     * @param mixed $versionUuid
     */
    public function setVersionUuid($versionUuid)
    {
        $this->versionUuid = $versionUuid;
    }

    /**
     * @return mixed
     */
    public function getVersionId()
    {
        return $this->versionId;
    }

    /**
     * @param mixed $versionId
     */
    public function setVersionId($versionId)
    {
        $this->versionId = $versionId;
    }
}