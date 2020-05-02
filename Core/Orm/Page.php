<?php

namespace MillenniumFalcon\Core\Orm;

use MillenniumFalcon\Core\Nestable\NodeTrait;
use MillenniumFalcon\Core\Nestable\PageInterface;
use MillenniumFalcon\Core\Nestable\PageNodeInterface;
use MillenniumFalcon\Core\Orm\Traits\PageTrait;
use MillenniumFalcon\Core\SolutionInterface\VersionInterface;

class Page extends \MillenniumFalcon\Core\Orm\Generated\Page implements PageNodeInterface, VersionInterface
{
    use PageTrait;
}