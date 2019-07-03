<?php

namespace MillenniumFalcon\Core\Form\Builder;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Form\Constraints\ConstraintUnique;
use MillenniumFalcon\Core\Form\Type\ChoiceMultiJson;
use MillenniumFalcon\Core\Form\Type\LabelType;
use MillenniumFalcon\Core\Form\Type\SpliterType;
use MillenniumFalcon\Core\Nestable\Node;
use MillenniumFalcon\Core\Orm\_Model;
use Symfony\Component\Form\AbstractType;
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
            if ($itm->widget == '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\CheckboxType') {
                $getMethod = 'get' . ucfirst($itm->field);
                $setMethod = 'set' . ucfirst($itm->field);
                $orm->$setMethod($orm->$getMethod() ? true : false);
            }

            $widget = $itm->widget;
            $options = $this->getOptoins($pdo, $itm, $orm);
            $builder->add($itm->field, $widget, $options);
        }

        $presetData = $model->getMetadata() ? json_decode($model->getPresetData()) : array();
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
            $label = preg_replace('/(?<!^)([A-Z])/', ' \\1', $itm);
            $builder->add($itm, LabelType::class, [
                'label' => ucfirst(strtolower($label)) . ':'
            ]);
        }
    }

    /**
     * @param $column
     * @return array
     */
    private function getOptoins($pdo, $column, $orm) {
        $options = array(
            'label' => $column->label,
        );

        switch ($column->widget) {
            case '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType':
            case '\\MillenniumFalcon\\Core\\Form\\Type\\ChoiceMultiJson':
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

                $stmt = $pdo->prepare($column->sql);
                $stmt->execute();
                $result = $stmt->fetchAll(\PDO::FETCH_OBJ);

                $options['choices'] = array();
                foreach ($result as $key => $val) {
                    $options['choices'][$val->value] = $val->key;
                }
                $options['required'] = false;
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

                $stmt = $pdo->prepare($column->sql);
                $stmt->execute();
                $result = $stmt->fetchAll(\PDO::FETCH_OBJ);

                $nodes = array();
                foreach ($result as $key => $val) {
                    $nodes[] = new Node($val->key, $val->parentId ?: 0, $key, 1, $val->value);
                }
                $tree = new Tree($nodes);
                $root = $tree->getRoot();

                $result = static::tree2Array($root, 1);
                $options['choices'] = array(
                    '@1@0' => ''
                );
                $count = 1;
                foreach ($result as $key => $val) {
                    $options['choices'][$val->value . "@$count"] = $val->key;
                    $count++;
                }
                $options['required'] = false;
                break;
        }

        if (!isset($options['constraints']) || gettype($options['constraints']) != 'array') {
            $options['constraints'] = array();
        }

        if ($column->required == 1) {
            $options['constraints'][] = new Assert\NotBlank();
        }

        if ($column->unique == 1) {
            $options['constraints'][] = new ConstraintUnique(array(
                'orm' => $orm,
                'field' => $column->field,
            ));
        }

        return $options;
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
