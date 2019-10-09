<?php
//Last updated: 2019-09-21 10:12:35
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait ProductVariantTrait
{
    /**
     * @param bool $doubleCheckExistence
     * @throws \Exception
     */
    public function save($doubleCheckExistence = false)
    {
        parent::save($doubleCheckExistence);

        $orm = $this->objProduct();
        if ($orm) {
            $orm->save();
        }
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objProduct()
    {
        $fullClass = ModelService::fullClass($this->getPdo(), 'Product');
        return $fullClass::getByField($this->getPdo(), 'uniqid', $this->getProductUniqid());
    }

    /**
     * @return mixed
     */
    public function objContent()
    {
        return json_decode($this->getContent());
    }
}