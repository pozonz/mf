<?php

namespace MillenniumFalcon\Core\Tree;

use BlueM\Tree;
use BlueM\Tree\Node;
use Cocur\Slugify\Slugify;

class TreeUtils
{
    /**
     * @param Node $childNode
     * @return Node
     */
    static public function ancestor(Node $theNode)
    {
        $ancestors = $theNode->getAncestors();
        return end($ancestors) ?: $theNode;
    }

    /**
     * @param Node $parentNode
     * @param Node $childNode
     * @return bool
     */
    static public function contains(Node $parentNode, Node $childNode)
    {
        if ($childNode->getId() == $parentNode->getId()) {
            return true;
        }

        $ancestors = $childNode->getAncestors();
        foreach ($ancestors as $ancestor) {
            if ($ancestor->getId() == $parentNode->getId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Node $parentNode
     * @param Node $childNode
     * @return bool
     */
    static public function containsInTree(Node $parentNode, Node $childNode, Tree $tree)
    {
        $childNode = $tree->getNodeById($childNode->getId());
        return static::contains($parentNode, $childNode);
    }

    /**
     * @param Node $theNode
     * @return bool
     */
    static public function hasActiveChildren(Node $theNode)
    {
        foreach ($theNode->getChildren() as $child) {
            if ($child->get('status') == 1) {
                return true;
            }
        }
        return false;
    }
}