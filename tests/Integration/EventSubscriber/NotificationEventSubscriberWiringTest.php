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

namespace Tests\Integration\EventSubscriber;

use App\Event\ItemDeletedEvent;
use App\Event\ItemPublishedEvent;
use App\EventSubscriber\NotificationEventSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Confirms autoconfiguration actually subscribes the notification subscriber
 * to the publish and delete events (the wiring the unit test cannot see).
 */
class NotificationEventSubscriberWiringTest extends KernelTestCase
{
    public function testSubscribesToPublishAndDeleteEvents(): void
    {
        self::bootKernel();
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');

        self::assertTrue(
            $this->isWired($dispatcher, ItemPublishedEvent::NAME),
            'subscriber must listen on ItemPublishedEvent'
        );
        self::assertTrue(
            $this->isWired($dispatcher, ItemDeletedEvent::NAME),
            'subscriber must listen on ItemDeletedEvent::NAME'
        );
    }

    private function isWired(EventDispatcherInterface $dispatcher, string $event): bool
    {
        foreach ($dispatcher->getListeners($event) as $listener) {
            if (is_array($listener) && $listener[0] instanceof NotificationEventSubscriber) {
                return true;
            }
        }

        return false;
    }
}
