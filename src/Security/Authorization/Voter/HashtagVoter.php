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
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class HashtagVoter extends Voter
{
    public const EDIT = 'HASHTAG_EDIT';

    private $legacyEnvironment;

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
        // get current logged in user
        // $user = $token->getUser();

        // make sure there is a user object (i.e. that the user is logged in)
        // if (!$user instanceof User) {
        //     return false
        // }

        $currentRoom = $this->legacyEnvironment->getCurrentContextItem();
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        return match ($attribute) {
            self::EDIT => $this->canEdit($currentRoom, $currentUser),
            default => throw new \LogicException('This code should not be reached!'),
        };
    }

    private function canEdit($currentRoom, $currentUser)
    {
        // hashtags are not editable by guests
        if ($currentUser->isReallyGuest()) {
            return false;
        }

        // hashtags are not editable in archived rooms
        if (method_exists($currentRoom, 'getArchived') && $currentRoom->getArchived()) {
            return false;
        }

        return true;
    }
}
