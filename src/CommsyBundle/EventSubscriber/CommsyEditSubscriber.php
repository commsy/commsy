<?php

namespace CommsyBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use CommsyBundle\Event\CommsyEditEvent;

class CommsyEditSubscriber implements EventSubscriberInterface {

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
        return array(commsyEvents::EDIT => array('onCommsyEdit', 0));
    }
}