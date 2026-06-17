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

namespace App\EventSubscriber;

use App\Enum\ReaderStatus;
use App\Event\ReadStatusPreChangeEvent;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Marks an account's notifications for an item read the moment the legacy reader
 * records that item as "seen" — i.e. when the entry's detail page is opened,
 * regardless of how it was reached (the activity panel, search, a direct link …).
 * This realises "opening the detail page marks it read" without the user having
 * to click through the notification itself.
 *
 * The notification's read state stays stored separately from the {@see \App\Entity\Reader}
 * tracking; only the trigger is shared. The reader event carries the room-scoped
 * user id, which is resolved to the owning account (the notification recipient).
 */
final readonly class NotificationReadStatusSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private UserRepository $userRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [ReadStatusPreChangeEvent::class => 'onReadStatusPreChange'];
    }

    public function onReadStatusPreChange(ReadStatusPreChangeEvent $event): void
    {
        if ($event->getNewReadStatus() !== ReaderStatus::STATUS_SEEN) {
            return;
        }

        $account = $this->userRepository->findOneBy(['itemId' => $event->getUserId()])?->getAccount();
        if ($account === null) {
            return;
        }

        $this->notificationRepository->markReadForAccountAndSourceItem(
            $account,
            $event->getItemId(),
            new \DateTimeImmutable(),
        );
    }
}
