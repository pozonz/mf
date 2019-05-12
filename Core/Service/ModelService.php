<?php

namespace MillenniumFalcon\Core\Service;

use MillenniumFalcon\Core\Orm\_Model;

class ModelService
{
    /**
     * DbService constructor.
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function __construct(\Doctrine\DBAL\Connection $connection)
    {
        $this->connection = $connection;
    }

//    /**
//     * @param $className
//     * @return mixed
//     */
//    public function create($className, $uniqId = null)
//    {
//        if (!$uniqId) {
//            $uniqId = uniqid();
//        }
//
//        $pdo = $this->connection->getWrappedConnection();
//        $fullClassName = static::fullClass($pdo, $className);
//        $orm = new $fullClassName($pdo);
//        $orm->setUniqid($uniqId);
//        return $orm;
//    }

    /**
     * @param $className
     * @param $field
     * @param $value
     * @return mixed
     */
    public function getByField($className, $field, $value)
    {
        return $this->data($className, array(
            'whereSql' => "m.$field = ?",
            'params' => array($value),
            'oneOrNull' => 1,
        ));
    }

    /**
     * @param $className
     * @param $id
     * @return mixed
     */
    public function getById($className, $id)
    {
        return $this->getByField($className, 'id', $id);
    }

    /**
     * @param $className
     * @param $slug
     * @return mixed
     */
    public function getBySlug($className, $slug)
    {
        return $this->getByField($className, 'slug', $slug);
    }

    /**
     * @param $pdo
     * @param array $options
     * @return mixed
     */
    public function active($pdo, $options = array())
    {
        if (isset($options['whereSql'])) {
            $options['whereSql'] .= ($options['whereSql'] ? ' AND ' : '') . 'm.status = 1';
        } else {
            $options['whereSql'] = 'm.status = 1';
        }
        return $this->data($pdo, $options);
    }

    /**
     * @param $className
     * @param array $options
     * @return mixed
     */
    public function data($className, $options = array())
    {
        $pdo = $this->connection->getWrappedConnection();
        $fullClassName = static::fullClass($pdo, $className);
        return $fullClassName::data($pdo, $options);
    }

    /**
     * @param $className
     * @return string
     */
    static public function fullClass($pdo, $className)
    {
        if ($className == '_Model') {
            return "\\MillenniumFalcon\\Core\\Orm\\_Model";
        }

        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', $className);
        if (!$model) {
            throw new \Exception("Class $className Not Found");
        }
        return $model->getNamespace() . '\\' . $model->getClassName();
    }
}