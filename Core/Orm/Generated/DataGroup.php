<?php
//Last updated: 2019-11-19 22:50:43
namespace MillenniumFalcon\Core\Orm\Generated;

use MillenniumFalcon\Core\Orm;

class DataGroup extends Orm
{
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $title;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $icon;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $builtInSectionCode;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $builtInSectionTemplate;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $builtInSection;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $loadFromConfig;
    
    /**
     * #pz text COLLATE utf8mb4_unicode_ci DEFAULT NULL
     */
    private $config;
    
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
    public function getIcon()
    {
        return $this->icon;
    }
    
    /**
     * @param mixed icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }
    
    /**
     * @return mixed
     */
    public function getBuiltInSectionCode()
    {
        return $this->builtInSectionCode;
    }
    
    /**
     * @param mixed builtInSectionCode
     */
    public function setBuiltInSectionCode($builtInSectionCode)
    {
        $this->builtInSectionCode = $builtInSectionCode;
    }
    
    /**
     * @return mixed
     */
    public function getBuiltInSectionTemplate()
    {
        return $this->builtInSectionTemplate;
    }
    
    /**
     * @param mixed builtInSectionTemplate
     */
    public function setBuiltInSectionTemplate($builtInSectionTemplate)
    {
        $this->builtInSectionTemplate = $builtInSectionTemplate;
    }
    
    /**
     * @return mixed
     */
    public function getBuiltInSection()
    {
        return $this->builtInSection;
    }
    
    /**
     * @param mixed builtInSection
     */
    public function setBuiltInSection($builtInSection)
    {
        $this->builtInSection = $builtInSection;
    }
    
    /**
     * @return mixed
     */
    public function getLoadFromConfig()
    {
        return $this->loadFromConfig;
    }
    
    /**
     * @param mixed loadFromConfig
     */
    public function setLoadFromConfig($loadFromConfig)
    {
        $this->loadFromConfig = $loadFromConfig;
    }
    
    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }
    
    /**
     * @param mixed config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }
    
}