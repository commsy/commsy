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
        $legacyEnvironment = $this->createConfiguredMock(LegacyEnvironment::class, [
            'getEnvironment' => $this->createConfiguredMock(cs_environment::class, [
                'getRoomManager' => $this->createConfiguredMock(cs_room_manager::class, [
                    'getItem' => $this->createConfiguredMock(cs_project_item::class, [
                        'getHomeConf' => 'material_show',
                    ])
                ])
            ]),
        ]);
        $legacyCopy = $this->createStub(LegacyCopy::class);

        $roomService = new RoomService($legacyEnvironment, $legacyCopy);
        $rubrics = $roomService->getRubricInformation(1, false);

        $this->assertCount(1, $rubrics);
        $this->assertContains('material', $rubrics);
    }
}
