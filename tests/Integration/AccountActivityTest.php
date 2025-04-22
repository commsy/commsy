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

namespace Tests\Integration;

use App\Entity\Account;
use App\Entity\Room;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Transition;

class AccountActivityTest extends KernelTestCase
{
    // tests
    public function testAccountWorkflowExists(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var Registry $registry */
        $registry = $container->get(Registry::class);

        $workflow = $registry->get(new Account(), 'account_activity');
        $this->assertNotNull($workflow);

        $definition = $workflow->getDefinition();

        $places = $definition->getPlaces();
        $this->assertContains('active', $places);
        $this->assertContains('active_notified', $places);
        $this->assertContains('idle', $places);
        $this->assertContains('idle_notified', $places);
        $this->assertContains('abandoned', $places);

        /** @var Transition $notifyLockTransition */
        $notifyLockTransition = array_filter($definition->getTransitions(), fn($transition) => $transition->getName() === 'notify_lock');
        $this->assertNotEmpty($notifyLockTransition);
        $this->assertContains('active', current($notifyLockTransition)->getFroms());
        $this->assertContains('active_notified', current($notifyLockTransition)->getTos());

        /** @var Transition $lockTransition */
        $lockTransition = array_filter($definition->getTransitions(), fn($transition) => $transition->getName() === 'lock');
        $this->assertNotEmpty($lockTransition);
        $this->assertContains('active_notified', current($lockTransition)->getFroms());
        $this->assertContains('idle', current($lockTransition)->getTos());

        /** @var Transition $notifyForsakeTransition */
        $notifyForsakeTransition = array_filter($definition->getTransitions(), fn($transition) => $transition->getName() === 'notify_forsake');
        $this->assertNotEmpty($notifyForsakeTransition);
        $this->assertContains('idle', current($notifyForsakeTransition)->getFroms());
        $this->assertContains('idle_notified', current($notifyForsakeTransition)->getTos());

        /** @var Transition $forsakeTransition */
        $forsakeTransition = array_filter($definition->getTransitions(), fn($transition) => $transition->getName() === 'forsake');
        $this->assertNotEmpty($forsakeTransition);
        $this->assertContains('idle_notified', current($forsakeTransition)->getFroms());
        $this->assertContains('abandoned', current($forsakeTransition)->getTos());
    }

    public function testRoomWorkflowExists(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var Registry $registry */
        $registry = $container->get(Registry::class);

        $workflow = $registry->get(new Room(), 'room_activity');
        $this->assertNotNull($workflow);

        $definition = $workflow->getDefinition();

        $places = $definition->getPlaces();
        $this->assertContains('active', $places);
        $this->assertContains('active_notified', $places);
        $this->assertContains('idle', $places);
        $this->assertContains('idle_notified', $places);
        $this->assertContains('abandoned', $places);

        /** @var Transition $notifyLockTransition */
        $notifyLockTransition = array_filter($definition->getTransitions(), fn($transition) => $transition->getName() === 'notify_lock');
        $this->assertNotEmpty($notifyLockTransition);
        $this->assertContains('active', current($notifyLockTransition)->getFroms());
        $this->assertContains('active_notified', current($notifyLockTransition)->getTos());

        /** @var Transition $lockTransition */
        $lockTransition = array_filter($definition->getTransitions(), fn($transition) => $transition->getName() === 'lock');
        $this->assertNotEmpty($lockTransition);
        $this->assertContains('active_notified', current($lockTransition)->getFroms());
        $this->assertContains('idle', current($lockTransition)->getTos());

        /** @var Transition $notifyForsakeTransition */
        $notifyForsakeTransition = array_filter($definition->getTransitions(), fn($transition) => $transition->getName() === 'notify_forsake');
        $this->assertNotEmpty($notifyForsakeTransition);
        $this->assertContains('idle', current($notifyForsakeTransition)->getFroms());
        $this->assertContains('idle_notified', current($notifyForsakeTransition)->getTos());

        /** @var Transition $forsakeTransition */
        $forsakeTransition = array_filter($definition->getTransitions(), fn($transition) => $transition->getName() === 'forsake');
        $this->assertNotEmpty($forsakeTransition);
        $this->assertContains('idle_notified', current($forsakeTransition)->getFroms());
        $this->assertContains('abandoned', current($forsakeTransition)->getTos());
    }
}
