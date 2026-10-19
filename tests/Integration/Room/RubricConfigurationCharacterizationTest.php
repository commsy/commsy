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

namespace Tests\Integration\Room;

use App\Services\LegacyEnvironment;
use cs_community_item;
use cs_context_item;
use cs_grouproom_item;
use cs_privateroom_item;
use cs_project_item;
use cs_userroom_item;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Which rubrics a room offers is read from `HOMECONF` in its extras, a list
 * like `material_show,todo_hide`. This pins how a legacy room answers, so the
 * answers stay the same wherever the reading moves to.
 */
final class RubricConfigurationCharacterizationTest extends KernelTestCase
{
    /**
     * @return iterable<string, array{class-string<cs_context_item>, list<string>}>
     */
    public static function roomTypes(): iterable
    {
        yield 'project' => [
            cs_project_item::class,
            ['announcement', 'todo', 'date', 'material', 'discussion', 'user', 'group', 'topic'],
        ];
        yield 'community' => [
            cs_community_item::class,
            ['announcement', 'project', 'todo', 'date', 'material', 'discussion', 'user', 'topic'],
        ];
        yield 'group room' => [
            cs_grouproom_item::class,
            ['announcement', 'todo', 'date', 'material', 'discussion', 'user', 'topic'],
        ];
        yield 'private room' => [cs_privateroom_item::class, ['myroom', 'date', 'entry']];
        yield 'user room' => [cs_userroom_item::class, ['announcement', 'todo', 'material', 'discussion', 'user']];
    }

    /**
     * @param class-string<cs_context_item> $class
     * @param list<string>                  $rubrics
     */
    #[DataProvider('roomTypes')]
    public function testARoomOffersTheDefaultOfItsTypeUntilItsSettingsSayOtherwise(string $class, array $rubrics): void
    {
        $room = $this->room($class);
        $shown = implode(',', array_map(static fn (string $rubric): string => $rubric.'_show', $rubrics));

        $this->assertSame($shown, $room->getDefaultHomeConf());
        $this->assertSame($rubrics, $room->getAvailableDefaultRubricArray());
        $this->assertSame($rubrics, $room->getAvailableRubrics());
        $this->assertSame($shown, $room->getHomeConf());
    }

    public function testTheSettingsListTheRubricsInTheirOrderShownOrHidden(): void
    {
        $room = $this->room(cs_project_item::class);
        $room->setHomeConf('material_show,todo_hide,user_show');

        $this->assertSame(['material', 'todo', 'user'], $room->getAvailableRubrics());
        $this->assertTrue((bool) $room->withRubric('material'));
        $this->assertTrue((bool) $room->withRubric('todo'));
        $this->assertFalse((bool) $room->withRubric('date'));
        $this->assertFalse((bool) $room->withRubric(''));
    }

    public function testEmptySettingsOfferNoRubric(): void
    {
        $room = $this->room(cs_project_item::class);
        $room->setHomeConf('');

        $this->assertSame([], $room->getAvailableRubrics());
        $this->assertFalse((bool) $room->withRubric('material'));
    }

    /**
     * Since 2023 the settings hold only `show` and `hide`. Older spellings
     * still offer the rubric.
     */
    public function testAnOlderSpellingStillOffersTheRubric(): void
    {
        $room = $this->room(cs_project_item::class);
        $room->_addExtra('HOMECONF', 'user_view,material_grid');

        $this->assertSame(['user', 'material'], $room->getAvailableRubrics());
        $this->assertTrue((bool) $room->withRubric('user'));
    }

    /**
     * @return iterable<string, array{class-string<cs_context_item>, string}>
     */
    public static function refusedSettings(): iterable
    {
        yield 'a rubric of another room type' => [cs_project_item::class, 'material_show,project_show'];
        yield 'groups in a community room' => [cs_community_item::class, 'group_show'];
        yield 'an unknown visibility' => [cs_project_item::class, 'material_none'];
        yield 'no visibility' => [cs_project_item::class, 'material'];
    }

    /**
     * @param class-string<cs_context_item> $class
     */
    #[DataProvider('refusedSettings')]
    public function testSettingsARoomCannotHaveAreRefused(string $class, string $configuration): void
    {
        $room = $this->room($class);

        $this->expectException(LogicException::class);

        $room->setHomeConf($configuration);
    }

    /**
     * @template T of cs_context_item
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function room(string $class): cs_context_item
    {
        self::bootKernel();

        return new $class(self::getContainer()->get(LegacyEnvironment::class)->getEnvironment());
    }
}
