<?php

namespace MillenniumFalcon\Core\Nestable;

trait NodeExtraTrait
{
    /**
     * @return array
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param array $children
     */
    public function setChildren(array $children)
    {
        $this->children = $children;
    }

    /**
     * @param NodeInterface $child
     */
    public function addChild(NodeInterface $child)
    {
        $this->children[] = $child;
    }

    /**
     * @param NodeInterface $node
     * @return int
     */
    public function contains(NodeInterface $node)
    {
        if (!$node) {
            return 0;
        }
        return static::_contains($this, $node);
    }

    /**
     * @param NodeInterface $parent
     * @param NodeInterface $child
     * @return int
     */
    private static function _contains(NodeInterface $parent, NodeInterface $child)
    {
        if ($parent->getId() == $child->getId()) {
            return 1;
        }
        foreach ($parent->getChildren() as $itm) {
            if (static::_contains($itm, $child)) {
                return 1;
            }
        }
        return 0;
    }

    /**
     * @return int
     */
    public function hasActiveChildren()
    {
        foreach ($this->getChildren() as $itm) {
            if ($itm->getStatus() == 1) {
                return 1;
            }
        }
        return 0;
    }

    /**
     * @param $needleId
     * @return array|bool
     */
    public function path($needleId)
    {
        return static::_path($this, $needleId);
    }

    /**
     * @param NodeInterface $node
     * @param $needleId
     * @return array|bool
     */
    private static function _path(NodeInterface $node, $needleId)
    {
        $n = clone $node;
        $n->setChildren(array());
        $result = array($n);

        if ($node->getId() == $needleId) {
            return $result;
        }
        foreach ($node->getChildren() as $itm) {
            $r = static::_path($itm, $needleId);
            if ($r !== false) {
                return array_merge($result, $r);
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getIdAndDescendantIds()
    {
        $result = array($this->getId());
        $decendants = $this->getDescendants();
        return array_merge($result, array_unique(array_map(function ($itm) {
            return $itm->getId();
        }, $decendants)));
    }

    /**
     * @return array
     */
    public function getDescendantIds()
    {
        $decendants = $this->getDescendants();
        return array_unique(array_map(function ($itm) {
            return $itm->getId();
        }, $decendants));
    }

    /**
     * @return array
     */
    public function getDescendants()
    {
        return static::_getDescendants($this);
    }

    /**
     * @param $node
     * @return array
     */
    static public function _getDescendants($node)
    {
        $result = array();

        /** @var Node[] $children */
        $children = $node->getChildren();
        foreach ($children as $child) {
            $result[] = $child;
            $result = array_merge($result, static::_getDescendants($child));
        }
        return $result;
    }

    /**
     * @param $selected
     * @return bool
     */
    public function hasSelected($selected, $compareFunc)
    {
        return static::_hasSelected($this, $compareFunc, $selected ?: array());
    }

    /**
     * @param $node
     * @param $selected
     * @return bool
     */
    static public function _hasSelected($node, $compareFunc, $selected)
    {
        $result = false;

        /** @var Node[] $children */
        $children = $node->getChildren();
        foreach ($children as $child) {
            if (in_array($child->{$compareFunc}(), $selected)) {
                $result = true;
            }
            if (!$result) {
                $r = static::_hasSelected($child, $compareFunc, $selected);
                if ($r) {
                    $result = true;
                }
            }
        }
        return $result;
    }

    /**
     * @param Node $root
     * @return mixed
     */
    public function getTopAncestor(Node $root)
    {
        foreach ($root->getChildren() as $child) {
            if ($child->contains($this) || $child->getId() == $this->getId()) {
                return $child;
            }
        }
        return null;
    }
}