<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Services\LegacyEnvironment;
use cs_community_item;
use cs_environment;
use cs_room_item;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MandatoryProjectRoomAssignmentValidator extends ConstraintValidator
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @param object $unused
     */
    public function validate($unused, Constraint $constraint)
    {
        $portalItem = $this->legacyEnvironment->getCurrentPortalItem();
        if ('mandatory' !== $portalItem->getProjectRoomLinkStatus()) {
            return;
        }

        /** @var cs_room_item $room */
        $room = $constraint->room;
        if (!$room instanceof cs_community_item) {
            return;
        }

        $linkedProjectRoomIds = $room->getProjectIDArray();

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
