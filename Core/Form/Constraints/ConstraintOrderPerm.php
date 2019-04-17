<?php

namespace MillenniumFalcon\Core\Form\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ConstraintOrderPerm extends Constraint
{
	public $message = 'You don\'t have the permission.';

	public $orderId;
    public $orderClass;
    public $customer;
    public $pdo;
}