<?php

namespace MillenniumFalcon\Core\Form\Builder;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Form\Constraints\ConstraintUnique;
use MillenniumFalcon\Core\Form\Type\ChoiceMultiJson;
use MillenniumFalcon\Core\Form\Type\ChoiceTree;
use MillenniumFalcon\Core\Form\Type\LabelType;
use MillenniumFalcon\Core\Form\Type\SpliterType;
use MillenniumFalcon\Core\Nestable\Node;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OrmProductsForm extends AbstractType
{

    public function getBlockPrefix()
    {
        return 'search';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $categories = $options['categories'];

        parent::buildForm($builder, $options);
        $builder
            ->add('category', ChoiceTree::class, [
                'label' => 'Category:',
                'choices' => $categories,
            ])
            ->add('stock', ChoiceType::class, [
                'label' => 'Stock status:',
                'choices' => [
                    'In stock & Out of stock' => 0,
                    'In stock only' => 1,
                    'Out of stock only' => 2,
                ],
            ])
            ->add('keywords', TextType::class, [
                'label' => 'Keywords:'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'categories' => null,
        ));
    }
}
