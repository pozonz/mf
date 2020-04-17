<?php
//Last updated: 2020-04-17 14:52:17
namespace MillenniumFalcon\Core\Orm\Generated;

use MillenniumFalcon\Core\Orm;

class News extends Orm
{
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz datetime DEFAULT NULL
     */
    private $date;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $image;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $excerpts;
    
    /**
     * #pz mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $content;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $relatedBlog;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $featured;
    
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
    public function getDate()
    {
        return $this->date;
    }
    
    /**
     * @param mixed date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }
    
    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }
    
    /**
     * @param mixed image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }
    
    /**
     * @return mixed
     */
    public function getExcerpts()
    {
        return $this->excerpts;
    }
    
    /**
     * @param mixed excerpts
     */
    public function setExcerpts($excerpts)
    {
        $this->excerpts = $excerpts;
    }
    
    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }
    
    /**
     * @param mixed content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
    
    /**
     * @return mixed
     */
    public function getRelatedBlog()
    {
        return $this->relatedBlog;
    }
    
    /**
     * @param mixed relatedBlog
     */
    public function setRelatedBlog($relatedBlog)
    {
        $this->relatedBlog = $relatedBlog;
    }
    
    /**
     * @return mixed
     */
    public function getFeatured()
    {
        return $this->featured;
    }
    
    /**
     * @param mixed featured
     */
    public function setFeatured($featured)
    {
        $this->featured = $featured;
    }
    
}