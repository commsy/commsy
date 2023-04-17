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

namespace App\Security\Authorization\Voter;

use App\Services\LegacyEnvironment;
use cs_environment;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CalendarsVoter extends Voter
{
    public const EDIT = 'CALENDARS_EDIT';

    private cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    protected function supports($attribute, $object)
    {
        return in_array($attribute, [self::EDIT]);
    }

    protected function voteOnAttribute($attribute, $object, TokenInterface $token)
    {
        $currentRoom = $this->legacyEnvironment->getCurrentContextItem();

        return match ($attribute) {
            self::EDIT => $this->canEdit($currentRoom),
            default => throw new LogicException('This code should not be reached!'),
        };
    }

    private function canEdit($currentRoom)
    {
        // categories are not editable in archived rooms
        if (method_exists($currentRoom, 'getArchived') && $currentRoom->getArchived()) {
            return false;
        }

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        if (!$currentRoom->usersCanEditCalendars() && !$currentUser->isModerator()) {
            return false;
        }

        return true;
    }
}
