<?php
namespace MillenniumFalcon\Core\Form\Constraints;

use MillenniumFalcon\Core\Service\ModelService;
use Pz\Service\DbService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Web\ORMs\Customer;

class ConstraintExistValidator extends ConstraintValidator
{
	public function validate($value, Constraint $constraint)
	{
        $pdo = $constraint->pdo;
        $className = $constraint->className;
        $field = $constraint->field;
        $extraSql = $constraint->extraSql;

        $fullClass = ModelService::fullClass($pdo, $className);
        $orm = $fullClass::data($pdo, array(
            'whereSql' => "(m.$field = ?)" . ($extraSql ? " AND ($extraSql)" : ''),
            'params' => array($value),
            'limit' => 1,
            'oneOrNull' => 1,
        ));
        if (!$orm) {
            $this->context->addViolation(
                $constraint->message,
                array('%string%' => $value)
            );
        }
	}
}