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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pins the values of the legacy `cs_constants.php` globals against both their
 * original literals and the new typed sources they now delegate to.
 *
 * This guards the whole cs_constants.php elimination: as long as these hold,
 * migrating call sites from the globals to the typed sources cannot change
 * behaviour.
 */
class CsConstantsCharacterizationTest extends TestCase
{
    #[DataProvider('itemTypeConstants')]
    public function testItemTypeConstantsKeepOriginalValues(string $constant, string $expected): void
    {
        self::assertTrue(defined($constant), "Constant {$constant} is not defined");
        self::assertSame($expected, constant($constant));
    }

    public static function itemTypeConstants(): array
    {
        return [
            ['CS_ALL', 'all'],
            ['CS_ITEM_TYPE', 'item'],
            ['CS_MATERIAL_TYPE', 'material'],
            ['CS_INSTITUTION_TYPE', 'institution'],
            ['CS_TOPIC_TYPE', 'topic'],
            ['CS_ANNOUNCEMENT_TYPE', 'announcement'],
            ['CS_ANNOTATION_TYPE', 'annotation'],
            ['CS_USER_TYPE', 'user'],
            ['CS_TODO_TYPE', 'todo'],
            ['CS_STEP_TYPE', 'step'],
            ['CS_DATE_TYPE', 'date'],
            ['CS_ENTRY_TYPE', 'entry'],
            ['CS_DISCUSSION_TYPE', 'discussion'],
            ['CS_GROUP_TYPE', 'group'],
            ['CS_SECTION_TYPE', 'section'],
            ['CS_DISCARTICLE_TYPE', 'discarticle'],
            ['CS_TASK_TYPE', 'task'],
            ['CS_BUZZWORD_TYPE', 'buzzword'],
            ['CS_TAG_TYPE', 'tag'],
            ['CS_TAG2TAG_TYPE', 'tag2tag'],
            ['CS_ROOM_TYPE', 'room'],
            ['CS_COMMUNITY_TYPE', 'community'],
            ['CS_PRIVATEROOM_TYPE', 'privateroom'],
            ['CS_GROUPROOM_TYPE', 'grouproom'],
            ['CS_MYROOM_TYPE', 'myroom'],
            ['CS_PROJECT_TYPE', 'project'],
            ['CS_SERVER_TYPE', 'server'],
            ['CS_FILE_TYPE', 'file'],
            ['CS_LABEL_TYPE', 'label'],
            ['CS_LINK_TYPE', 'link'],
            ['CS_LINKITEM_TYPE', 'link_item'],
            ['CS_LINKMODITEM_TYPE', 'link_modifier_item'],
            ['CS_LINKITEMFILE_TYPE', 'link_item_file'],
            ['CS_NOTICED_TYPE', 'noticed'],
            ['CS_TIME_TYPE', 'time'],
            ['CS_ASSESSMENT_TYPE', 'assessments'],
        ];
    }

    public function testTypeConstantsDelegateToTypedSources(): void
    {
        // Rubric types
        self::assertSame(RubricType::Material->value, CS_MATERIAL_TYPE);
        self::assertSame(RubricType::Announcement->value, CS_ANNOUNCEMENT_TYPE);
        self::assertSame(RubricType::Annotation->value, CS_ANNOTATION_TYPE);
        self::assertSame(RubricType::Date->value, CS_DATE_TYPE);
        self::assertSame(RubricType::Discussion->value, CS_DISCUSSION_TYPE);
        self::assertSame(RubricType::Label->value, CS_LABEL_TYPE);
        self::assertSame(RubricType::Todo->value, CS_TODO_TYPE);

        // Room types
        self::assertSame(RoomType::Community->value, CS_COMMUNITY_TYPE);
        self::assertSame(RoomType::PrivateRoom->value, CS_PRIVATEROOM_TYPE);
        self::assertSame(RoomType::GroupRoom->value, CS_GROUPROOM_TYPE);
        self::assertSame(RoomType::Project->value, CS_PROJECT_TYPE);

        // Label subtypes
        self::assertSame(LabelType::Group->value, CS_GROUP_TYPE);
        self::assertSame(LabelType::Topic->value, CS_TOPIC_TYPE);
        self::assertSame(LabelType::Institution->value, CS_INSTITUTION_TYPE);
        self::assertSame(LabelType::Buzzword->value, CS_BUZZWORD_TYPE);

        // Remaining discriminators
        self::assertSame(ItemType::Item->value, CS_ITEM_TYPE);
        self::assertSame(ItemType::User->value, CS_USER_TYPE);
        self::assertSame(ItemType::Step->value, CS_STEP_TYPE);
        self::assertSame(ItemType::Section->value, CS_SECTION_TYPE);
        self::assertSame(ItemType::DiscussionArticle->value, CS_DISCARTICLE_TYPE);
        self::assertSame(ItemType::Task->value, CS_TASK_TYPE);
        self::assertSame(ItemType::File->value, CS_FILE_TYPE);
        self::assertSame(ItemType::Tag->value, CS_TAG_TYPE);
        self::assertSame(ItemType::Tag2Tag->value, CS_TAG2TAG_TYPE);
        self::assertSame(ItemType::Link->value, CS_LINK_TYPE);
        self::assertSame(ItemType::LinkItem->value, CS_LINKITEM_TYPE);
        self::assertSame(ItemType::LinkModifierItem->value, CS_LINKMODITEM_TYPE);
        self::assertSame(ItemType::LinkItemFile->value, CS_LINKITEMFILE_TYPE);
        self::assertSame(ItemType::Noticed->value, CS_NOTICED_TYPE);
        self::assertSame(ItemType::Time->value, CS_TIME_TYPE);
        self::assertSame(ItemType::Entry->value, CS_ENTRY_TYPE);
        self::assertSame(ItemType::Assessment->value, CS_ASSESSMENT_TYPE);
        self::assertSame(ItemType::Room->value, CS_ROOM_TYPE);
        self::assertSame(ItemType::MyRoom->value, CS_MYROOM_TYPE);
        self::assertSame(ItemType::Server->value, CS_SERVER_TYPE);
    }

