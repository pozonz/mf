<?php
//Last updated: 2019-09-27 09:24:36
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait OrderTrait
{
    protected $orderItems;

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objOrderItems()
    {
        if (!$this->orderItems) {
            $fullClass = ModelService::fullClass($this->getPdo(), 'OrderItem');
            $this->orderItems = $fullClass::active($this->getPdo(), array(
                'whereSql' => 'm.orderId = ?',
                'params' => array($this->getId()),
            ));
        }
        return $this->orderItems;
    }
}