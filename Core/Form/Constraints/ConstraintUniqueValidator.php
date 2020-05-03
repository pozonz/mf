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

        $fullClass = ModelService::fullClass($orm->getPdo(), $orm->getModel()->getClassName());
        if ($orm->getId()) {
            $data = $fullClass::data($orm->getPdo(), array(
                'whereSql' => "(m.$field = ? AND m.id != ? AND m.versionId IS NULL)" . ($extraSql ? " AND ($extraSql)" : ''),
                'params' => array($value, $orm->getId()),
            ));
        } else {
            $data = $fullClass::data($orm->getPdo(), array(
                'whereSql' => "(m.$field = ? AND m.versionId IS NULL)" . ($extraSql ? " AND ($extraSql)" : ''),
                'params' => array($value),
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