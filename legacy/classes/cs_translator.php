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

include_once('functions/misc_functions.php');

/** class for authentication items
 * this class implements authentication items
 */
class cs_translator {

   /**
   * array - containing the messages
   */
   var $_message_array = array();

   /**
   * array - containing the messages for times
   */
   var $_time_message_array = array();

   /**
   * string - containing the path to the message.dats
   */
   var $_file_path = 'etc/messages/';

   /**
   * boolean - flag wether to resolve messagetags or not, set in config.php
   */
   var $_dont_resolve_messagetags = false;

   /**
   * reference - to the database, get from environment
   */
   var $_db_conntector;

   /**
   * string - containing the selected language
   */
   var $_selected_language = '';

   /**
   * string - containing the selected language
   */
   var $_session_language = '';

   /**
   * array - containing the special rubric names, get from current room
   */
   var $_rubric_translation_array = array();

   /**
   * array - containing the special email texts, get from current room
   */
   var $_email_array = array();

   /**
   * array - containing the loaded message.dats to delete while saving
   */
   var $_loaded_message_dats = array();

   /**
   * string - containing the context: community or project or portal
   */
   var $_context = NULL;

   /**
   * string - containing the default language / default = de (german)
   */
   var $_default_language = 'de';

   /**
   * integer - containing the current version of the commsy system
   */
   var $_version;

   private $_dat_folder_array = array();

   /** constructor
    * the only available constructor, initial values for internal variables
    */
   function __construct() {
    $this->_file_path = realpath(dirname(__FILE__)) . '/../' . $this->_file_path;
   }

   /** _loadAllMessages - INTERNAL
    * this methode loads all message.dats from commsy -> for language edit
    */
   function _loadAllMessages () {
      $directory = dir($this->_file_path);
      while ( $entry = $directory->read() ) {
         if ( mb_strpos($entry,'s_') == 1 and mb_strpos($entry,'.dat') and $entry[0] == 'm') {
            if (file_exists($this->_file_path.$entry)) {
               include_once($this->_file_path.$entry);
               $this->_loaded_message_dats[] = $entry;
               if (!empty($message)) {
                  $message = encode(FROM_FILE,$message);
                  $this->_message_array = multi_array_merge($this->_message_array,$message);
                  unset($message);
               }
            }
         }
      }
   }

   /** _loadMessages - INTERNAL
    * this methode loads ms_$rubric_$language.dat
    *
    * @param string $rubric to load (first word of message tag)
    * @param string $language to load (de,en,...), if is empty -> all languages will be loaded
    */
   function _loadMessages ($rubric, $language) {
      $message = array();
      if ( !empty($language) ) {
         $entry = 'ms_'.$rubric.'_'.$language.'.dat';
         if ( file_exists($this->_file_path.$entry) ) {
            include_once($this->_file_path.$entry);
            $this->_loaded_message_dats[] = $entry;
            if (!empty($message)) {
               $message = encode(FROM_FILE,$message);
               $this->_message_array = multi_array_merge($this->_message_array,$message);
               unset($message);
            }
         } else {
            foreach ( $this->_dat_folder_array as $folder ) {
               if ( file_exists($folder.'/'.$entry) ) {
                  include_once($folder.'/'.$entry);
                  $this->_loaded_message_dats[] = $entry;
                  if (!empty($message)) {
                     $message = encode(FROM_FILE,$message);
                     $this->_message_array = multi_array_merge($this->_message_array,$message);
                     unset($message);
                  }
                  break;
               }
            }
         }
      } else {
         $directory = dir($this->_file_path);
         while ( $entry = $directory->read() ) {
            if ( mb_stristr($entry,$rubric) ) {
               if (file_exists($this->_file_path.$entry)) {
                  include_once($this->_file_path.$entry);
                  $this->_loaded_message_dats[] = $entry;
                  if (!empty($message)) {
                     $message = encode(FROM_FILE,$message);
                     $this->_message_array = multi_array_merge($this->_message_array,$message);
                     unset($message);
                  }
               }
            }
         }
      }
   }

   /** saveMessages
    * save stored messages to the message.dats
    */
   function saveMessages () {
      $lang_array = array();
      foreach ($this->_message_array as $key => $value) {
         $rubric = $this->_getRubricOutMessageTag($key);
         foreach ($value as $language => $translation) {
            $lang_array[$language][$rubric][$key][$language] = $translation;
         }
      }
      $this->_deleteLoadedMessages();
      foreach ($lang_array as $language => $rubric_array) {
         foreach ($rubric_array as $rubric => $message_array) {
            $filename = $this->_file_path.'ms_'.$rubric.'_'.$language.'.dat';
            $messagefile = fopen($filename,"w");
            fwrite($messagefile, $this->_translate2String(encode(AS_FILE,$message_array)));
            fclose($messagefile);
         }
      }
   }

   /** saveMessageBundles
    * save stored messages to java bundle files
    */
   function saveMessageBundles () {
      $lang_array = array();
      foreach ($this->_message_array as $key => $value) {
         $rubric = $this->_getRubricOutMessageTag($key);
         foreach ($value as $language => $translation) {
            $lang_array[$language][$rubric][$key][$language] = $translation;
         }
      }
      $this->_deleteLoadedMessageBundles();
      foreach ($lang_array as $language => $rubric_array) {
        $filename = mb_strtolower($this->_file_path.''."c3p0".'_'.$language.'.properties', 'UTF-8');
        $messagefile = fopen($filename,"a");
        fwrite($messagefile, "// \$Id\$\n// DO NOT EDIT, CHANGES WILL BE LOST! - This file is generated on the basis of a PHP file\n// To make changes to this file use the edit message function within the commsy system itself\n");
        foreach ($rubric_array as $rubric => $message_array) {
            echo "writing file '".$filename."'<br/>\n"; flush();
            fwrite($messagefile, "// ### ".$filename." ###\n");
            fwrite($messagefile, $this->_translate2JavaString(encode(FROM_FILE,$message_array)));
         }
        fclose($messagefile);
      }
   }

   /** _deleteAllMessages - INTERNAL
    * this methode deletes all message.dats
    */
   function _deleteAllMessages () {
      $directory = dir($this->_file_path);
      while ( $entry = $directory->read() ) {
         if ( mb_strpos($entry,'s_') == 1 and mb_strpos($entry,'.dat') and $entry[0] == 'm') {
            if (file_exists($this->_file_path.$entry)) {
               unlink($this->_file_path.$entry);
            }
         }
      }
   }

   /** _deleteAllMessageBundles - INTERNAL
    * this methode deletes all message property files
    */
   function _deleteAllMessageBundles () {
      $directory = dir($this->_file_path);
      while ( $entry = $directory->read() ) {
         if ( mb_strpos($entry,'s_') == 1 and mb_strpos($entry,'.properties') and $entry[0] == 'm') {
            if (file_exists($this->_file_path.$entry)) {
               unlink($this->_file_path.$entry);
            }
         }
      }
   }

