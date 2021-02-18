<?php


namespace App\Validator\Constraints;


use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class LocalAccount extends Constraint
{
    public $message = 'login unknown';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}