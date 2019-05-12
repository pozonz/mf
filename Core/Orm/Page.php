<?php

namespace MillenniumFalcon\Core\Orm;

use MillenniumFalcon\Core\Nestable\NodeTrait;
use MillenniumFalcon\Core\Nestable\PageInterface;
use MillenniumFalcon\Core\Nestable\PageNodeInterface;
use MillenniumFalcon\Core\Orm\Traits\PageTrait;

class Page extends \MillenniumFalcon\Core\Orm\Generated\Page implements PageNodeInterface
{
    use PageTrait;
}