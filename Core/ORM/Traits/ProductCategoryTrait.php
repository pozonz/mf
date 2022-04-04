<?php
//Last updated: 2019-09-16 22:06:40
namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait ProductCategoryTrait
{
    function objProductCount()
    {
        $categories = new \BlueM\Tree(static::active($this->getPdo(), [
            "select" => 'm.id AS id, m.parentId AS parent, m.title, m.slug, m.status',
            "sort" => 'm.rank',
            "order" => 'ASC',
            "orm" => 0,
        ]), [
            'rootId' => null,
        ]);

        $selectedProductCategory = $categories->getNodeById($this->getId());
        $descendants = $selectedProductCategory->getDescendants();
        $selectedProductCategoryIds = array_merge([$selectedProductCategory->get('id')], array_map(function ($itm) {
            return $itm->getId();
        }, $descendants));

        $whereSql = '';
        $params = [];

        $s = array_map(function ($itm) {
            return "m.categories LIKE ?";
        }, $selectedProductCategoryIds);
        $p = array_map(function ($itm) {
            return '%"' . $itm . '"%';
        }, $selectedProductCategoryIds);

        $whereSql .= ($whereSql ? ' AND ' : '') . '(' . implode(' OR ', $s) . ')';
        $params = array_merge($params, $p);


        $fullClass = ModelService::fullClass($this->getPdo(), 'Product');
        $productCount = $fullClass::active($this->getPdo(), [
            'whereSql' => $whereSql,
            'params' => $params,
            "count" => 1,
        ]);
        
        return $productCount["count"];
    }

    /**
     * @return string
     */
    static public function getCmsOrmsTwig()
    {
        return 'cms/orms/orms-custom-product-category.html.twig';
    }
}