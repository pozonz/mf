<?php
//Last updated: 2019-04-18 11:48:16
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\TemplateService;

trait FragmentBlockTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo, $container)
    {
        $tagFullClass = ModelService::fullClass($pdo, 'FragmentTag');
        $tagOrm = $tagFullClass::getByField($pdo, 'title', 'Page');

        $orm = new static($pdo);
        $orm->setTitle('Heading & Content');
        $orm->setTwig('heading-content.twig');
        $orm->setTags(json_encode(array($tagOrm->getId())));
        $orm->setItems(json_encode(array(
            array(
                "widget" => "0",
                "id" => "heading",
                "title" => "Heading:",
                "sql" => "",
            ),
            array(
                "widget" => "5",
                "id" => "content",
                "title" => "Content:",
                "sql" => "",
            ),
        )));
        $orm->save();
    }
    
    /**
     * @return string
     */
    static public function getCmsOrmTwig() {
        return 'cms/orms/orm-custom-fragmentblock.html.twig';
    }

    /**
     * @return mixed
     */
    public function objItems()
    {
        return $this->getItems() ? json_decode($this->getItems()) : [];
    }

    /**
     * @param bool $doubleCheckExistence
     * @throws \Exception
     */
    public function save($doubleCheckExistence = false)
    {
        TemplateService::createBlockFile($this);
        parent::save($doubleCheckExistence);
    }
}