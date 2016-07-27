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

$class_name = 'cs_form';
define('FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_rubric_form';
define('RUBRIC_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_user_form';
define('USER_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

/************UNSWITCHED******************/

$class_name = 'cs_profile_form';
define('PROFILE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

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

$class_name = 'cs_configuration_mediaintegration_form';
define('CONFIGURATION_MEDIAINTEGRATION_FORM',$class_name);
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

$class_name = 'cs_configuration_connection_form';
define('CONFIGURATION_CONNECTION_FORM',$class_name);
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

$class_name = 'cs_configuration_portal_upload_form';
define('CONFIGURATION_PORTALUPLOAD_FORM',$class_name);
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

$class_name = 'cs_configuration_limesurvey_form';
define('CONFIGURATION_LIMESURVEY_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_wordpress_form';
define('CONFIGURATION_WORDPRESS_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_privateroom_home_form';
define('CONFIGURATION_PRIVATEROOM_HOME_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_autoaccounts_form';
define('CONFIGURATION_AUTOACCOUNTS_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_autoaccounts_selection_form';
define('CONFIGURATION_AUTOACCOUNTS_SELECTION_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_workflow_form';
define('CONFIGURATION_WORKFLOW_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_account_action_form';
define('ACCOUNT_ACTION_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_account_change_form';
define('ACCOUNT_CHANGE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_account_forget_form';
define('ACCOUNT_FORGET_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_account_merge_form';
define('ACCOUNT_MERGE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_account_password_form';
define('ACCOUNT_PASSWORD_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_account_password_admin_form';
define('ACCOUNT_PASSWORD_ADMIN_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_account_status_form';
define('ACCOUNT_STATUS_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_account_assignroom_form';
define('ACCOUNT_ASSIGNROOM_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

######################

$class_name = 'cs_become_member_form';
define('BECOME_MEMBER_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_annotation_form';
define('ANNOTATION_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_date_import_selection_form';
define('DATE_IMPORT_SELECTION_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_date_import_form';
define('DATE_IMPORT_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_discussion_close_form';
define('DISCUSSION_CLOSE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_group_form';
define('GROUP_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_group_mail_form';
define('GROUP_MAIL_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_home_member_form';
define('HOME_MEMBER_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_home_member2_form';
define('HOME_MEMBER2_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_institution_form';
define('INSTITUTION_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_institution_mail_form';
define('INSTITUTION_MAIL_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_internal_color_form';
define('INTERNAL_COLOR_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_language_unused_form';
define('LANGUAGE_UNUSED_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_link_item_form';
define('LINK_ITEM_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_mail_process_form';
define('MAIL_PROCESS_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_mail_to_moderator_form';
define('MAIL_TO_MODERATOR_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_password_change_form';
define('PASSWORD_CHANGE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_password_forget_form';
define('PASSWORD_FORGET_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_section_form';
define('SECTION_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_server_initialize_form';
define('SERVER_INITIALIZE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_topic_mail_form';
define('TOPIC_MAIL_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_user_close_form';
define('USER_CLOSE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_user_preferences_form';
define('USER_PREFERENCES_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_agb_form';
define('AGB_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_plugins_form';
define('CONFIGURATION_PLUGINS_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_update_form';
define('CONFIGURATION_UPDATE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_announcement_form';
define('ANNOUNCEMENT_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_discussion_form';
define('DISCUSSION_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_discarticle_form';
define('DISCARTICLE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_material_form';
define('MATERIAL_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_material_ims_import_form';
define('MATERIAL_IMS_IMPORT_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_date_form';
define('DATE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_todo_form';
define('TODO_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_search_short_form';
define('SEARCH_SHORT_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_topic_form';
define('TOPIC_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_step_form';
define('STEP_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_rubric_mail_form';
define('RUBRIC_MAIL_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_tag_form';
define('TAG_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_buzzwords_form';
define('BUZZWORDS_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_account_options_form';
define('CONFIGURATION_ACCOUNT_OPTIONS_FORM',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_room_options_form';
define('CONFIGURATION_ROOM_OPTIONS_FORM',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_rubric_options_form';
define('CONFIGURATION_RUBRIC_OPTIONS_FORM',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_structure_options_form';
define('CONFIGURATION_STRUCTURE_OPTIONS_FORM',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_template_options_form';
define('CONFIGURATION_TEMPLATE_OPTIONS_FORM',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_rubric_extras_form';
define('CONFIGURATION_RUBRIC_EXTRAS_FORM',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_datasecurity_form';
define('CONFIGURATION_DATASECURITY_FORM',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_inactive_form';
define('CONFIGURATION_INACTIVE_FORM',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_export_import_form';
define('CONFIGURATION_EXPORT_IMPORT_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;
?>
