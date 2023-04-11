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

use App\Event\CommsyEditEvent;
use App\Lock\LockManager;
use App\Services\CalendarsService;
use App\Utils\ReaderService;
use cs_item;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CommsyEditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ReaderService $readerService,
        private CalendarsService $calendarsService,
        private LockManager $lockManager,
        private RequestStack $requestStack
    ) {
    }

    public function onCommsyEdit(CommsyEditEvent $event)
    {
        if (!$event->getItem() instanceof cs_item) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request || !$request->isMethod('GET')) {
            return;
        }

        /** @var cs_item $item */
        $item = $event->getItem();
        if ($this->lockManager->supportsLocking($item->getItemID())) {
            $this->lockManager->lockEntry($this->lockManager->getItemIdForLock($item->getItemID()));
        }
    }

    public function onCommsySave(CommsyEditEvent $event)
    {
        if (!$event->getItem() instanceof cs_item) {
            return;
        }

        /** @var cs_item $item */
        $item = $event->getItem();
        if ($this->lockManager->supportsLocking($item->getItemID())) {
            $this->lockManager->unlockEntry($this->lockManager->getItemIdForLock($item->getItemID()));
        }

        if (CS_DATE_TYPE == $item->getItemType()) {
            if (!$item->isDraft()) {
                $this->calendarsService->updateSynctoken($item->getCalendarId());
            }
        }

        $this->updateSearchIndex($item);
    }

    public function onCommsyCancel(CommsyEditEvent $event)
    {
        if (!$event->getItem() instanceof cs_item) {
            return;
        }

        /** @var cs_item $item */
        $item = $event->getItem();
        if ($this->lockManager->supportsLocking($item->getItemID())) {
            $this->lockManager->unlockEntry($this->lockManager->getItemIdForLock($item->getItemID()));
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            CommsyEditEvent::EDIT => 'onCommsyEdit',
            CommsyEditEvent::SAVE => 'onCommsySave',
            CommsyEditEvent::CANCEL => 'onCommsyCancel',
        ];
    }

    /**
     * Updates the Elastic search index for the given item, and invalidates its cached read status.
     *
     * @param cs_item $item the item whose search index entry shall be updated
     */
    private function updateSearchIndex(cs_item $item)
    {
        if (method_exists($item, 'updateElastic')) {
            $item->updateElastic();

            // NOTE: read status cache items also get invalidated via the ReadStatusPreChangeEvent
            // which will be triggered when items get marked as read
            $this->readerService->invalidateCachedReadStatusForItem($item);
        }
    }
}
