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

namespace App\Twig\Components;

use App\Entity\Account;
use App\Repository\NotificationRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The global-navbar dashboard icon with an unread-notification badge. Replaces
 * the former notification bell + dropdown: there is no popup — the icon links to
 * the dashboard (where the activity panel lives) and only carries the unread
 * count, kept current by polling. The count is account-wide (all of the
 * recipient's rooms), matching the dashboard panel it points at.
 */
#[AsLiveComponent]
final class NotificationIndicator
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Account $account = null;

    /** Picks UIKit 3 vs UIKit 2 markup, matching the surrounding navbar. */
    #[LiveProp]
    public bool $uikit3 = false;

    /** Room id the dashboard link is built for (the user's private room). */
    #[LiveProp]
    public ?int $roomId = null;

    public function __construct(
        private readonly NotificationRepository $notificationRepository,
    ) {
    }

    public function getUnreadCount(): int
    {
        return $this->account !== null
            ? $this->notificationRepository->countUnreadForAccount($this->account)
            : 0;
    }
}
