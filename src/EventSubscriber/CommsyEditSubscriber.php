<?php

namespace App\EventSubscriber;

use App\Event\CommsyEditEvent;
use App\Utils\ReaderService;
use cs_item;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CommsyEditSubscriber implements EventSubscriberInterface {

    /**
     * @var ContainerInterface $container
     */
    private $container;

    /**
     * @var ReaderService $readerService
     */
    private $readerService;

    public function __construct(ContainerInterface $container, ReaderService $readerService)
    {
        $this->container = $container;
        $this->readerService = $readerService;
    }

    public function onCommsyEdit(CommsyEditEvent $event) {
        if ($event->getItem() instanceof cs_item) {
            if ($event->getItem()->hasLocking()) {
                $event->getItem()->lock();
            }
        }
    }

    public function onCommsySave(CommsyEditEvent $event) {
        if ($event->getItem() instanceof cs_item) {
            /** @var cs_item $item */
            $item = $event->getItem();

            if ($item->hasLocking()) {
                $item->unlock();
            }
            if ($item->getItemType() == CS_DATE_TYPE) {
                if (!$item->isDraft()) {
                    $this->container->get('commsy.calendars_service')->updateSynctoken($item->getCalendarId());
                }
            }

            $this->updateSearchIndex($item);
        }
    }

    public function onCommsyCancel(CommsyEditEvent $event) {
        if ($event->getItem() instanceof cs_item) {
            if ($event->getItem()->hasLocking()) {
                $event->getItem()->unlock();
            }
        }
    }

    public static function getSubscribedEvents() {
        return array(
            CommsyEditEvent::EDIT => array('onCommsyEdit', 0),
            CommsyEditEvent::SAVE => array('onCommsySave', 0),
            CommsyEditEvent::CANCEL => array('onCommsyCancel', 0)
        );
    }

    /**
     * Updates the Elastic search index for the given item, and invalidates its cached read status.
     * @param cs_item $item The item whose search index entry shall be updated.
     */
    private function updateSearchIndex(cs_item $item) {
        if (method_exists($item, 'updateElastic')) {
            $item->updateElastic();

            // NOTE: read status cache items also get invalidated via the ReadStatusPreChangeEvent
            // which will be triggered when items get marked as read
            $this->readerService->invalidateCachedReadStatusForItem($item);
        }
    }
}
