<?php

namespace MillenniumFalcon\Core\ORM\Traits;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Service\AssetService;
use MillenniumFalcon\Core\Service\UtilsService;
use Ramsey\Uuid\Uuid;

trait ABTestTrait
{
    /**
     * Orm constructor.
     * @param Connection $pdo
     */
    public function __construct(Connection $pdo)
    {
        $this->setVersion(1);
        parent::__construct($pdo);
    }
}