<?php

namespace MillenniumFalcon\Core\ORM;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Db\Base;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class _Model
 * @package MillenniumFalcon\Core\ORM
 */
class _Model extends \MillenniumFalcon\Core\ORM\Generated\_Model
{
    const metaExludes = array(
        'publishFrom',
        'publishTo',
        'metaTitle',
        'metaDescription',
        'ogTitle',
        'ogDescription',
        'ogImage',
        'rank',
        'status',
        'closed',
        'slug',
    );

    const presetData = array(
        'Publish date range' => 'publish',
        'Meta tags' => 'meta',
        'OG tags' => 'og',
        'Rank' => 'rank',
    );

    const presetDataMap = array(
        'publish' => array(
            'publishFrom' => '\\MillenniumFalcon\\Core\\Form\\Type\\DateTimePicker',
            'publishTo' => '\\MillenniumFalcon\\Core\\Form\\Type\\DateTimePicker'
        ),
        'meta' => array(
            'metaTitle' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',
            'metaDescription' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',
        ),
        'og' => array(
            'ogTitle' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',
            'ogDescription' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',
            'ogImage' => '\\MillenniumFalcon\\Core\\Form\\Type\\AssetPicker'
        ),
        'rank' => array(
            'rank' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',
        ),
    );

    /**
     * _Model constructor.
     * @param Connection $pdo
     */
    public function __construct(Connection $pdo)
    {
        $this->setTitle('New models');
        $this->setClassName('NewModel');
        $this->setNamespace('Web\\ORM');
        $this->setModelType(0);
        $this->setDataType(0);
        $this->setListType(0);
        $this->setNumberPerPage(50);
        $this->setDefaultSortBy('id');
        $this->setDefaultOrder(1);
        parent::__construct($pdo);
    }

    /**
     * @param _Model $orm
     * @param $container
     */
    static public function setGenereatedFile(_Model $orm, KernelInterface $kernel)
    {
        $myClass = get_class($orm);
        $fieldChoices = $myClass::getFieldChoices();
        $columnsJson = json_decode($orm->getColumnsJson());
        $fields = array_map(function ($value) use ($fieldChoices) {
            $fieldChoice = $fieldChoices[$value->column];
            return <<<EOD
    /**
     * #pz {$fieldChoice}
     */
    private \${$value->field};
    
EOD;
        }, $columnsJson);

        $methods = array_map(function ($value) {
            $ucfirst = ucfirst($value->field);
            return <<<EOD
    /**
     * @return mixed
     */
    public function get{$ucfirst}()
    {
        return \$this->{$value->field};
    }
    
    /**
     * @param mixed {$value->field}
     */
    public function set{$ucfirst}(\${$value->field})
    {
        \$this->{$value->field} = \${$value->field};
    }
    
EOD;
        }, $columnsJson);

        $generated_file = 'orm_generated.txt';
        $str = file_get_contents($kernel->getProjectDir() . '/vendor/pozoltd/mf/Resources/files/' . $generated_file);
        $str = str_replace('{namespace}', $orm->getNamespace() . '\\Generated', $str);
        $str = str_replace('{classname}', $orm->getClassName(), $str);
        $str = str_replace('{fields}', join("\n", $fields), $str);
        $str = str_replace('{methods}', join("\n", $methods), $str);

        $path = $kernel->getProjectDir() . ($orm->getModelType() == 0 ? '/src/ORM' : '/vendor/pozoltd/mf/Core/ORM') . '/Generated/';

        $file = $path . '../CmsConfig/' . $orm->getClassName() . '.json';
        $dir = dirname($file);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($file, _Model::getEncodedModel($orm));

        $file = $path . $orm->getClassName() . '.php';
        $dir = dirname($file);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($file, $str);
    }

