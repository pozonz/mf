<?php

namespace MillenniumFalcon\Core;

use Doctrine\DBAL\Connection;

class Db
{
    /**
     * @var Connection
     */
    private $pdo;

    /**
     * @var
     */
    private $table;

    /**
     * Db constructor.
     * @param Connection $pdo
     * @param $table
     */
    public function __construct(Connection $pdo, $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * @param $oldColumn
     * @param $tableColumns
     * @return string
     */
    private function getTrashColumnName($oldColumn, $tableColumns)
    {
        $idx = 1;
        $oldColumn = '__' . $oldColumn;

        do {
            $oldColumn = $oldColumn . ($idx == 1 ? '' : '_' . $idx);
            $idx = $idx + 1;
        } while (in_array($oldColumn, $tableColumns));
        return $oldColumn;
    }

    /**
     * @param $oldColumn
     * @param $tableColumns
     * @return string
     */
    private function getLastColumn($oldColumn, $tableColumns)
    {
        $result = array_reverse($tableColumns);
        foreach ($result as $itm) {
            if ($itm != $oldColumn && substr($itm, 0, 2) != '__') {
                return $itm;
            }
        }
        return 'id';
    }

    /**
     * @return array
     */
    public function getFields()
    {
        $fields = array();
        $sql = "DESCRIBE `{$this->table}`";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($result as $itm) {
            $fields[$itm['Field']] = $itm['Type'] . ($itm['Null'] == 'NO' ? ' NOT NULL' : ' NULL');
        }
        return $fields;
    }

    /**
     * @param $ormFields
     */
    public function sync($ormFields)
    {
        $tableFields = $this->getFields();
        $tableColumns = array_keys($tableFields);
        $ormColumns = array_keys($ormFields);

        $newColumns = array_diff($ormColumns, $tableColumns);
        $oldColumns = array_diff($tableColumns, $ormColumns);
//var_dump($newColumns, $oldColumns);exit;
        foreach ($newColumns as $newColumn) {
            $lastColumnName = $this->getLastColumn($newColumn, $tableColumns);
            $this->addColumn($newColumn, $ormFields[$newColumn], $lastColumnName);
            $tableColumns[] = $newColumn;
        }

        foreach ($oldColumns as $oldColumn) {
            if (substr($oldColumn, 0, 2) == '__') {
                continue;
            }
            $trashColumnName = $this->getTrashColumnName($oldColumn, $tableColumns);
            $lastColumnName = $this->getLastColumn($oldColumn, $tableColumns);
            $this->renameColumn($oldColumn, $trashColumnName, $tableFields[$oldColumn], $lastColumnName);
            $tableColumns[] = $trashColumnName;
        }
    }

    /**
     * @param $column
     * @param $attrs
     * @param string $lastColumn
     * @return bool
     */
    public function addColumn($column, $attrs, $lastColumn = '')
    {
        try {
            $sql = "ALTER TABLE `{$this->table}` 
                      ADD COLUMN `$column` $attrs" . ($lastColumn ? " AFTER $lastColumn" : '');
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute();
        } catch (\Exception $ex) {
            var_dump($ex->getMessage());
            exit;
        }
    }

    /**
     * @param $oldColumn
     * @param $newColumn
     * @param $dataType
     * @param string $lastColumn
     * @return bool
     */
    public function renameColumn($oldColumn, $newColumn, $dataType, $lastColumn = '')
    {
        try {
            $sql = "ALTER TABLE `{$this->table}` 
                      CHANGE `$oldColumn` `$newColumn` $dataType" . ($lastColumn ? " AFTER $lastColumn" : '');
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute();
        } catch (\Exception $ex) {
            var_dump($ex->getMessage());
            exit;
        }
    }

    /**
     *
     */
    public function create()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
                    `id` int(11) NOT NULL AUTO_INCREMENT, 
                    PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        } catch (\Exception $ex) {
            var_dump($ex->getMessage());
            exit;
        }
    }

    /**
     * @param $newTableName
     */
    public function rename($newTableName)
    {
        try {
            $sql = "ALTER TABLE `$this->table` RENAME TO `$newTableName`;";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $this->table = $newTableName;
        } catch (\Exception $ex) {
            var_dump($ex->getMessage());
            exit;
        }
    }

    /**
     * @return int
     */
    public function exists()
    {
        try {
            $sql = "SELECT 1 FROM `{$this->table}`";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return 1;
        } catch (\Exception $ex) {
//            var_dump($ex->getMessage());
//            exit;
        }
        return 0;
    }

    /**
     * @param $index
     * @param $column
     * @return bool
     */
    public function addIndex($index, $column)
    {
        try {
            $sql = "ALTER TABLE `{$this->table}`
                      ADD INDEX `$index` (`$column` ASC);";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute();
        } catch (\Exception $ex) {
            var_dump($ex->getMessage());
            exit;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function drop()
    {
        $sql = "DROP TABLE IF EXISTS `$this->table`";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }
}