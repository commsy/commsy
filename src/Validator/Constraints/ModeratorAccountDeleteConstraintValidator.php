<?php


namespace App\Validator\Constraints;


use App\Entity\Room;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use Symfony\Component\Debug\Exception\UndefinedMethodException;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Exception;

class ModeratorAccountDeleteConstraintValidator extends ConstraintValidator
{
    private $userService;

    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    public function __construct(UserService $userService, LegacyEnvironment $legacyEnvironment)
    {
        $this->userService = $userService;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function validate($roomId, Constraint $constraint)
    {
        $currentUser = $this->userService->getCurrentUserItem();
        $rooms = $this->getRoomsOnlyModeratedByUser($currentUser);

        if(!empty($rooms)) {
            $this->context->buildViolation($constraint->messageBeginning)
                ->addViolation();

            // community rooms
            $communityRooms = array_filter($rooms, function (\cs_room_item $room) {
                return $room->isCommunityRoom();
            });
            
            foreach ($communityRooms as $communityRoom) {
                /** @var \cs_community_item $communityRoom */
                $this->context->buildViolation($constraint->itemMessage)
                    ->setParameter('{{ criteria }}', $communityRoom->getItemID())
                    ->addViolation();
            }

            // project rooms
            $projectRooms = array_filter($rooms, function (\cs_room_item $room) {
                return $room->isProjectRoom();
            });

            foreach ($projectRooms as $projectRoom) {
                /** @var \cs_project_item $projectRoom */
                $this->context->buildViolation($constraint->itemMessage)
                    ->setParameter('{{ criteria }}', $projectRoom->getItemID())
                    ->addViolation();
            }

            // group rooms
            $groupRooms = array_filter($rooms, function (\cs_room_item $room) {
                return $room->isGroupRoom();
            });

            foreach ($groupRooms as $groupRoom) {
                $this->context->buildViolation($constraint->itemMessage)
                    ->setParameter('{{ criteria }}', $groupRoom->getItemID())
                    ->addViolation();
            }

            $this->context->buildViolation($constraint->messageEnd)
                ->addViolation();
        }
    }

    private function getRoomsOnlyModeratedByUser(\cs_user_item $currentUser): array
    {
        $roomsOnlyModeratedByUser = [];
        $userRooms = $this->legacyEnvironment->getRoomManager()->getAllRelatedRoomListForUser($currentUser);

        foreach ($userRooms as $userRoom) {
            /** @var \cs_room_item $userRoom */
            if (!$currentUser->getRelatedUserItemInContext($userRoom->getItemID())->isModerator()) {
                continue;
            }

            if ($userRoom->getModeratorList()->getCount() == 1) {
                $roomsOnlyModeratedByUser[] = $userRoom;
            }
        }

        return $roomsOnlyModeratedByUser;
    }
}