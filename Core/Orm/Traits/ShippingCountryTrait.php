<?php
//Last updated: 2019-09-27 09:57:41
namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Reader\Xlsx;

trait ShippingCountryTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo)
    {
        $csv = new Xlsx(__DIR__ . '/../../../../../../vendor/pozoltd/millennium-falcon/Resources/files/countries.xlsx');
        $row = $csv->getNextRow();
        while ($row = $csv->getNextRow()) {
            if ($row[2]) {
                $orm = new static($pdo);
                $orm->setTitle($row[1]);
                $orm->setCode($row[2]);
                $orm->save();
            }
        }
    }
}