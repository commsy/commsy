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
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
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
            'getAuthMembershipEnabled' => function () {
                return true;
            },
            'getAuthMembershipIdentifier' => function () {
                return 'roomslugs';
            }
        ]);
        $this->assertInstanceOf(Portal::class, $portal);

        $authSource = $this->makeEmpty(AuthSource::class, [
            'getPortal' => function () use ($portal) {
                return $portal;
            }]);
        $this->assertInstanceOf(AuthSource::class, $authSource);

        $account = $this->makeEmpty(Account::class, [
            'getAuthSource' => function () use ($authSource) {
                return $authSource;
            }
        ]);
        $this->assertInstanceOf(Account::class, $account);

        $passport = $this->makeEmpty(Passport::class, [
            'getUser' => function () use ($account) {
                return $account;
            }
        ]);
        $this->assertInstanceOf(Passport::class, $passport);

        $request = $this->makeEmpty(Request::class, [
            'server' => $this->make(ParameterBag::class, [
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

        $subscriber = new AutoRoomMembershipSubscriber($userCreator);

        $loginEvent = $this->makeEmpty(LoginSuccessEvent::class, [
            'getRequest' => fn() => $request,
            'getUser' => fn() => $account,
        ]);

        $subscriber->onLoginSuccess($loginEvent);
    }
}
