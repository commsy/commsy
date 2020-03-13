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

    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    public function __construct(
        UserService $userService,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->userService = $userService;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @param object $unused
     * @param Constraint $constraint
     */
    public function validate($unused, Constraint $constraint)
    {
        /** @var \cs_room_item $room */
        $room = $constraint->room;
        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();

        if ($portalItem->getProjectRoomLinkStatus() === 'mandatory') {
            $room_names = null;

            if ($room->isCommunityRoom()) {
                /** @var \cs_community_item $room */
                $linkedProjectRoomIds = $room->getProjectIDArray();
                $this->legacyEnvironment->toggleArchiveMode();
                $linkedProjectRoomIds = array_merge($linkedProjectRoomIds, $room->getProjectIDArray());
                $this->legacyEnvironment->toggleArchiveMode();

                if (!empty($linkedProjectRoomIds)) {
                    $this->context->buildViolation($constraint->messageStart)
                        ->addViolation();

                    foreach ($linkedProjectRoomIds as $linkedProjectRoomId) {
                        $this->context->buildViolation($constraint->message)
                            ->setParameter('{{ criteria }}', $linkedProjectRoomId)
                            ->addViolation();
                    }
                }
            }
        }
    }
}