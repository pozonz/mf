<?php
//Last updated: 2019-08-06 19:44:20
namespace MillenniumFalcon\Core\Orm\Traits;

trait ContentSnippetTrait
{
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