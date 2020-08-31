<?php
//Last updated: 2019-06-17 20:35:06
namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait RedirectTrait
{
    /**
     * @return string
     */
    static public function getCmsOrmsTwig()
    {
        return 'cms/orms/orms-custom-redirect.twig';
    }
}