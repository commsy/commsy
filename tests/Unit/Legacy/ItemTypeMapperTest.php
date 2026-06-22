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

namespace Tests\Unit\Legacy;

use App\Legacy\ItemTypeMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ItemTypeMapperTest extends TestCase
{
    public function testFromModuleMapsKnownModules(): void
    {
        self::assertSame('topic', ItemTypeMapper::fromModule('topics'));
        self::assertSame('announcement', ItemTypeMapper::fromModule('announcement'));
        self::assertSame('date', ItemTypeMapper::fromModule('dates'));
    }

    public function testFromModuleLowercasesInput(): void
    {
        self::assertSame('topic', ItemTypeMapper::fromModule('Topics'));
    }

    public function testFromModuleReturnsUnknownModuleAsIs(): void
    {
        self::assertSame('material', ItemTypeMapper::fromModule('material'));
        self::assertSame('whatever', ItemTypeMapper::fromModule('whatever'));
    }

    #[DataProvider('dbTableCases')]
    public function testFromDbTableMapsKnownTables(string $table, string $expected): void
    {
        self::assertSame($expected, ItemTypeMapper::fromDbTable($table));
    }

    public static function dbTableCases(): array
    {
        return [
            ['annotations', 'annotation'],
            ['dates', 'date'],
            ['discussionarticles', 'discarticle'],
            ['discussions', 'discussion'],
            ['labels', 'label'],
            ['materials', 'material'],
            ['files', 'file'],
            ['todos', 'todo'],
            ['links', 'link'],
            ['link_items', 'link_item'],
            ['item_link_file', 'link_item_file'],
        ];
    }

    public function testFromDbTableLowercasesInput(): void
    {
        self::assertSame('material', ItemTypeMapper::fromDbTable('Materials'));
    }

    public function testFromDbTableReturnsUnknownTableAsIs(): void
    {
        // e.g. "tags" has no mapping -> returned verbatim (CS_TAG_TYPE is 'tag')
        self::assertSame('tags', ItemTypeMapper::fromDbTable('tags'));
    }
}
