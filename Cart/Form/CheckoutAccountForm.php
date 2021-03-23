<?php

namespace MillenniumFalcon\Cart\Form;

use MillenniumFalcon\Cart\Form\Constraints\NotBlankIfRequired;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CheckoutAccountForm extends AbstractType
{

    public function getBlockPrefix()
    {
        return 'cart_account';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $request = $options['request'];

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email address',
                'required' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Email(),
                )
            ])
            ->add('createAnAccount', CheckboxType::class, [
                'required' => false,
                'label' => 'Create an account?',
            ])
            ->add('passwordInput', RepeatedType::class, [
                'constraints' => [
                    new NotBlankIfRequired([
                        'callback' => function($request) {
                            $data = $request->get($this->getBlockPrefix());
                            return isset($data['createAnAccount']) && $data['createAnAccount'] == 1 ? 1 : 0;
                        },
                        'request' => $request,
                    ]),
                    new Assert\Length([
                        'min' => 6,
                        'max' => 64,
                    ]),
                ],
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'required' => true,
                'first_options'  => [
                    'label' => 'Password'
                ],
                'second_options' => [
                    'label' => 'Repeat password'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'request' => null,
        ]);
    }
}
