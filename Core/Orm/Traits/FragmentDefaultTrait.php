<?php

namespace MillenniumFalcon\Core\Orm\Traits;

trait FragmentDefaultTrait
{
    /**
     * @return string
     */
    static public function getCmsOrmTwig() {
        return 'cms/orms/orm-custom-fragmentdefault.html.twig';
    }
}