<?php


namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueModeratorConstraint extends Constraint
{
    public $message = 'Please first assign another moderator for the room {{ criteria }} or delete the room {{ criteria }}.';
}