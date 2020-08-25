<?php

namespace MillenniumFalcon\Core\Service;

use BlueM\Tree;
use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Tree\RawData;

class UtilsService
{
    const COUNTRY_SESSION_KEY = '__form_country';
    
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
        $slugify = new Slugify(['trim' => false]);
        $pdo = $this->connection;

        $fullClass = ModelService::fullClass($pdo, 'FragmentBlock');
        $blocks = $fullClass::active($pdo, [
            'sort' => 'title',
        ]);
        foreach ($blocks as $block) {
            $items = $block->getItems() ? json_decode($block->getItems()) : [];
            foreach ($items as &$item) {
                $choices = array();
                if (($item->widget == 9 || $item->widget == 10) && isset($item->sql)) {
                    preg_match('/\bfrom\b\s*(\w+)/i', $item->sql, $matches);
                    if (count($matches) == 2) {
                        if (substr($matches[1], 0, 1) == '_') {
                            $tablename = strtolower($matches[1]);
                        } else {
                            $tablename = $slugify->slugify($matches[1]);
                        }

                        $item->sql = str_replace($matches[0], "FROM $tablename", $item->sql);
                    }
                    if ($item->sql) {
                        $stmt = $pdo->prepare($item->sql);
                        $stmt->execute();
                        foreach ($stmt->fetchAll() as $key => $val) {
                            $choices[] = $val['key'] . "-" . $val['value'];
                        }
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
        $widgets = array(
            'Date' => '\\MillenniumFalcon\\FormDescriptor\\Type\\DateType',
            'File' => '\\MillenniumFalcon\\FormDescriptor\\Type\\FileType',
            'Dropdown' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType',
            'Checkboxes' => '\\MillenniumFalcon\\FormDescriptor\\Type\\CheckboxesType',
            'Radio buttons' => '\\MillenniumFalcon\\FormDescriptor\\Type\\RadioButtonsType',
            'Checkbox' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\CheckboxType',
            'Email' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\EmailType',
            'Hidden' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\HiddenType',
            'Text' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',
            'Textarea' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextareaType',
            'Repeated' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\RepeatedType',
            'Submit' => '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\SubmitType',
        );
        ksort($widgets);
        return array_flip($widgets);
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
     * @param array $array The multi-dimensional array.
     * @return array
     * @author Sean Cannon, LitmusBox.com | seanc@litmusbox.com
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
     * @param $container
     * @return null
     */
    static public function getUser($container)
    {
        $token = $container->get('security.token_storage')->getToken();
        $user = $token ? $token->getUser() : null;
        return $user && gettype($user) == 'object' ? $user : null;
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
            $pages = $fullClass::active($pdo, array(
                'whereSql' => 'm.category LIKE ? ',
                'params' => array('%"' . $category->getId() . '"%'),
            ));

            $nodes = array();
            foreach ($pages as $itm) {
                $categoryParent = !$itm->getCategoryParent() ? array() : (array)json_decode($itm->getCategoryParent());
                $categoryRank = !$itm->getCategoryRank() ? array() : (array)json_decode($itm->getCategoryRank());
                $parent = isset($categoryParent['cat' . $category->getId()]) ? $categoryParent['cat' . $category->getId()] : 0;
                $rank = isset($categoryRank['cat' . $category->getId()]) ? $categoryRank['cat' . $category->getId()] : 0;

                $nodes[] = (array)new RawData([
                    'id' => $itm->getId(),
                    'parent' => $parent,
                    'title' => $itm->getTitle(),
                    'url' => $itm->getUrl(),
                    'rank' => $rank,
                    'status' => $itm->getHideFromWebNav() ? 0 : 1,
                    'icon' => $itm->getIconClass(),
                    'allowExtra' => $itm->getAllowExtra(),
                    'maxParams' => $itm->getMaxParams(),
                    'extraInfo' => $itm,
                ]);
            }

            usort($nodes, function ($a, $b) {
                return $a['rank'] >= $b['rank'];
            });

            $tree = new Tree($nodes, [
                'buildwarningcallback' => function () {
                },
            ]);
            $result = $tree->getRootNodes();
        }

        return $result;
    }

    /**
     * @param $value
     * @return string
     */
    public function getFormData($value)
    {
        if ($value[2] == '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextareaType') {
            return nl2br($value[1]);
        } else if ($value[2] == '\\MillenniumFalcon\\Core\\Form\\Type\\Wysiwyg') {
            return $value[1];
        }
        return strip_tags($value[1]);
    }

    /**
     * @param null $ip
     * @param string $purpose
     * @param bool $deep_detect
     * @return array|string|null
     */
    static public function ip_info($ip = NULL, $purpose = "location", $deep_detect = TRUE)
    {
        $output = NULL;
        if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
            $ip = $_SERVER["REMOTE_ADDR"];
            if ($deep_detect) {
                if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
            }
        }
        $purpose = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
        $support = array("country", "countrycode", "state", "region", "city", "location", "address");
        $continents = array(
            "AF" => "Africa",
            "AN" => "Antarctica",
            "AS" => "Asia",
            "EU" => "Europe",
            "OC" => "Australia (Oceania)",
            "NA" => "North America",
            "SA" => "South America"
        );
        if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
            $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
            if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
                switch ($purpose) {
                    case "location":
                        $output = array(
                            "city" => @$ipdat->geoplugin_city,
                            "state" => @$ipdat->geoplugin_regionName,
                            "country" => @$ipdat->geoplugin_countryName,
                            "country_code" => @$ipdat->geoplugin_countryCode,
                            "continent" => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
                            "continent_code" => @$ipdat->geoplugin_continentCode
                        );
                        break;
                    case "address":
                        $address = array($ipdat->geoplugin_countryName);
                        if (@strlen($ipdat->geoplugin_regionName) >= 1)
                            $address[] = $ipdat->geoplugin_regionName;
                        if (@strlen($ipdat->geoplugin_city) >= 1)
                            $address[] = $ipdat->geoplugin_city;
                        $output = implode(", ", array_reverse($address));
                        break;
                    case "city":
                        $output = @$ipdat->geoplugin_city;
                        break;
                    case "state":
                        $output = @$ipdat->geoplugin_regionName;
                        break;
                    case "region":
                        $output = @$ipdat->geoplugin_regionName;
                        break;
                    case "country":
                        $output = @$ipdat->geoplugin_countryName;
                        break;
                    case "countrycode":
                        $output = @$ipdat->geoplugin_countryCode;
                        break;
                }
            }
        }
        return $output;
    }
}