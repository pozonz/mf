<?php

namespace MillenniumFalcon\Controller;

use MillenniumFalcon\Core\RouterController;

class CmsController extends RouterController
{
    use CmsOrmTrait,
        CmsModelTrait,
        CmsRestFileTrait,
        CmsRestProductTrait,
        CmsRestTrait,
        CmsTrait;
}