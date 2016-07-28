<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2008 Iver Jackewitz
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

// views
$view_folder = 'views/';

// switchable

$class_name = 'cs_page_view';
define('PAGE_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_index_view';
define('INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_user_index_view';
define('USER_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_detail_view';
define('DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_user_detail_view';
define('USER_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_form_view';
define('FORM_VIEW',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_configuration_form_view';
define('CONFIGURATION_FORM_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_link_preference_list_view';
define('LINK_PREFERENCE_LIST_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_account_index_view';
define('ACCOUNT_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = true;


// unswitchable

$class_name = 'cs_page_room_view';
define('PAGE_ROOM_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;


/*************HOME**************/

$class_name = 'cs_home_view';
define('HOME_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_activity_view';
define('ACTIVITY_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_home_usageinfo_view';
define('HOME_USAGEINFO_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_home_buzzword_view';
define('HOME_BUZZWORD_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_buzzword_view';
define('PRIVATEROOM_HOME_BUZZWORD_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_configuration_view';
define('PRIVATEROOM_HOME_CONFIGURATION_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_clock_view';
define('PRIVATEROOM_HOME_CLOCK_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_rss_ticker_view';
define('PRIVATEROOM_HOME_RSS_TICKER_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_dokuverser_view';
define('PRIVATEROOM_HOME_DOKUVERSER_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_twitter_view';
define('PRIVATEROOM_HOME_TWITTER_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_youtube_view';
define('PRIVATEROOM_HOME_YOUTUBE_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_flickr_view';
define('PRIVATEROOM_HOME_FLICKR_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_room_view';
define('PRIVATEROOM_HOME_ROOM_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_portlet_view';
define('PRIVATEROOM_HOME_PORTLET_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_new_entries_view';
define('PRIVATEROOM_HOME_NEW_ENTRIES_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_news_view';
define('PRIVATEROOM_HOME_NEWS_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_weather_view';
define('PRIVATEROOM_HOME_WEATHER_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_search_view';
define('PRIVATEROOM_HOME_SEARCH_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_roomwide_search_view';
define('PRIVATEROOM_HOME_ROOMWIDE_SEARCH_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_home_tag_view';
define('HOME_TAG_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_new_item_view';
define('PRIVATEROOM_HOME_NEW_ITEM_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_note_view';
define('PRIVATEROOM_HOME_NOTE_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_released_entries_view';
define('PRIVATEROOM_HOME_RELEASED_ENTRIES_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_privateroom_home_tag_view';
define('PRIVATEROOM_HOME_TAG_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

/*************INDEX**************/

$class_name = 'cs_announcement_index_view';
define('ANNOUNCEMENT_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_entry_index_view';
define('ENTRY_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_material_index_view';
define('MATERIAL_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_discussion_index_view';
define('DISCUSSION_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_date_index_view';
define('DATE_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_date_calendar_index_view';
define('DATE_CALENDAR_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_todo_index_view';
define('TODO_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_topic_index_view';
define('TOPIC_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_group_index_view';
define('GROUP_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_copy_index_view';
define('COPY_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_buzzword_index_view';
define('BUZZWORD_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_tag_index_view';
define('TAG_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_myroom_index_view';
define('MYROOM_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_institution_index_view';
define('INSTITUTION_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_project_index_view';
define('PROJECT_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_context_index_view';
define('CONTEXT_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;


/*************HOME**************/

$class_name = 'cs_todo_short_view';
define('TODO_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_announcement_short_view';
define('ANNOUNCEMENT_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_material_short_view';
define('MATERIAL_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_topic_short_view';
define('TOPIC_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_date_short_view';
define('DATE_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_institution_short_view';
define('INSTITUTION_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_group_short_view';
define('GROUP_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_discussion_short_view';
define('DISCUSSION_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_project_short_view';
define('PROJECT_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_user_short_view';
define('USER_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;


/*************DETAIL**************/
$class_name = 'cs_announcement_detail_view';
define('ANNOUNCEMENT_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_material_detail_view';
define('MATERIAL_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_discussion_detail_view';
define('DISCUSSION_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_date_detail_view';
define('DATE_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_todo_detail_view';
define('TODO_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_topic_detail_view';
define('TOPIC_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_group_detail_view';
define('GROUP_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_myroom_detail_view';
define('MYROOM_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_context_detail_view';
define('CONTEXT_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_institution_detail_view';
define('INSTITUTION_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;


/**************FORM********************/
$class_name = 'cs_profile_form_view';
define('PROFILE_FORM_VIEW',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

/**************CONFIG********************/
$class_name = 'cs_configuration_room_options_form_view';
define('CONFIGURATION_ROOM_OPTIONS_FORM_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_material_admin_index_view';
define('MATERIAL_ADMIN_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_material_admin_detail_view';
define('MATERIAL_ADMIN_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;


/**************MISC**********************/
$class_name = 'cs_home_title_view';
define('HOME_TITLE_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_item_index_view';
define('ITEM_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_item_attach_index_view';
define('ITEM_ATTACH_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_private_room_detailed_short_view';
define('PRIVATEROOM_DETAILED_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_mail_view';
define('MAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_material_version_detail_view';
define('MATERIAL_VERSION_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;



/**************UNSWITCHED***************/
$class_name = 'cs_guide_community_announcement_view';
define('ANNOUNCEMENT_SHORT_COMMUNITY_GUIDE_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_community_detail_view';
define('COMMUNITY_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_community_index_view';
define('COMMUNITY_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_context_guide_detail_view';
define('CONTEXT_GUIDE_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_context_short_view';
define('CONTEXT_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_errorbox_view';
define('ERRORBOX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_overlaybox_view';
define('OVERLAYBOX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_fun_weather_view';
define('FUN_WEATHER_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_helpbox_view';
define('HELPBOX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_home_action_view';
define('HOME_ACTION_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_home_configuration_view';
define('HOME_CONFIGURATION_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_home_extension_view';
define('HOME_EXTENSION_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_home_informationbox_view';
define('HOME_INFORMATIONBOX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_item_short_view';
define('ITEM_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_guide_list_view';
define('LIST_GUIDE_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_list_view_plain';
define('LIST_PLAIN_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_page_guide_view';
define('PAGE_GUIDE_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_page_print_view';
define('PAGE_PRINT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_project_detail_view';
define('PROJECT_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_room_index_view';
define('ROOM_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_search_short_view';
define('SEARCH_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_statistic_view';
define('STATISTIC_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_table_view';
define('TABLE_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_text_view';
define('TEXT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_outofservice_view';
define('OUTOFSERVICE_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_view';
define('VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_wiki_view';
define('WIKI_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_wordpress_view';
define('WORDPRESS_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_home_form_view';
define('CONFIGURATION_HOME_FORM_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_rubric_form_view';
define('CONFIGURATION_RUBRIC_FORM_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_form_view_left';
define('FORM_LEFT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_form_view_plain';
define('FORM_PLAIN_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_form_view_detail';
define('FORM_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_form_view_overlay';
define('FORM_OVERLAY_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_language_form_view';
define('LANGUAGE_FORM_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_plugin_view';
define('PLUGIN_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;
$class_name = 'cs_plugin_view';

$class_name = 'cs_update_view';
define('UPDATE_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_datasecurity_form_view';
define('CONFIGURATION_DATASECURITY_FORM_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

?>