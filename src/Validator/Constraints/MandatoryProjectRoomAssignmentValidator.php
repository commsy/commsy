<?php


namespace App\Validator\Constraints;


use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_community_item;
use cs_environment;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MandatoryProjectRoomAssignmentValidator extends ConstraintValidator
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @param object $unused
     * @param Constraint $constraint
     */
    public function validate($unused, Constraint $constraint)
    {
        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
        if ($portalItem->getProjectRoomLinkStatus() !== 'mandatory') {
            return;
        }

        /** @var \cs_room_item $room */
        $room = $constraint->room;
        if (!$room instanceof cs_community_item) {
            return;
        }

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