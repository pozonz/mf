<?php
//Last updated: 2019-04-18 11:47:07
namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\TemplateService;

trait PageTemplateTrait
{
    /**
     * @param bool $doNotSaveVersion
     * @param array $options
     * @return mixed|null
     */
    public function save($doNotSaveVersion = false, $options = [])
    {
        TemplateService::createTemplateFile($this);
        return parent::save($doNotSaveVersion, $options);
    }
}