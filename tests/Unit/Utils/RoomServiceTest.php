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

namespace Tests\Unit\Utils;

use App\Room\Copy\LegacyCopy;
use App\Security\Permission\Legacy\LegacyPermissionBridge;
use App\Services\CurrentContextResolver;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use cs_environment;
use cs_project_item;
use cs_room_manager;
use PHPUnit\Framework\TestCase;

class RoomServiceTest extends TestCase
{
    public function testGetRubricInformationWithoutModifier(): void
    {
        $rubrics = $this->roomService('material_show')->getRubricInformation(1, false);

        $this->assertCount(1, $rubrics);
        $this->assertContains('material', $rubrics);
    }

    /**
     * The menu lists every rubric the room offers, hidden ones included.
     */
    public function testTheMenuGetsEveryRubricTheRoomOffers(): void
    {
        $rubrics = $this->roomService('material_show,todo_hide')->getVisibleRoomRubrics(1, ['off']);

        $this->assertSame(['material', 'todo'], array_values($rubrics));
    }

    private function roomService(string $homeConf): RoomService
    {
        $legacyEnvironment = $this->createConfiguredMock(LegacyEnvironment::class, [
            'getEnvironment' => $this->createConfiguredMock(cs_environment::class, [
                'getRoomManager' => $this->createConfiguredMock(cs_room_manager::class, [
                    'getItem' => $this->createConfiguredMock(cs_project_item::class, [
                        'getHomeConf' => $homeConf,
                    ])
                ])
            ]),
        ]);
        $legacyCopy = $this->createStub(LegacyCopy::class);
        $legacyBridge = $this->createStub(LegacyPermissionBridge::class);
        $currentContextResolver = $this->createStub(CurrentContextResolver::class);

        return new RoomService(
            $legacyEnvironment,
            $legacyCopy,
            $legacyBridge,
            $currentContextResolver,
        );
    }
}
