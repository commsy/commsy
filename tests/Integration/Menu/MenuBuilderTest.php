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

namespace Tests\Integration\Menu;

use App\Menu\MenuBuilder;
use App\Repository\PortalRepository;
use App\Services\InvitationsService;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use cs_environment;
use cs_privateroom_item;
use cs_project_item;
use cs_user_item;
use Knp\Menu\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuBuilderTest extends KernelTestCase
{
    private function getMenuBuilder(
        cs_user_item $cs_user_item,
        bool $isRoot,
        RoomService $roomService,
    ): MenuBuilder
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var FactoryInterface $factory */
        $factory = $container->get(FactoryInterface::class);

        $legacyEnvironment = $this->createConfiguredMock(LegacyEnvironment::class, [
            'getEnvironment' => $this->createConfiguredMock(cs_environment::class, [
                'getCurrentUserItem' => $cs_user_item,
            ]),
        ]);
        $authorizationChecker = $this->createConfiguredMock(AuthorizationCheckerInterface::class, [
            'isGranted' => false,
        ]);
        $invitationsService = $this->createStub(InvitationsService::class);
        $portalRepository = $this->createConfiguredMock(PortalRepository::class, [
            'find' => null,
        ]);
        $security = $this->createStub(Security::class);
        $security->method('isGranted')
            ->willReturnCallback(fn ($attr) => $attr === 'ROLE_ROOT' && $isRoot);
        $router = $this->createConfiguredMock(RouterInterface::class, [
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

    public function testMainMenuReplicatesRoomConfiguration(): void
    {
        $requestStack = $this->createConfiguredMock(RequestStack::class, [
            'getCurrentRequest' => new Request([], [], [
                'roomId' => 1,
                '_route' => 'app_room_home',
            ])
        ]);

        $user = $this->createConfiguredMock(cs_user_item::class, [
            'isGuest' => false,
            'getOwnRoom' => $this->createConfiguredMock(cs_privateroom_item::class, [
                'getItemId' => 0,
            ])
        ]);

        $roomService = $this->createConfiguredMock(RoomService::class, [
            'getVisibleRoomRubrics' => [
                'date', 'todo', 'some'
            ],
            'getRoomItem' => $this->createConfiguredMock(cs_project_item::class, [
                'getDatesPresentationStatus' => 'normal',
            ])
        ]);

        $menuBuilder = $this->getMenuBuilder($user, false, $roomService);
        $mainMenu = $menuBuilder->createMainMenu($requestStack);

        $firstMenu = $mainMenu->getChild('room_home');
        $this->assertNotNull($firstMenu);

        $secondMenu = $mainMenu->getChild('date');
        $this->assertNotNull($secondMenu);

        $thirdMenu = $mainMenu->getChild('todo');
        $this->assertNotNull($thirdMenu);

        $fourthMenu = $mainMenu->getChild('some');
        $this->assertNull($fourthMenu);
    }
}
