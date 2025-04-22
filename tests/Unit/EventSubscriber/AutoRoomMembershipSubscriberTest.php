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

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\Portal;
use App\EventSubscriber\AutoRoomMembershipSubscriber;
use App\Facade\UserCreatorFacade;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class AutoRoomMembershipSubscriberTest extends TestCase
{
    /**
     * Tests whether `AutoRoomMembershipSubscriber->onSecurityInteractiveLogin()` gets to call
     * the `UserCreatorFacade->addUserToRoomsWithSlugs()` method.
     *
     * @throws Exception
     */
    public function testSubscriberMethodCalled(): void
    {
        $roomslugs = ['a-test-room', 'another-test-room'];

        $portal = $this->createConfiguredMock(Portal::class, [
            'getAuthMembershipEnabled' => true,
            'getAuthMembershipIdentifier' => 'roomslugs'
        ]);

        $authSource = $this->createConfiguredMock(AuthSource::class, [
            'getPortal' => $portal]);
        $account = $this->createConfiguredMock(Account::class, [
            'getAuthSource' => $authSource
        ]);

        $request = new Request(server: ['roomslugs' => join(';', $roomslugs)]);

        $userCreator = $this->createMock(UserCreatorFacade::class);
        $userCreator->expects($this->once())
            ->method('addUserToRoomsWithSlugs');

        $loginEvent = $this->createConfiguredMock(LoginSuccessEvent::class, [
            'getUser' => $account,
            'getRequest' => $request,
        ]);

        $subscriber = new AutoRoomMembershipSubscriber($userCreator);
        $subscriber->onLoginSuccess($loginEvent);
    }
}
