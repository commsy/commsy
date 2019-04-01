<?php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class SendRecipientsConstraint extends Constraint
{
    public $message = 'There are no recipients selected';
}
