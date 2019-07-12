<?php


namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DeleteGroupRoomConstraint extends Constraint
{
    public $message = 'Caution, the following project rooms are attached to this group room: {{ criteria }}. Those project rooms must be delete first or must be assigned to a different group room.';
}