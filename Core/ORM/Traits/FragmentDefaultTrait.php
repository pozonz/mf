<?php

namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait FragmentDefaultTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo)
    {
        $tagFullClass = ModelService::fullClass($pdo, 'FragmentTag');
        $tagOrm = $tagFullClass::getByField($pdo, 'title', 'Page');

        $orm = new static($pdo);
        $orm->setTitle('Page');
        $orm->setAttr('content');
        $orm->setContent(json_encode(array(
            array(
                "id" => "content",
                "title" => "Content:",
                "tags" => array($tagOrm->getId()),
            ),
        )));
        $orm->save();
    }
    
    /**
     * @return string
     */
    static public function getCmsOrmTwig() {
        return 'cms/orms/orm-custom-fragmentdefault.html.twig';
    }
}