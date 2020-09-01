<?php
//Last updated: 2019-09-16 21:43:24
namespace MillenniumFalcon\Core\ORM\Traits;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Service\ModelService;

trait ProductTrait
{
    /**
     * @return string
     */
    static public function getCmsOrmsTwig()
    {
        return 'cms/orms/orms-custom-product.html.twig';
    }

    /**
     * @return string
     */
    static public function getCmsOrmTwig()
    {
        return 'cms/orms/orm-custom-product.html.twig';
    }
}