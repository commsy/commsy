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

use App\Entity\Portal;
use App\Services\LegacyEnvironment;
use cs_environment;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class PortalModeratorVoter extends Voter
{
    public const PORTAL_MODERATOR = 'PORTAL_MODERATOR';

    /**
     * @var cs_environment
     */
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    protected function supports($attribute, $subject)
    {
        return $subject instanceof Portal && self::PORTAL_MODERATOR === $attribute;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if ('root' === $user->getUsername()) {
            return true;
        }

        switch ($attribute) {
            case self::PORTAL_MODERATOR:
                $currentUserItem = $this->legacyEnvironment->getCurrentUserItem();
                if ($currentUserItem) {
                    /** @var $subject Portal */
                    if (
                        3 === (int) $currentUserItem->getStatus() &&
                        null === $subject->getDeletionDate() &&
                        (int) $currentUserItem->getContextID() === $subject->getId()
                    ) {
                        return true;
                    }
                }
                break;
        }

        return false;
    }
}
