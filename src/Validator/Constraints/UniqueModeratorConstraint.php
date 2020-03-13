<?php


namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueModeratorConstraint extends Constraint
{
    public $messageBeginning = 'You can not delete or lock your account. The following workspaces would otherwise be without moderators:';
    public $itemMessage = '{{ criteria }}';
    public $messageEnd = 'Please assign further moderators or delete said workspace/s.';
}