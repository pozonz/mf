<?php
//Last updated: 2019-04-18 11:48:16
namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\TemplateService;

trait FragmentBlockTrait
{
    /**
     * @return string
     */
    static public function getCmsOrmTwig()
    {
        return 'cms/orms/orm-custom-fragmentblock.html.twig';
    }

    /**
     * @return mixed
     */
    public function objItems()
    {
        return $this->getItems() ? json_decode($this->getItems()) : [];
    }

    /**
     * @param bool $doNotSaveVersion
     * @param array $options
     */
    public function save($doNotSaveVersion = false, $options = [])
    {
        TemplateService::createBlockFile($this);
        return parent::save($doNotSaveVersion, $options);
    }
}