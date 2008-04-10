<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

include_once('classes/cs_helpbox_view.php');
include_once('functions/language_functions.php');

$textbox = new cs_helpbox_view($environment,true);

$context_item = $environment->getCurrentContextItem();

$module = (isset($_GET['module'])) ? cs_strtoupper($_GET['module']) : '';
$function = (isset($_GET['function'])) ? cs_strtoupper($_GET['function']) : '';
$function .= (($module=='CAMPUS_SEARCH') and isset($_GET['parameter']) and (substr($_GET['parameter'], 0, 6)=='modus=')) ? '_'.cs_strtoupper(substr($_GET['parameter'], 6)) : '';

// called from context-help-page
if (isset($_GET['context'])){
   $params = array();
   $params['module'] = $_GET['module'];
   $params['function'] = $_GET['function'];
   $textbox->addAction(getMessage('COMMON_BACK'),'',$current_module,$current_function, $params);

   switch ( $_GET['context'] ){
      case 'HELP_COMMON_CLIPBOARD':
         $textbox->setTitle(getMessage('HELP_COMMON_CLIPBOARD_TITLE'),NONE);
         $textbox->addRow(getMessage('HELP_COMMON_CLIPBOARD'));
         break;
      case 'HELP_COMMON_EDIT':
         $textbox->setTitle(getMessage('HELP_COMMON_EDIT_TITLE'),NONE);
         $textbox->addRow(getMessage('HELP_COMMON_EDIT'));
         break;
      case 'HELP_COMMON_FORMAT':
         $textbox->setTitle(getMessage('HELP_COMMON_FORMAT_TITLE'),NONE);
         $textbox->addRow(getMessage('HELP_COMMON_FORMAT'));
         break;
      case 'HELP_COMMON_LINK':
         $textbox->setTitle(getMessage('HELP_COMMON_LINK_TITLE'),NONE);
         $textbox->addRow(getMessage('HELP_COMMON_LINK'));
         break;
      case 'HELP_COMMON_NAVIGATE':
         $textbox->setTitle(getMessage('HELP_COMMON_NAVIGATE_TITLE'),NONE);
         $textbox->addRow(getMessage('HELP_COMMON_NAVIGATE'));
         break;
      case 'HELP_COMMON_PASSWD':
         $textbox->setTitle(getMessage('HELP_COMMON_PASSWD_TITLE'),NONE);
         $textbox->addRow(getMessage('HELP_COMMON_PASSWD'));
         break;
      case 'HELP_COMMON_REGISTER':
         $textbox->setTitle(getMessage('HELP_COMMON_REGISTER_TITLE'),NONE);
         $textbox->addRow(getMessage('HELP_COMMON_REGISTER'));
         break;
      case 'HELP_COMMON_RIGHTS':
         $textbox->setTitle(getMessage('HELP_COMMON_RIGHTS_TITLE'),NONE);
         $textbox->addRow(getMessage('HELP_COMMON_RIGHTS'));
         break;
      case 'HELP_COMMON_SETUP':
         $textbox->setTitle(getMessage('HELP_COMMON_SETUP_TITLE'),NONE);
         $textbox->addRow(getMessage('HELP_COMMON_SETUP'));
         break;
      case 'HELP_COMMON_STRUCTURE':
         $textbox->setTitle(getMessage('HELP_COMMON_STRUCTURE_TITLE'),NONE);
         $textbox->addRow(getMessage('HELP_COMMON_STRUCTURE'));
         break;
      default:
         $textbox->setTitle( getMessage('COMMON_MESSAGETAG_ERROR'.' help_context(95) ') );
         $textbox->addRow(getMessage('COMMON_MESSAGETAG_ERROR'.' help_context(95) ') );
         break;
   }

// called from CommSy-Page
} else {
   // Hints for testing: ---> click on the question mark at the right upper corner on any page <---
   switch ( $module.'_'.$function )
   {
      case 'ACCOUNT_DETAIL':
         $MsgID           = 'HELP_ACCOUNT_DETAIL';
         $help_title      = 'HELP_ACCOUNT_DETAIL_TITLE';
         $translatedMsg   = getMessage('HELP_ACCOUNT_DETAIL');
         $translatedTitle = getMessage('HELP_ACCOUNT_DETAIL_TITLE');
         $translatedLinks = getMessage('HELP_ACCOUNT_DETAIL_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_ACCOUNT_DETAIL_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_ACCOUNT');
            $help_function = getMessage('HELP_FUNCTION_DETAIL');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'ACCOUNT_EDIT':
         $MsgID           = 'HELP_ACCOUNT_EDIT';
         $help_title      = 'HELP_ACCOUNT_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_ACCOUNT_EDIT');
         $translatedTitle = getMessage('HELP_ACCOUNT_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_ACCOUNT_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_ACCOUNT_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_ACCOUNT');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'ACCOUNT_INDEX':
         $MsgID           = 'HELP_ACCOUNT_INDEX';
         $help_title      = 'HELP_ACCOUNT_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_ACCOUNT_INDEX');
         $translatedTitle = getMessage('HELP_ACCOUNT_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_ACCOUNT_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_ACCOUNT_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_ACCOUNT');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'ACCOUNT_PREFERENCES':
         $MsgID           = 'HELP_ACCOUNT_PREFERENCES';
         $help_title      = 'HELP_ACCOUNT_PREFERENCES_TITLE';
         $translatedMsg   = getMessage('HELP_ACCOUNT_PREFERENCES');
         $translatedTitle = getMessage('HELP_ACCOUNT_PREFERENCES_TITLE');
         $translatedLinks = getMessage('HELP_ACCOUNT_PREFERENCES_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_ACCOUNT_PREFERENCES_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_ACCOUNT');
            $help_function = getMessage('HELP_FUNCTION_PREFERENCES');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'ACCOUNT_STATUS':
         $MsgID           = 'HELP_ACCOUNT_STATUS';
         $help_title      = 'HELP_ACCOUNT_STATUS_TITLE';
         $translatedMsg   = getMessage('HELP_ACCOUNT_STATUS');
         $translatedTitle = getMessage('HELP_ACCOUNT_STATUS_TITLE');
         $translatedLinks = getMessage('HELP_ACCOUNT_STATUS_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_ACCOUNT_STATUS_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_ACCOUNT');
            $help_function = getMessage('HELP_FUNCTION_STATUS');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'ANNOTATION_EDIT':
         $MsgID           = 'HELP_ANNOTATION_EDIT';
         $help_title      = 'HELP_ANNOTATION_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_ANNOTATION_EDIT');
         $translatedTitle = getMessage('HELP_ANNOTATION_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_ANNOTATION_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_ANNOTATION_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_ANNOTATION');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'ANNOUNCEMENT_DETAIL':
         $MsgID           = 'HELP_ANNOUNCEMENT_DETAIL';
         $help_title      = 'HELP_ANNOUNCEMENT_DETAIL_TITLE';
         $translatedMsg   = getMessage('HELP_ANNOUNCEMENT_DETAIL');
         $translatedTitle = getMessage('HELP_ANNOUNCEMENT_DETAIL_TITLE');
         $translatedLinks = getMessage('HELP_ANNOUNCEMENT_DETAIL_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_ANNOUNCEMENT_DETAIL_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_ANNOUNCEMENT');
            $help_function = getMessage('HELP_FUNCTION_DETAIL');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'ANNOUNCEMENT_EDIT':
         $MsgID           = 'HELP_ANNOUNCEMENT_EDIT';
         $help_title      = 'HELP_ANNOUNCEMENT_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_ANNOUNCEMENT_EDIT');
         $translatedTitle = getMessage('HELP_ANNOUNCEMENT_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_ANNOUNCEMENT_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_ANNOUNCEMENT_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_ANNOUNCEMENT');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'ANNOUNCEMENT_INDEX':
         $MsgID           = 'HELP_ANNOUNCEMENT_INDEX';
         $help_title      = 'HELP_ANNOUNCEMENT_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_ANNOUNCEMENT_INDEX');
         $translatedTitle = getMessage('HELP_ANNOUNCEMENT_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_ANNOUNCEMENT_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_ANNOUNCEMENT_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_ANNOUNCEMENT');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'BUZZWORDS_EDIT':
         $MsgID           = 'HELP_BUZZWORDS_EDIT';
         $help_title      = 'HELP_BUZZWORDS_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_BUZZWORDS_EDIT');
         $translatedTitle = getMessage('HELP_BUZZWORDS_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_BUZZWORDS_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_BUZZWORDS_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_BUZZWORDS');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CAMPUS_SEARCH_INDEX':
         $MsgID           = 'HELP_CAMPUS_SEARCH_INDEX';
         $help_title      = 'HELP_CAMPUS_SEARCH_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_CAMPUS_SEARCH_INDEX');
         $translatedTitle = getMessage('HELP_CAMPUS_SEARCH_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_CAMPUS_SEARCH_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CAMPUS_SEARCH_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CAMPUS_SEARCH');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CHAT_INDEX':
         $MsgID           = 'HELP_CHAT_INDEX';
         $help_title      = 'HELP_CHAT_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_CHAT_INDEX');
         $translatedTitle = getMessage('HELP_CHAT_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_CHAT_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CHAT_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CHAT');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'COMMUNITY_EDIT':
         $MsgID           = 'HELP_COMMUNITY_EDIT';
         $help_title      = 'HELP_COMMUNITY_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_COMMUNITY_EDIT');
         $translatedTitle = getMessage('HELP_COMMUNITY_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_COMMUNITY_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_COMMUNITY_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_COMMUNITY');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_AGB':
         $MsgID           = 'HELP_CONFIGURATION_AGB';
         $help_title      = 'HELP_CONFIGURATION_AGB_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_AGB');
         $translatedTitle = getMessage('HELP_CONFIGURATION_AGB_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_AGB_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_AGB_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_AGB');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_AUTHENTICATION':
         $MsgID           = 'HELP_CONFIGURATION_AUTHENTICATION';
         $help_title      = 'HELP_CONFIGURATION_AUTHENTICATION_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_AUTHENTICATION');
         $translatedTitle = getMessage('HELP_CONFIGURATION_AUTHENTICATION_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_AUTHENTICATION_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_AUTHENTICATION_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_AUTHENTICATION');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_BACKUP':
         $MsgID           = 'HELP_CONFIGURATION_BACKUP';
         $help_title      = 'HELP_CONFIGURATION_BACKUP_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_BACKUP');
         $translatedTitle = getMessage('HELP_CONFIGURATION_BACKUP_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_BACKUP_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_BACKUP_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_BACKUP');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_CHAT':
         $MsgID           = 'HELP_CONFIGURATION_CHAT';
         $help_title      = 'HELP_CONFIGURATION_CHAT_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_CHAT');
         $translatedTitle = getMessage('HELP_CONFIGURATION_CHAT_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_CHAT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_CHAT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_CHAT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_COLOR':
         $MsgID           = 'HELP_CONFIGURATION_COLOR';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_COLOR');
         $help_title      = 'HELP_CONFIGURATION_COLOR_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_COLOR');
         $translatedTitle = getMessage('HELP_CONFIGURATION_COLOR_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_COLOR_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_COLOR_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_COLOR');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_COMMON':
         $MsgID           = 'HELP_CONFIGURATION_COMMON';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_COMMON');
         $help_title      = 'HELP_CONFIGURATION_COMMON_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_COMMON');
         $translatedTitle = getMessage('HELP_CONFIGURATION_COMMON_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_COMMON_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_COMMON_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_COMMON');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_DATES':
         $MsgID           = 'HELP_CONFIGURATION_DATES';
         $help_title      = 'HELP_CONFIGURATION_DATES_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_DATES');
         $translatedTitle = getMessage('HELP_CONFIGURATION_DATES_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_DATES_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_DATES_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_DATES');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_DISCUSSION':
         $MsgID           = 'HELP_CONFIGURATION_DISCUSSION';
         $help_title      = 'HELP_CONFIGURATION_DISCUSSION_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_DISCUSSION');
         $translatedTitle = getMessage('HELP_CONFIGURATION_DISCUSSION_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_DISCUSSION_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_DISCUSSION_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_DISCUSSION');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_DEFAULTS':
         $MsgID           = 'HELP_CONFIGURATION_DEFAULTS';
         $help_title      = 'HELP_CONFIGURATION_DEFAULTS_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_DEFAULTS');
         $translatedTitle = getMessage('HELP_CONFIGURATION_DEFAULTS_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_DEFAULTS_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_DEFAULTS_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_DEFAULTS');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_EXPORT':
         $MsgID           = 'HELP_CONFIGURATION_EXPORT';
         $help_title      = 'HELP_CONFIGURATION_EXPORT_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_EXPORT');
         $translatedTitle = getMessage('HELP_CONFIGURATION_EXPORT_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_EXPORT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_EXPORT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_EXPORT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_EXTRA':
         $MsgID           = 'HELP_CONFIGURATION_EXTRA';
         $help_title      = 'HELP_CONFIGURATION_EXTRA_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_EXTRA');
         $translatedTitle = getMessage('HELP_CONFIGURATION_EXTRA_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_EXTRA_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_EXTRA_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_EXTRA');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_GROUPROOM':
         $MsgID           = 'HELP_CONFIGURATION_GROUPROOM';
         $help_title      = 'HELP_CONFIGURATION_GROUPROOM_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_GROUPROOM');
         $translatedTitle = getMessage('HELP_CONFIGURATION_GROUPROOM_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_GROUPROOM_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_GROUPROOM_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_GROUPROOM');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_HOME':
         $MsgID           = 'HELP_CONFIGURATION_HOME';
         $help_title      = 'HELP_CONFIGURATION_HOME_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_HOME');
         $translatedTitle = getMessage('HELP_CONFIGURATION_HOME_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_HOME_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_HOME_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_HOME');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_HOMEPAGE':
         $MsgID           = 'HELP_CONFIGURATION_HOMEPAGE';
         $help_title      = 'HELP_CONFIGURATION_HOMEPAGE_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_HOMEPAGE');
         $translatedTitle = getMessage('HELP_CONFIGURATION_HOMEPAGE_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_HOMEPAGE_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_HOMEPAGE_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_HOMEPAGE');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_HTMLTEXTAREA':
         $MsgID           = 'HELP_CONFIGURATION_HTMLTEXTAREA';
         $help_title      = 'HELP_CONFIGURATION_HTMLTEXTAREA_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_HTMLTEXTAREA');
         $translatedTitle = getMessage('HELP_CONFIGURATION_HTMLTEXTAREA_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_HTMLTEXTAREA_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_HTMLTEXTAREA_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_HTMLTEXTAREA');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_IMS':
         $MsgID           = 'HELP_CONFIGURATION_IMS';
         $help_title      = 'HELP_CONFIGURATION_IMS_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_IMS');
         $translatedTitle = getMessage('HELP_CONFIGURATION_IMS_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_IMS_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_IMS_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_IMS');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_INDEX':
         $MsgID           = 'HELP_CONFIGURATION_INDEX';
         $help_title      = 'HELP_CONFIGURATION_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_INDEX');
         $translatedTitle = getMessage('HELP_CONFIGURATION_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_LANGUAGE':
         $MsgID           = 'HELP_CONFIGURATION_LANGUAGE';
         $help_title      = 'HELP_CONFIGURATION_LANGUAGE_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_LANGUAGE');
         $translatedTitle = getMessage('HELP_CONFIGURATION_LANGUAGE_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_LANGUAGE_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_LANGUAGE_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_LANGUAGE');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_MAIL':
         $MsgID           = 'HELP_CONFIGURATION_MAIL';
         $help_title      = 'HELP_CONFIGURATION_MAIL_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_MAIL');
         $translatedTitle = getMessage('HELP_CONFIGURATION_MAIL_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_MAIL_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_MAIL_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_MAIL');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_MOVE':
         $MsgID           = 'HELP_CONFIGURATION_MOVE';
         $help_title      = 'HELP_CONFIGURATION_MOVE_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_MOVE');
         $translatedTitle = getMessage('HELP_CONFIGURATION_MOVE_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_MOVE_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_MOVE_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_MOVE');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_NEWS':
         $MsgID           = 'HELP_CONFIGURATION_NEWS';
         $help_title      = 'HELP_CONFIGURATION_NEWS_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_NEWS');
         $translatedTitle = getMessage('HELP_CONFIGURATION_NEWS_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_NEWS_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_NEWS_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_NEWS');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_NEWSLETTER':
         $MsgID           = 'HELP_CONFIGURATION_NEWSLETTER';
         $help_title      = 'HELP_CONFIGURATION_NEWSLETTER_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_NEWSLETTER');
         $translatedTitle = getMessage('HELP_CONFIGURATION_NEWSLETTER_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_NEWSLETTER_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_NEWSLETTER_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_NEWSLETTER');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_PORTALHOME':
         $MsgID           = 'HELP_CONFIGURATION_PORTALHOME';
         $help_title      = 'HELP_CONFIGURATION_PORTALHOME_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_PORTALHOME');
         $translatedTitle = getMessage('HELP_CONFIGURATION_PORTALHOME_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_PORTALHOME_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_PORTALHOME_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_PORTALHOME');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_PREFERENCES':
         $MsgID           = 'HELP_CONFIGURATION_PREFERENCES';
         $help_title      = 'HELP_CONFIGURATION_PREFERENCES_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_PREFERENCES');
         $translatedTitle = getMessage('HELP_CONFIGURATION_PREFERENCES_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_PREFERENCES_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_PREFERENCES_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_PREFERENCES');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_PRIVATEROOM_NEWSLETTER':
         $MsgID           = 'HELP_CONFIGURATION_PRIVATEROOM_NEWSLETTER';
         $help_title      = 'HELP_CONFIGURATION_PRIVATEROOM_NEWSLETTER_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_PRIVATEROOM_NEWSLETTER');
         $translatedTitle = getMessage('HELP_CONFIGURATION_PRIVATEROOM_NEWSLETTER_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_PRIVATEROOM_NEWSLETTER_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_PRIVATEROOM_NEWSLETTER_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_PRIVATEROOM_NEWSLETTER');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_ROOM_OPENING':
         $MsgID           = 'HELP_CONFIGURATION_ROOM_OPENING';
         $help_title      = 'HELP_CONFIGURATION_ROOM_OPENING_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_ROOM_OPENING');
         $translatedTitle = getMessage('HELP_CONFIGURATION_ROOM_OPENING_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_ROOM_OPENING_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_ROOM_OPENING_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_ROOM_OPENING');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_RUBRIC':
         $MsgID           = 'HELP_CONFIGURATION_RUBRIC';
         $help_title      = 'HELP_CONFIGURATION_RUBRIC_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_RUBRIC');
         $translatedTitle = getMessage('HELP_CONFIGURATION_RUBRIC_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_RUBRIC_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_RUBRIC_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_RUBRIC');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_SERVICE':
         $MsgID           = 'HELP_CONFIGURATION_SERVICE';
         $help_title      = 'HELP_CONFIGURATION_SERVICE_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_SERVICE');
         $translatedTitle = getMessage('HELP_CONFIGURATION_SERVICE_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_SERVICE_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_SERVICE_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_SERVICE');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_SPONSOR':
         $MsgID           = 'HELP_CONFIGURATION_SPONSOR';
         $help_title      = 'HELP_CONFIGURATION_SPONSOR_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_SPONSOR');
         $translatedTitle = getMessage('HELP_CONFIGURATION_SPONSOR_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_SPONSOR_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_SPONSOR_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_SPONSOR');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_STATISTIC':
         $MsgID           = 'HELP_CONFIGURATION_STATISTIC';
         $help_title      = 'HELP_CONFIGURATION_STATISTIC_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_STATISTIC');
         $translatedTitle = getMessage('HELP_CONFIGURATION_STATISTIC_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_STATISTIC_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_STATISTIC_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_STATISTIC');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_TIME':
         $MsgID           = 'HELP_CONFIGURATION_TIME';
         $help_title      = 'HELP_CONFIGURATION_TIME_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_TIME');
         $translatedTitle = getMessage('HELP_CONFIGURATION_TIME_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_TIME_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_TIME_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_TIME');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_USAGEINFO':
         $MsgID           = 'HELP_CONFIGURATION_USAGEINFO';
         $help_title      = 'HELP_CONFIGURATION_USAGEINFO_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_USAGEINFO');
         $translatedTitle = getMessage('HELP_CONFIGURATION_USAGEINFO_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_USAGEINFO_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_USAGEINFO_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_USAGEINFO');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'CONFIGURATION_WIKI':
         $MsgID           = 'HELP_CONFIGURATION_WIKI';
         $help_title      = 'HELP_CONFIGURATION_WIKI_TITLE';
         $translatedMsg   = getMessage('HELP_CONFIGURATION_WIKI');
         $translatedTitle = getMessage('HELP_CONFIGURATION_WIKI_TITLE');
         $translatedLinks = getMessage('HELP_CONFIGURATION_WIKI_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_CONFIGURATION_WIKI_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_CONFIGURATION');
            $help_function = getMessage('HELP_FUNCTION_WIKI');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'DATE_DETAIL':
         $MsgID           = 'HELP_DATE_DETAIL';
         $help_title      = 'HELP_DATE_DETAIL_TITLE';
         $translatedMsg   = getMessage('HELP_DATE_DETAIL');
         $translatedTitle = getMessage('HELP_DATE_DETAIL_TITLE');
         $translatedLinks = getMessage('HELP_DATE_DETAIL_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_DATE_DETAIL_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_DATE');
            $help_function = getMessage('HELP_FUNCTION_DETAIL');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'DATE_EDIT':
         $MsgID           = 'HELP_DATE_EDIT';
         $help_title      = 'HELP_DATE_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_DATE_EDIT');
         $translatedTitle = getMessage('HELP_DATE_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_DATE_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_DATE_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_DATE');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'DATE_INDEX':
         $MsgID           = 'HELP_DATE_INDEX';
         $help_title      = 'HELP_DATE_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_DATE_INDEX');
         $translatedTitle = getMessage('HELP_DATE_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_DATE_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_DATE_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_DATE');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'DATE_IMPORT':
         $MsgID           = 'HELP_DATE_IMPORT';
         $help_title      = 'HELP_DATE_IMPORT_TITLE';
         $translatedMsg   = getMessage('HELP_DATE_IMPORT');
         $translatedTitle = getMessage('HELP_DATE_IMPORT_TITLE');
         $translatedLinks = getMessage('HELP_DATE_IMPORT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_DATE_IMPORT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_DATE');
            $help_function = getMessage('HELP_FUNCTION_IMPORT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'DISCARTICLE_EDIT':
         $MsgID           = 'HELP_DISCARTICLE_EDIT';
         $help_title      = 'HELP_DISCARTICLE_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_DISCARTICLE_EDIT');
         $translatedTitle = getMessage('HELP_DISCARTICLE_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_DISCARTICLE_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_DISCARTICLE_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_DISCARTICLE');
            $help_function = getMessage('HELP_FUNCTION_EDIT_DISCARTICLE');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'DISCUSSION_CLOSE':
         $MsgID           = 'HELP_DISCUSSION_CLOSE';
         $help_title      = 'HELP_DISCUSSION_CLOSE_TITLE';
         $translatedMsg   = getMessage('HELP_DISCUSSION_CLOSE');
         $translatedTitle = getMessage('HELP_DISCUSSION_CLOSE_TITLE');
         $translatedLinks = getMessage('HELP_DISCUSSION_CLOSE_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_DISCUSSION_CLOSE_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_DISCUSSION');
            $help_function = getMessage('HELP_FUNCTION_CLOSE_DISCUSSION');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'DISCUSSION_DETAIL':
         $MsgID           = 'HELP_DISCUSSION_DETAIL';
         $help_title      = 'HELP_DISCUSSION_DETAIL_TITLE';
         $translatedMsg   = getMessage('HELP_DISCUSSION_DETAIL');
         $translatedTitle = getMessage('HELP_DISCUSSION_DETAIL_TITLE');
         $translatedLinks = getMessage('HELP_DISCUSSION_DETAIL_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_DISCUSSION_DETAIL_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_DISCUSSION');
            $help_function = getMessage('HELP_FUNCTION_DETAIL');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'DISCUSSION_EDIT':
         $MsgID           = 'HELP_DISCUSSION_EDIT';
         $help_title      = 'HELP_DISCUSSION_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_DISCUSSION_EDIT');
         $translatedTitle = getMessage('HELP_DISCUSSION_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_DISCUSSION_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_DISCUSSION_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_DISCUSSION');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'DISCUSSION_INDEX':
         $MsgID           = 'HELP_DISCUSSION_INDEX';
         $help_title      = 'HELP_DISCUSSION_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_DISCUSSION_INDEX');
         $translatedTitle = getMessage('HELP_DISCUSSION_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_DISCUSSION_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_DISCUSSION_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_DISCUSSION');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'GROUP_DETAIL':
         $MsgID           = 'HELP_GROUP_DETAIL';
         $help_title      = 'HELP_GROUP_DETAIL_TITLE';
         $translatedMsg   = getMessage('HELP_GROUP_DETAIL');
         $translatedTitle = getMessage('HELP_GROUP_DETAIL_TITLE');
         $translatedLinks = getMessage('HELP_GROUP_DETAIL_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_GROUP_DETAIL_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_GROUP');
            $help_function = getMessage('HELP_FUNCTION_DETAIL');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'GROUP_EDIT':
         $MsgID           = 'HELP_GROUP_EDIT';
         $help_title      = 'HELP_GROUP_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_GROUP_EDIT');
         $translatedTitle = getMessage('HELP_GROUP_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_GROUP_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_GROUP_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_GROUP');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'GROUP_INDEX':
         $MsgID           = 'HELP_GROUP_INDEX';
         $help_title      = 'HELP_GROUP_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_GROUP_INDEX');
         $translatedTitle = getMessage('HELP_GROUP_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_GROUP_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_GROUP_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_GROUP');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'GROUP_MAIL':
         $MsgID           = 'HELP_GROUP_MAIL';
         $help_title      = 'HELP_GROUP_MAIL_TITLE';
         $translatedMsg   = getMessage('HELP_GROUP_MAIL');
         $translatedTitle = getMessage('HELP_GROUP_MAIL_TITLE');
         $translatedLinks = getMessage('HELP_GROUP_MAIL_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_GROUP_MAIL_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_GROUP');
            $help_function = getMessage('HELP_FUNCTION_MAIL');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'HOME_INDEX':
         $MsgID           = 'HELP_HOME_INDEX';
         $help_title      = 'HELP_HOME_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_HOME_INDEX');
         $translatedTitle = getMessage('HELP_HOME_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_HOME_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_HOME_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_HOME');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'INSTITUTION_DETAIL':
         $MsgID           = 'HELP_INSTITUTION_DETAIL';
         $help_title      = 'HELP_INSTITUTION_DETAIL_TITLE';
         $translatedMsg   = getMessage('HELP_INSTITUTION_DETAIL');
         $translatedTitle = getMessage('HELP_INSTITUTION_DETAIL_TITLE');
         $translatedLinks = getMessage('HELP_INSTITUTION_DETAIL_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_INSTITUTION_DETAIL_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_INSTITUTION');
            $help_function = getMessage('HELP_FUNCTION_DETAIL');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'INSTITUTION_EDIT':
         $MsgID           = 'HELP_INSTITUTION_EDIT';
         $help_title      = 'HELP_INSTITUTION_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_INSTITUTION_EDIT');
         $translatedTitle = getMessage('HELP_INSTITUTION_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_INSTITUTION_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_INSTITUTION_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_INSTITUTION');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'INSTITUTION_INDEX':
         $MsgID           = 'HELP_INSTITUTION_INDEX';
         $help_title      = 'HELP_INSTITUTION_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_INSTITUTION_INDEX');
         $translatedTitle = getMessage('HELP_INSTITUTION_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_INSTITUTION_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_INSTITUTION_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_INSTITUTION');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'INSTITUTION_MAIL':
         $MsgID           = 'HELP_INSTITUTION_MAIL';
         $help_title      = 'HELP_INSTITUTION_MAIL_TITLE';
         $translatedMsg   = getMessage('HELP_INSTITUTION_MAIL');
         $translatedTitle = getMessage('HELP_INSTITUTION_MAIL_TITLE');
         $translatedLinks = getMessage('HELP_INSTITUTION_MAIL_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_INSTITUTION_MAIL_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_INSTITUTION');
            $help_function = getMessage('HELP_FUNCTION_MAIL');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'LABELS_EDIT':
         $MsgID           = 'HELP_LABELS_EDIT';
         $help_title      = 'HELP_LABELS_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_LABELS_EDIT');
         $translatedTitle = getMessage('HELP_LABELS_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_LABELS_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_LABELS_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_LABELS');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'LANGUAGE_EDIT':
         $MsgID           = 'HELP_LANGUAGE_EDIT';
         $help_title      = 'HELP_LANGUAGE_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_LANGUAGE_EDIT');
         $translatedTitle = getMessage('HELP_LANGUAGE_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_LANGUAGE_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_LANGUAGE_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_LANGUAGE');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'LANGUAGE_INDEX':
         $MsgID           = 'HELP_LANGUAGE_INDEX';
         $help_title      = 'HELP_LANGUAGE_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_LANGUAGE_INDEX');
         $translatedTitle = getMessage('HELP_LANGUAGE_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_LANGUAGE_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_LANGUAGE_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_LANGUAGE');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'MATERIAL_DETAIL':
         $MsgID           = 'HELP_MATERIAL_DETAIL';
         $help_title      = 'HELP_MATERIAL_DETAIL_TITLE';
         $translatedMsg   = getMessage('HELP_MATERIAL_DETAIL');
         $translatedTitle = getMessage('HELP_MATERIAL_DETAIL_TITLE');
         $translatedLinks = getMessage('HELP_MATERIAL_DETAIL_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_MATERIAL_DETAIL_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_MATERIAL');
            $help_function = getMessage('HELP_FUNCTION_DETAIL');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'MATERIAL_EDIT':
         $MsgID           = 'HELP_MATERIAL_EDIT';
         $help_title      = 'HELP_MATERIAL_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_MATERIAL_EDIT');
         $translatedTitle = getMessage('HELP_MATERIAL_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_MATERIAL_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_MATERIAL_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_MATERIAL');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'MATERIAL_INDEX':
         $MsgID           = 'HELP_MATERIAL_INDEX';
         $help_title      = 'HELP_MATERIAL_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_MATERIAL_INDEX');
         $translatedTitle = getMessage('HELP_MATERIAL_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_MATERIAL_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_MATERIAL_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_MATERIAL');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'MYROOM_INDEX':
         $MsgID           = 'HELP_MYROOM_INDEX';
         $help_title      = 'HELP_MYROOM_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_MYROOM_INDEX');
         $translatedTitle = getMessage('HELP_MYROOM_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_MYROOM_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_MYROOM_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_MYROOM');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'MYROOM_DETAIL':
         $MsgID           = 'HELP_MYROOM_DETAIL';
         $help_title      = 'HELP_MYROOM_DETAIL_TITLE';
         $translatedMsg   = getMessage('HELP_MYROOM_DETAIL');
         $translatedTitle = getMessage('HELP_MYROOM_DETAIL_TITLE');
         $translatedLinks = getMessage('HELP_MYROOM_DETAIL_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_MYROOM_DETAIL_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_MYROOM');
            $help_function = getMessage('HELP_FUNCTION_DETAIL');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'PROJECT_DETAIL':
         $MsgID           = 'HELP_PROJECT_DETAIL';
         $help_title      = 'HELP_PROJECT_DETAIL_TITLE';
         $translatedMsg   = getMessage('HELP_PROJECT_DETAIL');
         $translatedTitle = getMessage('HELP_PROJECT_DETAIL_TITLE');
         $translatedLinks = getMessage('HELP_PROJECT_DETAIL_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_PROJECT_DETAIL_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_PROJECT');
            $help_function = getMessage('HELP_FUNCTION_DETAIL');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'PROJECT_EDIT':
         $MsgID           = 'HELP_PROJECT_EDIT';
         $help_title      = 'HELP_PROJECT_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_PROJECT_EDIT');
         $translatedTitle = getMessage('HELP_PROJECT_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_PROJECT_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_PROJECT_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_PROJECT');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'PROJECT_INDEX':
         $MsgID           = 'HELP_PROJECT_INDEX';
         $help_title      = 'HELP_PROJECT_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_PROJECT_INDEX');
         $translatedTitle = getMessage('HELP_PROJECT_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_PROJECT_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_PROJECT_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_PROJECT');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'RUBRIC_MAIL':
         $MsgID           = 'HELP_RUBRIC_MAIL';
         $help_title      = 'HELP_RUBRIC_MAIL_TITLE';
         $translatedMsg   = getMessage('HELP_RUBRIC_MAIL');
         $translatedTitle = getMessage('HELP_RUBRIC_MAIL_TITLE');
         $translatedLinks = getMessage('HELP_RUBRIC_MAIL_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_RUBRIC_MAIL_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_RUBRIC');
            $help_function = getMessage('HELP_FUNCTION_MAIL');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'SECTION_EDIT':
         $MsgID           = 'HELP_SECTION_EDIT';
         $help_title      = 'HELP_SECTION_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_SECTION_EDIT');
         $translatedTitle = getMessage('HELP_SECTION_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_SECTION_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_SECTION_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_SECTION');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'TODO_DETAIL':
         $MsgID           = 'HELP_TODO_DETAIL';
         $help_title      = 'HELP_TODO_DETAIL_TITLE';
         $translatedMsg   = getMessage('HELP_TODO_DETAIL');
         $translatedTitle = getMessage('HELP_TODO_DETAIL_TITLE');
         $translatedLinks = getMessage('HELP_TODO_DETAIL_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_TODO_DETAIL_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_TODO');
            $help_function = getMessage('HELP_FUNCTION_DETAIL');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'TODO_EDIT':
         $MsgID           = 'HELP_TODO_EDIT';
         $help_title      = 'HELP_TODO_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_TODO_EDIT');
         $translatedTitle = getMessage('HELP_TODO_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_TODO_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_TODO_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_TODO');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'TODO_INDEX':
         $MsgID           = 'HELP_TODO_INDEX';
         $help_title      = 'HELP_TODO_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_TODO_INDEX');
         $translatedTitle = getMessage('HELP_TODO_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_TODO_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_TODO_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_TODO');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'TOPIC_DETAIL':
         $MsgID           = 'HELP_TOPIC_DETAIL';
         $help_title      = 'HELP_TOPIC_DETAIL_TITLE';
         $translatedMsg   = getMessage('HELP_TOPIC_DETAIL');
         $translatedTitle = getMessage('HELP_TOPIC_DETAIL_TITLE');
         $translatedLinks = getMessage('HELP_TOPIC_DETAIL_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_TOPIC_DETAIL_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_TOPIC');
            $help_function = getMessage('HELP_FUNCTION_DETAIL');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'TOPIC_EDIT':
         $MsgID           = 'HELP_TOPIC_EDIT';
         $help_title      = 'HELP_TOPIC_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_TOPIC_EDIT');
         $translatedTitle = getMessage('HELP_TOPIC_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_TOPIC_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_TOPIC_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_TOPIC');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'TOPIC_INDEX':
         $MsgID           = 'HELP_TOPIC_INDEX';
         $help_title      = 'HELP_TOPIC_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_TOPIC_INDEX');
         $translatedTitle = getMessage('HELP_TOPIC_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_TOPIC_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_TOPIC_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_TOPIC');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'USER_ACTION':
         $MsgID           = 'HELP_USER_ACTION';
         $help_title      = 'HELP_USER_ACTION_TITLE';
         $translatedMsg   = getMessage('HELP_USER_ACTION');
         $translatedTitle = getMessage('HELP_USER_ACTION_TITLE');
         $translatedLinks = getMessage('HELP_USER_ACTION_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_USER_ACTION_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_USER');
            $help_function = getMessage('HELP_FUNCTION_ACTION');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'USER_CLOSE':
         $MsgID           = 'HELP_USER_CLOSE';
         $help_title      = 'HELP_USER_CLOSE_TITLE';
         $translatedMsg   = getMessage('HELP_USER_CLOSE');
         $translatedTitle = getMessage('HELP_USER_CLOSE_TITLE');
         $translatedLinks = getMessage('HELP_USER_CLOSE_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_USER_CLOSE_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_USER');
            $help_function = getMessage('HELP_FUNCTION_CLOSE_USER');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'USER_DETAIL':
         $MsgID           = 'HELP_USER_DETAIL';
         $help_title      = 'HELP_USER_DETAIL_TITLE';
         $translatedMsg   = getMessage('HELP_USER_DETAIL');
         $translatedTitle = getMessage('HELP_USER_DETAIL_TITLE');
         $translatedLinks = getMessage('HELP_USER_DETAIL_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_USER_DETAIL_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_USER');
            $help_function = getMessage('HELP_FUNCTION_DETAIL');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'USER_EDIT':
         $MsgID           = 'HELP_USER_EDIT';
         $help_title      = 'HELP_USER_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_USER_EDIT');
         $translatedTitle = getMessage('HELP_USER_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_USER_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_USER_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_USER');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'USER_INDEX':
         $MsgID           = 'HELP_USER_INDEX';
         $help_title      = 'HELP_USER_INDEX_TITLE';
         $translatedMsg   = getMessage('HELP_USER_INDEX');
         $translatedTitle = getMessage('HELP_USER_INDEX_TITLE');
         $translatedLinks = getMessage('HELP_USER_INDEX_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_USER_INDEX_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_USER');
            $help_function = getMessage('HELP_FUNCTION_INDEX');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'USER_PREFERENCES':
         $MsgID           = 'HELP_USER_PREFERENCES';
         $help_title      = 'HELP_USER_PREFERENCES_TITLE';
         $translatedMsg   = getMessage('HELP_USER_PREFERENCES');
         $translatedTitle = getMessage('HELP_USER_PREFERENCES_TITLE');
         $translatedLinks = getMessage('HELP_USER_PREFERENCES_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_USER_PREFERENCES_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_USER');
            $help_function = getMessage('HELP_FUNCTION_PREFERENCES');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      case 'TAG_EDIT':
         $MsgID           = 'HELP_TAG_EDIT';
         $help_title      = 'HELP_TAG_EDIT_TITLE';
         $translatedMsg   = getMessage('HELP_TAG_EDIT');
         $translatedTitle = getMessage('HELP_TAG_EDIT_TITLE');
         $translatedLinks = getMessage('HELP_TAG_EDIT_LINKS');
         if ( $translatedTitle != $help_title ) {
            $headline      = getMessage('HELP_HEADLINE', getMessage('HELP_TAG_EDIT_TITLE'));
         } else {
            $help_module   = getMessage('HELP_MODULE_TAG');
            $help_function = getMessage('HELP_FUNCTION_EDIT');
            $headline      = getMessage('HELP_HEADLINE', $help_module.' - '.$help_function);
         }
         break;
      default:
             $MsgID = 'HELP_'.$module.'_'.$function;
             $help_title      = '';
             $headline     = getMessage('COMMON_MESSAGETAG_ERROR'.' help_context.php(1452) ');
         break;
   }

   $textbox->setTitle($headline);

   // inserting the help text
   if ($translatedMsg != $MsgID) {
      $textbox->addRow($translatedMsg.BRLF);
   } else {
      if ($function == 'EDIT' and $module != 'LANGUAGE'
                              and $module != 'CAMPUS_SEARCH_NORMAL'
                              and $module != 'CAMPUS_SEARCH_SHORT'
                              and $module != 'ACCOUNT'
                              and $module != 'MAIL'
                              and $module != 'INTERNAL_COLOR') {
         $textbox->addRow( getMessage('HELP_ALL_EDIT') );
         // linking to relevant common help topics for edit pages
         $commonInfo = '<b>'.getMessage('HELP_COMMON_INFO').'</b>';
         $linkTags = explode(' ', getMessage('HELP_ALL_EDIT_LINKS'));
         foreach ($linkTags as $linkTag) {
            $params = array();
            $params['module'] = $_GET['module'];
            $params['function'] = $_GET['function'];
            $params['context'] = $linkTag;
            switch ( $linkTag ) {
               case 'HELP_COMMON_FORMAT':
                  $title = getMessage('HELP_COMMON_FORMAT_TITLE');
                  break;
               case 'HELP_COMMON_EDIT':
                  $title = getMessage('HELP_COMMON_EDIT_TITLE');
                  break;
               case 'HELP_COMMON_LINK':
                  $title = getMessage('HELP_COMMON_LINK_TITLE');
                  break;
               case 'HELP_COMMON_STRUCTURE':
                  $title = getMessage('HELP_COMMON_STRUCTURE_TITLE');
                  break;
               case 'HELP_COMMON_RIGHTS':
                  $title = getMessage('HELP_COMMON_RIGHTS_TITLE');
                  break;
               default:
                  $title = '';
            }
            if ( !empty($title) ) {
               $commonInfo .= '<br />'.ahref_curl( $environment->getCurrentContextID(),
                                                   'help',
                                                   'context',
                                                   $params,
                                                   $title
                                                 );
            }
            unset($params);
         }
        // inserting the help links for edit pages
        $textbox->addRow($commonInfo);

      } else {
         $textbox->addRow(getMessage('HELP_NOT_AVAILABLE').LF.'<!-- lost message: '.$MsgID.' -->'.LF);
      }
   }

   // linking to relevant common help topics
   if ($translatedLinks != $MsgID.'_LINKS') {
      $commonInfo = '<b>'.getMessage('HELP_COMMON_INFO').'</b>';
      $linkTags = explode(' ', $translatedLinks);
      foreach ($linkTags as $linkTag) {
         $params = array();
         $params['module'] = $_GET['module'];
         $params['function'] = $_GET['function'];
         $params['context'] = $linkTag;
         switch ( $linkTag ) {
            case 'HELP_COMMON_FORMAT':
               $title = getMessage('HELP_COMMON_FORMAT_TITLE');
               break;
            case 'HELP_COMMON_EDIT':
               $title = getMessage('HELP_COMMON_EDIT_TITLE');
               break;
            case 'HELP_COMMON_LINK':
               $title = getMessage('HELP_COMMON_LINK_TITLE');
               break;
            case 'HELP_COMMON_STRUCTURE':
               $title = getMessage('HELP_COMMON_STRUCTURE_TITLE');
               break;
            case 'HELP_COMMON_RIGHTS':
               $title = getMessage('HELP_COMMON_RIGHTS_TITLE');
               break;
            default:
               $title = '';
         }
         if ( !empty($title) ) {
            $commonInfo .= '<br />'.ahref_curl( $environment->getCurrentContextID(),
                                                'help',
                                                'context',
                                                $params,
                                                $title
                                              );
         }
         unset($params);
      }
      // inserting the help links
      $textbox->addRow($commonInfo);
   }

   // linking to (static) additional help topics
   $help_links['additional']['de'][] = 'CommSy-Kurzbeschreibung[http://www.commsy.net/uploads/Software/commsy_kurzbeschreibung.pdf] (als PDF-Datei zum Herunterladen)';
   $help_links['additional']['de'][] = '<a href="http://www.commsy.net/uploads/Software/commsy_nutzungshandbuch.pdf" target="_blank">CommSy-Handbuch</a> (als PDF-Datei zum Herunterladen)';
   $help_links['additional']['de'][] = 'CommSy-Moderationshandbuch[http://www.commsy.net/uploads/Software/commsy_moderationshandbuch.pdf]  (als PDF-Datei zum Herunterladen)';
   $help_links['additional']['de'][] = '[http://www.commsy.net/Software/FAQ|Fragen und Antworten von BenutzerInnen|external]';
   $help_links['additional']['de'][] = 'Weitere Informationen unter http://www.commsy.net';
   $help_links['additional']['de'][] = ahref_curl($environment->getCurrentContextID(), 'mail', 'to_moderator', '', getMessage('HELP_MAIL_TO_MODERATOR_TITLE'), '', '_blank');

   $help_links['additional']['en'][] = '[http://www.commsy.net/uploads/Software/commsy_kurzbeschreibung.pdf|A short description for CommSy|external] (downloadable pdf-file in german)';
   $help_links['additional']['en'][] = '<a href="http://www.commsy.net/uploads/Software/commsy_nutzungshandbuch.pdf" target="_blank">CommSy Handbook</a> (downloadable pdf-file in german)';
   $help_links['additional']['en'][] = '<a href="http://www.commsy.net/uploads/Software/commsy_moderationshandbuch.pdf" target="_blank">CommSy moderator\'s handbook</a> downloadable pdf-file in german)';
   $help_links['additional']['en'][] = '<a href="http://www.commsy.net/Software/FAQ" target="_blank">Users\' questions and answers</a> (in german)';
   $help_links['additional']['en'][] = 'Further information at http://www.commsy.net (german website)';
   $help_links['additional']['en'][] = ahref_curl($environment->getCurrentContextID(), 'mail', 'to_moderator', '', getMessage('HELP_MAIL_TO_MODERATOR_TITLE'), '', '_blank');

   $textbox->addRow("<b>".getMessage('HELP_ADDITIONAL_INFO')."</b>:".BRLF.encode(AS_HTML_LONG,implode(LF,$help_links['additional'][$environment->getSelectedLanguage()])));
}

$page->add($textbox);
$page->setHelpPageStatus();
$page->withoutCommSyFooter();
if ($environment->inPortal() or $environment->inServer()) {
   $page->withoutCommSyColumn();
}
$page->setFocusOnload();
?>