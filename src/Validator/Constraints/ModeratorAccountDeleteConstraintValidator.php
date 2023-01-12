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

use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_community_item;
use cs_environment;
use cs_project_item;
use cs_room_item;
use cs_user_item;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ModeratorAccountDeleteConstraintValidator extends ConstraintValidator
{
    /**
     * @var cs_environment
     */
    private $legacyEnvironment;

    public function __construct(private UserService $userService, LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function validate($roomId, Constraint $constraint)
    {
        $currentUser = $this->userService->getCurrentUserItem();
        $rooms = $this->getRoomsOnlyModeratedByUser($currentUser);

        if (!empty($rooms)) {
            $this->context->buildViolation($constraint->messageBeginning)
                ->addViolation();

            // community rooms
            $communityRooms = array_filter($rooms, fn (cs_room_item $room) => $room->isCommunityRoom());

            foreach ($communityRooms as $communityRoom) {
                /* @var cs_community_item $communityRoom */
                $this->context->buildViolation($constraint->itemMessage)
                    ->setParameter('{{ criteria }}', $communityRoom->getItemID())
                    ->addViolation();
            }

            // project rooms
            $projectRooms = array_filter($rooms, fn (cs_room_item $room) => $room->isProjectRoom());

            foreach ($projectRooms as $projectRoom) {
                /* @var cs_project_item $projectRoom */
                $this->context->buildViolation($constraint->itemMessage)
                    ->setParameter('{{ criteria }}', $projectRoom->getItemID())
                    ->addViolation();
            }

            // group rooms
            $groupRooms = array_filter($rooms, fn (cs_room_item $room) => $room->isGroupRoom());

            foreach ($groupRooms as $groupRoom) {
                $this->context->buildViolation($constraint->itemMessage)
                    ->setParameter('{{ criteria }}', $groupRoom->getItemID())
                    ->addViolation();
            }

            $this->context->buildViolation($constraint->messageEnd)
                ->addViolation();
        }
    }

    private function getRoomsOnlyModeratedByUser(cs_user_item $currentUser): array
    {
        $roomsOnlyModeratedByUser = [];
        $userRooms = $this->legacyEnvironment->getRoomManager()->getAllRelatedRoomListForUser($currentUser);

        foreach ($userRooms as $userRoom) {
            /** @var cs_room_item $userRoom */
            if (!$currentUser->getRelatedUserItemInContext($userRoom->getItemID())->isModerator()) {
                continue;
            }

            if (1 == $userRoom->getModeratorList()->getCount()) {
                $roomsOnlyModeratedByUser[] = $userRoom;
            }
        }

        return $roomsOnlyModeratedByUser;
    }
}
