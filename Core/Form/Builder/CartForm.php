<?php

namespace MillenniumFalcon\Core\Form\Builder;

use MillenniumFalcon\Core\Form\Constraints\ConstraintRequired;
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CartForm extends AbstractType
{

    public function getBlockPrefix()
    {
        return 'cart';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $pdo = $options['pdo'];

        $fullClass = ModelService::fullClass($pdo, 'ShippingOption');
        $shippingOptions = $fullClass::active($pdo);

        $result = array();
        foreach ($shippingOptions as $itm) {
            $result = array_merge($result, $itm->objCountries());
        }

        $countries = array();
        foreach ($result as $itm) {
            $countries[$itm->getTitle()] = $itm->getCode();
        }

        ksort($countries);

        parent::buildForm($builder, $options);

        $builder
            ->add('action', TextType::class, array(
                'mapped' => false,
            ))
            ->add('email', TextType::class, array(
                'label' => 'Email address:',
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Email(),
                )
            ))
            ->add('billingFirstName', TextType::class, array(
                'label' => 'First name:',
                'constraints' => array(
                    new Assert\NotBlank(),
                )
            ))
            ->add('billingLastName', TextType::class, array(
                'label' => 'Last name:',
                'constraints' => array(
                    new Assert\NotBlank(),
                )
            ))
            ->add('billingPhone', TextType::class, array(
                'label' => 'Phone:',
                'constraints' => array(
                    new Assert\NotBlank(),
                )
            ))
            ->add('billingAddress', TextType::class, array(
                'label' => 'Address:',
                'constraints' => array(
                    new Assert\NotBlank(),
                )
            ))
            ->add('billingAddress2', TextType::class, array(
                'label' => 'Address2:',
            ))
            ->add('billingCity', TextType::class, array(
                'label' => 'City:',
                'constraints' => array(
                    new Assert\NotBlank(),
                )
            ))
            ->add('billingPostcode', TextType::class, array(
                'label' => 'Postcode:',
                'constraints' => array(
                    new Assert\NotBlank(),
                )
            ))
            ->add('billingState', TextType::class, array(
                'label' => 'State:',
            ))
            ->add('billingCountry', ChoiceType::class, array(
                'required' => false,
                'empty_data' => null,
                'label' => 'Country:',
                'choices' => $countries,
                'constraints' => array(
                    new Assert\NotBlank(),
                )
            ))
            ->add('billingSave', CheckboxType::class, array(
                'label' => 'Save this address',
            ))
            ->add('note', TextareaType::class, array(
                'label' => 'Note:',
            ))
            ->add('billingSame', CheckboxType::class, array(
                'label' => 'Same as Billing Address',
            ))
            ->add('shippingFirstName', TextType::class, array(
                'label' => 'First name:',
                'constraints' => array(
                    new ConstraintRequired(array(
                        'form' => $this,
                        'field' => 'billingSame',
                    )),
                )
            ))
            ->add('shippingLastName', TextType::class, array(
                'label' => 'Last Name:',
                'constraints' => array(
                    new ConstraintRequired(array(
                        'form' => $this,
                        'field' => 'billingSame',
                    )),
                )
            ))
            ->add('shippingPhone', TextType::class, array(
                'label' => 'Phone:',
                'constraints' => array(
                    new ConstraintRequired(array(
                        'form' => $this,
                        'field' => 'billingSame',
                    )),
                )
            ))
            ->add('shippingAddress', TextType::class, array(
                'label' => 'Address:',
                'constraints' => array(
                    new ConstraintRequired(array(
                        'form' => $this,
                        'field' => 'billingSame',
                    )),
                )
            ))
            ->add('shippingAddress2', TextType::class, array(
                'label' => 'Address2:',
            ))
            ->add('shippingCity', TextType::class, array(
                'label' => 'City:',
                'constraints' => array(
                    new ConstraintRequired(array(
                        'form' => $this,
                        'field' => 'billingSame',
                    )),
                )
            ))
            ->add('shippingPostcode', TextType::class, array(
                'label' => 'Postcode:',
                'constraints' => array(
                    new ConstraintRequired(array(
                        'form' => $this,
                        'field' => 'billingSame',
                    )),
                )
            ))
            ->add('shippingState', TextType::class, array(
                'label' => 'State:',
            ))
            ->add('shippingCountry', ChoiceType::class, array(
                'required' => false,
                'empty_data' => null,
                'label' => 'Country:',
                'choices' => $countries,
                'constraints' => array(
                    new ConstraintRequired(array(
                        'form' => $this,
                        'field' => 'billingSame',
                    )),
                )
            ))
            ->add('shippingSave', CheckboxType::class, array(
                'label' => 'Save this address',
            ))
            ->add('shippingId', TextType::class, array(
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
            'pdo' => null,
        ));
    }
}
