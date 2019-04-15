<?php

namespace MillenniumFalcon\Core\Nestable;

interface PageInterface extends NodeInterface
{
    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return null|string
     */
    public function getUrl();

    /**
     * @return null|string
     */
    public function getTemplate();

    /**
     * @return null|string
     */
    public function getIcon();

    /**
     * @return int
     */
    public function getAllowExtra();

    /**
     * @return int
     */
    public function getMaxParams();
}