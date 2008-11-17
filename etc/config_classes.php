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

// definitions and initialisiation
$main_folder = 'classes/';
$view_folder = 'views/';
$class_config = array();

// views
$class_name = 'cs_account_index_view';
define('ACCOUNT_INDEX_VIEW',$class_name);
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

$class_name = 'cs_announcement_detail_view';
define('ANNOUNCEMENT_DETAIL_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_announcement_index_view';
define('ANNOUNCEMENT_INDEX_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_announcement_short_view';
define('ANNOUNCEMENT_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_guide_community_announcement_view';
define('ANNOUNCEMENT_SHORT_COMMUNITY_GUIDE_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_campus_index_view';
define('CAMPUS_INDEX_VIEW',$class_name);
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

$class_name = 'cs_color_configuration_form_view';
define('CONFIGURATION_COLOR_FORM_VIEW',$class_name);
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

$class_name = 'cs_context_guide_detail_view';
define('CONTEXT_GUIDE_DETAIL_VIEW',$class_name);
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

$class_name = 'cs_context_short_view';
define('CONTEXT_SHORT_VIEW',$class_name);
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

$class_name = 'cs_date_detail_view';
define('DATE_DETAIL_VIEW',$class_name);
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

$class_name = 'cs_date_short_view';
define('DATE_SHORT_VIEW',$class_name);
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

$class_name = 'cs_discussion_index_view';
define('DISCUSSION_INDEX_VIEW',$class_name);
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

$class_name = 'cs_fun_weather_view';
define('FUN_WEATHER_VIEW',$class_name);
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

$class_name = 'cs_group_index_view';
define('GROUP_INDEX_VIEW',$class_name);
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

$class_name = 'cs_home_action_view';
define('HOME_ACTION_VIEW',$class_name);
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

$class_name = 'cs_home_tag_view';
define('HOME_TAG_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_home_title_view';
define('HOME_TITLE_VIEW',$class_name);
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

$class_name = 'cs_institution_detail_view';
define('INSTITUTION_DETAIL_VIEW',$class_name);
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

$class_name = 'cs_institution_short_view';
define('INSTITUTION_SHORT_VIEW',$class_name);
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

$class_name = 'cs_material_short_view';
define('MATERIAL_SHORT_VIEW',$class_name);
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

$class_name = 'cs_myroom_index_view';
define('MYROOM_INDEX_VIEW',$class_name);
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

$class_name = 'cs_project_index_view';
define('PROJECT_INDEX_VIEW',$class_name);
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

$class_name = 'cs_search_short_view';
define('SEARCH_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_todo_short_view';
define('TODO_SHORT_VIEW',$class_name);
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

$class_name = 'cs_user_short_view';
define('USER_SHORT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;
?>