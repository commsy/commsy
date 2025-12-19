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

use App\Entity\Account;
use App\Entity\Room;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Utils\UserService;
use cs_community_item;
use cs_project_item;
use cs_user_item;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ModeratorAccountDeleteConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserService $userService,
        private readonly RoomRepository $roomRepository,
        private readonly UserRepository $userRepository,
        private readonly Security $security,
    ) {
    }

    public function validate($roomId, Constraint $constraint): void
    {
        $currentUser = $this->userService->getCurrentUserItem();
        $rooms = $this->getRoomsOnlyModeratedByUser($currentUser);

        if (!empty($rooms)) {
            $this->context->buildViolation($constraint->messageBeginning)
                ->addViolation();

            // community rooms
            $communityRooms = array_filter($rooms, fn (Room $room) => $room->isCommunityRoom());

            foreach ($communityRooms as $communityRoom) {
                /* @var cs_community_item $communityRoom */
                $this->context->buildViolation($constraint->itemMessage)
                    ->setParameter('{{ criteria }}', $communityRoom->getItemID())
                    ->addViolation();
            }

            // project rooms
            $projectRooms = array_filter($rooms, fn (Room $room) => $room->isProjectRoom());

            foreach ($projectRooms as $projectRoom) {
                /* @var cs_project_item $projectRoom */
                $this->context->buildViolation($constraint->itemMessage)
                    ->setParameter('{{ criteria }}', $projectRoom->getItemID())
                    ->addViolation();
            }

            // group rooms
            $groupRooms = array_filter($rooms, fn (Room $room) => $room->isGroupRoom());

            foreach ($groupRooms as $groupRoom) {
                $this->context->buildViolation($constraint->itemMessage)
                    ->setParameter('{{ criteria }}', $groupRoom->getItemID())
                    ->addViolation();
            }

            $this->context->buildViolation($constraint->messageEnd)
                ->addViolation();
        }
    }

    /**
     * @return Room[]
     */
    private function getRoomsOnlyModeratedByUser(cs_user_item $currentUser): array
    {
        $account = $this->security->getUser();
        if (!$account instanceof Account) {
            return [];
        }

        $nonPersonalRooms = $this->roomRepository->getActiveRoomsByAccount($account);
        $roomsOnlyModeratedByUser = [];

        foreach ($nonPersonalRooms as $nonPersonalRoom) {
            if (!$currentUser->getRelatedUserItemInContext($nonPersonalRoom->getItemID())->isModerator()) {
                continue;
            }

            $moderators = $this->userRepository->getModeratorsByRoomId($nonPersonalRoom->getItemId());
            if (1 == count($moderators)) {
                $roomsOnlyModeratedByUser[] = $nonPersonalRoom;
            }
        }

        return $roomsOnlyModeratedByUser;
    }
}
