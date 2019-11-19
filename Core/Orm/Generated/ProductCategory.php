<?php
//Last updated: 2019-11-19 22:52:39
namespace MillenniumFalcon\Core\Orm\Generated;

use MillenniumFalcon\Core\Orm;
use MillenniumFalcon\Core\Nestable\NodeInterface;
use MillenniumFalcon\Core\Nestable\NodeExtraTrait;

class ProductCategory extends Orm implements NodeInterface
{
    use NodeExtraTrait;

    /**
     * @var array
     */
    private $children = array();

    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $count;
    
    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }
    
    /**
     * @param mixed title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }
    
    /**
     * @return mixed
     */
    public function getCount()
    {
        return $this->count;
    }
    
    /**
     * @param mixed count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }
    
}