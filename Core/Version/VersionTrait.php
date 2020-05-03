<?php

namespace MillenniumFalcon\Core\Version;

use Ramsey\Uuid\Uuid;

trait VersionTrait
{
    /**
     * @return mixed
     */
    protected function getCurrentVersion()
    {
        $orm = static::getById($this->getPdo(), $this->getId());
        if ($orm) {
            $orm->setVersionId($this->getId());
            $orm->setVersionUuid(Uuid::uuid4());
            $orm->setId(null);
            $orm->setUniqid(uniqid());
            $orm->setAdded($this->getModified());
            return $orm;
        }
        return null;
    }

    /**
     * @return bool
     */
    public function canBeRestored()
    {
        return true;
    }

    /**
     * @return mixed
     */
    public function saveVersion()
    {
        $orm = $this->getCurrentVersion();
        if ($orm) {
            $orm->save(true);
        }
        return $orm;
    }

    /**
     * @param $versionUuid
     * @return mixed
     */
    public function getByVersionUuid($versionUuid)
    {
        $orm = static::data($this->getPdo(), [
            'whereSql' => 'm.versionUuid = ?',
            'params' => [$versionUuid],
            'limit' => 1,
            'oneOrNull' => 1,
            'includePreviousVersion' => 1,
        ]);
        $orm->setVersionId($this->getVersionId());
        $orm->setVersionUuid($this->getVersionUuid());
        $orm->setId($this->getId());
        $orm->setUniqid($this->getUniqid());
        $orm->setAdded($this->getAdded());
        $orm->setModified($this->getModified());
        return $orm;
    }

    /**
     * @return $this
     */
    public function savePreview()
    {
        $this->setVersionId(0);
        $this->setVersionUuid(Uuid::uuid4());
        $this->setId(null);
        $this->setUniqid(uniqid());
        $this->setAdded(date('Y-m-d H:i:s'));
        $this->save(true);
        return $this;
    }
}