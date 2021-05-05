<?php
//Last updated: 2019-09-27 09:55:00
namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait OrderItemTrait
{
    protected $variant;

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objVariant()
    {
        if (!$this->variant) {
            $fullClass = ModelService::fullClass($this->getPdo(), 'ProductVariant');
            $this->variant = $fullClass::getById($this->getPdo(), $this->getProductId());
        }
        return $this->variant;
    }
}