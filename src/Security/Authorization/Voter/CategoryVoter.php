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

use App\Entity\Account;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use cs_environment;
use cs_user_item;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CategoryVoter extends Voter
{
    final public const EDIT = 'CATEGORY_EDIT';

    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        private Security $security,
        private UserService $userService,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, [self::EDIT]);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $currentRoom = $this->legacyEnvironment->getCurrentContextItem();
        $account = $this->security->getUser();
        if (!$account instanceof Account) {
            return false;
        }

        $userInContext = $this->userService->getUserInContext($account, $currentRoom->getItemId());
        if (!$userInContext) {
            return false;
        }

        return match ($attribute) {
            self::EDIT => $this->canEdit($currentRoom, $userInContext),
            default => throw new LogicException('This code should not be reached!'),
        };
    }

    private function canEdit($currentRoom, cs_user_item $currentUser): bool
    {
        // categories are not editable by guests
        if ($currentUser->isReallyGuest()) {
            return false;
        }

        // categories are not editable in archived rooms
        if (method_exists($currentRoom, 'getArchived') && $currentRoom->getArchived()) {
            return false;
        }

        // categories are editable if tags are editable by all or
        // the user is moderator
        if ($currentUser->isUser()) {
            $currentContext = $this->legacyEnvironment->getCurrentContextItem();

            if ($currentContext->isTagEditedByAll() || $currentUser->isModerator()) {
                return true;
            }
        }

        return false;
    }
}
