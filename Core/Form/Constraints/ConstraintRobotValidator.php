<?php
namespace MillenniumFalcon\Core\Form\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ConstraintRobotValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $myvars = 'response=' . ($_POST['g-recaptcha-response'] ?? "") . '&secret=' . ($_ENV['RECAPTCHA_SECRET_KEY'] ?? false);

        $response = file_get_contents("{$url}?{$myvars}");
        $response = json_decode($response);
        if (!$response || !$response->success) {
            $this->context->addViolation(
                $constraint->message,
                array()
            );
        }
    }
}
