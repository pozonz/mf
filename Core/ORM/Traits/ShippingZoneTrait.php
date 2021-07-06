<?php
//Last updated: 2019-09-27 09:57:41
namespace MillenniumFalcon\Core\ORM\Traits;

trait ShippingZoneTrait
{
    /**
     * @return mixed
     */
    public function objChildren()
    {
        return static::active($this->getPdo(), [
            'whereSql' => 'm.parentId = ?',
            'params' => [$this->getId()],
        ]);
    }
}