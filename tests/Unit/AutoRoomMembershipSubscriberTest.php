<?php

namespace Tests\Unit;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\Portal;
use App\EventSubscriber\AutoRoomMembershipSubscriber;
use App\Facade\UserCreatorFacade;
use Codeception\Stub;
use Codeception\Test\Unit;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Tests\Support\UnitTester;

class AutoRoomMembershipSubscriberTest extends Unit
{
    protected UnitTester $tester;

    /**
     * Tests whether `AutoRoomMembershipSubscriber->onSecurityInteractiveLogin()` gets to call
     * the `UserCreatorFacade->addUserToRoomsWithSlugs()` method.
     *
     * @throws Exception
     */
    public function testSubscriberMethodCalled()
    {
        $roomslugs = ['a-test-room', 'another-test-room'];

        $portal = $this->makeEmpty(Portal::class, [
            'getAuthMembershipEnabled' => fn() => true,
            'getAuthMembershipIdentifier' => fn() => 'roomslugs'
        ]);
        $this->assertInstanceOf(Portal::class, $portal);

        $authSource = $this->makeEmpty(AuthSource::class, [
            'getPortal' => fn() => $portal]);
        $this->assertInstanceOf(AuthSource::class, $authSource);

        $account = $this->makeEmpty(Account::class, [
            'getAuthSource' => fn() => $authSource
        ]);
        $this->assertInstanceOf(Account::class, $account);

        $request = $this->makeEmpty(Request::class, [
            'server' => $this->make(ServerBag::class, [
                'parameters' => [
                    'roomslugs' => join(';', $roomslugs)
                ]
            ])
        ]);
        $this->assertInstanceOf(Request::class, $request);

        $userCreator = $this->makeEmpty(UserCreatorFacade::class, [
            'addUserToRoomsWithSlugs' => Stub\Expected::once(),
        ]);
        $this->assertInstanceOf(UserCreatorFacade::class, $userCreator);

        $loginEvent = $this->makeEmpty(LoginSuccessEvent::class, [
            'getUser' => $account,
            'getRequest' => $request,
        ]);

        $subscriber = new AutoRoomMembershipSubscriber($userCreator);
        $subscriber->onLoginSuccess($loginEvent);
    }
}
