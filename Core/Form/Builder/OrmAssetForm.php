<?php

namespace MillenniumFalcon\Core\Form\Builder;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Form\Type\ChoiceMultiJson;
use MillenniumFalcon\Core\Nestable\Node;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OrmAssetForm extends OrmForm
{

    public function getBlockPrefix()
    {
        return 'orm';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('file', FileType::class, [
            'mapped' => false,
        ]);
    }
}
