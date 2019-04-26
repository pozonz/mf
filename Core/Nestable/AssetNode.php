<?php

namespace MillenniumFalcon\Core\Nestable;

class AssetNode extends Node
{
    /**
     * @var string
     */
    private $text;

    /**
     * @var string
     */
    private $url;

    /**
     * @var int
     */
    private $state = array();

    /**
     * NodeAsset constructor.
     * @param $id
     * @param null $parentId
     * @param int $rank
     * @param int $status
     * @param string $title
     * @param $state
     */
    public function __construct($id, $parentId = null, $rank = 0, $status = 1, $title = '', $url = '', $state = array())
    {
        parent::__construct($id, $parentId, $rank, $status, $title);

        $this->text = $title;
        $this->url = $url;
        $this->state = $state;
    }

    /**
     * @return null|string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param null|string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return array
     */
    public function getState(): array
    {
        return $this->state;
    }

    /**
     * @param array $state
     */
    public function setState(array $state)
    {
        $this->state = $state;
    }

    /**
     * @param $idx
     * @param $value
     */
    public function setStateValue($idx, $value)
    {
        $this->state[$idx] = $value;
    }
}