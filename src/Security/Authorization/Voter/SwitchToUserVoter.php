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
use App\Entity\Portal;
use App\Utils\UserService;
use cs_user_item;
use DateTimeImmutable;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class SwitchToUserVoter extends Voter
{
    /**
     * SwitchToUserVoter constructor.
     */
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    /**
     * {@inheritDoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, ['CAN_SWITCH_USER'])
            && $subject instanceof UserInterface;
    }

    /**
     * {@inheritDoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $account = $token->getUser();

        // Narrowed to Account, not UserInterface: everything below reads the
        // username or the portal off these two, and only Account carries them.
        // The previous UserInterface guard sat under a `@var Account` docblock
        // and would have run into an undefined getUsername() had the token ever
        // held anything else.
        if (!$account instanceof Account || !$subject instanceof Account) {
            return false;
        }

        if ('root' === $account->getUsername()) {
            return true;
        }

        // Taking over an account is a moderation act, so it takes moderation of
        // the portal the target belongs to. Without this the check below stood
        // alone, and since getCanImpersonateAnotherUser() is an opt-out that
        // nobody sets, EVERY authenticated member passed it — on any url,
        // because the switch_user listener runs firewall-wide and not just on
        // the portal settings route.
        //
        // The target must have a portal, which is also what puts root out of
        // reach for good: the server-context root account has none, and
        // UserProvider hands it out before any portal scoping. Becoming root
        // requires logging in as root.
        //
        // 10.5 delegates all of this to UserVoter::PORTAL_MODERATOR. That is
        // not available here: on this branch the attribute ignores its subject
        // (so it would not bind the target's portal), it casts the subject with
        // intval() before dispatching (so a Portal entity is not a valid
        // argument at all), and it reads the actor from the legacy
        // currentUserItem rather than from the token — which this voter cannot
        // rely on, running inside the firewall listener. The rule is therefore
        // spelled out here, off the two accounts and the actor's portal user
        // item that is fetched below anyway.
        $actorPortal = $account->getPortal();
        $targetPortal = $subject->getPortal();

        if (!$actorPortal instanceof Portal
            || !$targetPortal instanceof Portal
            || $actorPortal->getId() !== $targetPortal->getId()
            || null !== $targetPortal->getDeletionDate()
        ) {
            return false;
        }

        /** @var cs_user_item $portalUser */
        $portalUser = $this->userService->getPortalUser($account);

        if (!$portalUser->isModerator()) {
            return false;
        }

        // A moderator holds the right by default; it can be withdrawn for
        // individuals, optionally with a deadline.
        if ($portalUser->getCanImpersonateAnotherUser()) {
            // check if the impersonate grant is expired
            $now = new DateTimeImmutable();
            $expiryDate = $portalUser->getImpersonateExpiryDate();
            if (null === $expiryDate || $expiryDate >= $now) {
                return true;
            }
        }

        return false;
    }
}
