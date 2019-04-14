<?php

namespace MillenniumFalcon\Orm;

/**
 * Class _Model
 * @package Web\Orm
 */
class _Model extends \MillenniumFalcon\Orm\Generated\_Model
{
    /**
     * _Model constructor.
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->setTitle('New models');
        $this->setClassName('NewModel');
        $this->setNamespace('Web\\Orm');
        $this->setModelType(0);
        $this->setDataType(0);
        $this->setListType(0);
        $this->setNumberPerPage(50);
        $this->setDefaultSortBy('id');
        $this->setDefaultOrder(1);
        parent::__construct($pdo);
    }

    /**
     * @return array
     */
    public static function getFieldChoices()
    {
        return array(
            'startdate' => "datetime DEFAULT NULL",
            'enddate' => "datetime DEFAULT NULL",
            'firstdate' => "datetime DEFAULT NULL",
            'lastdate' => "datetime DEFAULT NULL",
            'date' => "datetime DEFAULT NULL",
            'date1' => "datetime DEFAULT NULL",
            'date2' => "datetime DEFAULT NULL",
            'date3' => "datetime DEFAULT NULL",
            'date4' => "datetime DEFAULT NULL",
            'date5' => "datetime DEFAULT NULL",
            'date6' => "datetime DEFAULT NULL",
            'date7' => "datetime DEFAULT NULL",
            'date8' => "datetime DEFAULT NULL",
            'date9' => "datetime DEFAULT NULL",
            'date10' => "datetime DEFAULT NULL",
            'date11' => "datetime DEFAULT NULL",
            'date12' => "datetime DEFAULT NULL",
            'date13' => "datetime DEFAULT NULL",
            'date14' => "datetime DEFAULT NULL",
            'date15' => "datetime DEFAULT NULL",
            'title' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'isactive' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'subtitle' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'shortdescription' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'description' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'content' => "mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'category' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'subcategory' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'phone' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'mobile' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'fax' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'email' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'facebook' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'twitter' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'pinterest' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'linkedIn' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'instagram' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'qq' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'weico' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'address' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'website' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'author' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'authorbio' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'url' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'value' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'image' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'gallery' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'thumbnail' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'lastname' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'firstname' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'name' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'region' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'destination' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'excerpts' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'about' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'latitude' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'longitude' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'price' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'saleprice' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'features' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'account' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'username' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'password' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra1' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra2' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra3' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra4' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra5' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra6' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra7' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra8' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra9' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra10' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra11' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra12' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra13' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra14' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra15' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
        );
    }

    /**
     * @return array
     */
    public static function getWidgetChoices()
    {
        return array(
            'Asset picker' => '\\Pz\\Form\\Type\\AssetPicker',
            'Asset folder picker' => '\\Pz\\Form\\Type\\AssetFolderPicker',
            'Choice tree' => '\\Pz\\Form\\Type\\ChoiceTree',
            'Choice multi json' => '\\Pz\\Form\\Type\\ChoiceMultiJson',
            'Choice multi json tree' => '\\Pz\\Form\\Type\\ChoiceMultiJsonTree',
            'Date picker' => '\\Pz\\Form\\Type\\DatePicker',
            'Date time picker' => '\\Pz\\Form\\Type\\DateTimePicker',
            'Wysiwyg' => '\\Pz\\Form\\Type\\Wysiwyg',
            'Content blocks' => '\\Pz\\Form\\Type\\ContentBlock',
            'Checkbox' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\CheckboxType',
            'Choice' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType',
            'Email' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\EmailType',
            'Password' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\PasswordType',
            'Text' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',
            'Textarea' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextareaType',
            'Hidden' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\HiddenType',
        );
    }
}