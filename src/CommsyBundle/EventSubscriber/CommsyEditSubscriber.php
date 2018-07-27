<?php

namespace CommsyBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use CommsyBundle\Event\CommsyEditEvent;

class CommsyEditSubscriber implements EventSubscriberInterface {

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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