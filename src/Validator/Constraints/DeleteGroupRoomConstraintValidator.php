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
        $env = $this->legacyEnvironment->getEnvironment();
        $roomId = $env->getCurrentContextItem();
        $portalItem = $this->legacyEnvironment->getEnvironment()->getCurrentPortalItem();

        try{
            $project_room_link_status = $portalItem->_data['extras']['PROJECTROOMLINKSTATUS'];
        }catch(Exception $e){
            $project_room_link_status = "";
        }
        if(!empty($project_room_link_status)){
            $room_names = null;
            if($env->inCommunityRoom()){
                $room_names = "";
                $project_IDs = $roomId->_data['extras']['PROJECT_ID_ARRAY'];
                $rooms = $env->_current_portal->getRoomList();

                if(!empty($rooms)){
                    $this->context->buildViolation($constraint->messageStart)
                        ->addViolation();
                }

                foreach ($rooms as $current_room){
                    $current_room_id = $current_room->_data['item_id'];
                    if(in_array($current_room_id, $project_IDs)){
                        $this->context->buildViolation($constraint->message)
                            ->setParameter('{{ criteria }}', $current_room->_data['title'])
                            ->addViolation();
                    }
                }
            }


                if(strcmp($project_room_link_status, "mandatory") == 0){
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ criteria }}', $project_room_names)
                        ->addViolation();
                }
        }
    }

    private function roomsModeratedByUser($currentUser){
        $env = $this->legacyEnvironment->getEnvironment();
        $rooms = $env->getRoomManager()->getRelatedRoomListForUser($currentUser);
        return $rooms;

    }
}