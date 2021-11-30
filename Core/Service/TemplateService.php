<?php

namespace MillenniumFalcon\Core\Service;

class TemplateService
{
    const blockMap = array(
        0 => 'fragment-0-text.html.txt',
        1 => 'fragment-1-textarea.html.txt',
        2 => 'fragment-2-assetpicker.html.txt',
        3 => 'fragment-3-assetfolderpicker.html.txt',
        4 => 'fragment-4-checkbox.html.txt',
        5 => 'fragment-5-wysiwyg.html.txt',
        6 => 'fragment-6-date.html.txt',
        7 => 'fragment-7-datetime.html.txt',
        8 => 'fragment-8-time.html.txt',
        9 => 'fragment-9-choice.html.txt',
        10 => 'fragment-10-choicemultijson.html.txt',
        11 => 'fragment-11-placeholder.html.txt',
        12 => 'fragment-12-choice-tree.html.txt',
        13 => 'fragment-13-choice-multi-json-tree.html.txt',
        14 => 'fragment-14-choice-sortable.html.txt',
        15 => 'fragment-15-list.html.txt',
    );

    /**
     * @param $pageTemplate
     */
    static public function createBlockFile($fragmentBlock) {
        $dir = static::getTemplateFragmentPath();
        $file = $dir . $fragmentBlock->getTwig();
        if (!file_exists($file)) {
            $str = '<div>' . "\n";
            $objItems = $fragmentBlock->objItems();
            foreach ($objItems as $objItem) {
                if ($objItem->widget == 11) {
                    continue;
                }
                $lines = explode("\n", str_replace('[value]', $objItem->id, file_get_contents(static::getResourceFilesPath() . 'fragments/' . static::blockMap[$objItem->widget])));
                foreach ($lines as $line) {
                    $str .= "\t" . $line . "\n";
                }
            }
            $str .= '</div>';

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($file, $str);
        }
    }

    /**
     * @param $pageTemplate
     */
    static public function createTemplateFile($pageTemplate) {
        $dir = static::getTemplatePath();
        $file = $dir . $pageTemplate->getFilename();
        if (!file_exists($file)) {
            $str = file_get_contents(static::getResourceFilesPath() . 'template.html.txt');
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($file, $str);
        }
    }

    /**
     * @return string
     */
    static public function getResourceFilesPath() {
        return __DIR__ . '/../../Resources/files/';
    }

    /**
     * @return string
     */
    static public function getTemplatePath() {
        return __DIR__ . '/../../../../../templates/';
    }

    /**
     * @return string
     */
    static public function getTemplateFragmentPath() {
        return static::getTemplatePath() . 'fragments/';
    }
}