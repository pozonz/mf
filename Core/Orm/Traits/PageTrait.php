<?php

namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Nestable\NodeInterface;
use MillenniumFalcon\Core\Service\ModelService;

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
     * @param bool $doubleCheckExistence
     * @throws \Exception
     */
    public function save($doubleCheckExistence = false) {
        if (!is_numeric($this->getTemplateFile())) {
            $json = json_decode($this->getTemplateFile());

            $templateName = $json->name;
            $templateFile = preg_replace("/[^a-z0-9\_\-\.]/i", '', $json->file);
            $templateFile = rtrim($templateFile, '.twig') . '.twig';

            $fullClass = ModelService::fullClass($this->getPdo(), 'PageTemplate');
            /** @var \MillenniumFalcon\Core\Orm\PageTemplate $orm */
            $orm = new $fullClass($this->getPdo());
            $orm->setTitle($templateName);
            $orm->setFilename($templateFile);
            $orm->save();

            $this->setTemplateFile($orm->getId());
        }

        parent::save($doubleCheckExistence);
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
     */
    public function objContent()
    {
        return json_decode($this->getContent());
    }

    /**
     * @return string
     */
    static public function getCmsOrmsTwig()
    {
        return 'cms/orms/orms-custom-page.html.twig';
    }

    /**
     * @return string
     */
    static public function getCmsOrmTwig()
    {
        return 'cms/orms/orm-custom-page.html.twig';
    }
}