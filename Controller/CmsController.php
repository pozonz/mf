<?php

namespace MillenniumFalcon\Controller;

use MillenniumFalcon\Core\Router;

class CmsController extends Router
{
    use CmsOrmTrait,
        CmsModelTrait,
        CmsRestTrait,
        CmsTrait;
}