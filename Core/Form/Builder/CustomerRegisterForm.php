<?php

namespace MillenniumFalcon\Core\Form\Builder;


use MillenniumFalcon\Core\Form\Constraints\ConstraintUnique;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CustomerRegisterForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $orm = $options['orm'];

        $builder
            ->add('title', TextType::class, array(
                'label' => 'Email address:',
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Email(),
                    new ConstraintUnique(array(
                        'orm' => $orm,
                        'field' => 'title',
                        'extraSql' => 'm.isActivated IS NOT NULL',
                    )),
                )
            ))
            ->add('firstName', TextType::class, array(
                'label' => 'First name:',
                'constraints' => array(
                    new Assert\NotBlank(),
                )
            ))
            ->add('lastName', TextType::class, array(
                'label' => 'Last name:',
                'constraints' => array(
                    new Assert\NotBlank(),
                )
            ))
            ->add('passwordInput', RepeatedType::class, array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array('min' => 6)),
                ),
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'required' => true,
                'first_options' => array('label' => 'Password'),
                'second_options' => array('label' => 'Repeat Password')
            ))
            ->add('agree', CheckboxType::class, array(
                'label' => 'Agree:',
                'mapped' => false,
                'constraints' => array(
                    new Assert\NotBlank(),
                )
            ))
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'orm' => null,
        ));
    }
}
