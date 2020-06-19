<?php

namespace MillenniumFalcon\Core\Form\Builder;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Form\Constraints\ConstraintUnique;
use MillenniumFalcon\Core\Form\Type\ChoiceMultiJson;
use MillenniumFalcon\Core\Form\Type\ChoiceTree;
use MillenniumFalcon\Core\Form\Type\LabelType;
use MillenniumFalcon\Core\Form\Type\SpliterType;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CartAddItemForm extends AbstractType
{

    public function getBlockPrefix()
    {
        return 'cart_add';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $maxQuantity = $options['maxQuantity'];

        parent::buildForm($builder, $options);
        $builder
            ->add('quantity', TextType::class, [
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Range([
                        'min' => 1,
                        'max' => $maxQuantity,
                        'maxMessage' => 'Sorry, we only have {{ limit }} in stock'
                    ]),
                )
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'maxQuantity' => null,
        ));
    }
}
