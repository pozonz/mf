<?php

namespace MillenniumFalcon\Core\ORM\Traits;

trait ContentSnippetTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo)
    {

    }

    /**
     * @return mixed
     */
    public function objContent()
    {
        $result = [];
        $objContent = json_decode($this->getContent());
        foreach ($objContent as $itm) {
            $result[$itm->attr] = $itm;
        }
        return $result;
    }
}