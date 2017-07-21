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
            if ($event->getItem()->hasLocking()) {
                $event->getItem()->unlock();
            }
            if ($event->getItem()->getItemType() == CS_DATE_TYPE) {
                if (!$event->getItem()->isDraft()) {
                    $this->container->get('commsy.calendars_service')->updateSynctoken($event->getItem()->getCalendarId());
                }
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