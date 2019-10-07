<?php

namespace MillenniumFalcon\Controller;

use MillenniumFalcon\Core\RouterController;

class WebController extends RouterController
{
    use WebAssetTrait,
        WebTrait;
}