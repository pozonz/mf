<?php

namespace MillenniumFalcon\Core\Db\Traits;

use Cocur\Slugify\Slugify;
use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Version\VersionInterface;
use Symfony\Component\HttpFoundation\Request;

trait BaseCustomisationTrait
{
    /**
     * @return mixed
     */
    static public function getCmsOrmsTwig()
    {
        return null;
    }

    /**
     * @return mixed
     */
    static public function getCmsOrmTwig()
    {
        return 'cms/orms/orm.twig';
    }
}