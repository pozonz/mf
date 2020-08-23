<?php

namespace MillenniumFalcon\Core\ORM;

use MillenniumFalcon\Core\ORM\Traits\PageTrait;
use MillenniumFalcon\Core\Pattern\Version\VersionInterface;
use MillenniumFalcon\Core\Pattern\Version\VersionTrait;


class Page extends \MillenniumFalcon\Core\ORM\Generated\Page implements VersionInterface
{
    use PageTrait, VersionTrait;
}