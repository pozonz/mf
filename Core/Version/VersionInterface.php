<?php

namespace MillenniumFalcon\Core\Version;

Interface VersionInterface
{
    public function saveVersion();

    public function getByVersionUuid($versionUuid);

    public function canBeRestored();
}