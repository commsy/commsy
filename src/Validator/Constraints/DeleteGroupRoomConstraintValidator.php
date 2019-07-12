<?php


namespace App\Validator\Constraints;


use App\Entity\Room;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Exception;

class DeleteGroupRoomConstraintValidator extends ConstraintValidator
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
        $roomId = $this->legacyEnvironment->getEnvironment()->getCurrentContextItem();
        $project_room_names = $this->projectRoomsAttached($roomId);


        if(!empty($project_room_names)){
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ criteria }}', $project_room_names)
                    ->addViolation();
        }
    }

    private function projectRoomsAttached($roomId){
        $env = $this->legacyEnvironment->getEnvironment();
        $room_names = null;
        if($env->inCommunityRoom()){
            $room_names = "";
            $project_IDs = $roomId->_data['extras']['PROJECT_ID_ARRAY'];
            $rooms = $env->_current_portal->getRoomList();

            foreach ($rooms as $current_room){
                $current_room_id = $current_room->_data['item_id'];
                if(in_array($current_room_id, $project_IDs)){
                    $room_names .= $current_room->_data['title'] . " ";
                }
            }
        }

        return $room_names;

    }

    private function roomsModeratedByUser($currentUser){
        $env = $this->legacyEnvironment->getEnvironment();
        $rooms = $env->getRoomManager()->getRelatedRoomListForUser($currentUser);
        return $rooms;

    }
}