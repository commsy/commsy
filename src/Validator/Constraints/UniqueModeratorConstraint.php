<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueModeratorConstraint extends Constraint
{
    public bool $concernsOwnRoomMembership = false;

    /**
     * e.g.: 'user-delete', 'user-block', 'user-status-reading-user',
     * 'user-status-user', 'user-confirm'
     */
    public string $newUserStatus = '';

    /** @var int[] */
    public array $userIds = [];

    public ?string $messageBeginning = null;
    public string $messageBeginningDeleteOwnUserId = 'You cannot delete or lock your workspace membership. The following workspaces would otherwise be without moderators:';
    public string $messageBeginningDeleteOtherUserIds = 'You cannot delete or lock the chosen user(s) from this workspace. The following workspaces would otherwise be without moderators:';
    public string $messageBeginningChangeOtherUserIds = 'This action would leave the following workspace(s) without moderation:';
    public string $itemMessage = '{{ criteria }}';
    public string $messageEnd = 'Please assign further moderators or delete said workspace(s).';
    public string $messageEndGroupRooms = 'You can delete an unneeded group workspace by deleting its corresponding group. If you want to assign a new moderation to the group workspace please contact the portal moderation.';

    public function __construct(
        bool $concernsOwnRoomMembership = false,
        string $newUserStatus = '',
        array $userIds = [],
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->concernsOwnRoomMembership = $concernsOwnRoomMembership;
        $this->newUserStatus = $newUserStatus;
        $this->userIds = $userIds;

        if (in_array($this->newUserStatus, ['user-delete', 'user-block'])) {
            $this->messageBeginning = $this->concernsOwnRoomMembership
                ? $this->messageBeginningDeleteOwnUserId
                : $this->messageBeginningDeleteOtherUserIds;
        } else {
            $this->messageBeginning = $this->messageBeginningChangeOtherUserIds;
        }
    }
}
