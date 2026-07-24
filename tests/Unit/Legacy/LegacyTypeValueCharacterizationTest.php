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

use App\Item\ItemType;
use App\Legacy\Chars;
use App\Legacy\TextConverterMode;
use App\Room\RoomType;
use App\Rubric\Label\LabelType;
use App\Rubric\RubricType;
use PHPUnit\Framework\TestCase;

/**
 * Pins the string/int values of the typed sources that replaced the former
 * legacy/etc/cs_constants.php globals.
 *
 * These values must stay byte-for-byte compatible with the legacy `items.type`
 * / `labels.type` column contents and the text-converter mode selectors, so
 * this guards against accidental drift after the globals were removed.
 */
class LegacyTypeValueCharacterizationTest extends TestCase
{
    public function testRubricTypeValues(): void
    {
        self::assertSame('announcement', RubricType::Announcement->value);
        self::assertSame('annotation', RubricType::Annotation->value);
        self::assertSame('date', RubricType::Date->value);
        self::assertSame('discussion', RubricType::Discussion->value);
        self::assertSame('label', RubricType::Label->value);
        self::assertSame('material', RubricType::Material->value);
        self::assertSame('todo', RubricType::Todo->value);
    }

    public function testRoomTypeValues(): void
    {
        self::assertSame('community', RoomType::Community->value);
        self::assertSame('grouproom', RoomType::GroupRoom->value);
        self::assertSame('privateroom', RoomType::PrivateRoom->value);
        self::assertSame('project', RoomType::Project->value);
        self::assertSame('userroom', RoomType::UserRoom->value);
    }

    public function testLabelTypeValues(): void
    {
        self::assertSame('group', LabelType::Group->value);
        self::assertSame('topic', LabelType::Topic->value);
        self::assertSame('institution', LabelType::Institution->value);
        self::assertSame('buzzword', LabelType::Buzzword->value);
    }

    public function testItemTypeValues(): void
    {
        self::assertSame('item', ItemType::Item->value);
        self::assertSame('user', ItemType::User->value);
        self::assertSame('step', ItemType::Step->value);
        self::assertSame('section', ItemType::Section->value);
        self::assertSame('discarticle', ItemType::DiscussionArticle->value);
        self::assertSame('task', ItemType::Task->value);
        self::assertSame('file', ItemType::File->value);
        self::assertSame('tag', ItemType::Tag->value);
        self::assertSame('tag2tag', ItemType::Tag2Tag->value);
        self::assertSame('link', ItemType::Link->value);
        self::assertSame('link_item', ItemType::LinkItem->value);
        self::assertSame('link_modifier_item', ItemType::LinkModifierItem->value);
        self::assertSame('link_item_file', ItemType::LinkItemFile->value);
        self::assertSame('noticed', ItemType::Noticed->value);
        self::assertSame('time', ItemType::Time->value);
        self::assertSame('entry', ItemType::Entry->value);
        self::assertSame('assessments', ItemType::Assessment->value);
        self::assertSame('room', ItemType::Room->value);
        self::assertSame('myroom', ItemType::MyRoom->value);
        self::assertSame('server', ItemType::Server->value);
    }

    public function testCharValues(): void
    {
        self::assertSame("\n", Chars::LF);
        self::assertSame('<br />', Chars::BR);
        self::assertSame("<br />\n", Chars::BRLF);
        self::assertSame("\t", Chars::TAB);
        self::assertSame('ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞ', Chars::UC_CHARS);
        self::assertSame('àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþ', Chars::LC_CHARS);
        self::assertSame(Chars::UC_CHARS.Chars::LC_CHARS.'ß', Chars::SPECIAL_CHARS);
        self::assertSame("A-Za-z0-9\?:@&=/;_\.\+!\*'(,%\$~#-", Chars::RFC1738_CHARS);
        self::assertSame("A-Za-z0-9!#\$%&'\*\+/=\?\^_`{\|}~-", Chars::RFC2822_CHARS);
    }

    public function testTextConverterModeValues(): void
    {
        self::assertSame(2, TextConverterMode::AS_HTML_SHORT);
        self::assertSame(4, TextConverterMode::AS_FORM);
        self::assertSame(5, TextConverterMode::AS_DB);
        self::assertSame(6, TextConverterMode::AS_FILE);
        self::assertSame(7, TextConverterMode::AS_MAIL);
        self::assertSame(8, TextConverterMode::AS_RSS);
        self::assertSame(10, TextConverterMode::NONE);
        self::assertSame(12, TextConverterMode::FROM_DB);
        self::assertSame(13, TextConverterMode::FROM_FILE);
        self::assertSame(14, TextConverterMode::FROM_GET);
    }
}
