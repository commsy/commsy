<?php


namespace App\Validator\Constraints;


use App\Entity\Room;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Exception;

class ModeratorAccountDeleteConstraintValidator extends ConstraintValidator
{
    private $userService;
    private $legacyEnvironment;

    public function __construct(UserService $userService, LegacyEnvironment $legacyEnvironment)
    {
        $this->userService = $userService;
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function validate($roomId, Constraint $constraint)
    {
        $currentUser = $this->userService->getCurrentUserItem();
        $rooms = $this->roomsModeratedByUser($currentUser);


        if(!empty($rooms)){
            $room_names = "";
            foreach($rooms as $room){
                $room_names .= $room->getTitle() . " ";
            }
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ criteria }}', $room_names)
                    ->addViolation();
        }
    }

    private function roomsModeratedByUser($currentUser){
        $env = $this->legacyEnvironment->getEnvironment();
        $rooms = $env->getRoomManager()->getRelatedRoomListForUser($currentUser);
        return $rooms;

    }
}