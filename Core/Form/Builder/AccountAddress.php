<?php

namespace MillenniumFalcon\Core\Form\Builder;

use MillenniumFalcon\Core\Service\ModelService;
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

class AccountAddress extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $pdo = $options['pdo'];

        $countries = array();

        $result = array();

        $fullClass = ModelService::fullClass($pdo, 'ShippingOption');
        $shippingOptions = $fullClass::active($pdo);
        foreach ($shippingOptions as $itm) {
            $result = array_merge($result, $itm->objCountries());
        }
        foreach ($result as $itm) {
            $countries[$itm->getTitle()] = $itm->getCode();
        }

        ksort($countries);

        parent::buildForm($builder, $options);

        $builder->add('firstName', TextType::class, array(
            'label' => 'First name:',
            'constraints' => array(
                new Assert\NotBlank(),
            )
        ))->add('lastName', TextType::class, array(
            'label' => 'Last name:',
            'constraints' => array(
                new Assert\NotBlank(),
            )
        ))->add('phone', TextType::class, array(
            'label' => 'Phone:',
            'constraints' => array(
                new Assert\NotBlank(),
            )
        ))->add('address', TextType::class, array(
            'label' => 'Address:',
            'constraints' => array(
                new Assert\NotBlank(),
            )
        ))->add('address2', TextType::class, array(
            'label' => 'Address2:',
        ))->add('city', TextType::class, array(
            'label' => 'City:',
            'constraints' => array(
                new Assert\NotBlank(),
            )
        ))->add('postcode', TextType::class, array(
            'label' => 'Postcode:',
            'constraints' => array(
                new Assert\NotBlank(),
            )
        ))->add('state', TextType::class, array(
            'label' => 'State:',
        ))->add('country', ChoiceType::class, array(
            'required' => false,
            'empty_data' => null,
            'label' => 'Country:',
            'choices' => $countries,
            'constraints' => array(
                new Assert\NotBlank(),
            ),
        ))->add('primaryAddress', CheckboxType::class, array(
            'label' => 'Use as primary address:',
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
