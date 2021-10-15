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

$interval = 20;
/*
include('cs_config.php');
global $cs_max_list_length;
global $cs_max_search_list_length;
if (isset($cs_max_list_length) and !empty($cs_max_list_length)){
	define('CS_HOME_RUBRIC_LIST_LIMIT', $cs_max_list_length);
	define('CS_LIST_INTERVAL',$cs_max_list_length);

}else{*/
	define('CS_HOME_RUBRIC_LIST_LIMIT', 50);
	define('CS_LIST_INTERVAL',20);/*
}
*/

define('CS_YES',1);
define('CS_NO',-1);

$no_room = 0;
define('CS_NO_ROOM',0);
define('CS_ROOM_OPEN',1);
define('CS_ROOM_CLOSED',2);
define('CS_ROOM_LOCK',3);
$text='';
for($i=0;$i<40;$i++){
   $text.='&nbsp;';
}
define('CS_WITDH_CONSTANT',$text);
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
define('CS_ITEM_BACKUP','item_backup');
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
define('CS_AUTH_SOURCE_TYPE','auth_source');

define('CS_TIME_TYPE','time');

define('CS_LOG_TYPE','log');
define('CS_LOGARCHIVE_TYPE','log_archive');
define('CS_LOG_ERROR_TYPE','log_error');

define('CS_WIKI_TYPE','wiki');

define('CS_ASSESSMENT_TYPE', 'assessments');

define('CS_PLUGIN_TYPE','plugin');

define('CS_ITEMBACKUP_TYPE','item_backup');

define('CS_GRADIENT_24','cs_gradient_24.png');
define('CS_GRADIENT_24_FOCUS','cs_gradient_24_focus.png');
define('CS_GRADIENT_32','cs_gradient_32.png');

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


// IIS - microsoft compatibility
if ( empty( $_SERVER['REQUEST_URI'] ) ) {

    // IIS Mod-Rewrite
    if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
        $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
    }
    // IIS Isapi_Rewrite
    else if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
        $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
    }
    else
    {
        // Some IIS + PHP configurations puts the script-name in the path-info (No need to append it twice)
        if ( isset($_SERVER['PATH_INFO']) ) {
            if ( $_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME'] )
                $_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
            else
                $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
        }

        // Append the query string if it exists and isn't null
        $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'],0);

        if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
            $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
        }
    }
}

// new constants for CommSy8
define('CS_OPTION_SAVE', 'save');
define('CS_OPTION_CHANGE', 'change');
define('CS_OPTION_NEW', 'new');
define('CS_OPTION_CANCEL', 'cancel');
define('CS_OPTION_DELETE', 'delete');
define('CS_OPTION_JOIN', 'join');

define('CS_LISTOPTION_NONE','listoption_none');
define('CS_LISTOPTION_COPY', 'listoption_copy');
define('CS_LISTOPTION_DOWNLOAD', 'listoption_download');
define('CS_LISTOPTION_MARK_AS_READ', 'listoption_mark_as_read');
define('CS_LISTOPTION_DELETE', 'listoption_delete');
define('CS_LISTOPTION_EMAIL_SEND', 'listoption_email_send');
define('CS_LISTOPTION_TODO_DONE', 'listoption_todo_done');
define('CS_LISTOPTION_TODO_IN_PROGRESS', 'listoption_todo_in_progress');
define('CS_LISTOPTION_TODO_NOT_STARTED', 'listoption_todo_not_started');

define('CS_LISTOPTION_CONFIRM_DELETE', 'listoption_confirm_delete');
define('CS_LISTOPTION_CONFIRM_CANCEL', 'listoption_confirm_cancel');
?>