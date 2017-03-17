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

    public static function getSubscribedEvents() {
        return array(commsyEvents::EDIT => array('onCommsyEdit', 0));
    }
}