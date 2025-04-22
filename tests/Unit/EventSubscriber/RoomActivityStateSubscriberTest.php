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

namespace Tests\Unit\EventSubscriber;

use App\EventSubscriber\RoomActivityStateSubscriber;
use PHPUnit\Framework\TestCase;

class RoomActivityStateSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $subscribedEvents = RoomActivityStateSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey('workflow.room_activity.guard', $subscribedEvents);
        $this->assertArrayHasKey('workflow.room_activity.guard.notify_lock', $subscribedEvents);
        $this->assertArrayHasKey('workflow.room_activity.guard.lock', $subscribedEvents);
        $this->assertArrayHasKey('workflow.room_activity.guard.notify_forsake', $subscribedEvents);
        $this->assertArrayHasKey('workflow.room_activity.guard.forsake', $subscribedEvents);
        $this->assertArrayHasKey('workflow.room_activity.entered', $subscribedEvents);
        $this->assertArrayHasKey('workflow.room_activity.entered.active_notified', $subscribedEvents);
        $this->assertArrayHasKey('workflow.room_activity.entered.idle', $subscribedEvents);
        $this->assertArrayHasKey('workflow.room_activity.entered.idle_notified', $subscribedEvents);
        $this->assertArrayHasKey('workflow.room_activity.entered.abandoned', $subscribedEvents);

        $this->assertContains('guard', $subscribedEvents['workflow.room_activity.guard']);
        $this->assertContains('guardNotifyLock', $subscribedEvents['workflow.room_activity.guard.notify_lock']);
        $this->assertContains('guardLock', $subscribedEvents['workflow.room_activity.guard.lock']);
        $this->assertContains('guardNotifyForsake', $subscribedEvents['workflow.room_activity.guard.notify_forsake']);
        $this->assertContains('guardForsake', $subscribedEvents['workflow.room_activity.guard.forsake']);
        $this->assertContains('entered', $subscribedEvents['workflow.room_activity.entered']);
        $this->assertContains('enteredActiveNotified', $subscribedEvents['workflow.room_activity.entered.active_notified']);
        $this->assertContains('enteredIdle', $subscribedEvents['workflow.room_activity.entered.idle']);
        $this->assertContains('enteredIdleNotified', $subscribedEvents['workflow.room_activity.entered.idle_notified']);
        $this->assertContains('enteredAbandoned', $subscribedEvents['workflow.room_activity.entered.abandoned']);
    }
}
