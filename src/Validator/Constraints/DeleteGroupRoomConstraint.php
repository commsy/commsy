<?php


namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DeleteGroupRoomConstraint extends Constraint
{
    public $messageStart = 'Caution, the following project rooms are attached to this workspace:';
    public $message = '- {{ criteria }}';
    public $messageEnd = 'Those project rooms must be delete first or must be assigned to a different workspace.';
}