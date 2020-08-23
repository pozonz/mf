<?php

namespace MillenniumFalcon\Core\Db\Traits;

use MillenniumFalcon\Core\Pattern\Version\VersionInterface;

trait BaseVersionTrait
{
    /**
     * @param $siteMapUrl
     * @return string|string[]|null
     */
    public function getFrontendUrlByCustomUrl($customUrl)
    {
        $fields = array_keys(static::getFields());
        foreach ($fields as $field) {
            $method = 'get' . ucfirst($field);
            $customUrl = str_replace("{{{$field}}}", $this->$method(), $customUrl);
        }
        return $customUrl;
    }

    /**
     * Return the front-end URL by replacing the value of the sitemap URL's variables
     * @return string|string[]|null
     */
    public function getFrontendUrl()
    {
        $model = $this->getModel();
        if (!$model) {
            $frontendUrl = $model->getFrontendUrl();
            return $this->getFrontendUrlByCustomUrl($frontendUrl);
        }
        return null;
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