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

class OrmShippingOptionMethodForm extends AbstractType
{

    public function getBlockPrefix()
    {
        return 'shipping';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $methods = $options['methods'];

        parent::buildForm($builder, $options);
        $builder
            ->add('className', ChoiceType::class, [
                'label' => 'Method:',
                'choices' => $methods,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'methods' => null,
        ));
    }
}
