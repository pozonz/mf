<?php
//Last updated: 2019-07-04 20:17:29
namespace MillenniumFalcon\Core\Orm\Traits;

trait FormDescriptorTrait
{
    /**
     * @return string
     */
    static public function getCmsOrmTwig() {
        return 'cms/orms/orm-custom-formdescriptor.html.twig';
    }

}