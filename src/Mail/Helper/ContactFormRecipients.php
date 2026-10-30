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

declare(strict_types=1);

namespace App\Mail\Helper;

use App\Services\CurrentContextResolver;
use App\Utils\UserService;
use cs_user_item;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Who the contact form may write to:
 * - a profile of the room in the address, for whoever may enter that room
 *   (a profile of the portal itself only for its moderation);
 * - a contact person or moderator of any room of the current portal, so a
 *   room can be asked before joining it.
 */
final readonly class ContactFormRecipients
{
    public function __construct(
        private UserService $userService,
        private Security $security,
        private CurrentContextResolver $currentContextResolver,
    ) {
    }

    public function mayWriteTo(int $roomId, int $userId): ?cs_user_item
    {
        $user = $userId > 0 ? $this->userService->getUser($userId) : null;
        if (null === $user || $user->isDeleted()) {
            return null;
        }

        $portalId = (int) $this->currentContextResolver->getPortal()?->getId();
        if ((int) $user->getContextID() === $roomId) {
            $mayReachTheRoom = $roomId === $portalId
                ? $this->security->isGranted('PORTAL_MODERATOR')
                : $this->security->isGranted('ITEM_ENTER', $roomId);
            if ($mayReachTheRoom) {
                return $user;
            }
        }

        $speaksForItsRoom = $user->isContact() || $user->isModerator();
        if ($speaksForItsRoom && 0 !== $portalId && $user->getPortalId() === $portalId && (int) $user->getContextID() !== $portalId) {
            return $user;
        }

        return null;
    }
}
