<?php

namespace MillenniumFalcon\Core\Nestable;

trait PageNodeTrait
{
    /**
     * @return null|string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param null|string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return null|string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param null|string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * @return null|string
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @param null|string $icon
     */
    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    /**
     * @return int
     */
    public function getAllowExtra(): int
    {
        return $this->allowExtra;
    }

    /**
     * @param int $allowExtra
     */
    public function setAllowExtra(int $allowExtra)
    {
        $this->allowExtra = $allowExtra;
    }

    /**
     * @return int
     */
    public function getMaxParams(): int
    {
        return $this->maxParams;
    }

    /**
     * @param int $maxParams
     */
    public function setMaxParams(int $maxParams)
    {
        $this->maxParams = $maxParams;
    }
}