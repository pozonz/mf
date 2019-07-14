<?php

namespace MillenniumFalcon\FormDescriptor;

use Doctrine\DBAL\Connection;
//use Pz\Form\Type\Robot;
//use Pz\Orm\FormDescriptor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;


class FormDescriptorBuilder extends AbstractType
{

    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var FormDescriptor $formDescriptor */
        $formDescriptor = $options['formDescriptor'];

        $formFields = json_decode($formDescriptor->getFormFields());
        foreach ($formFields as $key => $field) {
            $widgetClassName = $field->widget;
            $builder->add($field->id, $widgetClassName, $this->getOptionsForField($field));
        }

//        ini_set('xdebug.var_display_max_depth', '10');
//        ini_set('xdebug.var_display_max_children', '256');
//        ini_set('xdebug.var_display_max_data', '1024');
//        var_dump($builder);exit;

        if ($formDescriptor->getAntispam()) {
//            $builder->add('robot', Robot::class, array(
//                "mapped" => false,
//                'label' => '',
//                'constraints' => array(
//                    new \Pz\Form\Constraints\ConstraintRobot(),
//                )
//            ));
        }

        $this->formDescriptor = $formDescriptor;
    }

    public function getName()
    {
        return 'form_' . $this->formDescriptor->getCode();
    }

    public function getOptionsForField($field)
    {
        $options = array(
            'label' => $field->label,
            'attr' => array(
                'placeholder' => preg_replace("/[^a-zA-Z0-9\ ]+/", "", $field->label),
            ),
        );

        switch ($field->widget) {
            case 'choice':
                $options['choices'] = $this->getChoicesForField($field);
                $options['multiple'] = false;
                $options['expanded'] = false;
                $options['empty_data'] = null;
                $options['required'] = false;
                $options['placeholder'] = 'Choose...';
                break;
            case 'repeated':
                $options['type'] = 'password';
                $options['invalid_message'] = 'The password fields must match.';
                $options['options'] = array('attr' => array('class' => 'password-field'));
                $options['required'] = true;
                $options['first_options'] = array('label' => 'Password (8 characters or more):', 'attr' => array('placeholder' => 'Enter Password'));
                $options['second_options'] = array('label' => 'Repeat Password:', 'attr' => array('placeholder' => 'Confirm Password'));
                break;
        }

        $constraints = $this->getValidationForField($field);
        if (count($constraints) > 0) {
            $options['constraints'] = $constraints;
        }

        return $options;
    }

    public function getChoicesForField($field)
    {
        if (isset($field->sql)) {
            /** @var \PDO $pdo */
            $pdo = $this->connection->getWrappedConnection();
            $stmt = $pdo->executeQuery($field->sql);
            $stmt->execute();
            $choices = array();
            foreach ($stmt->fetchAll() as $key => $val) {
                $choices[$val['key']] = $val['value'];
            }
            return $choices;
        }
        return array();
    }

    public function getValidationForField($field)
    {

        $validations = array();

        if ($field->widget == 'email') {
            $validations[] = new Assert\Email();
        }

        if ($field->widget == 'repeated') {
            $validations[] = new Assert\Length(array(
                'min' => 8,
                'max' => 32,
            ));
        }

        if (isset($field->required) && $field->required) {
            $validations[] = new Assert\NotBlank();
        }

        return $validations;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('formDescriptor',null);
        $resolver->setRequired(['formDescriptor']);
    }
}
