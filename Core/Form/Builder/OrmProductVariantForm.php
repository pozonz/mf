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
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OrmProductVariantForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('title', TextType::class, [
                'label' => 'Title:',
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('sku', TextType::class, [
                'label' => 'SKU:',
                'constraints' => [
                    new Assert\NotBlank(),
                    new ConstraintUnique([
                        'orm' => $options['orm'],
                        'field' => 'sku',
                    ]),
                ],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price:',
                'constraints' => [
                    new Assert\NotBlank(),
                ],

            ])
            ->add('weight', NumberType::class, [
                'label' => 'Weight:',
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('stock', NumberType::class, [
                'label' => 'Stock:',
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'orm' => null,
        ));
    }
}
