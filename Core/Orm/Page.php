<?php

namespace MillenniumFalcon\Core\ORM;

use MillenniumFalcon\Core\ORM\Traits\PageTrait;
use MillenniumFalcon\Core\Version\VersionInterface;
use MillenniumFalcon\Core\Version\VersionTrait;

class Page extends \MillenniumFalcon\Core\ORM\Generated\Page implements VersionInterface
{
    use PageTrait, VersionTrait;
}