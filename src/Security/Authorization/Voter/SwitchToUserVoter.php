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
use App\Utils\UserService;
use cs_user_item;
use DateTimeImmutable;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class SwitchToUserVoter extends Voter
{
    /**
     * SwitchToUserVoter constructor.
     */
    public function __construct(private Security $security, private UserService $userService)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function supports($attribute, $subject)
    {
        return in_array($attribute, ['CAN_SWITCH_USER'])
            && $subject instanceof UserInterface;
    }

    /**
     * {@inheritDoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var Account $account */
        $account = $token->getUser();

        if (!$account instanceof UserInterface || !$subject instanceof UserInterface) {
            return false;
        }

        /** @var cs_user_item $portalUser */
        $portalUser = $this->userService->getPortalUser($account);

        // check if the user is allowed to impersonate by flag
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
