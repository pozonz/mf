<?php

namespace MillenniumFalcon\Core\SolutionInterface;

Interface VersionInterface
{
    public function saveVersion();

    public function restoreVersion();
}