   /** _deleteLoadedMessages - INTERNAL
    * this methode deletes all loaded message.dats
    */
   function _deleteLoadedMessages () {
      foreach ( $this->_loaded_message_dats as $entry ) {
         if (file_exists($this->_file_path.$entry)) {
            unlink($this->_file_path.$entry);
         }
      }
   }

   /** _deleteLoadedMessageBundles - INTERNAL
    * this methode deletes all loaded message.dats
    */
   function _deleteLoadedMessageBundles () {
      foreach ( $this->_loaded_message_dats as $entry ) {
         if (file_exists($this->_file_path.$entry)) {
            // unlink($this->_file_path.$entry);
         }
      }
   }

   /** _translate2String - INTERNAL
    * this methode translate a message array to a string to write it into a file
    *
    * @param array message array to translate
    *
    * @return string $message_text message array as string
    *
    * @author CommSy Development Group
    */
   function _translate2String($message_array) {
      ksort($message_array);
      reset($message_array);
      $message_text = "<?php\n";
      foreach($message_array as $key => $value){
         foreach($value as $key2 => $value2){
            $message_text .= '$message["'.$key.'"]["'.$key2.'"] = "'.$value2.'";'."\n";
         }
      }
      $message_text .= "?>";
      return  $message_text;
   }

   /** _translate2String - INTERNAL
    * this methode translate a message array to a string to write it into a file
    *
    * @param array message array to translate
    *
    * @return string $message_text message array as string
    */
   function _translate2JavaString($message_array) {
      ksort($message_array);
      reset($message_array);
      $message_text = "";
      foreach($message_array as $key => $value){
         foreach($value as $key2 => $value2){
            for($i=0; $i<10; $i++ )
              {
              $value2 = str_replace("%".($i+1),"{".$i."}",$value2);
              }
            $value2 = strtr($value2, "\n", " ");
            $key = strtr($key, " ", "_");
            $message_text .= ''.$key.'='.$value2.''."\r\n";
         }
      }
      $message_text .= "";
      return  $message_text;
   }

   /** _getRubricOutMessageTag - INTERNAL
    * this methode separate the rubric (first word) out of the messagetag-name
    *
    * @param string message_tag
    *
    * @return string rubric (first word)
    */
   function _getRubricOutMessageTag ($messag_tag) {
      return mb_substr($messag_tag,0,mb_strpos($messag_tag,'_'));
   }

   /** get an array of the available languages
    *  this method returns the available languages, which are used as indices in the array message
    *
    * @return array of available languages
    */
   function getAvailableLanguages () {
      if (!isset($this->_avialable_languages)) {
         $filename_array = array();
         $language_array = array();
         $directory = dir($this->_file_path);
         while ( $entry = $directory->read() ) {
            if ( mb_stristr($entry,'COMMON_') and !mb_stristr($entry,'#') ) {
               $filename_array[] = $entry;
            }
         }
         foreach ($filename_array as $filename) {
            $pos1 = mb_strrpos($filename,'_')+1;
            $pos2 = mb_strpos($filename,'.');
            $language = mb_substr($filename,$pos1,$pos2-$pos1);
            $language_array[] = $language;
         }
         sort($language_array);
         $this->_avialable_languages = $language_array;
      }
      return $this->_avialable_languages;
   }

   function isLanguageAvailable ($lang) {
      $lang_array = $this->getAvailableLanguages();
      return in_array($lang,$lang_array);
   }

   function setDefaultLanguage ($value) {
      $this->_default_language = $value;
   }

   /** get the translation of the messageID in the right language
    * this method returns the translation of the messageID in the right language
    *
    * @param string mode            mode of text encoding
    * @param string MsgID           The MessageID, which should be translated
    * @param string param1          The string %1 in the translated text is replaced by param1
    * @param string param2          see param1
    * @param string param3          see param1
    * @param string param4          see param1
    * @param string param5          see param1
    *
    * @return string the translated text
    */
   function getMessage($MsgID, $param1='', $param2='', $param3='', $param4='', $param5='') {
      if ( !empty($this->_selected_language) ) {
         $retour = $this->getMessageInLang($this->_selected_language,$MsgID,$param1,$param2,$param3,$param4,$param5);
      } else {
         include_once('functions/error_functions.php');
         trigger_error('no selected language is set',E_USER_WARNING);
         $retour = $MsgID;
      }
      return $retour;
   }

   /** get a text of a message in a particular language
    * this method returns the translated MessageID
    *
    * @param string mode            mode of text encoding
    * @param string language        The particular language
    * @param string MsgID           The MessageID, which should be translated
    * @param string param1          The string %1 in the translated text is replaced by param1
    * @param string param2          see param1
    * @param string param3          see param1
    * @param string param4          see param1
    * @param string param5          see param1
    *
    * @return string the translation of MsgID
    */
   function getMessageInLang($language, $MsgID, $param1='', $param2='', $param3='', $param4='', $param5='') {
      if ( $this->_issetSessionLanguage() ) {
         $language = $this->_getSessionLanguage();
      }
      if ( $this->_dont_resolve_messagetags or $language == 'no_trans' ) {
         $text = $MsgID;
      } else {

         if (!$this->isLanguageAvailable($language)) {
            $language = $this->_default_language;
         }

         // load message.dat
         if ( !isset($this->_message_array[$MsgID][$language]) ) {
            $this->_loadMessages($this->_getRubricOutMessageTag($MsgID),$language);
         }

         if ( isset($this->_message_array[$MsgID][$language]) ) {
            $text = $this->_message_array[$MsgID][$language];
            $text = $this->text_replace($text,$param1,$param2,$param3,$param4,$param5);
         } else {
            $text = $MsgID;
         }
      }
      return $text;
   }

   /** get the translation of the email message in the right language
    * this method returns the translation of the email text in the right language
    * just from the current room or default
    *
    * @param string mode            mode of text encoding
    * @param string MsgID           The MessageID, which should be translated
    * @param string param1          The string %1 in the translated text is replaced by param1
    * @param string param2          see param1
    * @param string param3          see param1
    * @param string param4          see param1
    * @param string param5          see param1
    *
    * @return string the translated text
    */
   function getEmailMessage ($MsgID, $param1='', $param2='', $param3='', $param4='', $param5='') {
      if ( !empty($this->_selected_language) ) {
         $retour = $this->getEmailMessageInLang($this->_selected_language,$MsgID,$param1,$param2,$param3,$param4,$param5);
      } else {
         include_once('functions/error_functions.php');trigger_error('no selected language is set',E_USER_WARNING);
         $retour = $MsgID;
      }
      return $retour;
   }

