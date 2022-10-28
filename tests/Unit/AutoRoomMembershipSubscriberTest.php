<?php

namespace App\Tests\Unit;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\Portal;
use App\EventSubscriber\AutoRoomMembershipSubscriber;
use App\Facade\UserCreatorFacade;
use App\Tests\UnitTester;
use Codeception\Stub;
use Codeception\Test\Unit;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class AutoRoomMembershipSubscriberTest extends Unit
{
    protected UnitTester $tester;

    /**
     * Tests whether `AutoRoomMembershipSubscriber->onSecurityInteractiveLogin()` gets to call
     * the `UserCreatorFacade->addUserToRoomsWithSlugs()` method.
     *
     * @throws \Exception
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

        $authSource = $this->makeEmpty(AuthSource::class, [
            'getPortal' => function () use ($portal) {
                return $portal;
            }]);

        $account = $this->makeEmpty(Account::class, [
            'getAuthSource' => function () use ($authSource) {
                return $authSource;
            }
        ]);

        $authToken = $this->makeEmpty(TokenInterface::class, [
            'getUser' => function () use ($account) {
                return $account;
            }
        ]);

        $request = $this->makeEmpty(Request::class, [
            'request' => $this->make(ParameterBag::class, [
                'parameters' => [
                    'roomslugs' => join(',', $roomslugs)
                ]
            ])
        ]);

        $event = $this->makeEmpty(InteractiveLoginEvent::class, [
            'getAuthenticationToken' => function () use ($authToken) {
                return $authToken;
            },
            'getRequest' => function () use ($request) {
                return $request;
            },
        ]);

        $userCreator = $this->makeEmpty(UserCreatorFacade::class, [
            'addUserToRoomsWithSlugs' => Stub\Expected::once(),
        ]);

        $subscriber = new AutoRoomMembershipSubscriber($userCreator);

        $subscriber->onSecurityInteractiveLogin($event);
    }
}
