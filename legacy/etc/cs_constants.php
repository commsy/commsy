<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.


// home rubric list limit

define('CS_LIST_INTERVAL',20);

define('CS_NO_ROOM',0);
define('CS_ROOM_OPEN',1);
define('CS_ROOM_CLOSED',2);
define('CS_ROOM_LOCK',3);
define('CS_ALL','all');

define('CS_ITEM_TYPE','item');

define('CS_MATERIAL_TYPE','material');
define('CS_INSTITUTION_TYPE','institution');
define('CS_TOPIC_TYPE','topic');
define('CS_ANNOUNCEMENT_TYPE','announcement');
define('CS_ANNOTATION_TYPE','annotation');
define('CS_USER_TYPE','user');
define('CS_TODO_TYPE','todo');
define('CS_STEP_TYPE','step');
define('CS_DATE_TYPE','date');
define('CS_ENTRY_TYPE','entry');
define('CS_DISCUSSION_TYPE','discussion');
define('CS_GROUP_TYPE','group');
define('CS_SECTION_TYPE','section');
define('CS_DISCARTICLE_TYPE','discarticle');
define('CS_TASK_TYPE','task');
define('CS_BUZZWORD_TYPE','buzzword');
define('CS_TAG_TYPE','tag');
define('CS_TAG2TAG_TYPE','tag2tag');
define('CS_PORTFOLIO_TYPE','portfolio');

define('CS_ROOM_TYPE','room');
define('CS_COMMUNITY_TYPE','community');
define('CS_PRIVATEROOM_TYPE','privateroom');
define('CS_GROUPROOM_TYPE','grouproom');
define('CS_MYROOM_TYPE','myroom');
define('CS_PROJECT_TYPE','project');
define('CS_PORTAL_TYPE','portal');
define('CS_SERVER_TYPE','server');
// NOTE: for room type 'userroom', use const `cs_userroom_item::ROOM_TYPE_USER`

define('CS_FILE_TYPE','file');
define('CS_LABEL_TYPE','label');
define('CS_LINK_TYPE','link');
define('CS_LINKITEM_TYPE','link_item');
define('CS_LINKMODITEM_TYPE','link_modifier_item');
define('CS_LINKITEMFILE_TYPE','link_item_file');
define('CS_READER_TYPE','reader');
define('CS_NOTICED_TYPE','noticed');

define('CS_TIME_TYPE','time');

define('CS_ASSESSMENT_TYPE', 'assessments');

define('LF'  , "\n");       // line feed
define('BR'  , "<br />");   // line feed
define('BRLF', "<br />\n"); // line feed
define('TAB' , "\t");       // tab

define ("UC_CHARS", "ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞ"); // If you need more, add
define ("LC_CHARS", "àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþ"); // If you need more, add

//All Special chars
define ("SPECIAL_CHARS",UC_CHARS.LC_CHARS."ß");

//Chars allowed  in URLs by RFC 1738. Use in character class definition: '['.RFC1738_CHARS.']'
//IMPORTANT: for preg-functions: use "§" as delimiters for search-expression!
//
// the ")" character belongs to the allowed cahracters too, but there are problems with things like "(www.test.de)." where both ")" and "." are punctuation marks,
//but both could be also parts of the url. For the few occurances of ")" in urls, we decided to solve this problem by not allowing ")" in urls
//define ("RFC1738_CHARS","A-Za-z0-9?:@&=/;_.+!*'(,%$~#-");
define ("RFC1738_CHARS","A-Za-z0-9\?:@&=/;_\.\+!\*'(,%\$~#-");

//Chars allowed  in Email-adressess by RFC 2822. Use in character class definition: '['.RFC2822_CHARS.']'
//IMPORTANT: for preg-functions: use "§" as delimiters for search-expression!
//define("RFC2822_CHARS","A-Za-z0-9!#$%&'*+/=?^_`{|}~-");
define("RFC2822_CHARS","A-Za-z0-9!#\$%&'\*\+/=\?\^_`{\|}~-");

// text functions
define ("AS_HTML_LONG", 1);
define ("AS_HTML_SHORT", 2);
define ("AS_FORM", 4);
define ("AS_DB", 5);
define ("AS_FILE", 6);
define ("AS_MAIL", 7);
define ("AS_RSS", 8);
define ("NONE", 10);
define ("FROM_FORM", 11);
define ("FROM_DB", 12);
define ("FROM_FILE", 13);
define ("FROM_GET", 14);
define ("HELP_AS_HTML_LONG", 21);
