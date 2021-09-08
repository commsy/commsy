<?php

namespace App\Validator\Constraints;

use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueModeratorConstraintValidator extends ConstraintValidator
{
    private $userService;
    private $legacyEnvironment;
    private $translator;

    public function __construct(UserService $userService, LegacyEnvironment $legacyEnvironment, TranslatorInterface $translator)
    {
        $this->userService = $userService;
        $this->legacyEnvironment = $legacyEnvironment;
        $this->translator = $translator;
    }

    public function validate($submittedDeleteString, Constraint $constraint)
    {
        $currentUser = $this->userService->getCurrentUserItem();
        $currentUserId = $currentUser->getItemID();
        $legacyEnvironment = $this->legacyEnvironment->getEnvironment();

        /** @var \cs_room_item $roomItem */
        $roomItem = $legacyEnvironment->getCurrentContextItem();
        $roomId = $roomItem->getItemID();

        $contextHasModerators = $this->userService->contextHasModerators($roomId, [$currentUserId]);
        $orphanedGroupRooms = $this->userService->grouproomsWithoutOtherModeratorsInRoom($roomItem, [$currentUser]);

        if (!$contextHasModerators || !empty($orphanedGroupRooms)) {
            $this->context->buildViolation($constraint->messageBeginning)
                ->addViolation();
        }

        if (!$contextHasModerators) {
            $roomName = " - " . $roomItem->getTitle();
            $isProjectRoom = $roomItem->isProjectRoom();
            if ($isProjectRoom) {
                $roomName = $roomName . " (" . $this->translator->trans('project', [], 'room') . ")";
            }

            $this->context->buildViolation($constraint->itemMessage)
                ->setParameter('{{ criteria }}', $roomName)
                ->addViolation();
        }

        foreach ($orphanedGroupRooms as $groupRoom) {
            $this->context->buildViolation($constraint->itemMessage)
                ->setParameter('{{ criteria }}', " - " . $groupRoom->getTitle() . " (" . $this->translator->trans('grouproom', [], 'room') . ")")
                ->addViolation();
        }

        if (!$contextHasModerators || !empty($orphanedGroupRooms)) {
            $this->context->buildViolation($constraint->messageEnd)
                ->addViolation();
        }
    }
}