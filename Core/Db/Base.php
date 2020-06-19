<?php

namespace MillenniumFalcon\Core\Db;

use Cocur\Slugify\Slugify;
use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Db\Traits\BaseCustomisationTrait;
use MillenniumFalcon\Core\Db\Traits\BaseDefaultTrait;
use MillenniumFalcon\Core\Db\Traits\BaseModelTrait;
use MillenniumFalcon\Core\Db\Traits\BaseQueryTrait;
use MillenniumFalcon\Core\Db\Traits\BaseReflectionTrait;
use MillenniumFalcon\Core\Db\Traits\BaseVersionTrait;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Version\VersionInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class Base implements \JsonSerializable
{
    use BaseCustomisationTrait,
        BaseDefaultTrait,
        BaseModelTrait,
        BaseQueryTrait,
        BaseReflectionTrait,
        BaseVersionTrait;

    /**
     * @var
     */
    protected $_objLastEditedBy;

    /**
     * Orm constructor.
     * @param Connection $pdo
     */
    public function __construct(Connection $pdo)
    {
        $this->pdo = $pdo;

        $this->uniqid = uniqid();
        $this->rank = 0;
        $this->added = date('Y-m-d H:i:s');
        $this->modified = date('Y-m-d H:i:s');
        $this->status = 1;
        $this->versionUuid = '';
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objLastEditedBy()
    {
        if (!$this->_objLastEditedBy) {
            $fullClass = ModelService::fullClass($this->getPdo(), 'User');
            $this->_objLastEditedBy = $fullClass::getById($this->getPdo(), $this->lastEditedBy);
        }
        return $this->_objLastEditedBy;
    }

    /**
     * @return mixed|\stdClass
     */
    public function jsonSerialize()
    {
        $fields = array_keys(static::getFields());

        $obj = new \stdClass();
        foreach ($fields as $field) {
            $getMethod = "get" . ucfirst($field);
            $obj->{$field} = $this->$getMethod();
        }
        return $obj;
    }

    /**
     * @param $pdo
     * @return array|null
     */
    static public function lastRank($pdo)
    {
        $result = static::data($pdo, array(
            'select' => 'm.`rank` AS `rank`',
            'sort' => '`rank`',
            'order' => 'DESC',
            'limit' => 1,
            'oneOrNull' => 1,
            'orm' => 0,
        ));
        return $result['rank'] + 1;
    }
}