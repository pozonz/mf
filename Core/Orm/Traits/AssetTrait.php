<?php
//Last updated: 2019-04-30 11:33:32
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Orm\AssetOrm;
use MillenniumFalcon\Core\Service\UtilsService;

trait AssetTrait
{
    /**
     * @param bool $doubleCheckExistence
     * @return mixed
     */
    public function save($doubleCheckExistence = false) {
        if (!$this->getId()) {
            do {
                $code = UtilsService::generateHex(4);
                $orm = static::getByField($this->getPdo(), 'code', $code);
            } while($orm);
            $this->setCode($code);
        }
        return parent::save($doubleCheckExistence);
    }

    /**
     * @return mixed
     */
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

    /**
     * @return mixed
     */
    static public function getCmsOrmsTwig() {
        return 'cms/files/files.html.twig';
    }

    /**
     * @return mixed
     */
    static public function getCmsOrmTwig() {
        return 'cms/files/file.html.twig';
    }
}