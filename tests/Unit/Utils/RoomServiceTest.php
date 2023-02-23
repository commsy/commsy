<?php

namespace Tests\Unit\Utils;

use App\Room\Copy\LegacyCopy;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use Codeception\Stub;
use Codeception\Test\Unit;
use cs_environment;
use cs_project_item;
use cs_room_manager;
use Tests\Support\UnitTester;

class RoomServiceTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    public function testGetRubricInformationWithoutModifier()
    {
        $legacyEnvironment = Stub::make(LegacyEnvironment::class, [
            'getEnvironment' => Stub::make(cs_environment::class, [
                'getRoomManager' => Stub::make(cs_room_manager::class, [
                    'getItem' => Stub::make(cs_project_item::class, [
                        'getHomeConf' => fn () => 'material_show',
                    ])
                ])
            ]),
        ]);
        $legacyCopy = Stub::make(LegacyCopy::class);

        $roomService = new RoomService($legacyEnvironment, $legacyCopy);
        $rubrics = $roomService->getRubricInformation(1, false);

        $this->tester->assertCount(1, $rubrics);
        $this->tester->assertContains('material', $rubrics);
    }
}
