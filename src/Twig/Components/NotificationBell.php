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
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The global-navbar notification bell: unread badge, a dropdown of the latest
 * notifications, and mark-all-read — as a Live Component so the badge/list
 * stay current via polling and mark-all-read happens in place. The recipient
 * is passed in as the logged-in account; rendering and reads stay scoped to it.
 */
#[AsLiveComponent]
final class NotificationBell
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Account $account = null;

    /** Picks UIKit 3 vs UIKit 2 markup, matching the surrounding navbar. */
    #[LiveProp]
    public bool $uikit3 = false;

    public function __construct(
        private readonly NotificationRepository $notificationRepository,
    ) {
    }

    #[LiveAction]
    public function markAllRead(): void
    {
        if ($this->account !== null) {
            $this->notificationRepository->markAllReadForAccount($this->account, new \DateTimeImmutable());
        }
    }

    public function getUnreadCount(): int
    {
        return $this->account !== null
            ? $this->notificationRepository->countUnreadForAccount($this->account)
            : 0;
    }

    /**
     * @return Notification[]
     */
    public function getLatest(): array
    {
        return $this->account !== null
            ? $this->notificationRepository->findLatestForAccount($this->account, 6)
            : [];
    }

    public function getPortalId(): ?int
    {
        return $this->account?->getPortal()?->getId();
    }
}
