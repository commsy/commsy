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

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Entity\Room;
use PHPUnit\Framework\TestCase;

/**
 * The entity answers which rubrics a room offers the way the legacy room does.
 */
class RoomRubricConfigurationTest extends TestCase
{
    public function testTheSettingsSayWhichRubricsTheRoomOffers(): void
    {
        $room = (new Room())->setType('project')->setExtras(['HOMECONF' => 'material_show,todo_hide']);

        $this->assertSame(['material', 'todo'], $room->getRubricConfiguration()->rubrics());
    }

    public function testARoomWithoutSettingsOffersTheDefaultOfItsType(): void
    {
        $room = (new Room())->setType('grouproom')->setExtras([]);

        $this->assertSame(
            ['announcement', 'todo', 'date', 'material', 'discussion', 'user', 'topic'],
            $room->getRubricConfiguration()->rubrics()
        );
    }

    public function testEmptySettingsOfferNoRubric(): void
    {
        $room = (new Room())->setType('project')->setExtras(['HOMECONF' => '']);

        $this->assertSame([], $room->getRubricConfiguration()->rubrics());
    }
}
