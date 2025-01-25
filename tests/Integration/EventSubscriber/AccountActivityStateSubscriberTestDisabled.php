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

//class AccountActivityStateSubscriberTest extends KernelTestCase
//{
//
//    // TODO: These tests fail because GuardEvent is now final and cannot be mocked
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
//}
