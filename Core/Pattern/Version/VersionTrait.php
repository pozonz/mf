<?php

namespace MillenniumFalcon\Core\Pattern\Version;

use Ramsey\Uuid\Uuid;

trait VersionTrait
{
    /**
     * @return bool
     */
    public function canBeRestored()
    {
        return true;
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
        if ($orm) {
            $orm->setVersionId($this->getVersionId());
            $orm->setVersionUuid($this->getVersionUuid());
            $orm->setId($this->getId());
            $orm->setUniqid($this->getUniqid());
            $orm->setAdded($this->getAdded());
            $orm->setModified($this->getModified());
            $orm->setStatus($this->getStatus());
        }
        return $orm;
    }

    /**
     * @return mixed
     */
    protected function getCurrentVersion()
    {
        $orm = static::data($this->getPdo(), [
            'whereSql' => 'm.id = ?',
            'params' => [$this->getId()],
            'limit' => 1,
            'oneOrNull' => 1,
            'includePreviousVersion' => 1,
        ]);

        if ($orm) {
            $orm->setVersionId($this->getId());
            $orm->setVersionUuid(Uuid::uuid4());
            $orm->setId(null);
            $orm->setUniqid(Uuid::uuid4());
            $orm->setAdded($this->getModified());
            return $orm;
        }
        return $orm;
    }

    /**
     * @return mixed|null
     */
    public function saveVersion()
    {
        $orm = $this->getCurrentVersion();
        if ($orm) {
            $orm->setIsDraft(0);
            $orm->save(true, [
                'doNotUpdateModified' => true,
            ]);
        }
        return $orm;
    }

    /**
     * @return mixed|null
     */
    public function savePreview()
    {
        $this->setVersionId(0);
        $this->setVersionUuid(Uuid::uuid4());
        $this->setId(null);
        $this->setUniqid(Uuid::uuid4());
        $this->setAdded(date('Y-m-d H:i:s'));
        $this->setStatus(1);
        $this->save(true);
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function saveDraft()
    {
        if (!$this->getId()) {
            $this->save(true);
        }

        $this->setVersionId($this->getId());
        $this->setVersionUuid(Uuid::uuid4());
        $this->setId(null);
        $this->setUniqid(Uuid::uuid4());
        $this->setAdded(date('Y-m-d H:i:s'));
        $this->save(true);
        return $this;
    }
}