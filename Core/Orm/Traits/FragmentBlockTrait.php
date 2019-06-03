<?php
//Last updated: 2019-04-18 11:48:16
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Service\TemplateService;

trait FragmentBlockTrait
{
    /**
     * @return string
     */
    static public function getCmsOrmTwig() {
        return 'cms/orms/orm-custom-fragmentblock.html.twig';
    }

    /**
     * @return mixed
     */
    public function objItems()
    {
        return json_decode($this->getItems());
    }

    /**
     * @param bool $doubleCheckExistence
     * @throws \Exception
     */
    public function save($doubleCheckExistence = false)
    {
        TemplateService::createBlockFile($this);
        parent::save($doubleCheckExistence);
    }
}