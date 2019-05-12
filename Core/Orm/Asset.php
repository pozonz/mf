<?php

namespace MillenniumFalcon\Core\Orm;

use MillenniumFalcon\Core\Nestable\AssetNodeInterface;
use MillenniumFalcon\Core\Nestable\AssetNodeTrait;
use MillenniumFalcon\Core\Nestable\NodeExtraTrait;
use MillenniumFalcon\Core\Nestable\NodeInterface;
use MillenniumFalcon\Core\Nestable\NodeTrait;
use MillenniumFalcon\Core\Orm\Traits\AssetTrait;

class Asset extends \MillenniumFalcon\Core\Orm\Generated\Asset
{
    use AssetTrait;
}