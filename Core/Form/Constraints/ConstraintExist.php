<?php
namespace MillenniumFalcon\Core\Form\Constraints;

use Symfony\Component\Validator\Constraint;

class ConstraintExist extends Constraint
{
	public $message = '"%string%" does not exist';
    public $pdo = null;
    public $className = null;
    public $field = null;
    public $extraSql = null;
}