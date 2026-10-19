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

namespace Tests\Unit\Room;

use App\Room\RoomType;
use App\Room\RubricConfiguration;
use App\Room\RubricVisibility;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Reading what a room stored bears with what older versions wrote; storing
 * accepts only what the settings can produce today.
 */
class RubricConfigurationTest extends TestCase
{
    public function testTheRubricsComeInTheRoomsOrderShownOrHidden(): void
    {
        $configuration = RubricConfiguration::fromString('material_show,todo_hide');

        $this->assertSame(['material', 'todo'], $configuration->rubrics());
        $this->assertSame(
            ['material' => RubricVisibility::Show, 'todo' => RubricVisibility::Hide],
            $configuration->visibilities()
        );
        $this->assertTrue($configuration->offers('todo'));
        $this->assertFalse($configuration->offers('date'));
        $this->assertNull($configuration->visibilityOf('date'));
        $this->assertSame('material_show,todo_hide', $configuration->toString());
    }

    public function testAnOlderSpellingCountsAsShown(): void
    {
        $configuration = RubricConfiguration::fromString('user_view,material_grid,date');

        $this->assertSame(['user', 'material', 'date'], $configuration->rubrics());
        $this->assertSame(RubricVisibility::Show, $configuration->visibilityOf('material'));
    }

    public function testAnOlderModuleNameReadsAsTodays(): void
    {
        $this->assertSame(['topic', 'date'], RubricConfiguration::fromString('topics_show,dates_hide')->rubrics());
    }

    public function testEmptySettingsOfferNothing(): void
    {
        $this->assertSame([], RubricConfiguration::fromString('')->rubrics());
        $this->assertSame([], RubricConfiguration::fromString(',,')->rubrics());
    }

    public function testTheDefaultShowsEveryRubricOfTheRoomType(): void
    {
        $this->assertSame(
            'announcement_show,todo_show,material_show,discussion_show,user_show',
            RubricConfiguration::defaultFor(RoomType::UserRoom)->toString()
        );
        $this->assertSame([], RubricConfiguration::defaultFor(null)->rubrics());
    }

    public function testWhatTheSettingsProduceCanBeStored(): void
    {
        $configuration = RubricConfiguration::validated('todo_hide,material_show', RoomType::Project);

        $this->assertSame(['todo', 'material'], $configuration->rubrics());
        $this->assertSame([], RubricConfiguration::validated('', null)->rubrics());
    }

    /**
     * @return iterable<string, array{string, RoomType|null}>
     */
    public static function refused(): iterable
    {
        yield 'an older spelling' => ['material_view', RoomType::Project];
        yield 'no visibility' => ['material', RoomType::Project];
        yield 'a rubric of another room type' => ['project_show', RoomType::Project];
        yield 'an older module name' => ['topics_show', RoomType::Project];
        yield 'any rubric outside a room' => ['material_show', null];
    }

    #[DataProvider('refused')]
    public function testStoringRefusesWhatReadingBearsWith(string $configuration, ?RoomType $type): void
    {
        $this->expectException(InvalidArgumentException::class);

        RubricConfiguration::validated($configuration, $type);
    }
}
