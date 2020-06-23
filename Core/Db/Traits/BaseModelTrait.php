<?php

namespace MillenniumFalcon\Core\Db\Traits;

use MillenniumFalcon\Core\ORM\_Model;

trait BaseModelTrait
{
    /**
     * @return array|null
     */
    public function getModel()
    {
        $rc = static::getReflectionClass();
        return _Model::getByField($this->getPdo(), 'className', $rc->getShortName());
    }

    /**
     * @return null|_Model
     */
    static public function updateModel($pdo)
    {
        $encodedModel = static::getCmsConfigModel();
        if (gettype($encodedModel) == 'string') {
            $decodedModel = json_decode($encodedModel);
            $model = _Model::getByField($pdo, 'className', $decodedModel->className);
            if (!$model) {
                $model = new _Model($pdo);
            }
            foreach ($decodedModel as $idx => $itm) {
                if ($idx === 'id') {
                    continue;
                }
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
    static public function getEncodedModel($model)
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
    static public function getCmsConfigModel()
    {
        $rc = static::getReflectionClass();
        $configFilePath = dirname($rc->getFileName()) . '/CmsConfig/' . $rc->getShortName() . '.json';
        return file_get_contents($configFilePath);
    }
}