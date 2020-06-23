<?php

namespace MillenniumFalcon\Core\Db\Traits;

use MillenniumFalcon\Core\Version\VersionInterface;

trait BaseVersionTrait
{
    /**
     * Return the front-end URL by replacing the value of the sitemap URL's variables
     * @return string|string[]|null
     */
    public function getFrontendUrl()
    {
        $model = $this->getModel();
        $siteMapUrl = $model->getSiteMapUrl();
        if (!$siteMapUrl) {
            return null;
        }
        $frontendUrl = $siteMapUrl;
        $fields = array_keys(static::getFields());
        foreach ($fields as $field) {
            $method = 'get' . ucfirst($field);
            $frontendUrl = str_replace("{{{$field}}}", $this->$method(), $frontendUrl);
        }
        return $frontendUrl;
    }

    /**
     * @param $siteMapUrl
     * @return string|string[]|null
     */
    public function getFrontendUrlBySiteMapUrl($siteMapUrl)
    {
        $frontendUrl = $siteMapUrl;
        $fields = array_keys(static::getFields());
        foreach ($fields as $field) {
            $method = 'get' . ucfirst($field);
            $frontendUrl = str_replace("{{{$field}}}", $this->$method(), $frontendUrl);
        }
        return $frontendUrl;
    }

    /**
     * @return bool
     */
    public function isVersioned()
    {
        return $this instanceof VersionInterface;
    }

    /**
     * @return int
     */
    public function canBePreviewed()
    {
        return $this->isVersioned() && $this->getFrontendUrl() !== null ? 1 : 0;
    }
}