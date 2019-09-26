<?php

namespace MillenniumFalcon\Core\Service;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Nestable\PageNode;
use MillenniumFalcon\Core\Nestable\Tree;

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
    public function slugify($string)
    {
        $slugify = new Slugify(['trim' => false]);
        return $slugify->slugify($string);
    }

    /**
     * @return FragmentBlock[]
     */
    public function getBlockDropdownOptions()
    {
        $pdo = $this->connection;

        $fullClass = ModelService::fullClass($pdo, 'FragmentBlock');
        $blocks = $fullClass::active($pdo);
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
        $widgets = array(
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
        );
        asort($widgets);
        return array_flip($widgets);
    }

    /**
     * @return array
     */
    public function getFormWidgets()
    {
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
    public function getUniqId()
    {
        return uniqid();
    }

    /**
     * @param $valLength
     * @return bool|string
     */
    static public function generateUniqueHex($valLength, $exists)
    {
        do {
            $uniqeHex = static::generateHex($valLength);
        } while (in_array($uniqeHex, $exists));
        return $uniqeHex;
    }

    /**
     * @param $valLength
     * @return bool|string
     */
    static public function generateHex($valLength)
    {
        $result = '';
        $moduleLength = 40;   // we use sha1, so module is 40 chars
        $steps = round(($valLength / $moduleLength) + 0.5);

        for ($i = 0; $i < $steps; $i++) {
            $result .= sha1(uniqid() . md5(rand() . uniqid()));
        }

        return substr($result, 0, $valLength);
    }

    /**
     * Convert a multi-dimensional array into a single-dimensional array.
     * @author Sean Cannon, LitmusBox.com | seanc@litmusbox.com
     * @param  array $array The multi-dimensional array.
     * @return array
     */
    static public function flattenArray($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, static::flattenArray($value));
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * @param $categoryCode
     * @return null
     */
    public function nav($categoryCode)
    {
        $pdo = $this->connection;

        $result = null;
        $fullClass = ModelService::fullClass($pdo, 'PageCategory');
        $category = $fullClass::getByField($pdo, 'code', $categoryCode);
        if ($category) {
            $fullClass = ModelService::fullClass($pdo, 'Page');
            $pages = $fullClass::data($pdo, array(
                'whereSql' => 'm.category LIKE ? ',
                'params' => array('%"' . $category->getId() . '"%'),
            ));

            $nodes = array();
            foreach ($pages as $itm) {
                if ($itm->getHideFromWebNav()) {
//                    continue;
                }
                $categoryParent = !$itm->getCategoryParent() ? array() : (array)json_decode($itm->getCategoryParent());
                $categoryRank = !$itm->getCategoryRank() ? array() : (array)json_decode($itm->getCategoryRank());
                $parent = isset($categoryParent['cat' . $category->getId()]) ? $categoryParent['cat' . $category->getId()] : 0;
                $rank = isset($categoryRank['cat' . $category->getId()]) ? $categoryRank['cat' . $category->getId()] : 0;

                $node = new PageNode($itm->getId(), $parent, $rank, $itm->getHideFromWebNav() ? 0 : 1, $itm->getTitle(), $itm->getUrl(), null/** $itm->objPageTempalte()->getFilename() */, '', $itm->getAllowExtra(), $itm->getMaxParams());
//                $node->objContent = $itm->objContent();
                $nodes[] = $node;
            }

            $tree = new Tree($nodes);
            $result = $tree->getRoot();
        }

        return $result;
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