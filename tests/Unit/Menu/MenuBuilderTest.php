<?php

namespace Tests\Unit\Menu;

use App\Menu\MenuBuilder;
use App\Repository\PortalRepository;
use App\Services\InvitationsService;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use Codeception\Stub;
use Codeception\Test\Unit;
use cs_environment;
use cs_privateroom_item;
use cs_project_item;
use cs_user_item;
use Knp\Menu\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use Tests\Support\UnitTester;

class MenuBuilderTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    private function getMenuBuilder(
        cs_user_item $cs_user_item,
        bool $isRoot,
        RoomService $roomService,
    ): MenuBuilder
    {
        /** @var FactoryInterface $factory */
        $factory = $this->tester->grabService(FactoryInterface::class);

        $legacyEnvironment = Stub::make(LegacyEnvironment::class, [
            'getEnvironment' => Stub::make(cs_environment::class, [
                'getCurrentUserItem' => $cs_user_item,
            ]),
        ]);
        $authorizationChecker = Stub::makeEmpty(AuthorizationCheckerInterface::class, [
            'isGranted' => false,
        ]);
        $invitationsService = Stub::make(InvitationsService::class);
        $portalRepository = Stub::make(PortalRepository::class, [
            'find' => fn() => null,
        ]);
        $security = Stub::make(Security::class, [
            'isGranted' => fn ($attr) => $attr === 'ROLE_ROOT' && $isRoot,
        ]);
        $router = Stub::makeEmpty(RouterInterface::class, [
            'generate' => 'some_route',
        ]);

        return new MenuBuilder(
            $factory,
            $roomService,
            $legacyEnvironment,
            $authorizationChecker,
            $invitationsService,
            $portalRepository,
            $security,
            $router
        );
    }

    public function testMainMenuReplicatesRoomConfiguration()
    {
        $requestStack = Stub::make(RequestStack::class, [
            'getCurrentRequest' => new Request([], [], [
                'roomId' => 1,
                '_route' => 'app_room_home',
            ])
        ]);

        $user = Stub::make(cs_user_item::class, [
            'isGuest' => false,
            'getOwnRoom' => Stub::make(cs_privateroom_item::class, [
                'getItemId' => 0,
            ])
        ]);

        $roomService = Stub::make(RoomService::class, [
            'getVisibleRoomRubrics' => [
                'date', 'todo', 'some'
            ],
            'getRoomItem' => Stub::make(cs_project_item::class, [
                'getDatesPresentationStatus' => 'normal',
            ])
        ]);

        $menuBuilder = $this->getMenuBuilder($user, false, $roomService);
        $mainMenu = $menuBuilder->createMainMenu($requestStack);

        $firstMenu = $mainMenu->getChild('room_home');
        $this->tester->assertNotNull($firstMenu);

        $secondMenu = $mainMenu->getChild('date');
        $this->tester->assertNotNull($secondMenu);

        $thirdMenu = $mainMenu->getChild('todo');
        $this->tester->assertNotNull($thirdMenu);

        $fourthMenu = $mainMenu->getChild('some');
        $this->tester->assertNull($fourthMenu);
    }
}
