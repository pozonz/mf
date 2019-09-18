<?php
//Last updated: 2019-09-18 21:35:14
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
    
}