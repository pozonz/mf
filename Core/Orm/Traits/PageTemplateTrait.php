<?php
//Last updated: 2019-04-18 11:47:07
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Service\TemplateService;

trait PageTemplateTrait
{
    /**
     * @param bool $doubleCheckExistence
     * @throws \Exception
     */
    public function save($doubleCheckExistence = false)
    {
        TemplateService::createTemplateFile($this);
        parent::save($doubleCheckExistence);
    }
}