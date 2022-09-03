<?php
namespace Tests\Unit\EventSubscriber;

use App\EventSubscriber\RoomActivityStateSubscriber;
use Codeception\Test\Unit;

class RoomActivityStateSubscriberTest extends Unit
{
    /**
     * @var Tests\Support\UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testSubscribedEvents()
    {
        $subscribedEvents = RoomActivityStateSubscriber::getSubscribedEvents();

        $this->tester->assertArrayHasKey('workflow.room_activity.guard', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.room_activity.guard.notify_lock', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.room_activity.guard.lock', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.room_activity.guard.notify_forsake', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.room_activity.guard.forsake', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.room_activity.entered', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.room_activity.entered.active_notified', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.room_activity.entered.idle', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.room_activity.entered.idle_notified', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.room_activity.entered.abandoned', $subscribedEvents);

        $this->tester->assertContains('guard', $subscribedEvents['workflow.room_activity.guard']);
        $this->tester->assertContains('guardNotifyLock', $subscribedEvents['workflow.room_activity.guard.notify_lock']);
        $this->tester->assertContains('guardLock', $subscribedEvents['workflow.room_activity.guard.lock']);
        $this->tester->assertContains('guardNotifyForsake', $subscribedEvents['workflow.room_activity.guard.notify_forsake']);
        $this->tester->assertContains('guardForsake', $subscribedEvents['workflow.room_activity.guard.forsake']);
        $this->tester->assertContains('entered', $subscribedEvents['workflow.room_activity.entered']);
        $this->tester->assertContains('enteredActiveNotified', $subscribedEvents['workflow.room_activity.entered.active_notified']);
        $this->tester->assertContains('enteredIdle', $subscribedEvents['workflow.room_activity.entered.idle']);
        $this->tester->assertContains('enteredIdleNotified', $subscribedEvents['workflow.room_activity.entered.idle_notified']);
        $this->tester->assertContains('enteredAbandoned', $subscribedEvents['workflow.room_activity.entered.abandoned']);
    }
}
