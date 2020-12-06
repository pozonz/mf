<?php
//Last updated: 2019-04-18 11:48:16
namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\TemplateService;

trait FragmentBlockTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo)
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

        $tagOrm = $tagFullClass::getByField($pdo, 'title', 'Shipping');

        $orm = new static($pdo);
        $orm->setTitle('Shipping price by weight');
        $orm->setTwig('_');
        $orm->setTags(json_encode(array($tagOrm->getId())));
        $orm->setItems(json_encode(array(
            array(
                "widget" => "10",
                "id" => "countries",
                "title" => "Countries:",
                "sql" => "SELECT t1.id AS `key`, t1.title AS value FROM ShippingCountry AS t1 ORDER BY t1.title",
            ),
            array(
                "widget" => "11",
                "id" => "id",
                "title" => "Title:",
                "sql" => "",
            ),
            array(
                "widget" => "0",
                "id" => "basePrice",
                "title" => "$ / base unit:",
                "sql" => "",
            ),
            array(
                "widget" => "0",
                "id" => "baseWeight",
                "title" => "Base unit weight:",
                "sql" => "",
            ),
            array(
                "widget" => "0",
                "id" => "extraPrice",
                "title" => "$ / extra unit:",
                "sql" => "",
            ),
            array(
                "widget" => "0",
                "id" => "extraWeight",
                "title" => "Extra unit weight:",
                "sql" => "",
            ),
        )));
        $orm->save();
    }

    /**
     * @return string
     */
    static public function getCmsOrmTwig()
    {
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
     * @param bool $doNotSaveVersion
     * @param array $options
     */
    public function save($doNotSaveVersion = false, $options = [])
    {
        TemplateService::createBlockFile($this);
        return parent::save($doNotSaveVersion, $options);
    }
}