<?php
//Last updated: 2019-04-30 11:33:32
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Orm\AssetOrm;

trait AssetTrait
{
    public function delete()
    {
        $result = AssetOrm::data($this->getPdo(), array(
            'whereSql' => 'm.title = ?',
            'params' => array($this->getId()),
        ));
        foreach ($result as $itm) {
            $itm->delete();
        }

        if ($this->getIsFolder()) {
            $data = static::data($this->getPdo(), array(
                'whereSql' => 'm.parentId = ?',
                'params' => array($this->getId())
            ));
            foreach ($data as $itm) {
                $itm->delete();
            }
        } else {
            $physicalLink = __DIR__ . '/../../../../../../uploads/' . $this->getFileLocation();
            if (file_exists($physicalLink)) {
                unlink($physicalLink);
            }
        }

        return parent::delete();
    }
}