    /**
     * @param _Model $orm
     * @param $container
     */
    static public function setCustomFile(_Model $orm, KernelInterface $kernel)
    {
        $path = $kernel->getProjectDir() . ($orm->getModelType() == 0 ? '/src/ORM' : '/vendor/pozoltd/mf/Core/ORM') . '/';
        $file = $path . $orm->getClassName() . '.php';
        if (!file_exists($file)) {
            $custom_file = 'orm_custom.txt';
            $str = file_get_contents($kernel->getProjectDir() . '/vendor/pozoltd/mf/Resources/files/' . $custom_file);
            $str = str_replace('{namespace}', $orm->getNamespace(), $str);
            $str = str_replace('{classname}', $orm->getClassName(), $str);

            $dir = dirname($file);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($file, $str);
        }
    }

    /**
     * @param mixed $metadata
     */
    public function setMetadata($metadata): void
    {
        if (gettype($metadata) == 'array') {
            $metadata = json_encode($metadata);
        }
        parent::setMetadata($metadata);
    }

    /**
     * @param mixed $presetData
     */
    public function setPresetData($presetData): void
    {
        if (gettype($presetData) == 'array') {
            $presetData = json_encode($presetData);
        }
        parent::setPresetData($presetData);
    }

    /**
     * @return array
     */
    static public function getMetadataChoices()
    {
        $fields = Base::getFields();
        $values = array_diff(array_keys($fields), static::metaExludes);
        $keys = array_map(function ($itm) {
            return ucfirst($itm);
        }, $values);
        $result = array_combine($keys, $values);
        return $result;
    }

    /**
     * @return array
     */
    static public function getFieldChoices()
    {
        $result = array(
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
            'extra16' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra17' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra18' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra19' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra20' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra21' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra22' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra23' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra24' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra25' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra26' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra27' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra28' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra29' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra30' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra31' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra32' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra33' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra34' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra35' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra36' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra37' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra38' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra39' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra40' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra41' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra42' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra43' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra44' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra45' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra46' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra47' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra48' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra49' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'extra50' => "text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'blob' => "LONGBLOB NULL",
        );
        ksort($result, SORT_NATURAL);
        return $result;
    }

    /**
     * @return array
     */
    static public function getWidgetChoices()
    {
        $result = array(
            'Asset picker' => '\\MillenniumFalcon\\Core\\Form\\Type\\AssetPicker',
            'Asset folder picker' => '\\MillenniumFalcon\\Core\\Form\\Type\\AssetFolderPicker',
            'Choice tree' => '\\MillenniumFalcon\\Core\\Form\\Type\\ChoiceTree',
            'Choice multi json' => '\\MillenniumFalcon\\Core\\Form\\Type\\ChoiceMultiJson',
            'Choice multi json tree' => '\\MillenniumFalcon\\Core\\Form\\Type\\ChoiceMultiJsonTree',
            'Date picker' => '\\MillenniumFalcon\\Core\\Form\\Type\\DatePicker',
            'Date time picker' => '\\MillenniumFalcon\\Core\\Form\\Type\\DateTimePicker',
            'Time picker' => '\\MillenniumFalcon\\Core\\Form\\Type\\TimePicker',
            'Wysiwyg' => '\\MillenniumFalcon\\Core\\Form\\Type\\Wysiwyg',
            'Content blocks' => '\\MillenniumFalcon\\Core\\Form\\Type\\ContentBlock',
            'Checkbox' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\CheckboxType',
            'Choice sortable' => '\\MillenniumFalcon\\Core\\Form\\Type\\ChoiceSortable',
            'Choice' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType',
            'Email' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\EmailType',
            'Password' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\PasswordType',
            'Text' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',
            'Textarea' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextareaType',
            'Hidden' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\HiddenType',
            'MKVP' => '\\MillenniumFalcon\\Core\\Form\\Type\\MkvpType',
            'A/B test pages' => '\\MillenniumFalcon\\Core\\Form\\Type\\ABTestType',
        );
        global $CUSTOM_WIDGETS;
        if ($CUSTOM_WIDGETS) {
            $result = array_merge($result, $CUSTOM_WIDGETS);
        }
        ksort($result);
        return $result;
    }
}