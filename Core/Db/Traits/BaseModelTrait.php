<?php

namespace MillenniumFalcon\Core\Db\Traits;

use MillenniumFalcon\Core\ORM\_Model;

trait BaseModelTrait
{
    protected $model;

    /**
     * @return array|null
     */
    public function getModel()
    {
        if (!$this->model) {
            $rc = static::getReflectionClass();
            $this->model = _Model::getByField($this->getPdo(), 'className', $rc->getShortName());
        }
        return $this->model;
    }

    /**
     * @return null|_Model
     */
    static public function createOrUpdateModel($pdo)
    {
        $response = 0;

        $encodedModel = static::getCmsConfigModel();
        if (gettype($encodedModel) == 'string') {
            $decodedModel = json_decode($encodedModel);

            $response = 3;

            $model = _Model::getByField($pdo, 'className', $decodedModel->className);
            if (!$model) {
                $response = 1;
                $model = new _Model($pdo);
            }

            foreach ($decodedModel as $idx => $itm) {
                if ($idx === 'id') {
                    continue;
                }
                $setMethod = "set" . ucfirst($idx);
                $getMethod = "get" . ucfirst($idx);

                if ($response === 1) {
                    $model->$setMethod($itm);
                } else {
                    if ($itm != $model->$getMethod()) {
//                        var_dump($decodedModel->className, $idx, $itm, $model->$getMethod());exit;
                        $response = 2;
                        $model->$setMethod($itm);
                    }
                }
            }

            $model->setPdo($pdo);
            $model->save(true, [
                'doNotUpdateModified' => 1,
            ]);
        }

        return $response;
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
            if ($field == 'id') {
                continue;
            }
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