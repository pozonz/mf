<?php

namespace MillenniumFalcon\Core\Form\Builder;

//use MillenniumFalcon\Core\Form\Type\ChoiceMultiJson;
use MillenniumFalcon\Core\Form\Type\ChoiceMultiJson;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class Model extends AbstractType
{

    public function getBlockPrefix()
    {
        return 'model';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $defaultSortByOptions = isset($options['defaultSortByOptions']) ? $options['defaultSortByOptions'] : array();
        $dataGroups = isset($options['dataGroups']) ? $options['dataGroups'] : array();

        $builder
            ->add('title', TextType::class, array(
                'label' => 'Name:',
                'constraints' => array(
                    new Assert\NotBlank()
                )
            ))
            ->add('className', TextType::class, array(
                'label' => 'Class Name:',
                'constraints' => array(
                    new Assert\NotBlank()
                )
            ))
            ->add('modelType', ChoiceType::class, array(
                'label' => 'Model Type:',
                'choices' => array(
                    'Customised' => 0,
                    'Built in' => 1,
                )
            ))
            ->add('listType', ChoiceType::class, array(
                'label' => 'Listing Type:',
                'choices' => array(
                    'Drag & Drop' => 0,
                    'Pagination' => 1,
                    'Tree' => 2,
                )
            ))
            ->add('numberPerPage', TextType::class, array(
                'label' => 'Page Size:',
            ))
            ->add('defaultSortBy', ChoiceType::class, array(
                'label' => 'Default Sorted By:',
                'choices' => $defaultSortByOptions,
            ))
            ->add('dataType', ChoiceType::class, array(
                'label' => 'Display In:',
                'choices' => array(
                    'Admin' => 1,
                    'Custom Partitions' => 0,
                    'Hidden' => 2,
                )
            ))
            ->add('defaultOrder', ChoiceType::class, array(
                'label' => 'Default Order:',
                'choices' => array(
                    'ASC' => 0,
                    'DESC' => 1,
                )
            ))
            ->add('dataGroups', ChoiceMultiJson::class, array(
                'label' => 'Choose Partitions:',
                'choices' => $dataGroups,
            ))
            ->add('columnsJson', TextareaType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'defaultSortByOptions' => array(),
            'dataGroups' => array(),
        ));
    }
}
