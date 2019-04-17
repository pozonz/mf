<?php
namespace MillenniumFalcon\Core\Form\Constraints;

use Symfony\Component\Validator\Constraint;

class ConstraintUnique extends Constraint
{
	public $message = '"%string%" already exists';

	public $pdo;
    public $fieldToCheck;
    public $className;
}