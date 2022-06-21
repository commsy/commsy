<?php
namespace App\Tests\EventSubscriber;

use App\Account\AccountManager;
use App\Entity\Account;
use App\Entity\Portal;
use App\EventSubscriber\AccountActivityStateSubscriber;
use App\Mail\Factories\AccountMessageFactory;
use App\Mail\Mailer;
use App\Repository\PortalRepository;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Symfony\Component\Workflow\Event\GuardEvent;

class AccountActivityStateSubscriberTest extends Unit
{
    /**
     * @var \App\Tests\UnitTester
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
        $subscribedEvents = AccountActivityStateSubscriber::getSubscribedEvents();

        $this->tester->assertArrayHasKey('workflow.account_activity.guard', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.account_activity.guard.notify_lock', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.account_activity.guard.lock', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.account_activity.guard.notify_forsake', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.account_activity.guard.forsake', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.account_activity.entered', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.account_activity.entered.active_notified', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.account_activity.entered.idle', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.account_activity.entered.idle_notified', $subscribedEvents);
        $this->tester->assertArrayHasKey('workflow.account_activity.entered.abandoned', $subscribedEvents);

        $this->tester->assertContains('guard', $subscribedEvents['workflow.account_activity.guard']);
        $this->tester->assertContains('guardNotifyLock', $subscribedEvents['workflow.account_activity.guard.notify_lock']);
        $this->tester->assertContains('guardLock', $subscribedEvents['workflow.account_activity.guard.lock']);
        $this->tester->assertContains('guardNotifyForsake', $subscribedEvents['workflow.account_activity.guard.notify_forsake']);
        $this->tester->assertContains('guardForsake', $subscribedEvents['workflow.account_activity.guard.forsake']);
        $this->tester->assertContains('entered', $subscribedEvents['workflow.account_activity.entered']);
        $this->tester->assertContains('enteredActiveNotified', $subscribedEvents['workflow.account_activity.entered.active_notified']);
        $this->tester->assertContains('enteredIdle', $subscribedEvents['workflow.account_activity.entered.idle']);
        $this->tester->assertContains('enteredIdleNotified', $subscribedEvents['workflow.account_activity.entered.idle_notified']);
        $this->tester->assertContains('enteredAbandoned', $subscribedEvents['workflow.account_activity.entered.abandoned']);
    }

    // TODO: These tests fail because GuardEvent is now final and cannot be mocked
//    public function testGuardPortalFeature()
//    {
//        $portalRepositoryStub = $this->makeEmpty(PortalRepository::class, [
//            'find' => $this->make(Portal::class, [
//                'isClearInactiveAccountsFeatureEnabled' => false,
//            ]),
//        ]);
//        $guardEventStub = $this->make(GuardEvent::class, [
//            'getSubject' => $this->make(Account::class, [
//                'contextId' => 12345,
//                'username' => 'user',
//            ]),
//            'setBlocked' => Expected::atLeastOnce(),
//        ]);
//        $accountManagerStub = $this->makeEmpty(AccountManager::class, [
//            'isLastModerator' => false,
//        ]);
//        $accountMessageFactoryStub = $this->makeEmpty(AccountMessageFactory::class);
//        $mailerStub = $this->makeEmpty(Mailer::class);
//
//        $subscriber = new AccountActivityStateSubscriber(
//            $portalRepositoryStub,
//            $accountManagerStub,
//            $accountMessageFactoryStub,
//            $mailerStub
//        );
//        $subscriber->guard($guardEventStub);
//
//        //////
//
//        $portalRepositoryStub = $this->makeEmpty(PortalRepository::class, [
//            'find' => $this->make(Portal::class, [
//                'isClearInactiveAccountsFeatureEnabled' => true,
//            ]),
//        ]);
//
//        $guardEventStub = $this->make(GuardEvent::class, [
//            'getSubject' => $this->make(Account::class, [
//                'contextId' => 12345,
//                'username' => 'user',
//            ]),
//            'setBlocked' => Expected::never(),
//        ]);
//
//        $subscriber = new AccountActivityStateSubscriber(
//            $portalRepositoryStub,
//            $accountManagerStub,
//            $accountMessageFactoryStub,
//            $mailerStub
//        );
//        $subscriber->guard($guardEventStub);
//    }
//
//    public function testGuardRootUser()
//    {
//        $portalRepositoryStub = $this->makeEmpty(PortalRepository::class, [
//            'find' => $this->make(Portal::class, [
//                'isClearInactiveAccountsFeatureEnabled' => true,
//            ]),
//        ]);
//        $guardEventStub = $this->make(GuardEvent::class, [
//            'getSubject' => $this->make(Account::class, [
//                'contextId' => 12345,
//                'username' => 'root',
//            ]),
//            'setBlocked' => Expected::atLeastOnce(),
//        ]);
//        $accountManagerStub = $this->makeEmpty(AccountManager::class, [
//            'isLastModerator' => false,
//        ]);
//        $accountMessageFactoryStub = $this->makeEmpty(AccountMessageFactory::class);
//        $mailerStub = $this->makeEmpty(Mailer::class);
//
//        $subscriber = new AccountActivityStateSubscriber(
//            $portalRepositoryStub,
//            $accountManagerStub,
//            $accountMessageFactoryStub,
//            $mailerStub
//        );
//        $subscriber->guard($guardEventStub);
//    }
//
//    public function testGuardLastModerator()
//    {
//        $account = $this->make(Account::class, [
//            'contextId' => 12345,
//            'username' => 'user',
//        ]);
//
//        $portalRepositoryStub = $this->makeEmpty(PortalRepository::class, [
//            'find' => $this->make(Portal::class, [
//                'isClearInactiveAccountsFeatureEnabled' => true,
//            ]),
//        ]);
//        $guardEventStub = $this->make(GuardEvent::class, [
//            'getSubject' => $account,
//            'setBlocked' => Expected::atLeastOnce(),
//        ]);
//        $accountManagerStub = $this->makeEmpty(AccountManager::class, [
//            'isLastModerator' => true,
//            'resetInactivity' => Expected::once(),
//        ]);
//        $accountMessageFactoryStub = $this->makeEmpty(AccountMessageFactory::class);
//        $mailerStub = $this->makeEmpty(Mailer::class);
//
//        $subscriber = new AccountActivityStateSubscriber(
//            $portalRepositoryStub,
//            $accountManagerStub,
//            $accountMessageFactoryStub,
//            $mailerStub
//        );
//        $subscriber->guard($guardEventStub);
//    }
}