<?php

namespace MillenniumFalcon\Core\Form\Constraints;

use MillenniumFalcon\Core\Form\Builder\Cart;
use Pz\Orm\Order;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ConstraintOrderPermValidator extends ConstraintValidator
{
	public function validate($value, Constraint $constraint)
	{
	    $orderContainer = $constraint->orderClass::getByField($constraint->pdo, 'uniqid', $constraint->orderId);
	    if (!$orderContainer or gettype($constraint->customer) != 'object' or $orderContainer->getCustomerId() != $constraint->customer->getId()) {
			$this->context->addViolation(
				$constraint->message,
				array()
            );
        }
	}
}