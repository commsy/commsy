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

use App\Enum\EditableSection;
use App\Event\CommsyEditEvent;
use App\Event\ItemReindexEvent;
use App\Lock\LockManager;
use App\Rubric\RubricType;
use App\Services\CalendarsService;
use cs_item;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class CommsyEditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CalendarsService $calendarsService,
        private LockManager $lockManager,
        private RequestStack $requestStack,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function onCommsyEdit(CommsyEditEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request || !$request->isMethod('GET')) {
            return;
        }

        $item = $event->getItem();

        if ($item->getItemType() === 'material' &&
            $event->getEditableSection() === EditableSection::DESCRIPTION &&
            $event->getExtras()['etherpad'] ?? false
        ) {
            return;
        }

        if ($this->lockManager->supportsLocking($item->getItemID())) {
            $this->lockManager->lockEntry($this->lockManager->getItemIdForLock($item->getItemID()));
        }
    }

    public function onCommsySave(CommsyEditEvent $event): void
    {
        $item = $event->getItem();
        if ($this->lockManager->supportsLocking($item->getItemID())) {
            $this->lockManager->unlockEntry($this->lockManager->getItemIdForLock($item->getItemID()));
        }

        if (RubricType::Date->value == $item->getItemType()) {
            if (!$item->isDraft()) {
                $this->calendarsService->updateSynctoken($item->getCalendarId());
            }
        }

        $this->updateSearchIndex($item);
    }

    public function onCommsyCancel(CommsyEditEvent $event): void
    {
        $item = $event->getItem();
        if ($this->lockManager->supportsLocking($item->getItemID())) {
            $this->lockManager->unlockEntry($this->lockManager->getItemIdForLock($item->getItemID()));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CommsyEditEvent::EDIT => 'onCommsyEdit',
            CommsyEditEvent::SAVE => 'onCommsySave',
            CommsyEditEvent::CANCEL => 'onCommsyCancel',
        ];
    }

    /**
     * Dispatches an {@see ItemReindexEvent} so the ES index and the read-status
     * cache stay consistent with the just-saved content. The ES reindex is
     * handled by {@see ElasticaSubscriber::onItemReindex()} and the cache
     * invalidation by {@see ReadStatusSubscriber::onItemReindex()}.
     */
    private function updateSearchIndex(cs_item $item): void
    {
        $this->eventDispatcher->dispatch(new ItemReindexEvent($item), ItemReindexEvent::class);
    }
}
