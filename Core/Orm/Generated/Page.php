<?php
//Last updated: 2019-11-19 22:57:46
namespace MillenniumFalcon\Core\Orm\Generated;

use MillenniumFalcon\Core\Orm;
use MillenniumFalcon\Core\Nestable\NodeInterface;
use MillenniumFalcon\Core\Nestable\NodeExtraTrait;

class Page extends Orm implements NodeInterface
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
    private $type;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $url;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $redirectTo;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $category;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $templateFile;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $image;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $attachedModels;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $hideFromWebNav;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $hideFromCMSNav;
    
    /**
     * #pz mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $content;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $allowExtra;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $maxParams;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $categoryRank;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $categoryParent;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $categoryClosed;
    
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
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * @param mixed type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
    
    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }
    
    /**
     * @param mixed url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }
    
    /**
     * @return mixed
     */
    public function getRedirectTo()
    {
        return $this->redirectTo;
    }
    
    /**
     * @param mixed redirectTo
     */
    public function setRedirectTo($redirectTo)
    {
        $this->redirectTo = $redirectTo;
    }
    
    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }
    
    /**
     * @param mixed category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }
    
    /**
     * @return mixed
     */
    public function getTemplateFile()
    {
        return $this->templateFile;
    }
    
    /**
     * @param mixed templateFile
     */
    public function setTemplateFile($templateFile)
    {
        $this->templateFile = $templateFile;
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
    public function getAttachedModels()
    {
        return $this->attachedModels;
    }
    
    /**
     * @param mixed attachedModels
     */
    public function setAttachedModels($attachedModels)
    {
        $this->attachedModels = $attachedModels;
    }
    
    /**
     * @return mixed
     */
    public function getHideFromWebNav()
    {
        return $this->hideFromWebNav;
    }
    
    /**
     * @param mixed hideFromWebNav
     */
    public function setHideFromWebNav($hideFromWebNav)
    {
        $this->hideFromWebNav = $hideFromWebNav;
    }
    
    /**
     * @return mixed
     */
    public function getHideFromCMSNav()
    {
        return $this->hideFromCMSNav;
    }
    
    /**
     * @param mixed hideFromCMSNav
     */
    public function setHideFromCMSNav($hideFromCMSNav)
    {
        $this->hideFromCMSNav = $hideFromCMSNav;
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
    public function getAllowExtra()
    {
        return $this->allowExtra;
    }
    
    /**
     * @param mixed allowExtra
     */
    public function setAllowExtra($allowExtra)
    {
        $this->allowExtra = $allowExtra;
    }
    
    /**
     * @return mixed
     */
    public function getMaxParams()
    {
        return $this->maxParams;
    }
    
    /**
     * @param mixed maxParams
     */
    public function setMaxParams($maxParams)
    {
        $this->maxParams = $maxParams;
    }
    
    /**
     * @return mixed
     */
    public function getCategoryRank()
    {
        return $this->categoryRank;
    }
    
    /**
     * @param mixed categoryRank
     */
    public function setCategoryRank($categoryRank)
    {
        $this->categoryRank = $categoryRank;
    }
    
    /**
     * @return mixed
     */
    public function getCategoryParent()
    {
        return $this->categoryParent;
    }
    
    /**
     * @param mixed categoryParent
     */
    public function setCategoryParent($categoryParent)
    {
        $this->categoryParent = $categoryParent;
    }
    
    /**
     * @return mixed
     */
    public function getCategoryClosed()
    {
        return $this->categoryClosed;
    }
    
    /**
     * @param mixed categoryClosed
     */
    public function setCategoryClosed($categoryClosed)
    {
        $this->categoryClosed = $categoryClosed;
    }
    
}