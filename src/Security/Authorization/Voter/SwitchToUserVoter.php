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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class SwitchToUserVoter extends Voter
{
    /**
     * SwitchToUserVoter constructor.
     */
    public function __construct(
        private readonly UserService $userService,
        private readonly Security $security
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
        /** @var Account $account */
        $account = $token->getUser();

        if (!$account instanceof UserInterface || !$subject instanceof UserInterface) {
            return false;
        }

        if ('root' === $account->getUsername()) {
            return true;
        }

        // Taking over an account is a moderation act, so it takes moderation of
        // the portal the target belongs to. Asking PORTAL_MODERATOR rather than
        // re-deriving it keeps the rule in one place: it already compares the
        // actor's portal with the given one and rejects a soft-deleted portal.
        //
        // Two consequences worth naming. Without this check the one below stood
        // alone, and since getCanImpersonateAnotherUser() is an opt-out that
        // nobody sets, EVERY authenticated member passed it — on any url,
        // because the switch_user listener runs firewall-wide and not just on
        // the portal settings route. And root can never be taken over: the
        // server-context root account has no portal, so there is no portal to
        // be a moderator of. Becoming root requires logging in as root.
        $targetPortal = $subject instanceof Account ? $subject->getPortal() : null;
        if (!$targetPortal instanceof Portal
            || !$this->security->isGranted(UserVoter::PORTAL_MODERATOR, $targetPortal)
        ) {
            return false;
        }

        /** @var cs_user_item $portalUser */
        $portalUser = $this->userService->getPortalUser($account);

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
