<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueModeratorConstraint extends Constraint
{
    /** @var bool $concernsOwnRoomMembership */
    public $concernsOwnRoomMembership = false;

    /** @var string $newUserStatus */
    public $newUserStatus = ''; // e.g.: 'user-delete', 'user-block', 'user-status-reading-user', 'user-status-user', 'user-confirm'

    /** @var int[] $userIds */
    public $userIds = [];

    public $messageBeginning;
    public $messageBeginningDeleteOwnUserId = 'You cannot delete or lock your workspace membership. The following workspaces would otherwise be without moderators:';
    public $messageBeginningDeleteOtherUserIds = 'You cannot delete or lock the chosen user(s) from this workspace. The following workspaces would otherwise be without moderators:';
    public $messageBeginningChangeOtherUserIds = 'This action would leave the following workspace(s) without moderation:';
    public $itemMessage = '{{ criteria }}';
    public $messageEnd = 'Please assign further moderators or delete said workspace(s).';
    public $messageEndGroupRooms = 'You can delete an unneeded group workspace by deleting its corresponding group. If you want to assign a new moderation to the group workspace please contact the portal moderation.';

    public function __construct($options = null)
    {
        parent::__construct($options);

        if (in_array($this->newUserStatus, ['user-delete', 'user-block'])) {
            $this->messageBeginning = ($this->concernsOwnRoomMembership) ? $this->messageBeginningDeleteOwnUserId : $this->messageBeginningDeleteOtherUserIds;
        } else {
            $this->messageBeginning = $this->messageBeginningChangeOtherUserIds;
        }
    }
}