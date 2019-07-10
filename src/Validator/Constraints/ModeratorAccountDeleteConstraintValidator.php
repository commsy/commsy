<?php


namespace App\Validator\Constraints;


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
        $legacyEnvironment = $this->legacyEnvironment->getEnvironment();

        $roomId = $legacyEnvironment->current_context_id;
        try{
            $roomName = $legacyEnvironment->current_context->_data['title'];
        } catch (Exception $e){
            $roomName = $legacyEnvironment->current_context_id;
        }


        $hasModerators = $this->contextHasModerators($roomId, [$currentUser]);
        $hasMoreThanOneModerator = $this->contextModeratorsGreaterOne($roomId);

        if(!$hasModerators or !$hasMoreThanOneModerator){
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ criteria }}', $roomName)
                    ->addViolation();
        }
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
        $moderators = $this->userService->getModeratorsForContext($roomId);

        $moderatorIds = [];
        foreach ($moderators as $moderator) {
            $moderatorIds[] = $moderator->getItemId();
        }

        return $moderatorIds;
    }
}