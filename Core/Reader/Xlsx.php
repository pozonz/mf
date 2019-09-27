<?php

namespace MillenniumFalcon\Core\Reader;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Xlsx
{

    private $sheet;
    private $count;
    private $heighest;

    public function __construct($filepath, $sheet = 0)
    {
        $type = IOFactory::identify($filepath);
        $objReader = IOFactory::createReader($type);
        $this->phpExcel = $objReader->load($filepath);
        $this->setSheet($sheet);
    }

    /**
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    public function getPhpExcel()
    {
        return $this->phpExcel;
    }

    /**
     * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    public function getSheet()
    {
        return $this->sheet;
    }

    /**
     * @param \PHPExcel_Worksheet $sheet
     */
    public function setSheet($sheet)
    {
        $this->sheet = $this->phpExcel->getSheet($sheet);
        $this->count = 1;
        $this->heighest = $this->sheet->getHighestRow();
    }

    public function getNextRow()
    {
        if ($this->count > $this->heighest) {
            return FALSE;
        }

        $row = array();
        $highestColumn = $this->sheet->getHighestColumn();

        for ($i = Coordinate::columnIndexFromString('A'), $il = Coordinate::columnIndexFromString($highestColumn); $i <= $il; $i++) {
            $row[] = trim($this->sheet->getCell(Coordinate::stringFromColumnIndex($i) . $this->count)->getValue());
        }

        $this->count++;
        return $row;
    }

    public function getNextCalculatedRow()
    {
        if ($this->count > $this->heighest) {
            return FALSE;
        }

        $row = array();
        $highestColumn = $this->sheet->getHighestColumn();

        for ($i = Coordinate::columnIndexFromString('A') - 1, $il = Coordinate::columnIndexFromString($highestColumn); $i < $il; $i++) {
            try {
                $row[] = trim($this->sheet->getCell(Coordinate::stringFromColumnIndex($i) . $this->count)->getCalculatedValue());
            } catch (\Exception $ex) {
//                $row[Coordinate::stringFromColumnIndex($i) . $this->count] = '';
            }
        }

        $this->count++;
        return $row;
    }
}