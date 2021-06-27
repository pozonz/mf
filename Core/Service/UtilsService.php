<?php

namespace MillenniumFalcon\Core\Service;

use BlueM\Tree;
use BlueM\Tree\Node;
use Cocur\Slugify\Slugify;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MillenniumFalcon\Core\Tree\RawData;
use Symfony\Component\HttpFoundation\Request;

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
                if (($item->widget == 9 || $item->widget == 10 || $item->widget == 14) && isset($item->sql)) {
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
                } else if (($item->widget == 12 || $item->widget == 13) && isset($item->sql)) {
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

                        $nodes = [];
                        foreach ($stmt->fetchAll() as $key => $val) {
                            $nodes[] = (array)new RawData([
                                'id' => $val['key'],
                                'parent' => $val['parentId'],
                                'title' => $val['value'],
                                'status' => 1,
                            ]);
                        }
                        $tree = new Tree($nodes, [
                            'rootId' => null,
                            'buildwarningcallback' => function () {},
                        ]);

                        $choices = [];
                        foreach ($tree->getRootNodes() as $itm) {
                            $choices = array_merge($choices, $this->getChoices($itm, 1));
                        }

//                        ini_set('xdebug.var_display_max_depth', '100');
//                        ini_set('xdebug.var_display_max_children', '2560');
//                        ini_set('xdebug.var_display_max_data', '10240');
//                        while (@ob_end_clean());
//                        var_dump($choices);exit;
                    }
                }
                $item->choices = $choices;
            }
            $block->setItems(json_encode($items));
        }
        return $blocks;
    }

    /**
     * @param Node $node
     * @param $level
     * @return array
     */
    private function getChoices(Node $node, $level) {
        $result = [];
        $result[] = [
            'level' => $level,
            'value' => $node->getId(),
            'label' => $node->getTitle(),
        ];
        foreach ($node->getChildren() as $itm) {
            $result = array_merge($result, $this->getChoices($itm, $level + 1));
        }
        return $result;
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
            12 => 'Choice tree',
            13 => 'Choice multi json tree',
            14 => 'Choice sortable',
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
        $tree = $this->navTree($categoryCode);
        if ($tree) {
            return $tree->getRootNodes();
        }
        return null;
    }

    /**
     * @param $nodeId
     * @param $tree
     * @return Node|null
     */
    public function getNodeInTree($nodeId, $tree)
    {
        if (gettype($tree) == 'string') {
            $tree = $this->navTree($tree);
        }

        try {
            return $tree->getNodeById($nodeId);
        } catch (\InvalidArgumentException $ex) {
        }

        return null;
    }

    /**
     * @param $categoryCode
     * @return null
     */
    public function navTree($categoryCode)
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

            return new Tree($nodes, [
                'buildwarningcallback' => function () {
                },
            ]);
        }

        return null;
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
     * @param Request $request
     * @return array|null[]
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    static public function ip_info(Request $request)
    {
        $ip = getenv('TEST_CLIENT_IP') ?: $request->getClientIp();
        if (getenv('GEOIP_DB_PATH')) {
            $geoDbPath = getenv('GEOIP_DB_PATH');
            if (file_exists($geoDbPath)) {
                $geoipReader = new Reader($geoDbPath);
                try {
                    $geoIpCountry = $geoipReader->country($ip);
                    return [
                        'name' => $geoIpCountry->country->name,
                        'country_code' => $geoIpCountry->country->isoCode,
                        'geonameId' => $geoIpCountry->country->geonameId
                    ];


                } catch (AddressNotFoundException $addressNotFoundException){}
            }
        }
        return null;
    }
}