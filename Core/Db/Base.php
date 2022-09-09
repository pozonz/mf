<?php

namespace MillenniumFalcon\Core\Db;

use Cocur\Slugify\Slugify;
use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Db\Traits\BaseCustomisationTrait;
use MillenniumFalcon\Core\Db\Traits\BaseDefaultTrait;
use MillenniumFalcon\Core\Db\Traits\BaseModelTrait;
use MillenniumFalcon\Core\Db\Traits\BaseORMTrait;
use MillenniumFalcon\Core\Db\Traits\BaseReflectionTrait;
use MillenniumFalcon\Core\Db\Traits\BaseVersionTrait;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Version\VersionInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

abstract class Base implements \JsonSerializable
{
    use BaseCustomisationTrait,
        BaseDefaultTrait,
        BaseModelTrait,
        BaseORMTrait,
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

        $this->uniqid = Uuid::uuid4()->toString();
        $this->rank = 0;
        $this->added = date('Y-m-d H:i:s');
        $this->modified = date('Y-m-d H:i:s');
        $this->status = 1;
        $this->versionUuid = '';
    }

    /**
     * @param $pdo
     */
    static public function sync($pdo)
    {
        $tableName = static::getTableName();

        $response = 0;
        $db = new Sql($pdo, $tableName);
        if (!$db->exists()) {
            $response = 1;
            $db->create();
        }
        
        $syncResponse = $db->sync(static::getFields());
        if ($response === 1) {
            return $response;
        } else {
            return $syncResponse;
        }
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


        $class = new \ReflectionClass(get_called_class());
        $methods = $class->getMethods();

        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (strpos($methodName, 'objJson') === 0) {
                $obj->{$methodName} = $this->$methodName();
            }
        }

        return $obj;
    }

    /**
     * @return mixed
     */
    public function objContent()
    {
        if (!method_exists($this, 'getContent')) {
            return null;
        }
        
        $result = [];
        $objContent = json_decode($this->getContent());
        if ($objContent === null && json_last_error() !== JSON_ERROR_NONE) {
            $objContent = [];
        }

        foreach ($objContent as $itm) {
            $result[$itm->attr] = $itm;
        }
        return $result;
    }
}