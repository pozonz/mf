<?php

namespace MillenniumFalcon\Core;

use Cocur\Slugify\Slugify;
use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Service\ModelService;

abstract class Orm implements \JsonSerializable
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
     * #pz varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL
     */
    private $uniqid;

    /**
     * #pz varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL
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
    private $metaDescirption;

    /**
     * #pz varchar(256) COLLATE utf8mb4_unicode_ci NULL
     */
    private $ogTitle;

    /**
     * #pz varchar(256) COLLATE utf8mb4_unicode_ci NULL
     */
    private $ogDescirption;

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
    }

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
    public function setPdo(Connection $pdo): void
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
     * @return array|null
     */
    public function getModel() {
        $rc = static::getReflectionClass();
        return _Model::getByField($this->getPdo(), 'className', $rc->getShortName());
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        $rc = static::getReflectionClass();
        $fullClass = ModelService::fullClass($this->getPdo(), 'AssetOrm');
        $result = $fullClass::data($this->getPdo(), array(
            'whereSql' => 'm.modelName = ? AND m.ormId = ?',
            'params' => array($rc->getShortName(), $this->getUniqid()),
        ));
        foreach ($result as $itm) {
            $itm->delete();
        }
        $tableName = static::getTableName();
        $sql = "DELETE FROM `{$tableName}` WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(array($this->getId()));
    }

    /**
     * @return mixed
     */
    public function save($doubleCheckExistence = false)
    {
        $tableName = static::getTableName();
        $fields = array_keys(static::getFields());

        if (method_exists($this, 'getTitle')) {
            $slugify = new Slugify(['trim' => false]);
            $this->setSlug($slugify->slugify($this->getTitle()));
        }
        $this->setModified(date('Y-m-d H:i:s'));

        $notFound = 0;
        if ($this->getId() && $doubleCheckExistence) {
            $orm = static::getById($this->getPdo(), $this->getId());
            if (!$orm) {
                $notFound = 1;
            }
        }

        $sql = '';
        $params = array();
        if (!$this->getId() || $notFound) {
            $sql = "INSERT INTO `{$tableName}` ";
            $part1 = '(';
            $part2 = ' VALUES (';
            foreach ($fields as $field) {
                if ($field == 'id') {
//                    continue;
                }

                $part1 .= "`$field`, ";
                $part2 .= "?, ";
                $method = 'get' . ucfirst($field);
                $params[] = $this->$method();
            }
            $part1 = rtrim($part1, ', ') . ')';
            $part2 = rtrim($part2, ', ') . ')';
            $sql = $sql . $part1 . $part2;
//            var_dump('<pre>', $sql, $params, '</pre>');exit;
        } else {
            $sql = "UPDATE `{$tableName}` SET ";
            foreach ($fields as $field) {
                if ($field == 'id') {
                    continue;
                }
                $sql .= "`$field` = ?, ";
                $method = 'get' . ucfirst($field);
                $params[] = $this->$method();
            }
            $sql = rtrim($sql, ', ') . ' WHERE id = ?';
            $params[] = $this->id;
        }

        try {
//            var_dump($params, $sql);exit;
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            if (!$this->getId()) {
                $this->setId($this->pdo->lastInsertId());
            }
            return $this->getId();
        } catch (\Exception $ex) {
            var_dump($ex->getMessage());
            exit;
        }

        return null;

    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
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
     * @return mixed
     */
    static public function getCmsOrmsTwig() {
        return null;
    }

    /**
     * @return mixed
     */
    static public function getCmsOrmTwig() {
        return null;
    }

    /**
     * @param $pdo
     * @return array|null
     */
    static public function lastRank($pdo)
    {
        $result = static::data($pdo, array(
            'select' => 'm.rank AS rank',
            'sort' => 'rank',
            'order' => 'DESC',
            'limit' => 1,
            'oneOrNull' => 1,
            'orm' => 0,
        ));
        return $result['rank'] + 1;
    }

    /**
     * @param $pdo
     */
    static public function sync($pdo)
    {
        $tableName = static::getTableName();

        $db = new Db($pdo, $tableName);
        $db->create();
        $db->sync(static::getFields());
    }

    /**
     * @return array
     */
    static public function getFields()
    {
        $result = array();
        $rc = static::getReflectionClass();
        do {
            $result = array_merge($rc->getProperties(), $result);
            $rc = $rc->getParentClass();
        } while ($rc);
        return static::propertiesToFields($result);
    }

    /**
     * @return array
     */
    static public function getParentFields()
    {
        $rc = new \ReflectionClass(__CLASS__);
        return static::propertiesToFields($rc->getProperties());
    }

    /**
     * @param $properties
     * @return array
     */
    static public function propertiesToFields($properties)
    {
        $result = array();
        foreach ($properties as $property) {
            $comment = $property->getDocComment();
            preg_match('/#pz(\ )+(.*)/', $comment, $matches);
            if (count($matches) == 3) {
                $result[$property->getName()] = $matches[2];
            }
        }
        return $result;
    }

    /**
     * @param Connection $pdo
     * @param $id
     * @return array|null
     */
    static public function getByField(Connection $pdo, $field, $value)
    {
        return static::data($pdo, array(
            'whereSql' => "m.$field = ?",
            'params' => array($value),
            'oneOrNull' => 1,
        ));
    }

    /**
     * @param Connection $pdo
     * @param $id
     * @return array|null
     */
    static public function getById(Connection $pdo, $id)
    {
        return static::getByField($pdo, 'id', $id);
    }

    /**
     * @param Connection $pdo
     * @param $slug
     * @return array|null
     */
    static public function getBySlug(Connection $pdo, $slug)
    {
        return static::getByField($pdo, 'slug', $slug);
    }

    /**
     * @param $pdo
     * @param array $options
     * @return array|null
     */
    static public function active($pdo, $options = array())
    {
        if (isset($options['whereSql'])) {
            $options['whereSql'] .= ($options['whereSql'] ? ' AND ' : '') . 'm.status = 1';
        } else {
            $options['whereSql'] = 'm.status = 1';
        }
        return static::data($pdo, $options);
    }

    /**
     * @param Connection $pdo
     * @param array $options
     * @return array|null
     */
    static public function data(Connection $pdo, $options = array())
    {
        $options['select'] = isset($options['select']) && !empty($options['select']) ? $options['select'] : 'm.*';
        $options['joins'] = isset($options['joins']) && !empty($options['joins']) ? $options['joins'] : null;
        $options['whereSql'] = isset($options['whereSql']) && !empty($options['whereSql']) ? "({$options['whereSql']})" : null;
        $options['params'] = isset($options['params']) && gettype($options['params']) == 'array' && count($options['params']) ? $options['params'] : [];
        $options['sort'] = isset($options['sort']) && !empty($options['sort']) ? $options['sort'] : 'm.rank';
        $options['order'] = isset($options['order']) && !empty($options['order']) ? $options['order'] : 'ASC';
        $options['groupby'] = isset($options['groupby']) && !empty($options['groupby']) ? $options['groupby'] : null;
        $options['page'] = isset($options['page']) ? $options['page'] : 1;
        $options['limit'] = isset($options['limit']) ? $options['limit'] : 0;
        $options['orm'] = isset($options['orm']) ? $options['orm'] : 1;
        $options['debug'] = isset($options['debug']) ? $options['debug'] : 0;
        $options['idArray'] = isset($options['idArray']) ? $options['idArray'] : 0;

        $options['oneOrNull'] = isset($options['oneOrNull']) ? $options['oneOrNull'] == true : false;
        if ($options['oneOrNull']) {
            $options['limit'] = 1;
            $options['page'] = 1;
        }

        $options['count'] = isset($options['count']) ? $options['count'] == true : false;
        if ($options['count']) {
            $options['orm'] = false;
            $options['oneOrNull'] = true;
            $options['select'] = 'COUNT(*) AS count';
            $options['page'] = null;
            $options['limit'] = null;
        }

        $myClass = get_called_class();
        $tableName = static::getTableName();
        $fields = array_keys(static::getFields());

        $sql = "SELECT {$options['select']} FROM `{$tableName}` AS m";
        $sql .= $options['joins'] ? ' ' . $options['joins'] : '';
        $sql .= $options['whereSql'] ? ' WHERE ' . $options['whereSql'] : '';
        $sql .= $options['groupby'] ? ' GROUP BY ' . $options['groupby'] : '';
        if ($options['sort']) {
            $sql .= " ORDER BY {$options['sort']} {$options['order']}";
        }
        if ($options['limit'] && $options['page']) {
            $sql .= " LIMIT " . (($options['page'] - 1) * $options['limit']) . ", " . $options['limit'];
        }

        if ($options['debug']) {
            while (@ob_end_clean()) ;
            var_dump($sql, $options['params']);
            exit;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($options['params']);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if ($options['orm']) {
            $orms = array();
            foreach ($result as $itm) {
                $orm = new $myClass($pdo);
                foreach ($fields as $field) {
                    if (isset($itm[$field])) {
                        $method = 'set' . ucfirst($field);
                        $orm->$method($itm[$field]);
                    }
                }
                if ($options['idArray']) {
                    $orms[$orm->getId()] = $orm;
                } else {
                    $orms[] = $orm;
                }
            }
            $result = $orms;
        }

        if ($options['oneOrNull']) {
            $result = reset($result) ?: null;
        }

        return $result;
    }

    /**
     * @return \ReflectionClass
     */
    static public function getReflectionClass()
    {
        return new \ReflectionClass(get_called_class());
    }

    /**
     * @return string
     */
    static public function getTableName()
    {
        $rc = static::getReflectionClass();
        $slugify = new Slugify(['trim' => false]);
        return $slugify->slugify($rc->getShortName(), '_');
    }

    /**
     * @return null|_Model
     */
    static public function updateModel($pdo)
    {
        $encodedModel = static::getEncodedModel();
        if (gettype($encodedModel) == 'string') {
            $decodedModel = json_decode($encodedModel);
            $model = new _Model($pdo);
            foreach ($decodedModel as $idx => $itm) {
                $setMethod = "set" . ucfirst($idx);
                $model->$setMethod($itm);
            }
            $model->setPdo($pdo);
            $model->save(true);
        }
        return null;
    }

    /**
     * @param $model
     * @return string
     */
    static public function encodedModel($model)
    {
        $fields = array_keys(_Model::getFields());

        $obj = new \stdClass();
        foreach ($fields as $field) {
            $getMethod = "get" . ucfirst($field);
            $obj->{$field} = $model->$getMethod();
        }
        return json_encode($obj, JSON_PRETTY_PRINT);
    }

    /**
     * @return bool|string
     */
    static public function getEncodedModel()
    {
        $rc = static::getReflectionClass();
        return file_get_contents(dirname($rc->getFileName()) . '/CmsConfig/' . $rc->getShortName() . '.json');
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
    public function getMetaDescirption()
    {
        return $this->metaDescirption;
    }

    /**
     * @param mixed $metaDescirption
     */
    public function setMetaDescirption($metaDescirption)
    {
        $this->metaDescirption = $metaDescirption;
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
    public function getOgDescirption()
    {
        return $this->ogDescirption;
    }

    /**
     * @param mixed $ogDescirption
     */
    public function setOgDescirption($ogDescirption)
    {
        $this->ogDescirption = $ogDescirption;
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
}