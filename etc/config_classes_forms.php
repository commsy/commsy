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

// forms
$form_folder = 'forms/';

$class_name = 'cs_rubric_form';
define('RUBRIC_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_announcement_form';
define('ANNOUNCEMENT_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_discussion_form';
define('DISCUSSION_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_discarticle_form';
define('DISCARTICLE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_material_form';
define('MATERIAL_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_date_form';
define('DATE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_search_short_form';
define('SEARCH_SHORT_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_configuration_agb_form';
define('CONFIGURATION_AGB_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_archive_form';
define('CONFIGURATION_ARCHIVE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_authentication_form';
define('CONFIGURATION_AUTHENTICATION_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_backup_form';
define('CONFIGURATION_BACKUP_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_chat_form';
define('CONFIGURATION_CHAT_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_common_form';
define('CONFIGURATION_COMMON_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_date_form';
define('CONFIGURATION_DATE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_default_form';
define('CONFIGURATION_DEFAULT_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_discussion_form';
define('CONFIGURATION_DISCUSSION_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_export_form';
define('CONFIGURATION_EXPORT_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_extra_form';
define('CONFIGURATION_EXTRA_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_grouproom_form';
define('CONFIGURATION_GROUPROOM_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_home_form';
define('CONFIGURATION_HOME_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_homepage_form';
define('CONFIGURATION_HOMEPAGE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_htmltextarea_form';
define('CONFIGURATION_HTMLTEXTAREA_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_ims_form';
define('CONFIGURATION_IMS_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_informationbox_form';
define('CONFIGURATION_INFORMATIONBOX_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_language_form';
define('CONFIGURATION_LANGUAGE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_listview_form';
define('CONFIGURATION_LISTVIEW_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_mail_form';
define('CONFIGURATION_MAIL_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_move_form';
define('CONFIGURATION_MOVE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_news_form';
define('CONFIGURATION_NEWS_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_outofservice_form';
define('CONFIGURATION_OUTOFSERVICE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_path_form';
define('CONFIGURATION_PATH_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_portal_home_form';
define('CONFIGURATION_PORTALHOME_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_preferences_form';
define('CONFIGURATION_PREFERENCES_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_privateroom_newsletter_form';
define('CONFIGURATION_PRIVATEROOM_NEWSLETTER_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_room_opening_form';
define('CONFIGURATION_ROOM_OPENING_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_rubric_form';
define('CONFIGURATION_RUBRIC_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_scribd_form';
define('CONFIGURATION_SCRIBD_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_service_form';
define('CONFIGURATION_SERVICE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_tag_form';
define('CONFIGURATION_TAG_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_time_form';
define('CONFIGURATION_TIME_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_usageinfo_form';
define('CONFIGURATION_USAGEINFO_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_wiki_form';
define('CONFIGURATION_WIKI_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

######################

$class_name = 'cs_form';
define('FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;


?>