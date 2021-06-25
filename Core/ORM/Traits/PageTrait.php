<?php

namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use Ramsey\Uuid\Uuid;

trait PageTrait
{
    /**
     * @var array
     */
    private $children = array();

    /**
     * PageTrait constructor.
     * @param $pdo
     */
    public function __construct($pdo)
    {
        parent::__construct($pdo);

        $this->setType(1);
    }

    /**
     * @return mixed
     */
    public function getExtraInfo()
    {
        return $this->getUrl();
    }

    /**
     * @param bool $doNotSaveVersion
     * @param array $options
     * @return mixed|null
     * @throws \Exception
     */
    public function save($doNotSaveVersion = false, $options = [])
    {
        if (!is_numeric($this->getTemplateFile())) {
            $json = json_decode($this->getTemplateFile());
            if ($json) {
                $templateName = $json->name;
                $templateFile = preg_replace("/[^a-z0-9\_\-\.]/i", '', $json->file);
                $templateFile = basename($templateFile, '.twig') . '.twig';

                $fullClass = ModelService::fullClass($this->getPdo(), 'PageTemplate');
                /** @var \MillenniumFalcon\Core\ORM\PageTemplate $orm */
                $orm = new $fullClass($this->getPdo());
                $orm->setTitle($templateName);
                $orm->setFilename($templateFile);
                $orm->save();

                $this->setTemplateFile($orm->getId());
            }
        }
        return parent::save($doNotSaveVersion, $options);
    }

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
     * @return string
     */
    public function getIcon()
    {
        return '';
    }

    /**
     * @return mixed|null|string
     */
    public function getTemplate()
    {
        $objPageTemplate = $this->objPageTempalte();
        return $objPageTemplate ? $objPageTemplate->getFilename() : null;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objPageTempalte()
    {
        $fullClass = ModelService::fullClass($this->getPdo(), 'PageTemplate');
        $pageTemplate = $fullClass::getById($this->getPdo(), $this->getTemplateFile());
        return $pageTemplate;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objPageTemplate()
    {
        return $this->objPageTempalte();
    }

    /**
     * @return string
     */
    static public function getCmsOrmsTwig()
    {
        return 'cms/orms/orms-custom-page.twig';
    }

    /**
     * @return string
     */
    static public function getCmsOrmTwig()
    {
        return 'cms/orms/orm-custom-page.twig';
    }
}