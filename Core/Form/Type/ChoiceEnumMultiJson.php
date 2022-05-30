<?php

namespace MillenniumFalcon\Core\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class ChoiceEnumMultiJson extends AbstractType
{

    public function getBlockPrefix(): string
    {
        return 'choice_enum_multi_json';
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $c = $options['class'];
        $view->vars['choices'] = $c::cases();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'compound' => false,
            'choices' => [],
            'placeholder' => "Choose options...",
            'class' => null,
        ));
    }

}
