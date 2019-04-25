<?php

namespace MillenniumFalcon\Core\Service;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Orm\FragmentBlock;


class UtilsService
{
    /**
     * UtilsService constructor.
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function __construct(\Doctrine\DBAL\Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param $string
     * @return string
     */
    public function slugify($string) {
        $slugify = new Slugify(['trim' => false]);
        return $slugify->slugify($string);
    }

    /**
     * @return FragmentBlock[]
     */
    public function getBlockDropdownOptions()
    {
        /** @var \PDO $pdo */
        $pdo = $this->connection->getWrappedConnection();

        /** @var FragmentBlock[] $blocks */
        $blocks = FragmentBlock::active($pdo);
        foreach ($blocks as $block) {
            $items = json_decode($block->getItems());
            foreach ($items as &$item) {
                $choices = array();
                if ($item->widget == 9 || $item->widget == 10) {
                    $stmt = $pdo->prepare($item->sql);
                    $stmt->execute();
                    foreach ($stmt->fetchAll() as $key => $val) {
                        $choices[$val['key']] = $val['value'];
                    }
                }
                $item->choices = $choices;
            }
            $block->setItems(json_encode($items));
        }
        return $blocks;
    }

    /**
     * @return array
     */
    public function getBlockWidgets()
    {
        return array(
            0 => 'Text',
            1 => 'Textarea',
            2 => 'Asset picker',
            3 => 'Asset folder picker',
            4 => 'Checkbox',
            5 => 'Wysiwyg',
            6 => 'Date',
            7 => 'Datetime',
            8 => 'Time',
            9 => 'Choice',
            10 => 'Choice multi json',
            11 => 'Placeholder',
            12 => 'Read only text',
        );
    }

    /**
     * @return array
     */
    public function getFormWidgets() {
        return array(
            '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType' => 'Choice',
            '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\CheckboxType' => 'Checkbox',
//            '\\Pz\\Forms\\Types\\DatePicker' => 'Date picker',
//            '\\Pz\\Forms\\Types\\DateTimePicker' => 'Date time picker',
            '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\EmailType' => 'Email',
            '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\HiddenType' => 'Hidden',
            '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType' => 'Text',
            '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextareaType' => 'Textarea',
            '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\RepeatedType' => 'Repeated',
//            '\\Pz\\Forms\\Types\\Wysiwyg' => 'Wysiwyg',
            '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\SubmitType' => 'Submit',
        );
    }

    /**
     * @return string
     */
    public function getUniqId() {
        return uniqid();
    }

    /**
     * @param $value
     * @return string
     */
    public function getFormData($value) {
        if ($value[2] == '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextareaType') {
            return nl2br($value[1]);
        } else if ($value[2] == '\\MillenniumFalcon\\Core\\Form\\Type\\Wysiwyg') {
            return $value[1];
        }
        return strip_tags($value[1]);
    }
}