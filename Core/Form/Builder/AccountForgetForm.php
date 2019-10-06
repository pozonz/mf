<?php

namespace MillenniumFalcon\Core\Form\Builder;

use MillenniumFalcon\Core\Form\Constraints\ConstraintExist;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Tests\Constraints\EmailTest;

class AccountForgetForm extends AbstractType
{

    public function getBlockPrefix()
    {
        return 'forget_password';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $pdo = $options['pdo'];

        parent::buildForm($builder, $options);

        $builder->add('title', TextType::class, array(
            'label' => 'Enter your email address:',
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Email(),
                new ConstraintExist(array(
                    'pdo' => $pdo,
                    'field' => 'title',
                    'className' => 'Customer',
                    'extraSql' => 'm.isActivated = 1',
                )),
            )
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'pdo' => null,
        ));
    }
}
