<?php

namespace MillenniumFalcon\FormDescriptor\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CheckboxesType extends AbstractType
{
    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'form_descriptor_checkboxes';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => false,
        ));
    }
}