   function getEmailMessageInLang ($language, $MsgID, $param1='', $param2='', $param3='', $param4='', $param5='') {
      if ( $this->_issetSessionLanguage() ) {
         $language = $this->_getSessionLanguage();
      }
      if (!empty($this->_email_array[$MsgID][mb_strtoupper($language, 'UTF-8')])) {
         $retour = $this->text_replace($this->_email_array[$MsgID][mb_strtoupper($language, 'UTF-8')],$param1,$param2,$param3,$param4,$param5);
      } elseif (!empty($this->_email_array[$MsgID][mb_strtolower($language, 'UTF-8')])) {
         $retour = $this->text_replace($this->_email_array[$MsgID][mb_strtolower($language, 'UTF-8')],$param1,$param2,$param3,$param4,$param5);
      } else {
         if ($this->_inProjectRoom()) {
            switch ( $MsgID )
            {
               case 'MAIL_BODY_CIAO':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_CIAO_PR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_HELLO':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_HELLO_PR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_ACCOUNT_DELETE':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_DELETE_PR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_ACCOUNT_LOCK':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_LOCK_PR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_MAKE_CONTACT_PERSON':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_MAKE_CONTACT_PERSON_PR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_STATUS_MODERATOR':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_STATUS_MODERATOR_PR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_STATUS_USER':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_STATUS_USER_PR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON_PR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'EMAIL_BODY_PASSWORD_EXPIRATION':
                  $retour = $this->getMessageInLang($language, 'EMAIL_BODY_PASSWORD_EXPIRATION', $param1, $param2, $param3, $param4, $param5);
                  break;
               default:
                  $retour = $this->getMessageInLang($language, $MsgID, $param1, $param2, $param3, $param4, $param5);
                  break;
            }
         } elseif ($this->_inCommunityRoom()) {
            switch ( $MsgID )
            {
               case 'MAIL_BODY_CIAO':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_CIAO_GR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_HELLO':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_HELLO_GR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_MATERIAL_NOT_WORLDPUBLIC':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_MATERIAL_NOT_WORLDPUBLIC_GR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_MATERIAL_WORLDPUBLIC':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_MATERIAL_WORLDPUBLIC_GR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_ROOM_LOCK':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_ROOM_LOCK_GR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_ROOM_UNLINK':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_ROOM_UNLINK_GR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_ROOM_UNLOCK':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_ROOM_UNLOCK_GR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_ACCOUNT_DELETE':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_DELETE_GR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_ACCOUNT_LOCK':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_LOCK_GR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_ACCOUNT_MERGE':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_MERGE_GR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_ACCOUNT_PASSWORD':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_PASSWORD_GR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_MAKE_CONTACT_PERSON':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_MAKE_CONTACT_PERSON_GR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_STATUS_MODERATOR':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_STATUS_MODERATOR_GR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_STATUS_USER':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_STATUS_USER_GR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON_GR', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'EMAIL_BODY_PASSWORD_EXPIRATION':
                  $retour = $this->getMessageInLang($language, 'EMAIL_BODY_PASSWORD_EXPIRATION', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'EMAIL_CHOICE_PASSWORD_EXPIRATION_SOON':
                  $retour = $this->getMessageInLang($language, 'EMAIL_CHOICE_PASSWORD_EXPIRATION_SOON', $param1, $param2, $param3, $param4, $param5);
                  break;
               default:
                  $retour = $this->getMessageInLang($language, $MsgID, $param1, $param2, $param3, $param4, $param5);
                  break;
            }
         } elseif ($this->_inGroupRoom()) {
            switch ( $MsgID )
            {
               case 'MAIL_BODY_CIAO':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_CIAO_GP', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_HELLO':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_HELLO_GP', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_ACCOUNT_DELETE':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_DELETE_GP', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_ACCOUNT_LOCK':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_LOCK_GP', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_STATUS_MODERATOR':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_STATUS_MODERATOR_GP', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_STATUS_USER':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_STATUS_USER_GP', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_MAKE_CONTACT_PERSON':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_MAKE_CONTACT_PERSON_GP', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON_GP', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'EMAIL_BODY_PASSWORD_EXPIRATION':
                  $retour = $this->getMessageInLang($language, 'EMAIL_BODY_PASSWORD_EXPIRATION', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'EMAIL_CHOICE_PASSWORD_EXPIRATION_SOON':
                  $retour = $this->getMessageInLang($language, 'EMAIL_CHOICE_PASSWORD_EXPIRATION_SOON', $param1, $param2, $param3, $param4, $param5);
                  break;
               default:
                  $retour = $this->getMessageInLang($language, $MsgID, $param1, $param2, $param3, $param4, $param5);
                  break;
            }
         } else {
            switch ( $MsgID )
            {
               case 'MAIL_BODY_CIAO':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_CIAO_PO', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_HELLO':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_HELLO_PO', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_ROOM_DELETE':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_ROOM_DELETE_PO', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_ROOM_LOCK':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_ROOM_LOCK_PO', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_ROOM_OPEN':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_ROOM_OPEN_PO', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_ROOM_UNDELETE':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_ROOM_UNDELETE_PO', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_ROOM_UNLOCK':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_ROOM_UNLOCK_PO', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_ACCOUNT_DELETE':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_DELETE_PO', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_ACCOUNT_LOCK':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_LOCK_PO', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_ACCOUNT_MERGE':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_MERGE_PO', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_ACCOUNT_PASSWORD':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_ACCOUNT_PASSWORD_PO', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_MAKE_CONTACT_PERSON':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_MAKE_CONTACT_PERSON_PO', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_PASSWORD_CHANGE':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_PASSWORD_CHANGE_PO', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_STATUS_MODERATOR':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_STATUS_MODERATOR_PO', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_STATUS_USER':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_STATUS_USER_PO', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON':
                  $retour = $this->getMessageInLang($language, 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON_PO', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'EMAIL_BODY_PASSWORD_EXPIRATION_SOON':
                  $retour = $this->getMessageInLang($language, 'EMAIL_PASSWORD_EXPIRATION_SOON_BODY', $param1, $param2, $param3, $param4, $param5);
                  break;
               case 'EMAIL_BODY_PASSWORD_EXPIRATION':
                  $retour = $this->getMessageInLang($language, 'EMAIL_PASSWORD_EXPIRATION_BODY', $param1, $param2, $param3, $param4, $param5);
                  break;
               default:
                  $retour = $this->getMessageInLang($language, $MsgID, $param1, $param2, $param3, $param4, $param5);
                  break;
            }
         }
      }
      return $retour;
   }

   function getTimeMessage ($MsgID) {
      if ( !empty($this->_selected_language) ) {
         $retour = $this->getTimeMessageInLang($this->_selected_language,$MsgID);
      } else {
         include_once('functions/error_functions.php');
         trigger_error('no selected language is set',E_USER_WARNING);
         $retour = $MsgID;
      }
      return $retour;
   }

   function getTimeMessageInLang ($language, $MsgID) {
      if ( $this->_issetSessionLanguage() ) {
         $language = $this->_getSessionLanguage();
      }
      $retour = $MsgID;
      if (!$this->isLanguageAvailable($language)) {
         $language = $this->_default_language;
      }
      $msg_array = explode('_',$MsgID);
      $year_small_temp = $msg_array[0];
      $year_small = $year_small_temp[2].$year_small_temp[3];
      $year_small_plus = $year_small+1;
      if ($year_small_plus == 100) {
          $year_small_plus = '00';
      }
      if ($year_small_plus < 10) {
          $year_small_plus = '0'.$year_small_plus;
      }
      $year_small_minus = $year_small-1;
      if ($year_small_minus == -1) {
          $year_small_minus = '99';
      }
      if ($year_small_minus < 10) {
          $year_small_minus = '0'.$year_small_minus;
      }
      if (isset($msg_array[1]) and !empty($this->_time_message_array[$msg_array[1]][mb_strtoupper($language, 'UTF-8')])) {
         $retour = $this->text_replace($this->_time_message_array[$msg_array[1]][mb_strtoupper($language, 'UTF-8')],$msg_array[0],$msg_array[0]+1,$msg_array[0]-1,$year_small,$year_small_plus,$year_small_minus);
      }
      return $retour;
   }


   /** dontResolveMessageTags
    * this methode set the flag to: DONT RESOLVE MESSAGETAGS
    */
   function dontResolveMessageTags () {
      $this->_dont_resolve_messagetags = true;
   }

   /** setDBConnector
    * this methode set the class to connect the database
    *
    * @param class for connecting the database
    */
   function setDBConnector ($value) {
      $this->_db_connector = $value;
   }

   /** setSelectedLanguage
    * this methode set the selected language, form environment
    *
    * @param string language (de,en,...)
    */
   function setSelectedLanguage ($value) {
      if (!$this->isLanguageAvailable($value)) {
         $value = $this->_default_language;
      }
      $this->_selected_language = $value;
   }

   /** getSelectedLanguage
    * this methode get the selected language
    *
    * @return string language (de,en,...)
    */
   function getSelectedLanguage () {
      return $this->_selected_language;
   }

   /** setSessionLanguage
    * this methode set the session language, form environment
    *
    * @param string language (de,en,...)
    */
   public function setSessionLanguage ($value) {
      $this->_session_language = $value;
   }

   /** getSelectedLanguage
    * this methode get the selected language
    *
    * @return string language (de,en,...)
    */
   private function _getSessionLanguage () {
      return $this->_session_language;
   }

   private function _issetSessionLanguage () {
      $retour = false;
      if ( !empty($this->_session_language) ) {
         $retour = true;
      }
      return $retour;
   }

   /** setContext
    * this methode set the context (community or project)
    *
    * @param string context
    */
   function setContext ($value) {
      $this->_context = (string)$value;
   }

   function _inCommunityRoom () {
      $retour = false;
      if ( isset($this->_context) and $this->_context == 'community' ) {
         $retour = true;
      }
      return $retour;
   }

   function _inProjectRoom () {
      $retour = false;
      if ( isset($this->_context) and $this->_context == 'project' ) {
         $retour = true;
      }
      return $retour;
   }

   function _inGroupRoom () {
      $retour = false;
      if ( isset($this->_context) and $this->_context == CS_GROUPROOM_TYPE ) {
         $retour = true;
      }
      return $retour;
   }

   function initFromContext ( $context_item ) {
      if ($context_item->isCommunityRoom()) {
         $this->setContext('community');
         $portal_item = $context_item->getContextItem();
         $this->setTimeMessageArray($portal_item->getTimeTextArray());
      } elseif ($context_item->isProjectRoom()) {
         $this->setContext('project');
         $portal_item = $context_item->getContextItem();
         $this->setTimeMessageArray($portal_item->getTimeTextArray());
      } elseif ($context_item->isGroupRoom()) {
         $this->setContext(CS_GROUPROOM_TYPE);
         $portal_item = $context_item->getContextItem();
         $this->setTimeMessageArray($portal_item->getTimeTextArray());
      } elseif ($context_item->isPrivateRoom()) {
         $this->setContext('private');
         $portal_item = $context_item->getContextItem();
         $this->setTimeMessageArray($portal_item->getTimeTextArray());
      } elseif ($context_item->isPortal()) {
         $this->setContext('portal');
         $this->setTimeMessageArray($context_item->getTimeTextArray());
      } else {
         $this->setContext('server');
      }
      $this->setRubricTranslationArray($context_item->getRubricTranslationArray());
      $this->setEmailTextArray($context_item->getEmailTextArray());
   }

   /** setRubricTranslationArray
    * this methode set the special rubric names, get from current room
    *
    * @param array special rubric names
    */
   function setRubricTranslationArray ($value) {
      $this->_rubric_translation_array = (array)$value;
   }

   /** setEmailTextArray
    * this methode set the special email text, get from current room
    *
    * @param array email text
    */
   function setEmailTextArray ($value) {
      $this->_email_array = (array)$value;
   }

   /** setTimeMessageArray
    * this methode set the special time messages, get from current portal
    *
    * @param array time messages
    */
   function setTimeMessageArray ($value) {
      $this->_time_message_array = (array)$value;
   }

   /** setMessageArray
    * this methode set the message array, needed in language_edit
    *
    * @param array message_array
    */
   function setMessageArray ($value) {
      $this->_message_array = (array)$value;
   }

   /** replace %x in text
    * this method returns the replaced text
    *
    * @param string text            The MessageID, which should be translated
    * @param string param1          The string %1 in the translated text is replaced by param1
    * @param string param2          see param1
    * @param string param3          see param1
    * @param string param4          see param1
    * @param string param5          see param1
    * @param string param6          see param1
    *
    * @return string the replaced text
    */
   function text_replace ($text, $param1='', $param2='', $param3='', $param4='', $param5='', $param6='') {
      $text = $this->tag_replace($text);
      if ( $param1 !== '') {
         $text = str_replace('%1', (string)$param1, $text);
      }
      if ( $param2 !== '') {
         $text = str_replace('%2', (string)$param2, $text);
      }
      if ( $param3 !== '') {
         $text = str_replace('%3', (string)$param3, $text);
      }
      if ( $param4 !== '') {
         $text = str_replace('%4', (string)$param4, $text);
      }
      if ( $param5 !== '') {
         $text = str_replace('%5', (string)$param5, $text);
      }
      if ( $param6 !== '') {
         $text = str_replace('%6', (string)$param6, $text);
      }
      return $text;
   }

   /** replace placeholders for dynamic module-declarations
    * this method returns the replaced text
    *
    * @param string text           The Messagetext, which is to be translated
    *
    * @return string the replaced text
    */
   function tag_replace($text) {
      // filling the array $placeholders with the occurring placeholder strings
      preg_match_all('~%(?:_[A-Z0-9]+)+~u', $text, $placeholders);

      // if placeholders were found, explode them into their sub-elements
      if (count($placeholders[0]) > 0){
         $i=0;

         foreach ($placeholders[0] AS $placeholder) {
            $placeholder_elements = explode('_',$placeholder);

            // get the replacement strings for the placeholders
            if ($placeholder_elements[2] == 'ART') {
               $tags[$i++] = $this->_getRubricNameArticle(Module2Type($placeholder_elements[1]),
                                                  $placeholder_elements[3],
                                                  $placeholder_elements[4],
                                                  $placeholder_elements[5]);
            } else {
               $tags[$i++] = $this->_getRubricName(Module2Type($placeholder_elements[1]),
                                                   $placeholder_elements[3],
                                                   $placeholder_elements[4]);
               if ( !empty($placeholder_elements[5])
                    and $placeholder_elements[5] == 'ADJ'
                    and !empty($placeholder_elements[1])
                  ) {
                  $upper_lower = '';
                  if ( !empty($placeholder_elements[7]) ) {
                     $upper_lower = $placeholder_elements[7];
                  }
                  $tags[($i-1)] = $this->_getRubricAdjective(Module2Type($placeholder_elements[1]),$placeholder_elements[6],$upper_lower).$tags[($i-1)];
               }
            }
         }

         // replace the placeholders with their corresponding replacement strings
         $new_text = str_replace($placeholders[0], $tags, $text);
         return $new_text;
      }
      // if no placeholders were found, return the untouched text
      else {
         return $text;
      }
   }

   private function _getRubricGenus ( $rubric ) {
      $retour = '';
      if ( !empty($rubric) ) {
         $rubric_array = $this->_getRubricArray($rubric);
         if ( !empty($rubric_array) ) {
            $language = '';
            if ( $this->_issetSessionLanguage() ) {
               $language = $this->_getSessionLanguage();
            } else {
               $language = $this->_selected_language;
            }
            if ( !empty($language)
                 and !empty($rubric_array[mb_strtoupper($language)]['GENUS'])
               ) {
               $retour = $rubric_array[mb_strtoupper($language)]['GENUS'];
            }
         }

      }
      return $retour;
   }

   private function _getRubricAdjective ( $rubric, $adjective, $upper_case = '') {
      $retour = '';
      if ( !empty($rubric)
           and !empty($adjective)
         ) {
         $genus = $this->_getRubricGenus($rubric);
         $adjective_array = $this->_getAdjectiveArray();
         $language = '';
         if ( $this->_issetSessionLanguage() ) {
            $language = $this->_getSessionLanguage();
         } else {
            $language = $this->_selected_language;
         }
         if ( !empty($genus)
              and !empty($adjective_array)
              and !empty($language)
              and !empty($adjective_array[mb_strtoupper($adjective)][mb_strtoupper($language)][mb_strtoupper($genus)])
            ) {
            $adjective_tranlsation = $adjective_array[mb_strtoupper($adjective)][mb_strtoupper($language)][mb_strtoupper($genus)];
            if ( !empty($adjective_tranlsation) ) {
               if ($upper_case == 'BIG') {
                  include_once('functions/text_functions.php');
                  $adjective_tranlsation = cs_ucfirst($adjective_tranlsation);
               } elseif ($upper_case == 'LOW') {
                  include_once('functions/text_functions.php');
                  $adjective_tranlsation = cs_ucfirst($adjective_tranlsation);
               }
               $retour = $adjective_tranlsation.' ';
            }
         }
      }
      return $retour;
   }

   private function _getAdjectiveArray () {
      $retour = array();
      $retour['NEW']['DE']['F'] = $this->getMessageInLang('DE','COMMON_NEW_F');
      $retour['NEW']['DE']['M'] = $this->getMessageInLang('DE','COMMON_NEW_M');;
      $retour['NEW']['DE']['N'] = $this->getMessageInLang('DE','COMMON_NEW_N');;
      $retour['NEW']['EN']['F'] = $this->getMessageInLang('EN','COMMON_NEW_F');;
      $retour['NEW']['EN']['M'] = $this->getMessageInLang('EN','COMMON_NEW_M');;
      $retour['NEW']['EN']['N'] = $this->getMessageInLang('EN','COMMON_NEW_N');;
      return $retour;
   }

   /** get _getRubricArray - INTERNAL
    * this method gets the stored rubric array for one rubric
    *
    * @param string rubric
    * @param string mode for text encode
    *
    * @return array value name cases
    */
   function _getRubricArray($rubric) {
      if ( !empty($this->_rubric_translation_array)
           and !empty($rubric)
           and !empty($this->_rubric_translation_array[cs_strtoupper($rubric)])
         ) {
         $retour = $this->_rubric_translation_array[cs_strtoupper($rubric)];
      } else {
         $rubric_array['NAME'] = 'rubrics';
         $rubric_array['DE']['GENUS']= 'F';
         $rubric_array['DE']['NOMS']= 'Rubrik';
         $rubric_array['DE']['GENS']= 'Rubrik';
         $rubric_array['DE']['AKKS']= 'Rubrik';
         $rubric_array['DE']['DATS']= 'Rubrik';
         $rubric_array['DE']['NOMPL']= 'Rubriken';
         $rubric_array['DE']['GENPL']= 'Rubriken';
         $rubric_array['DE']['AKKPL']= 'Rubriken';
         $rubric_array['DE']['DATPL']= 'Rubriken';
         $rubric_array['EN']['GENUS']= 'F';
         $rubric_array['EN']['NOMS']= 'rubric';
         $rubric_array['EN']['GENS']= 'rubric';
         $rubric_array['EN']['AKKS']= 'rubric';
         $rubric_array['EN']['DATS']= 'rubric';
         $rubric_array['EN']['NOMPL']= 'rubrics';
         $rubric_array['EN']['GENPL']= 'rubrics';
         $rubric_array['EN']['AKKPL']= 'rubrics';
         $rubric_array['EN']['DATPL']= 'rubrics';
         $rubric_array['RU']['GENUS']= 'F';
         $rubric_array['RU']['NOMS']= 'rubrica';
         $rubric_array['RU']['GENS']= 'rubricii';
         $rubric_array['RU']['AKKS']= 'rubrica';
         $rubric_array['RU']['DATS']= 'rubricii';
         $rubric_array['RU']['NOMPL']= 'rubricile';
         $rubric_array['RU']['GENPL']= 'rubricilor';
         $rubric_array['RU']['AKKPL']= 'rubricile';
         $rubric_array['RU']['DATPL']= 'rubricilor';
         $retour = $rubric_array;
      }
      return $retour;
   }

   /** get _getRubricName - INTERNAL
    * this method gets the rubric name
    *
    * @param string rubric
    * @param string postion
    * @param string first letter BIG or not
    *
    * @return array value name cases
    */
   function _getRubricName ($rubric, $position, $upper_case) {
      $rubric_array = $this->_getRubricArray($rubric);
      if ( $this->_issetSessionLanguage() ) {
         $language = $this->_getSessionLanguage();
      } else {
         $language = $this->_selected_language;
      }
      if (isset($rubric_array[cs_strtoupper($language)][cs_strtoupper($position)])){
         $text = $rubric_array[cs_strtoupper($language)][cs_strtoupper($position)];
      } else {
         $text = 'rubric';
      }
      if ($upper_case == 'BIG') {
         include_once('functions/text_functions.php');
         $text = cs_ucfirst($text);
      }
      return $text;
   }

   /** get _getRubricNameArticle - INTERNAL
    * this method gets the rubric article
    *
    * @param string rubric
    * @param string def or undef
    * @param string postion
    * @param string first letter BIG or not
    *
    * @return array value name cases
    */
   function _getRubricNameArticle ($rubric, $mode, $position, $upper_case) {
      // default article arrays
      $cs_article['DE']['DEF']['M']['NOMS'] = 'der';
      $cs_article['DE']['DEF']['M']['GENS'] = 'des';
      $cs_article['DE']['DEF']['M']['AKKS'] = 'den';
      $cs_article['DE']['DEF']['M']['DATS'] = 'dem';
      $cs_article['DE']['DEF']['M']['NOMPL'] = 'die';
      $cs_article['DE']['DEF']['M']['GENPL'] = 'der';
      $cs_article['DE']['DEF']['M']['AKKPL'] = 'die';
      $cs_article['DE']['DEF']['M']['DATPL'] = 'den';

      $cs_article['DE']['DEF']['F']['NOMS'] = 'die';
      $cs_article['DE']['DEF']['F']['GENS'] = 'der';
      $cs_article['DE']['DEF']['F']['AKKS'] = 'die';
      $cs_article['DE']['DEF']['F']['DATS'] = 'der';
      $cs_article['DE']['DEF']['F']['NOMPL'] = 'die';
      $cs_article['DE']['DEF']['F']['GENPL'] = 'der';
      $cs_article['DE']['DEF']['F']['AKKPL'] = 'die';
      $cs_article['DE']['DEF']['F']['DATPL'] = 'den';

      $cs_article['DE']['DEF']['N']['NOMS'] = 'das';
      $cs_article['DE']['DEF']['N']['GENS'] = 'des';
      $cs_article['DE']['DEF']['N']['AKKS'] = 'das';
      $cs_article['DE']['DEF']['N']['DATS'] = 'dem';
      $cs_article['DE']['DEF']['N']['NOMPL'] = 'die';
      $cs_article['DE']['DEF']['N']['GENPL'] = 'der';
      $cs_article['DE']['DEF']['N']['AKKPL'] = 'die';
      $cs_article['DE']['DEF']['N']['DATPL'] = 'den';

      $cs_article['DE']['UNDEF']['M']['NOMS'] = 'ein';
      $cs_article['DE']['UNDEF']['M']['GENS'] = 'eines';
      $cs_article['DE']['UNDEF']['M']['AKKS'] = 'einen';
      $cs_article['DE']['UNDEF']['M']['DATS'] = 'einem';

      $cs_article['DE']['UNDEF']['F']['NOMS'] = 'eine';
      $cs_article['DE']['UNDEF']['F']['GENS'] = 'einer';
      $cs_article['DE']['UNDEF']['F']['AKKS'] = 'eine';
      $cs_article['DE']['UNDEF']['F']['DATS'] = 'einer';

      $cs_article['DE']['UNDEF']['N']['NOMS'] = 'ein';
      $cs_article['DE']['UNDEF']['N']['GENS'] = 'eines';
      $cs_article['DE']['UNDEF']['N']['AKKS'] = 'ein';
      $cs_article['DE']['UNDEF']['N']['DATS'] = 'einem';

      $cs_article['EN'] = 'the';
      $rubric_array = $this->_getRubricArray($rubric);
      $language = cs_strtoupper($this->_selected_language);
      if ( $this->_issetSessionLanguage() ) {
         $language = cs_strtoupper($this->_getSessionLanguage());
      } else {
         $language = cs_strtoupper($this->_selected_language);
      }
      if ($language == 'EN') {
         $text = $cs_article[$language];
      } else {
         $text = $cs_article[$language][$mode][$rubric_array[$language]['GENUS']][cs_strtoupper($position)];
      }
      if ($upper_case == 'BIG') {
         include_once('functions/text_functions.php');
         $text = cs_ucfirst($text);
      }
      return $text;
   }

   function getDateTimeInLang($datetime, $oclock=true){
      $date = $this->_getDateTimeInLang ($datetime, $oclock);
      $date = mb_eregi_replace('/',' ',$date);
      return $date;
   }

   /** translate a Date and Time from a MYSQL-datetime depending on selectet language
    */
   function _getDateTimeInLang ($datetime, $oclock=true) {
      $language = $this->_selected_language;
      if ( $this->_issetSessionLanguage() ) {
         $language = $this->_getSessionLanguage();
      }
      $length = mb_strlen($datetime);

      if (mb_substr_count($datetime,'-') == 2) {
         $year  = $datetime[0].$datetime[1].$datetime[2].$datetime[3];
         $month = $datetime[5].$datetime[6];
         $day   = $datetime[8].$datetime[9];
         if ($length > 12) {
            $hour = $datetime[11].$datetime[12];
         } else {
            $hour = '00';
         }
         if ($length > 15) {
            $min = $datetime[14].$datetime[15];
         } else {
            $min = '00';
         }
         //$sec   = $datetime[17].$datetime[18];
      } elseif (!empty($datetime)) {
         $year  = $datetime[0].$datetime[1].$datetime[2].$datetime[3];
         $month = $datetime[4].$datetime[5];
         $day   = $datetime[6].$datetime[7];
         $hour  = $datetime[8].$datetime[9];
         $min   = $datetime[10].$datetime[11];
      } else {
         $year  = '';
         $month = '';
         $day   = '';
         $hour  = '';
         $min   = '';
      }

      //create datetime depends on language
      if ($language == 'en') {
         $ampm = 'am';
         if ($hour > 12) {
            $hour = $hour-12;
            $ampm = 'pm';
         } elseif ($hour == 12) {
            $ampm = 'pm';
         }
         if (mb_strlen($hour) == 1) {
            $hour = '0'.$hour;
         }
         $Datetime = $day.'/'.$month.'/'.$year.' '.$hour.':'.$min.$ampm;
      } elseif ($language == 'de') {
         $Datetime = $day.'.'.$month.'.'.$year.' '.$hour.':'.$min;#.':'.$sec;
      }elseif ($language == 'ru') {
         $Datetime = $day.'.'.$month.'.'.$year.' '.$hour.':'.$min;#.':'.$sec;
      }

      $Datetime = mb_eregi_replace(' ',', ',$Datetime);
      if ($language != 'en' and $oclock) {
         $Datetime = $Datetime.' '.$this->getMessage('DATES_OCLOCK');
      }

      return $Datetime;
   }

function getShortMonthName($month) {
   switch($month) {
     case '01': $ret = $this->getMessage('COMMON_DATE_JANUARY_SHORT'); break;
     case '02': $ret = $this->getMessage('COMMON_DATE_FEBRUARY_SHORT'); break;
     case '03': $ret = $this->getMessage('COMMON_DATE_MARCH_SHORT'); break;
     case '04': $ret = $this->getMessage('COMMON_DATE_APRIL_SHORT'); break;
     case '05': $ret = $this->getMessage('COMMON_DATE_MAY_SHORT'); break;
     case '06': $ret = $this->getMessage('COMMON_DATE_JUNE_SHORT'); break;
     case '07': $ret = $this->getMessage('COMMON_DATE_JULY_SHORT'); break;
     case '08': $ret = $this->getMessage('COMMON_DATE_AUGUST_SHORT'); break;
     case '09': $ret = $this->getMessage('COMMON_DATE_SEPTEMBER_SHORT'); break;
     case '10': $ret = $this->getMessage('COMMON_DATE_OCTOBER_SHORT'); break;
     case '11': $ret = $this->getMessage('COMMON_DATE_NOVEMBER_SHORT'); break;
     case '12': $ret = $this->getMessage('COMMON_DATE_DECEMBER_SHORT'); break;
     default : $ret ='';
   }
   return $ret;
}

function getShortMonthNameToInt($month) {
   switch($month) {
     case $this->getMessage('COMMON_DATE_JANUARY_SHORT'): $ret = '01'; break;
     case $this->getMessage('COMMON_DATE_FEBRUARY_SHORT'): $ret = '02'; break;
     case $this->getMessage('COMMON_DATE_MARCH_SHORT'): $ret = '03'; break;
     case $this->getMessage('COMMON_DATE_APRIL_SHORT'): $ret = '04'; break;
     case $this->getMessage('COMMON_DATE_MAY_SHORT'): $ret = '05'; break;
     case $this->getMessage('COMMON_DATE_JUNE_SHORT'): $ret = '06'; break;
     case $this->getMessage('COMMON_DATE_JULY_SHORT'): $ret = '07'; break;
     case $this->getMessage('COMMON_DATE_AUGUST_SHORT'): $ret = '08'; break;
     case $this->getMessage('COMMON_DATE_SEPTEMBER_SHORT'): $ret = '09'; break;
     case $this->getMessage('COMMON_DATE_OCTOBER_SHORT'): $ret = '10'; break;
     case $this->getMessage('COMMON_DATE_NOVEMBER_SHORT'): $ret = '11'; break;
     case $this->getMessage('COMMON_DATE_DECEMBER_SHORT'): $ret = '12'; break;
     case $this->getMessage('COMMON_DATE_JANUARY_LONG'): $ret = '01'; break;
     case $this->getMessage('COMMON_DATE_FEBRUARY_LONG'): $ret = '02'; break;
     case $this->getMessage('COMMON_DATE_MARCH_LONG'): $ret = '03'; break;
     case $this->getMessage('COMMON_DATE_APRIL_LONG'): $ret = '04'; break;
     case $this->getMessage('COMMON_DATE_MAY_LONG'): $ret = '05'; break;
     case $this->getMessage('COMMON_DATE_JUNE_LONG'): $ret = '06'; break;
     case $this->getMessage('COMMON_DATE_JULY_LONG'): $ret = '07'; break;
     case $this->getMessage('COMMON_DATE_AUGUST_LONG'): $ret = '08'; break;
     case $this->getMessage('COMMON_DATE_SEPTEMBER_LONG'): $ret = '09'; break;
     case $this->getMessage('COMMON_DATE_OCTOBER_LONG'): $ret = '10'; break;
     case $this->getMessage('COMMON_DATE_NOVEMBER_LONG'): $ret = '11'; break;
     case $this->getMessage('COMMON_DATE_DECEMBER_LONG'): $ret = '12'; break;
     default : $ret = $month;
   }
   return $ret;
}

   function getDateTimeInLangWithoutOClock ($datetime, $oclock=true){
      $date = $this->_getDateTimeInLangWithoutOClock ($datetime, $oclock);
      $date = mb_eregi_replace('/',' ',$date);
      return $date;
   }

   /** translate a Date and Time from a MYSQL-datetime depending on selectet language
    */
   function _getDateTimeInLangWithoutOClock ($datetime, $oclock=true) {
      $language = $this->_selected_language;
      if ( $this->_issetSessionLanguage() ) {
         $language = $this->_getSessionLanguage();
      }
      $length = mb_strlen($datetime);

      if (mb_substr_count($datetime,'-') == 2) {
         $year  = $datetime[0].$datetime[1].$datetime[2].$datetime[3];
         $month = $datetime[5].$datetime[6];
         $day   = $datetime[8].$datetime[9];
         if ($length > 12) {
            $hour = $datetime[11].$datetime[12];
         } else {
            $hour = '00';
         }
         if ($length > 15) {
            $min = $datetime[14].$datetime[15];
         } else {
            $min = '00';
         }
         //$sec   = $datetime[17].$datetime[18];
      } elseif (!empty($datetime)) {
         $year  = $datetime[0].$datetime[1].$datetime[2].$datetime[3];
         $month = $datetime[4].$datetime[5];
         $day   = $datetime[6].$datetime[7];
         $hour  = $datetime[8].$datetime[9];
         $min   = $datetime[10].$datetime[11];
      } else {
         $year  = '';
         $month = '';
         $day   = '';
         $hour  = '';
         $min   = '';
      }

      //create datetime depends on language
      if ($language == 'en') {
         $ampm = 'am';
         if ($hour > 12) {
            $hour = $hour-12;
            $ampm = 'pm';
         } elseif ($hour == 12) {
            $ampm = 'pm';
         }
         if (mb_strlen($hour) == 1) {
            $hour = '0'.$hour;
         }
         $Datetime = $day.'/'.$this->getShortMonthName($month).'/'.$year.' '.$hour.':'.$min.$ampm;
      } elseif ($language == 'de') {
         $Datetime = $day.'.'.$month.'.'.$year.' '.$hour.':'.$min;#.':'.$sec;
      }elseif ($language == 'ru') {
         $Datetime = $day.'.'.$month.'.'.$year.' '.$hour.':'.$min;#.':'.$sec;
      }

      $Datetime = mb_eregi_replace(' ',', ',$Datetime);
      return $Datetime;
   }

   /** translate a Time from a MYSQL-datetime depending on selectet language
    */
   function getTimeInLang ($datetime) {
      $Time = explode(' ',$this->_getDateTimeInLang($datetime));
      return $Time[1];
   }

   /** translate a Date from a MYSQL-datetime depending on selectet language
    */
   function getDateInLang ($datetime) {
      $Date = explode(' ',$this->_getDateTimeInLang($datetime));
      $Date[0] = mb_eregi_replace(',','',$Date[0]);
      return $Date[0];
   }

   function getDateInLangWithoutOClock($datetime) {
      $Date = explode(' ',$this->_getDateTimeInLangWithoutOClock($datetime));
      $Date[0] = mb_eregi_replace(',','',$Date[0]);
      $Date[0] = mb_eregi_replace('/',' ',$Date[0]);
      return $Date[0];
   }

   /** translate a Time from a time string depending on selectet language
    */
   function getTimeLanguage ($timestring) {
      $language = $this->_selected_language;
      if ( $this->_issetSessionLanguage() ) {
         $language = $this->_getSessionLanguage();
      }

      if (mb_substr_count($timestring,':') == 2) {
         $hour = $timestring[0].$timestring[1];
         $min = $timestring[3].$timestring[4];
      } else {
         $hour = $timestring[0].$timestring[1];
         $min = $timestring[2].$timestring[3];
      }

      //create time depends on language
      if ($language == 'en') {
         $ampm = ' am';
         if ($hour > 12) {
            $hour = $hour-12;
            $ampm = ' pm';
         } else if ($hour == 12) {
            $ampm = ' pm';
         }
         if (mb_strlen($hour) == 1) {
            $hour = '0'.$hour;
         }
         $ret_time = $hour.':'.$min.$ampm;
      } else if ($language == 'de') {
         $ret_time = $hour.':'.$min;
      }else if ($language == 'ru') {
         $ret_time = $hour.':'.$min;
      }
      return $ret_time;
   }

   /** translate a Date from a date string depending on selectet language
    */
   function getDateLanguage ($datestring) {
      $language = $this->_selected_language;
      if ( $this->_issetSessionLanguage() ) {
         $language = $this->_getSessionLanguage();
      }

      if (mb_substr_count($datestring,'-') == 2) {
         $year  = $datestring[0].$datestring[1].$datestring[2].$datestring[3];
         $month = $datestring[5].$datestring[6];
         $day   = $datestring[8].$datestring[9];
      } else {
         $year  = $datestring[0].$datestring[1].$datestring[2].$datestring[3];
         $month = $datestring[4].$datestring[5];
         $day   = $datestring[6].$datestring[7];
      }

      //create datetime depends on language
      if ($language == 'en') {
         $datestring = $month.'/'.$day.'/'.$year;
      } else if ($language == 'de') {
         $datestring = $day.'.'.$month.'.'.$year;
      }else if ($language == 'ru') {
         $datestring = $day.'.'.$month.'.'.$year;
      }

      return $datestring;
   }

   /** getMessageArray
    * this method gets the message array
    *
    * @return array message array
    */
   function getMessageArray () {
      ksort($this->_message_array);
      reset($this->_message_array);
      return $this->_message_array;
   }

   /** getCompleteMessageArray
    * this method gets the complete message array, needed for language edit
    *
    * @return array message array
    */
   function getCompleteMessageArray () {
      $this->_loadAllMessages();
      return $this->getMessageArray();
   }

   public function getLanguageLabelTranslated ( $language ) {
      $retour = '';
      switch ( mb_strtoupper($language, 'UTF-8') )
      {
         case 'DE':
            $retour = $this->getMessage('DE');
            break;
         case 'EN':
            $retour = $this->getMessage('EN');
            break;
      }
      return $retour;
   }

   public function getLanguageLabelOriginally ( $language ) {
      $retour = '';
      switch ( mb_strtoupper($language, 'UTF-8') )
      {
         case 'DE':
            $retour = $this->getMessageInLang($language,'DE');
            break;
         case 'EN':
            $retour = $this->getMessageInLang($language,'EN');
            break;
      }
      return $retour;
   }

   public function setCommSyVersion ( $value ) {
      $this->_version = $value;
   }

   public function getUnusedTags () {
      $used_tags = $this->_searchDirForUsed('./',array());
      sort($used_tags);
      $message = $this->getCompleteMessageArray();

      $tags_not_used = array();
      foreach ($message as $tag_name => $translation) {
         if ( !in_array($tag_name, $used_tags) ) {
            $tags_not_used[] = $tag_name;
         }
      }
      return $tags_not_used;
   }

   private function _searchDirForUsed($directory, $used_tags) {
      $directory_handle  = opendir($directory);

      while ( false !== ($entry = readdir($directory_handle)) ) {
         if ($entry != '.' and $entry != '..' and is_dir($directory.'/'.$entry)) {
            $used_tags = $this->_searchDirForUsed($directory.'/'.$entry, $used_tags);
         } elseif (is_file($directory.'/'.$entry) and preg_match('~\.php$~u',$entry)) {
            $used_tags = $this->_searchFileForUsed($directory.'/'.$entry, $used_tags);
         }
      }
      return $used_tags;
   }

   private function _searchFileForUsed($filename, $used_tags) {
      $file_content = file($filename);

      for($i = 0; $i < count($file_content); $i++) {
         if ( preg_match_all('~getMessage\([\s\S]*\'([A-Z0-9_]+)\'~Uu', $file_content[$i], $matches) ) {
            if (count($matches) > 0) {
               for ($j=0; $j < count($matches[1]); $j++) {
                  if ( mb_strlen($matches[1][$j]) > 1 and !in_array($matches[1][$j],$used_tags) ) {
                     $used_tags[] = $matches[1][$j];
                  }
               }
            }
        }
        if ( preg_match_all('~getMessageInLang\([\s\S]*,\s*\'([A-Z0-9_]+)\'~Uu', $file_content[$i], $matches) ) {
           if (count($matches) > 0) {
              for ($j=0; $j < count($matches[1]); $j++) {
                 if ( mb_strlen($matches[1][$j]) > 1 and !in_array($matches[1][$j],$used_tags) ) {
                    $used_tags[] = $matches[1][$j];
                 }
              }
           }
        }
     }
     return $used_tags;
   }

   public function addMessageDatFolder ( $value ) {
      $this->_dat_folder_array[] = $value;
   }
}
?>