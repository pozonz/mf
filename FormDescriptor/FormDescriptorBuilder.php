<?php

namespace MillenniumFalcon\FormDescriptor;

use Cocur\Slugify\Slugify;
use Doctrine\DBAL\Connection;

//use Pz\Form\Type\Robot;
//use Pz\Orm\FormDescriptor;
use MillenniumFalcon\Core\Form\Constraints\ConstraintRobot;
use MillenniumFalcon\Core\Form\Type\RobotType;
use MillenniumFalcon\Core\Service\UtilsService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;


class FormDescriptorBuilder extends AbstractType
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    public function __construct(Connection $connection, SessionInterface $session, KernelInterface $kernel)
    {
        $this->connection = $connection;
        $this->session = $session;
        $this->kernel = $kernel;
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

        $countryInfo = $this->session->get(UtilsService::COUNTRY_SESSION_KEY);
        if (!$countryInfo) {
            $request = Request::createFromGlobals();
            $countryInfo = UtilsService::ip_info(getenv('TEST_CLIENT_IP') ?: $request->getClientIp());
            $countryInfo = $countryInfo ?: [];
            $this->session->set(UtilsService::COUNTRY_SESSION_KEY, $countryInfo);
        }

        if ($formDescriptor->getAntispam()) {
            $safeCountries = getenv('SAFE_COUNTRIES') ?: 'NZ,AU';
            if (!isset($countryInfo['country_code']) || !in_array($countryInfo['country_code'], explode(',', $safeCountries))) {
                $builder->add('robot', RobotType::class, array(
                    "mapped" => false,
                    'label' => '',
                    'constraints' => array(
                        new ConstraintRobot(),
                    )
                ));
            }
        }

        $this->formDescriptor = $formDescriptor;
    }

    public function getName()
    {
        return 'form_' . $this->formDescriptor->getCode();
    }

    public function getOptionsForField($field)
    {
        $options = [
            'required' => $field->required ? true : false,
            'label' => $field->label,
            'attr' => [
//                'placeholder' => preg_replace("/[^a-zA-Z0-9\ ]+/", "", $field->label),
            ],
        ];

        switch ($field->widget) {
            case '\\Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType':
                $options['choices'] = $this->getChoicesForField($field);
                $options['multiple'] = false;
                $options['expanded'] = false;
                $options['empty_data'] = null;
                $options['placeholder'] = 'Choose...';
                break;
            case '\\MillenniumFalcon\\FormDescriptor\\Type\\RadioButtonsType':
                $options['choices'] = $this->getChoicesForField($field);
                $options['multiple'] = false;
                $options['expanded'] = true;
                $options['empty_data'] = null;
                $options['placeholder'] = false;
                break;
            case '\\MillenniumFalcon\\FormDescriptor\\Type\\CheckboxesType':
                $options['choices'] = $this->getChoicesForField($field);
                $options['multiple'] = true;
                $options['expanded'] = true;
                $options['empty_data'] = null;
                $options['placeholder'] = false;
                break;
            case 'repeated':
                $options['type'] = 'password';
                $options['invalid_message'] = 'The password fields must match.';
                $options['options'] = array('attr' => array('class' => 'password-field'));
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
        if (!isset($field->optionType) || $field->optionType == 1) {
            $slugify = new Slugify(['trim' => false]);
            preg_match('/\bfrom\b\s*(\w+)/i', $field->sql, $matches);
            if (count($matches) == 2) {
                if (substr($matches[1], 0, 1) == '_') {
                    $tablename = strtolower($matches[1]);
                } else {
                    $tablename = $slugify->slugify($matches[1]);
                }

                $field->sql = str_replace($matches[0], "FROM $tablename", $field->sql);
            }

            $pdo = $this->connection;
            $stmt = $pdo->executeQuery($field->sql);
            $stmt->execute();
            $choices = [];
            foreach ($stmt->fetchAll() as $key => $val) {
                $choices[$val['value']] = $val['key'];
            }
            return $choices;
        } else {
            $options = $field->options ?? [];
            $choices = [];
            foreach ($options as $idx => $itm) {
                $choices[$itm->val] = $itm->key;
            }
            return $choices;
        }
        return [];
    }

    public function getValidationForField($field)
    {

        $validations = [];

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
            $notBlank = new Assert\NotBlank();
            if (isset($field->errorMessage) && $field->errorMessage) {
                $notBlank->message = $field->errorMessage;
            }
            $validations[] = $notBlank;
        }

        return $validations;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('formDescriptor', null);
        $resolver->setRequired(['formDescriptor']);
    }
}
