<?php

namespace MillenniumFalcon\Core\Db\Traits;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Db\Sql;

trait BaseReflectionTrait
{
    /**
     * @param $pdo
     */
    static public function sync($pdo)
    {
        $tableName = static::getTableName();

        $db = new Sql($pdo, $tableName);
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
     * @return array
     */
    static public function getParentFields()
    {
        $rc = new \ReflectionClass(__CLASS__);
        return static::propertiesToFields($rc->getProperties());
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
}