<?php
namespace MillenniumFalcon\Cart\Form\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NotBlankIfRequiredValidator extends ConstraintValidator
{
	public function validate($value, Constraint $constraint)
	{
	    $callback = $constraint->callback;
        $ifRequired = $callback($constraint->request);
        if ($ifRequired && !$value) {
            $this->context->addViolation(
                $constraint->message,
                array('%string%' => $value)
            );
        }
	}
}