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
            if(strcmp($project_room_link_status, "mandatory") == 0){
                $room_names = null;
                if($env->inCommunityRoom()){
                    $room_names = "";
                    try{
                        $project_IDs = $roomId->_data['extras']['PROJECT_ID_ARRAY'];
                    }catch(Exception $e){
                        $project_IDs = [];
                    }

                    $rooms = $env->_current_portal->getRoomList();

                    $projectRoomUnique = true;
                    foreach ($rooms as $current_room_tmp){
                        $current_room_id = $current_room_tmp->_data['item_id'];
                        $project_IDs_Current_Room = [];
                        if($current_room_tmp->getType() == 'community' && $current_room_tmp->getItemID() != $roomId->getItemID()){
                            try{
                                $project_IDs_Current_Room = $current_room_tmp->_data['extras']['PROJECT_ID_ARRAY'];
                            }catch(Exception $e){
                                $project_IDs_Current_Room = [];
                            }
                            foreach($project_IDs as $project_ID){
                                if(in_array($project_ID, $project_IDs_Current_Room)){
                                    $projectRoomUnique = false;
                                }
                            }
                        }
                    }

                    if($projectRoomUnique){
                        if(!empty($project_IDs)){
                            $this->context->buildViolation($constraint->messageStart)
                                ->addViolation();
                        }

                        foreach ($rooms as $current_room){
                            $current_room_id = $current_room->_data['item_id'];
                            if(in_array($current_room_id, $project_IDs)){
                                $this->context->buildViolation($constraint->message)
                                    ->setParameter('{{ criteria }}', $current_room->getItemID())
                                    ->addViolation();
                            }
                        }
                    }
                }
            }
        }
    }

    private function roomsModeratedByUser($currentUser){
        $env = $this->legacyEnvironment->getEnvironment();
        $rooms = $env->getRoomManager()->getRelatedRoomListForUser($currentUser);
        return $rooms;

    }
}