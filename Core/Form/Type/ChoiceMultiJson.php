<?php
namespace MillenniumFalcon\Core\Form\Type;

use Pz\Common\Utils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ChoiceMultiJson extends AbstractType
{

    public function getBlockPrefix()
    {
        return 'choice_multi_json';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['choices'] = array();
        foreach ($options['choices'] as $idx => $itm) {
            $view->vars['choices'][] = array(
                'value' => $itm,
                'label' => $idx,
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'compound' => false,
            'choices' => array(),
            'placeholder' => "Choose options...",
        ));
    }
}

