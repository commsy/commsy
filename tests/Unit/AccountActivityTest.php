<?php
namespace App\Tests\Unit;

use App\Account\AccountManager;
use App\Cron\Tasks\CronUpdateActivityState;
use App\Entity\Account;
use App\Entity\AuthSourceLocal;
use App\Entity\Portal;
use App\EventSubscriber\AccountActivityStateSubscriber;
use App\EventSubscriber\LoginSubscriber;
use App\Repository\PortalRepository;
use App\Utils\UserService;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use cs_user_item;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Workflow\Event\GuardEvent;
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

        $account = new Account();

        $workflow = $registry->get($account, 'activity');
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

//    private Account $account;
//
//    private CronUpdateAccountActivityState $cronTask;
//
//    protected function _before()
//    {
//        $this->account = $this->make(Account::class, [
//            'authSource' => $this->makeEmpty(AuthSourceLocal::class),
//            'activity' => Account::ACTIVITY_ACTIVE,
//            'contextId' => 12345,
//            'username' => 'user',
//            'firstname' => 'firstname',
//            'lastname' => 'lastname',
//            'email' => 'some@mail.com',
//            'language' => 'de',
//        ]);

//        $accountRepositoryStub = $this->makeEmpty(AccountsRepository::class, [
//            'findAll' => [$this->account],
//        ]);
//        $entityManagerStub = $this->makeEmpty(EntityManagerInterface::class);
//
//        /** @var Registry $workflows */
//        $workflows = $this->tester->grabService(Registry::class);
//        $accountActivityStateMachine = $workflows->get($this->account, 'account_activity');
//
//        var_dump($accountActivityStateMachine->getEnabledTransitions($this->account)); exit;
//
//        $this->cronTask = new CronUpdateAccountActivityState(
//            $accountRepositoryStub,
//            $entityManagerStub,
//            $accountActivityStateMachine
//        );
//    }
//
//    protected function _after()
//    {
//    }

    // tests
//    public function testLoginResetsInactivity()
//    {
//        $account = $this->account;
//        $loginSubscriber = $this->makeEmptyExcept(LoginSubscriber::class, 'onInteractiveLogin', [
//            'accountManager' => $this->make(AccountManager::class, [
//                'entityManager' => $this->makeEmpty(EntityManagerInterface::class),
//            ]),
//            'security' => $this->makeEmpty(Security::class, [
//                'getUser' => $account,
//            ]),
//        ]);
//
//        // idle -> active
//        $account->setActivity(Account::ACTIVITY_IDLE);
//        $this->assertEquals(Account::ACTIVITY_IDLE, $account->getActivity());
//        $loginSubscriber->onInteractiveLogin($this->makeEmpty(InteractiveLoginEvent::class));
//        $this->assertEquals(Account::ACTIVITY_ACTIVE, $account->getActivity());
//
//        // abandoned -> active
//        $account->setActivity(Account::ACTIVITY_ABANDONED);
//        $this->assertEquals(Account::ACTIVITY_ABANDONED, $account->getActivity());
//        $loginSubscriber->onInteractiveLogin($this->makeEmpty(InteractiveLoginEvent::class));
//        $this->assertEquals(Account::ACTIVITY_ACTIVE, $account->getActivity());
//    }
//
//    public function testLastModeratorMustStayActive()
//    {
//        $account = $this->account;
//
//        $accountManagerStub = $this->make(AccountManager::class, [
//            'entityManager' => $this->makeEmpty(EntityManagerInterface::class),
//            'isLastModerator' => true,
//        ]);
//        $this->tester->replaceServiceWithMock(AccountManager::class, $accountManagerStub);
//
//        /** @var Registry $workflows */
//        $workflows = $this->tester->grabService(Registry::class);
//        $accountActivityStateMachine = $workflows->get($account, 'account_activity');
//
//        /**
//         * If a user is last moderator he is always considered active and is not allowed
//         * to become idle
//         */
//        $account->setActivity(Account::ACTIVITY_ACTIVE);
//        $this->tester->assertEquals(Account::ACTIVITY_ACTIVE, $account->getActivity());
//        $this->tester->assertFalse($accountActivityStateMachine->can($account, 'lock'));
//    }
//
//    public function testRootMustStayActive()
//    {
//        $account = $this->account;
//        $account->setUsername('root');
//
//        $accountManagerStub = $this->make(AccountManager::class, [
//            'entityManager' => $this->makeEmpty(EntityManagerInterface::class),
//            'isLastModerator' => false,
//        ]);
//        $this->tester->replaceServiceWithMock(AccountManager::class, $accountManagerStub);
//
//        /** @var Registry $workflows */
//        $workflows = $this->tester->grabService(Registry::class);
//        $accountActivityStateMachine = $workflows->get($account, 'account_activity');
//
//        /**
//         * Root is considered to never become inactive
//         */
//        $account->setActivity(Account::ACTIVITY_ACTIVE);
//        $this->tester->assertEquals(Account::ACTIVITY_ACTIVE, $account->getActivity());
//        $this->tester->assertFalse($accountActivityStateMachine->can($account, 'lock'));
//
//        $account->setUsername('root');
//    }

