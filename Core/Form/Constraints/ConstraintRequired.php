<?php

namespace MillenniumFalcon\Core\Form\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ConstraintRequired extends Constraint
{
	public $message = 'This value should not be blank.';

	public $form;
    public $fieldToCheck;
}