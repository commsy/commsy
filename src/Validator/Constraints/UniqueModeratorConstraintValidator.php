<?php


namespace App\Validator\Constraints;


use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use App\Utils\UserService;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Exception;

class UniqueModeratorConstraintValidator extends ConstraintValidator
{
    private $userService;
    private $roomService;
    private $legacyEnvironment;
    private $translator;

    public function __construct(UserService $userService, LegacyEnvironment $legacyEnvironment, RoomService $roomService, TranslatorInterface $translator)
    {
        $this->userService = $userService;
        $this->legacyEnvironment = $legacyEnvironment;
        $this->roomService = $roomService;
        $this->translator = $translator;
    }

    public function validate($submittedDeleteString, Constraint $constraint)
    {
        $startedBeginningMessageFlag = False;
        $currentUser = $this->userService->getCurrentUserItem();
        $legacyEnvironment = $this->legacyEnvironment->getEnvironment();

        $roomId = $legacyEnvironment->current_context_id;
        try{
            $roomName = $legacyEnvironment->current_context->_data['title'];
        } catch (Exception $e){
            $roomName = $legacyEnvironment->current_context_id;
        }
        $roomItem = $this->roomService->getRoomItem($roomId);

        $hasModerators = $this->contextHasModerators($roomId, [$currentUser]);
        $hasMoreThanOneModerator = $this->contextModeratorsGreaterOne($roomId);
        $currentUserIsModerator = $this->isCurrentUserModerator($roomId, [$currentUser]);
        $isProjectRoom = $roomItem->getType() == 'project';


        if(!$hasModerators or !$hasMoreThanOneModerator and $currentUserIsModerator) {
            if ($isProjectRoom) {
                $roomName = " - " . $roomName . " (" . $this->translator->trans('project', [], 'room') . ")";
            }
            $startedBeginningMessageFlag = True;
            $this->context->buildViolation($constraint->messageBeginning)
                ->addViolation();

            $this->context->buildViolation($constraint->itemMessage)
                ->setParameter('{{ criteria }}', $roomName)
                ->addViolation();
        }

        if($isProjectRoom){
            $groupRooms = $roomItem->getGroupRoomList();
            foreach($groupRooms as $groupRoom){
                $hasModerators = $this->contextHasModerators($groupRoom->getItemId(), [$currentUser]);
                $hasMoreThanOneModerator = $this->contextModeratorsGreaterOne($groupRoom->getItemId());
                $currentUserIsModerator = $this->isCurrentUserModerator($groupRoom->getItemId(), [$currentUser]);
                if(!$hasModerators or !$hasMoreThanOneModerator and $currentUserIsModerator){
                    if(!$startedBeginningMessageFlag){
                        $startedBeginningMessageFlag = True;
                        $this->context->buildViolation($constraint->messageBeginning)
                            ->addViolation();
                    }
                    $this->context->buildViolation($constraint->itemMessage)
                        ->setParameter('{{ criteria }}', " - ".$groupRoom->getTitle()." (".$this->translator->trans('grouproom', [], 'room').")")
                        ->addViolation();
                }
            }
        }
        $this->context->buildViolation($constraint->messageEnd)
            ->addViolation();
        }

    private function isCurrentUserModerator($roomId, $currentUsers){
        $moderatorIds = $this->accessModeratorIds($roomId);
        foreach ($currentUsers as $selectedId) {
            if (in_array($selectedId->getItemID(), $moderatorIds)) {
                return true;
            }
        }
        return false;
    }

    private function contextHasModerators($roomId, $selectedIds) {
        $moderatorIds = $this->accessModeratorIds($roomId);

        foreach ($selectedIds as $selectedId) {
            if (in_array($selectedId, $moderatorIds)) {
                if(($key = array_search($selectedId, $moderatorIds)) !== false) {
                    unset($moderatorIds[$key]);
                }
            }
        }

        return !empty($moderatorIds);
    }

    private function contextModeratorsGreaterOne($roomId){
        $moderatorIds = $this->accessModeratorIds($roomId);
        return (sizeof($moderatorIds) > 1);
    }

    private function accessModeratorIds($roomId){
        $moderators = $this->roomService->getModeratorList($roomId);
        $moderatorIds = [];
        foreach ($moderators as $moderator) {
            $moderatorIds[] = $moderator->getItemId();
        }

        return $moderatorIds;
    }
}