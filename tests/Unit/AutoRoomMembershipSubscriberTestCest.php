<?php

namespace App\Tests\Unit;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\Portal;
use App\EventSubscriber\AutoRoomMembershipSubscriber;
use App\Facade\UserCreatorFacade;
use App\Tests\UnitTester;
use Codeception\Stub;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class AutoRoomMembershipSubscriberTestCest
{
    public function _before(UnitTester $I)
    {
    }

    /**
     * Tests whether `AutoRoomMembershipSubscriber->onSecurityInteractiveLogin()` gets to call
     * the `UserCreatorFacade->addUserToRoomsWithSlugs()` method.
     *
     * @param UnitTester $I
     * @throws \Exception
     */
    public function tryToTest(UnitTester $I)
    {
        // TODO fail test if AutoRoomMembershipSubscriber->onSecurityInteractiveLogin returns early

        $roomslugs = ['little-test-room', 'yet-another-little-test-room'];

        $portal = Stub::makeEmpty(Portal::class, [
            'getAuthMembershipEnabled' => function () {
                return true;
            },
            'getAuthMembershipIdentifier' => function () {
                return 'roomslugs';
            }
        ]);

        $authSource = Stub::makeEmpty(AuthSource::class, [
            'getPortal' => function () use ($portal) {
                return $portal;
            }]);

        $account = Stub::makeEmpty(Account::class, [
            'getAuthSource' => function () use ($authSource) {
                return $authSource;
            }
        ]);

        $authToken = Stub::makeEmpty(TokenInterface::class, [
            'getUser' => function () use ($account) {
                return $account;
            }
        ]);

        $request = Stub::makeEmpty(Request::class, [
            'request' => Stub::make(ParameterBag::class, [
                'parameters' => [
                    'roomslugs' => join(',', $roomslugs)
                ]
            ])
        ]);

        $event = Stub::makeEmpty(InteractiveLoginEvent::class, [
            'getAuthenticationToken' => function () use ($authToken) {
                return $authToken;
            },
            'getRequest' => function () use ($request) {
                return $request;
            },
        ]);

        $userCreator = Stub::makeEmpty(UserCreatorFacade::class, [
            'addUserToRoomsWithSlugs' => Stub\Expected::once(),
        ], $this);

        $subscriber = new AutoRoomMembershipSubscriber($userCreator);

        $subscriber->onSecurityInteractiveLogin($event);
    }
}
