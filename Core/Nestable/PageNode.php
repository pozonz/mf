<?php

namespace MillenniumFalcon\Core\Nestable;

class PageNode extends Node implements PageNodeInterface
{
    use PageNodeTrait;

    /**
     * @var string|null
     */
    private $url;

    /**
     * @var string|null
     */
    private $template;

    /**
     * @var string|null
     */
    private $icon;

    /**
     * @var int
     */
    private $allowExtra;

    /**
     * @var int
     */
    private $maxParams;

    /**
     * CmsPage constructor.
     * @param $id
     * @param $parentId
     * @param int $rank
     * @param int $status
     * @param string $title
     * @param string $url
     * @param string $template
     * @param string $icon
     * @param int $allowExtra
     * @param int $maxParams
     */
    public function __construct($id, $parentId, $rank = 0, $status = 1, $title = '', $url = '', $template = '', $icon = '', $allowExtra = 0, $maxParams = 0)
    {
        parent::__construct($id, $parentId, $rank, $status, $title);

        $this->url = $url;
        $this->template = $template;
        $this->icon = $icon;
        $this->allowExtra = $allowExtra;
        $this->maxParams = $maxParams;
    }
}