    public function testCharConstantsKeepOriginalValues(): void
    {
        self::assertSame("\n", LF);
        self::assertSame('<br />', BR);
        self::assertSame("<br />\n", BRLF);
        self::assertSame("\t", TAB);
        self::assertSame('ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞ', UC_CHARS);
        self::assertSame('àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþ', LC_CHARS);
        self::assertSame(UC_CHARS.LC_CHARS.'ß', SPECIAL_CHARS);
        self::assertSame("A-Za-z0-9\?:@&=/;_\.\+!\*'(,%\$~#-", RFC1738_CHARS);
        self::assertSame("A-Za-z0-9!#\$%&'\*\+/=\?\^_`{\|}~-", RFC2822_CHARS);
    }

    public function testCharConstantsDelegateToChars(): void
    {
        self::assertSame(Chars::LF, LF);
        self::assertSame(Chars::BR, BR);
        self::assertSame(Chars::BRLF, BRLF);
        self::assertSame(Chars::TAB, TAB);
        self::assertSame(Chars::UC_CHARS, UC_CHARS);
        self::assertSame(Chars::LC_CHARS, LC_CHARS);
        self::assertSame(Chars::SPECIAL_CHARS, SPECIAL_CHARS);
        self::assertSame(Chars::RFC1738_CHARS, RFC1738_CHARS);
        self::assertSame(Chars::RFC2822_CHARS, RFC2822_CHARS);
    }

    public function testConverterModeConstantsKeepOriginalValues(): void
    {
        self::assertSame(2, AS_HTML_SHORT);
        self::assertSame(4, AS_FORM);
        self::assertSame(5, AS_DB);
        self::assertSame(6, AS_FILE);
        self::assertSame(7, AS_MAIL);
        self::assertSame(8, AS_RSS);
        self::assertSame(10, NONE);
        self::assertSame(12, FROM_DB);
        self::assertSame(13, FROM_FILE);
        self::assertSame(14, FROM_GET);
    }

    public function testConverterModeConstantsDelegateToTextConverterMode(): void
    {
        self::assertSame(TextConverterMode::AS_HTML_SHORT, AS_HTML_SHORT);
        self::assertSame(TextConverterMode::AS_FORM, AS_FORM);
        self::assertSame(TextConverterMode::AS_DB, AS_DB);
        self::assertSame(TextConverterMode::AS_FILE, AS_FILE);
        self::assertSame(TextConverterMode::AS_MAIL, AS_MAIL);
        self::assertSame(TextConverterMode::AS_RSS, AS_RSS);
        self::assertSame(TextConverterMode::NONE, NONE);
        self::assertSame(TextConverterMode::FROM_DB, FROM_DB);
        self::assertSame(TextConverterMode::FROM_FILE, FROM_FILE);
        self::assertSame(TextConverterMode::FROM_GET, FROM_GET);
    }

    public function testListIntervalKeepsOriginalValue(): void
    {
        self::assertSame(20, CS_LIST_INTERVAL);
    }
}
