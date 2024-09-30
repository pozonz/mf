<?php

namespace MillenniumFalcon\Core\Pattern\Version;

Interface VersionInterface
{
    public function saveVersion();

    public function getByVersionUuid($versionUuid);

    public function canBeRestored();
}