<?php
namespace MillenniumFalcon\Core\Form\Constraints;

use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ConstraintUniqueValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $orm = $constraint->orm;
        $field = $constraint->field;
        $extraSql = $constraint->extraSql;
        $joins = $constraint->joins;

        $fullClass = ModelService::fullClass($orm->getPdo(), $orm->getModel()->getClassName());
        if ($orm->getId()) {
            $data = $fullClass::data($orm->getPdo(), array(
                'joins' => $joins,
                'whereSql' => "(m.$field = ? AND m.id != ? AND m.versionId IS NULL)" . ($extraSql ? " AND ($extraSql)" : ''),
                'params' => array($value, $orm->getId()),
                'debug' => 0,
            ));
        } else {
            $data = $fullClass::data($orm->getPdo(), array(
                'joins' => $joins,
                'whereSql' => "(m.$field = ? AND m.versionId IS NULL)" . ($extraSql ? " AND ($extraSql)" : ''),
                'params' => array($value),
                'debug' => 0,
            ));
        }
        if (count($data)) {
            $this->context->addViolation(
                $constraint->message,
                array('%string%' => $value)
            );
        }
    }
}