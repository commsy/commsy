<?php
namespace App\Tests\Unit;

use App\Entity\Account;
use App\Entity\Room;
use Codeception\Test\Unit;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Transition;

class AccountActivityTest extends Unit
{
    /**
     * @var \App\Tests\UnitTester
     */
    protected $tester;

    // tests
    public function testAccountWorkflowExists()
    {
        /** @var Registry $registry */
        $registry = $this->tester->grabService(Registry::class);

        $workflow = $registry->get(new Account(), 'account_activity');
        $this->tester->assertNotNull($workflow);

        $definition = $workflow->getDefinition();

        $places = $definition->getPlaces();
        $this->tester->assertContains('active', $places);
        $this->tester->assertContains('active_notified', $places);
        $this->tester->assertContains('idle', $places);
        $this->tester->assertContains('idle_notified', $places);
        $this->tester->assertContains('abandoned', $places);

        /** @var Transition $notifyLockTransition */
        $notifyLockTransition = array_filter($definition->getTransitions(), function ($transition) {
            return $transition->getName() === 'notify_lock';
        });
        $this->tester->assertNotEmpty($notifyLockTransition);
        $this->tester->assertContains('active', current($notifyLockTransition)->getFroms());
        $this->tester->assertContains('active_notified', current($notifyLockTransition)->getTos());

        /** @var Transition $lockTransition */
        $lockTransition = array_filter($definition->getTransitions(), function ($transition) {
            return $transition->getName() === 'lock';
        });
        $this->tester->assertNotEmpty($lockTransition);
        $this->tester->assertContains('active_notified', current($lockTransition)->getFroms());
        $this->tester->assertContains('idle', current($lockTransition)->getTos());

        /** @var Transition $notifyForsakeTransition */
        $notifyForsakeTransition = array_filter($definition->getTransitions(), function ($transition) {
            return $transition->getName() === 'notify_forsake';
        });
        $this->tester->assertNotEmpty($notifyForsakeTransition);
        $this->tester->assertContains('idle', current($notifyForsakeTransition)->getFroms());
        $this->tester->assertContains('idle_notified', current($notifyForsakeTransition)->getTos());

        /** @var Transition $forsakeTransition */
        $forsakeTransition = array_filter($definition->getTransitions(), function ($transition) {
            return $transition->getName() === 'forsake';
        });
        $this->tester->assertNotEmpty($forsakeTransition);
        $this->tester->assertContains('idle_notified', current($forsakeTransition)->getFroms());
        $this->tester->assertContains('abandoned', current($forsakeTransition)->getTos());
    }

    public function testRoomWorkflowExists()
    {
        /** @var Registry $registry */
        $registry = $this->tester->grabService(Registry::class);

        $workflow = $registry->get(new Room(), 'room_activity');
        $this->tester->assertNotNull($workflow);

        $definition = $workflow->getDefinition();

        $places = $definition->getPlaces();
        $this->tester->assertContains('active', $places);
        $this->tester->assertContains('active_notified', $places);
        $this->tester->assertContains('idle', $places);
        $this->tester->assertContains('idle_notified', $places);
        $this->tester->assertContains('abandoned', $places);

        /** @var Transition $notifyLockTransition */
        $notifyLockTransition = array_filter($definition->getTransitions(), function ($transition) {
            return $transition->getName() === 'notify_lock';
        });
        $this->tester->assertNotEmpty($notifyLockTransition);
        $this->tester->assertContains('active', current($notifyLockTransition)->getFroms());
        $this->tester->assertContains('active_notified', current($notifyLockTransition)->getTos());

        /** @var Transition $lockTransition */
        $lockTransition = array_filter($definition->getTransitions(), function ($transition) {
            return $transition->getName() === 'lock';
        });
        $this->tester->assertNotEmpty($lockTransition);
        $this->tester->assertContains('active_notified', current($lockTransition)->getFroms());
        $this->tester->assertContains('idle', current($lockTransition)->getTos());

        /** @var Transition $notifyForsakeTransition */
        $notifyForsakeTransition = array_filter($definition->getTransitions(), function ($transition) {
            return $transition->getName() === 'notify_forsake';
        });
        $this->tester->assertNotEmpty($notifyForsakeTransition);
        $this->tester->assertContains('idle', current($notifyForsakeTransition)->getFroms());
        $this->tester->assertContains('idle_notified', current($notifyForsakeTransition)->getTos());

        /** @var Transition $forsakeTransition */
        $forsakeTransition = array_filter($definition->getTransitions(), function ($transition) {
            return $transition->getName() === 'forsake';
        });
        $this->tester->assertNotEmpty($forsakeTransition);
        $this->tester->assertContains('idle_notified', current($forsakeTransition)->getFroms());
        $this->tester->assertContains('abandoned', current($forsakeTransition)->getTos());
    }
}