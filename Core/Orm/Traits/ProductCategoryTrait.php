<?php
//Last updated: 2019-09-16 22:06:40
namespace MillenniumFalcon\Core\Orm\Traits;

trait ProductCategoryTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo, $container)
    {

    }
    
    /**
     * @return string
     */
    static public function getCmsOrmsTwig()
    {
        return 'cms/orms/orms-custom-product-category.html.twig';
    }
}