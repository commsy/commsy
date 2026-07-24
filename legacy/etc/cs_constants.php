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

/*
 * Transitional compatibility layer.
 *
 * The values below are owned by typed sources under src/ and are only mirrored
 * into these legacy globals so that not-yet-migrated call sites keep working.
 * As call sites move to the typed sources this file shrinks and is eventually
 * removed. Do NOT add new constants here.
 *
 *   item types        → App\Item\ItemType
 *   rubric types      → App\Rubric\RubricType
 *   room types        → App\Room\RoomType
 *   label subtypes    → App\Rubric\Label\LabelType
 *   characters        → App\Legacy\Chars
 *   text conv. modes  → App\Legacy\TextConverterMode
 */

// home rubric list limit
define('CS_LIST_INTERVAL', 20);

// Version filter sentinel ("all versions"); no code references it any more.
define('CS_ALL', 'all');

define('CS_ITEM_TYPE', \App\Item\ItemType::Item->value);

define('CS_MATERIAL_TYPE', \App\Rubric\RubricType::Material->value);
define('CS_INSTITUTION_TYPE', \App\Rubric\Label\LabelType::Institution->value);
define('CS_TOPIC_TYPE', \App\Rubric\Label\LabelType::Topic->value);
define('CS_ANNOUNCEMENT_TYPE', \App\Rubric\RubricType::Announcement->value);
define('CS_ANNOTATION_TYPE', \App\Rubric\RubricType::Annotation->value);
define('CS_USER_TYPE', \App\Item\ItemType::User->value);
define('CS_TODO_TYPE', \App\Rubric\RubricType::Todo->value);
define('CS_STEP_TYPE', \App\Item\ItemType::Step->value);
define('CS_DATE_TYPE', \App\Rubric\RubricType::Date->value);
define('CS_ENTRY_TYPE', \App\Item\ItemType::Entry->value);
define('CS_DISCUSSION_TYPE', \App\Rubric\RubricType::Discussion->value);
define('CS_GROUP_TYPE', \App\Rubric\Label\LabelType::Group->value);
define('CS_SECTION_TYPE', \App\Item\ItemType::Section->value);
define('CS_DISCARTICLE_TYPE', \App\Item\ItemType::DiscussionArticle->value);
define('CS_TASK_TYPE', \App\Item\ItemType::Task->value);
define('CS_BUZZWORD_TYPE', \App\Rubric\Label\LabelType::Buzzword->value);
define('CS_TAG_TYPE', \App\Item\ItemType::Tag->value);
define('CS_TAG2TAG_TYPE', \App\Item\ItemType::Tag2Tag->value);

define('CS_ROOM_TYPE', \App\Item\ItemType::Room->value);
define('CS_COMMUNITY_TYPE', \App\Room\RoomType::Community->value);
define('CS_PRIVATEROOM_TYPE', \App\Room\RoomType::PrivateRoom->value);
define('CS_GROUPROOM_TYPE', \App\Room\RoomType::GroupRoom->value);
define('CS_MYROOM_TYPE', \App\Item\ItemType::MyRoom->value);
define('CS_PROJECT_TYPE', \App\Room\RoomType::Project->value);
define('CS_SERVER_TYPE', \App\Item\ItemType::Server->value);
// NOTE: for room type 'userroom', use const `cs_userroom_item::ROOM_TYPE_USER`

define('CS_FILE_TYPE', \App\Item\ItemType::File->value);
define('CS_LABEL_TYPE', \App\Rubric\RubricType::Label->value);
define('CS_LINK_TYPE', \App\Item\ItemType::Link->value);
define('CS_LINKITEM_TYPE', \App\Item\ItemType::LinkItem->value);
define('CS_LINKMODITEM_TYPE', \App\Item\ItemType::LinkModifierItem->value);
define('CS_LINKITEMFILE_TYPE', \App\Item\ItemType::LinkItemFile->value);
define('CS_NOTICED_TYPE', \App\Item\ItemType::Noticed->value);

define('CS_TIME_TYPE', \App\Item\ItemType::Time->value);

define('CS_ASSESSMENT_TYPE', \App\Item\ItemType::Assessment->value);

define('LF', \App\Legacy\Chars::LF);       // line feed
define('BR', \App\Legacy\Chars::BR);   // line feed
define('BRLF', \App\Legacy\Chars::BRLF); // line feed
define('TAB', \App\Legacy\Chars::TAB);       // tab

define('UC_CHARS', \App\Legacy\Chars::UC_CHARS);
define('LC_CHARS', \App\Legacy\Chars::LC_CHARS);
define('SPECIAL_CHARS', \App\Legacy\Chars::SPECIAL_CHARS);
define('RFC1738_CHARS', \App\Legacy\Chars::RFC1738_CHARS);
define('RFC2822_CHARS', \App\Legacy\Chars::RFC2822_CHARS);

// text functions
define('AS_HTML_SHORT', \App\Legacy\TextConverterMode::AS_HTML_SHORT);
define('AS_FORM', \App\Legacy\TextConverterMode::AS_FORM);
define('AS_DB', \App\Legacy\TextConverterMode::AS_DB);
define('AS_FILE', \App\Legacy\TextConverterMode::AS_FILE);
define('AS_MAIL', \App\Legacy\TextConverterMode::AS_MAIL);
define('AS_RSS', \App\Legacy\TextConverterMode::AS_RSS);
define('NONE', \App\Legacy\TextConverterMode::NONE);
define('FROM_DB', \App\Legacy\TextConverterMode::FROM_DB);
define('FROM_FILE', \App\Legacy\TextConverterMode::FROM_FILE);
define('FROM_GET', \App\Legacy\TextConverterMode::FROM_GET);
