<?php
//Last updated: 2019-09-16 22:06:40
namespace MillenniumFalcon\Core\Orm\Traits;

trait ProductCategoryTrait
{
    /**
     * @return mixed
     */
    public function getExtraInfo()
    {
        return '(' . ($this->getCount() ?: 0) . ')';
    }
}