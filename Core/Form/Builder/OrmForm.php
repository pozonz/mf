<?php

namespace MillenniumFalcon\Core\Form\Builder;

use BlueM\Tree;
use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Form\Constraints\ConstraintUnique;
use MillenniumFalcon\Core\Form\Type\ChoiceMultiJson;
use MillenniumFalcon\Core\Form\Type\ChoiceTree;
use MillenniumFalcon\Core\Form\Type\LabelType;
use MillenniumFalcon\Core\Form\Type\SpliterType;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OrmForm extends AbstractType
{

    public function getBlockPrefix()
    {
        return 'orm';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('uniqid', HiddenType::class);

        $model = isset($options['model']) ? $options['model'] : null;
        $orm = isset($options['orm']) ? $options['orm'] : null;
        $pdo = isset($options['pdo']) ? $options['pdo'] : null;

        $columnsJson = json_decode($model->getColumnsJson());
        foreach ($columnsJson as $itm) {
            $getMethod = 'get' . ucfirst($itm->field);
            $setMethod = 'set' . ucfirst($itm->field);

            if ($orm && !method_exists($orm, $getMethod)) {
                continue;
            }

            if ($itm->widget == '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\CheckboxType') {
                $orm->$setMethod($orm->$getMethod() ? true : false);
            }

            $widget = $itm->widget;
            $opts = $this->getOpts($pdo, $itm, $orm);
            $builder->add($itm->field, $widget, $opts);
        }

        $builder->add('status', ChoiceType::class, [
            'expanded' => 1,
            'choices' => [
                'Enabled' => 1,
                'Disabled' => 0,
            ]
        ]);

        $presetData = json_decode($model->getPresetData() ?: '[]');
        foreach ($presetData as $presetDataItem) {
            $presetDataMap = _Model::presetDataMap;
            if ($presetDataMap[$presetDataItem]) {
                $builder->add(uniqid(), SpliterType::class, array(
                    'mapped' => false,
                ));
                $presetDataMapItem = $presetDataMap[$presetDataItem];
                foreach ($presetDataMapItem as $idx => $itm) {
                    $label = preg_replace('/(?<!^)([A-Z])/', ' \\1', $idx);
                    $builder->add($idx, $itm, [
                        'label' => ucfirst(strtolower($label)) . ':'
                    ]);
                }
            }
        }

        $metadata = $model->getMetadata() ? json_decode($model->getMetadata()) : array();
        if (count($metadata)) {
            $builder->add(uniqid(), SpliterType::class, array(
                'mapped' => false,
            ));
        }
        foreach ($metadata as $itm) {
            switch ($itm) {
                case 'isBuiltIn':
                    $orm->setIsBuiltIn($orm->getIsBuiltIn() == 1 ? true : false);

                    $column = new \stdClass();
                    $column->widget = '\\MillenniumFalcon\\Core\\Form\\Type\\CheckboxType';
                    $column->label = 'Built-in data';
                    $column->required = 0;
                    $column->unique = 0;
                    $builder->add($itm, CheckboxType::class, $this->getOpts($pdo, $column, $orm));
                    break;
                default:
                    $label = preg_replace('/(?<!^)([A-Z])/', ' \\1', $itm);
                    $builder->add($itm, LabelType::class, [
                        'label' => ucfirst(strtolower($label)) . ':'
                    ]);
            }
        }
    }

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
            case '\\MillenniumFalcon\\Core\\Form\\Type\\ABTestType':
                $fullClass = ModelService::fullClass($pdo, 'Page');
                $data = $fullClass::active($pdo, [
                    'sort' => 'title',
                    'whereSql' => '(m.hideFromCMSNav != 1 OR m.hideFromCMSNav IS NULL) AND (m.title != 404)',
                ]);

                $opts['choices'] = array();
                foreach ($data as $key => $val) {
                    $opts['choices']["{$val->getTitle()} ({$val->getUrl()})"] = $val->getId();
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
            ));
        }

        return $opts;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'model' => null,
            'orm' => null,
            'pdo' => null,
        ));
    }
}
