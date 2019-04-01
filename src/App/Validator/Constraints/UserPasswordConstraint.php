<?php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UserPasswordConstraint extends Constraint
{
    public $message = 'Password incorrect.';
}