//    public function testAccountNotifyLockTransition()
//    {
//        $account = $this->account;
//
//        $subscriber = $this->make(AccountActivityStateSubscriber::class, [
//            'portalRepository' => $this->makeEmpty(PortalRepository::class, [
//                'find' => $this->makeEmpty(Portal::class, [
//                    'getInactivityLockDays' => 10,
//                ]),
//            ]),
//            'AccountManager' => $this->makeEmpty(AccountManager::class, [
//                'isLastModerator' => false,
//            ]),
//        ]);
//
//        $eventStub = $this->makeEmpty(GuardEvent::class, [
//            'getSubject' => $account,
//        ]);
//        $subscriber->guardNotifyLock($eventStub);
//
//        // assume the user has been inactive for 5 days
//        $lastLogin = new DateTime();
//        $lastLogin->sub(new DateInterval('P5D'));
//        $account->setLastLogin($lastLogin);
//        $this->tester->assertFalse($accountActivityStateMachine->can($account, 'lock'));
//        $this->tester->assertFalse($accountActivityStateMachine->can($account, 'forsake'));
//        $this->tester->assertFalse($account->isLocked());
//
//        // assume the user has been inactive for 15 days
//        $lastLogin = new DateTime();
//        $lastLogin->sub(new DateInterval('P15D'));
//        $account->setLastLogin($lastLogin);
//        $this->tester->assertTrue($accountActivityStateMachine->can($account, 'lock'));
//        $this->tester->assertFalse($accountActivityStateMachine->can($account, 'forsake'));
//
//        $accountActivityStateMachine->apply($account, 'lock');
        // We also expect accountManager->lock() to be called once
//    }

//    public function testAccountLock()
//    {
//        $account = $this->account;
//
//        $portal = $this->make(Portal::class, [
//            'id' => 12345,
//            'getInactivityLockDays' => 10,
//            'getInactivityDeleteDays' => 10,
//        ]);
//
//        $accountManagerStub = $this->make(AccountManager::class, [
//            'entityManager' => $this->makeEmpty(EntityManagerInterface::class),
//            'lock' => Expected::once(),
//            'getPortal' => $portal,
//            'isLastModerator' => false,
//        ]);
//        $this->tester->replaceServiceWithMock(AccountManager::class, $accountManagerStub);
//
//        /** @var Registry $workflows */
//        $workflows = $this->tester->grabService(Registry::class);
//        $accountActivityStateMachine = $workflows->get($account, 'account_activity');
//
//        $portalRepositoryStub = $this->makeEmpty(PortalRepository::class, [
//            'find' => $portal,
//        ]);
//        $this->tester->replaceServiceWithMock(PortalRepository::class, $portalRepositoryStub);
//
//        $account->setActivity(Account::ACTIVITY_ACTIVE);
//        $this->tester->assertEquals(Account::ACTIVITY_ACTIVE, $account->getActivity());
//
//        // assume the user has been inactive for 5 days
//        $lastLogin = new DateTime();
//        $lastLogin->sub(new DateInterval('P5D'));
//        $account->setLastLogin($lastLogin);
//        $this->tester->assertFalse($accountActivityStateMachine->can($account, 'lock'));
//        $this->tester->assertFalse($accountActivityStateMachine->can($account, 'forsake'));
//        $this->tester->assertFalse($account->isLocked());
//
//        // assume the user has been inactive for 15 days
//        $lastLogin = new DateTime();
//        $lastLogin->sub(new DateInterval('P15D'));
//        $account->setLastLogin($lastLogin);
//        $this->tester->assertTrue($accountActivityStateMachine->can($account, 'lock'));
//        $this->tester->assertFalse($accountActivityStateMachine->can($account, 'forsake'));
//
//        $accountActivityStateMachine->apply($account, 'lock');
//        // We also expect accountManager->lock() to be called once
//    }
//
//    public function testAccountForsake()
//    {
//        $account = $this->account;
//
//        $portal = $this->make(Portal::class, [
//            'id' => 12345,
//            'getInactivityLockDays' => 10,
//            'getInactivityDeleteDays' => 10,
//        ]);
//
//        $accountManagerStub = $this->make(AccountManager::class, [
//            'entityManager' => $this->makeEmpty(EntityManagerInterface::class),
//            'delete' => Expected::once(),
//            'getPortal' => $portal,
//            'isLastModerator' => false,
//        ]);
//        $this->tester->replaceServiceWithMock(AccountManager::class, $accountManagerStub);
//
//        /** @var Registry $workflows */
//        $workflows = $this->tester->grabService(Registry::class);
//        $accountActivityStateMachine = $workflows->get($account, 'account_activity');
//
//        $portalRepositoryStub = $this->makeEmpty(PortalRepository::class, [
//            'find' => $portal,
//        ]);
//        $this->tester->replaceServiceWithMock(PortalRepository::class, $portalRepositoryStub);
//
//        $account->setActivity(Account::ACTIVITY_IDLE);
//        $this->tester->assertEquals(Account::ACTIVITY_IDLE, $account->getActivity());
//
//        // assume the user has been locked for 5 days
//        $lastLogin = new DateTime();
//        $lastLogin->sub(new DateInterval('P5D'));
//        $account->setLastLogin($lastLogin);
//        $this->tester->assertFalse($accountActivityStateMachine->can($account, 'forsake'));
//        $this->tester->assertFalse($accountActivityStateMachine->can($account, 'lock'));
//
//        // assume the user has been locked for 15 days
//        $lastLogin = new DateTime();
//        $lastLogin->sub(new DateInterval('P15D'));
//        $account->setLastLogin($lastLogin);
//        $this->tester->assertTrue($accountActivityStateMachine->can($account, 'forsake'));
//        $this->tester->assertFalse($accountActivityStateMachine->can($account, 'lock'));
//
//        $accountActivityStateMachine->apply($account, 'forsake');
//        // We also expect accountManager->delete() to be called once
//    }
}