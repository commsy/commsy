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
        if (!in_array($constraint->newUserStatus, ['user-delete', 'user-block', 'user-status-reading-user', 'user-status-user', 'user-confirm'])) {
            return;
        }

        // get all affected users (or fall back to the current user)
        $userIds = $constraint->userIds;
        $users = [];

        if (!empty($userIds)) {
            $users = array_map(function (int $userId) {
                return $this->userService->getUser($userId);
            }, $userIds);
        } else {
            if ($currentUser = $this->userService->getCurrentUserItem()) {
                $users[] = $currentUser;
                $userIds[] = $currentUser->getItemID();
            }
        }

        if (empty($users)) {
            return;
        }

        $legacyEnvironment = $this->legacyEnvironment->getEnvironment();
        /** @var \cs_room_item $roomItem */
        $roomItem = $legacyEnvironment->getCurrentContextItem();
        $roomId = $roomItem->getItemID();

        // error if removing moderator status for all of the affected users would leave no other moderators
        $contextHasModerators = $this->userService->contextHasModerators($roomId, $userIds);
        $orphanedGroupRooms = [];
        if (in_array($constraint->newUserStatus, ['user-delete', 'user-block'])) {
            $orphanedGroupRooms = $this->userService->grouproomsWithoutOtherModeratorsInRoom($roomItem, $users);
        }

        if (!$contextHasModerators || !empty($orphanedGroupRooms)) {
            $this->context->buildViolation($constraint->messageBeginning)
                ->addViolation();
        }

        // TODO: link to rooms similar to ModeratorAccountDeleteConstraintValidator / delete_account.html.twig?
        if (!$contextHasModerators) {
            $roomName = " - " . $roomItem->getTitle();
            $roomType = ($roomItem->isGroupRoom())
                ? $this->translator->trans('grouproom', [], 'room')
                : $this->translator->trans($roomItem->getType(), [], 'room');
            $roomName = $roomName . " (" . $roomType . ")";

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
        if (!empty($orphanedGroupRooms)) {
            $this->context->buildViolation($constraint->messageEndGroupRooms)
                ->addViolation();
        }
    }
}