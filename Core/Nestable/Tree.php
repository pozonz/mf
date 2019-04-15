<?php

namespace MillenniumFalcon\Core\Nestable;

class Tree
{
    private $nodes;

    /**
     * Tree constructor.
     * @param $nodes
     */
    public function __construct($nodes)
    {
        usort($nodes, function ($node1, $node2) {
            return $node1->getRank() - $node2->getRank();
        });
        $this->nodes = $nodes;
    }

    /**
     * @param $url
     * @return null
     */
    public function getNodeByUrl($url)
    {
        foreach ($this->nodes as $node) {
            if ($node->getUrl() == $url) {
                return $node;
            }
        }
        return null;
    }

    /**
     * @param $node
     * @return NodeInterface
     */
    public function getRootFromNode($node)
    {
        return static::_getRoot($node);
    }

    /**
     * @return NodeInterface
     */
    public function getRoot()
    {
        return static::_getRoot(new Node(0));
    }

    /**
     * @param NodeInterface $node
     * @return NodeInterface
     */
    public function _getRoot(NodeInterface $node)
    {
        foreach ($this->nodes as $itm) {
            if (($itm->getParentId() . '') === ($node->getId() . '') || ($itm->getParentId() === null && $node->getId() === 0)) {
                $node->addChild($this->_getRoot($itm));
            }
        }
        return $node;
    }

    /**
     * @param $root
     * @param $needleId
     * @return array
     */
    public static function getChildrenAndSelfAsArray($root, $needleId)
    {
        return static::_getChildrenAndSelfAsArray($root, $needleId, 0);
    }

    /**
     * @param NodeInterface $node
     * @param $needleId
     * @param $added
     * @return array
     */
    private static function _getChildrenAndSelfAsArray(NodeInterface $node, $needleId, $added)
    {
        $result = array();
        if ($node->getId() == $needleId || $added) {
            $added = 1;
            $result[] = $node;
        }
        foreach ($node->getChildren() as $itm) {
            $r = static::_getChildrenAndSelfAsArray($itm, $needleId, $added);
            if ($added || count($r) > 0) {
                $result = array_merge($result, $r);
            }
        }
        return $result;
    }
}