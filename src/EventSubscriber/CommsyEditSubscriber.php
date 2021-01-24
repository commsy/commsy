<?php

namespace App\EventSubscriber;

use App\Event\CommsyEditEvent;
use App\Utils\ReaderService;
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
        if ($event->getItem()) {
            if ($event->getItem()->hasLocking()) {
                $event->getItem()->lock();
            }
        }
    }

    public function onCommsySave(CommsyEditEvent $event) {
        if ($event->getItem()) {
            /** @var \cs_item $item */
            $item = $event->getItem();

            if ($item->hasLocking()) {
                $item->unlock();
            }
            if ($item->getItemType() == CS_DATE_TYPE) {
                if (!$item->isDraft()) {
                    $this->container->get('commsy.calendars_service')->updateSynctoken($item->getCalendarId());
                }
            }

            if (method_exists($item, 'updateElastic')) {
                $item->updateElastic();

                // NOTE: read status cache items also get invalidated via the ReadStatusWillChangeEvent
                // which will be triggered when items get marked as read
                $this->readerService->invalidateCachedReadStatusForItem($item);
            }
        }
    }

    public function onCommsyCancel(CommsyEditEvent $event) {
        if ($event->getItem()) {
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
}