<?php
//Last updated: 2019-06-17 20:35:06
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait NewsTrait
{
    /**
     * @return array
     * @throws \Exception
     */
    public function objRelatedBlog()
    {
        $result = $this->getRelatedBlog() ? json_decode($this->getRelatedBlog()) : [];
        if (!count($result)) {
            return [];
        }
        
        $ids = array_map(function ($itm) {
            return '?';
        }, $result);
        $sql = "m.id IN (" . join(',', $ids) . ")";

        $fullClass = ModelService::fullClass($this->getPdo(), 'News');
        return $fullClass::data($this->getPdo(), [
            'whereSql' => $sql,
            'params' => $result,
        ]);
    }
}