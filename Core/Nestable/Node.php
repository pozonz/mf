<?php

namespace MillenniumFalcon\Core\Nestable;

class Node implements NodeInterface, \JsonSerializable
{

    use NodeTrait, NodeExtraTrait;

    /**
     * @var array
     */
    private $children = array();

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $parentId;

    /**
     * @var int
     */
    private $rank;

    /**
     * @var int
     */
    private $status;

    /**
     * @var string
     */
    private $title;

    /**
     * Node constructor.
     * @param $id
     * @param null $parentId
     * @param int $rank
     * @param int $status
     * @param string $title
     */
    public function __construct($id, $parentId = null, $rank = 0, $status = 1, $title = '')
    {
        $this->id = $id;
        $this->parentId = $parentId;
        $this->rank = $rank;
        $this->status = $status;
        $this->title = $title;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $fields = static::getFields();

        $obj = new \stdClass();
        foreach ($fields as $field) {
            $getMethod = "get" . ucfirst($field);
            $obj->{$field} = $this->$getMethod();
        }
        return $obj;
    }

    /**
     * @return array
     */
    public static function getFields()
    {
        $result = array();
        $rc = new \ReflectionClass(get_called_class());
        do {
            $result = array_merge($rc->getProperties(), $result);
            $rc = $rc->getParentClass();
        } while ($rc);
        return static::propertiesToFields($result);
    }

    /**
     * @param $properties
     * @return array
     */
    private static function propertiesToFields($properties)
    {
        $result = array();
        foreach ($properties as $property) {
            $result[] = $property->getName();
        }
        return $result;
    }
}