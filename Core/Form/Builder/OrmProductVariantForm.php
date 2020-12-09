<?php

namespace MillenniumFalcon\Core\Form\Builder;

use BlueM\Tree;
use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Form\Constraints\ConstraintUnique;
use MillenniumFalcon\Core\Form\Type\ChoiceMultiJson;
use MillenniumFalcon\Core\Form\Type\LabelType;
use MillenniumFalcon\Core\Form\Type\SpliterType;
use MillenniumFalcon\Core\ORM\_Model;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OrmProductVariantForm extends OrmForm
{
    /**
     * @param $column
     * @return array
     */
    protected function getOpts($pdo, $column, $orm)
    {
        $opts = array(
            'label' => $column->label,
        );

        switch ($column->widget) {
            case '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType':
            case '\\MillenniumFalcon\\Core\\Form\\Type\\ChoiceMultiJson':
            case '\\MillenniumFalcon\\Core\\Form\\Type\\ChoiceSortable':
                $slugify = new Slugify(['trim' => false]);
                preg_match('/\bfrom\b\s*(\w+)/i', $column->sql, $matches);
                if (count($matches) == 2) {
                    if (substr($matches[1], 0, 1) == '_') {
                        $tablename = strtolower($matches[1]);
                    } else {
                        $tablename = $slugify->slugify($matches[1]);
                    }

                    $column->sql = str_replace($matches[0], "FROM $tablename", $column->sql);
                }

                $result = [];
                if ($column->sql) {
                    $stmt = $pdo->prepare($column->sql);
                    $stmt->execute();
                    $result = $stmt->fetchAll(\PDO::FETCH_OBJ);

                    $result = array_filter(array_map(function ($itm) {
                        if (!isset($itm->value) || !isset($itm->key)) {
                            return null;
                        }
                        return $itm;
                    }, $result));
                }

                $opts['choices'] = array();
                foreach ($result as $key => $val) {
                    $opts['choices'][$val->value] = $val->key;
                }

                if ($column->widget == '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType') {
                    $opts['placeholder'] = 'Choose an option';
                }

//                $opts['required'] = false;
                break;

            case '\\MillenniumFalcon\\Core\\Form\\Type\\ChoiceMultiJsonTree':
            case '\\MillenniumFalcon\\Core\\Form\\Type\\ChoiceTree':
                $slugify = new Slugify(['trim' => false]);
                preg_match('/\bfrom\b\s*(\w+)/i', $column->sql, $matches);
                if (count($matches) == 2) {
                    if (substr($matches[1], 0, 1) == '_') {
                        $tablename = $matches[1];
                    } else {
                        $tablename = $slugify->slugify($matches[1]);
                    }

                    $column->sql = str_replace($matches[0], "FROM $tablename", $column->sql);
                }

                $result = [];
                if ($column->sql) {
                    $stmt = $pdo->prepare($column->sql);
                    $stmt->execute();
                    $result = $stmt->fetchAll(\PDO::FETCH_OBJ);
                }

                $nodes = array();
                foreach ($result as $key => $val) {
                    $nodes[] = [
                        'id' => $val->key,
                        'parent' => $val->parentId ?: 0, $key,
                        'title' => $val->value,
                    ];
                }
                $tree = new Tree($nodes, [
                    'buildwarningcallback' => function () {
                    },
                ]);
                $opts['choices'] = $tree->getRootNodes();
//                $opts['required'] = false;
                break;
        }

        if (!isset($opts['constraints']) || gettype($opts['constraints']) != 'array') {
            $opts['constraints'] = array();
        }

        if ($column->required == 1) {
            $opts['constraints'][] = new Assert\NotBlank();
        }

        if ($column->unique == 1) {
            $opts['constraints'][] = new ConstraintUnique(array(
                'orm' => $orm,
                'field' => $column->field,
                'joins' => 'RIGHT JOIN product AS p ON p.uniqid = m.productUniqid',
            ));
        }

        return $opts;
    }
}
