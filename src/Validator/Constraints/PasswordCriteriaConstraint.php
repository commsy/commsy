<?php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PasswordCriteriaConstraint extends Constraint
{
    public $message = 'The password does not fulfill the following criteria: {{ criteria }}.';
}
