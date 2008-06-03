<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

/** upper class of the context item
 */
include_once('classes/cs_guide_item.php');

/** class for a context
 * this class implements a context item
 */
class cs_server_item extends cs_guide_item {

   /** constructor: cs_server_item
    * the only available constructor, initial values for internal variables
    *
    * @param object environment the environment of the commsy
    */
   function cs_server_item ($environment) {
      $this->cs_guide_item($environment);
      $this->_type = CS_SERVER_TYPE;
#      $this->_default_rubrics_array[0] = CS_PORTAL_TYPE;
#      $this->_default_home_conf_array[CS_PORTAL_TYPE] = 'tiny';
   }

   function isServer () {
      return true;
   }

   /** get default email sender address
    *
    * @return string default email sender address
    */
   function getDefaultSenderAddress () {
      $retour = '';
      if ($this->_issetExtra('DEFAULT_SENDER_ADDRESS')) {
         $retour = $this->_getExtra('DEFAULT_SENDER_ADDRESS');
      }
      return $retour;
   }

   /** set default email sender address
    *
    * @param default email sender address
    */
   function setDefaultSenderAddress ($value) {
      $this->_addExtra('DEFAULT_SENDER_ADDRESS',$value);
   }

   /** get portal list
    * this function returns a list of all portals
    * existing on this commsy server
    *
    * @return list of portals
    */
   function getPortalList () {
      $portal_manager = $this->_environment->getPortalManager();
      $portal_manager->setContextLimit($this->getItemID());
      $portal_manager->select();
      $portal_list = $portal_manager->get();
      return $portal_list;
   }

   /** get portal list
    * this function returns a list of all portals
    * existing on this commsy server
    *
    * @return list of portals
    */
   function getPortalListByActivity () {
      $portal_manager = $this->_environment->getPortalManager();
      $portal_manager->setContextLimit($this->getItemID());
      $portal_manager->setOrder('activity_rev');
      $portal_manager->select();
      $portal_list = $portal_manager->get();
      return $portal_list;
   }

   /** get contact moderator of a room
    * this method returns a list of contact moderator which are linked to the room
    *
    * @return object cs_list a list of contact moderator (cs_label_item)
    */
   function getContactModeratorList() {
     $user_manager = $this->_environment->getUserManager();
     $mod_list = new cs_list();
     $mod_list->add($user_manager->getRootUser());
     return $mod_list;
   }

   #########################################################
   # COMMSY CRON JOBS
   #
   # this cron jobs only works if a daily cron job is
   # configured to run cron.php in /htdocs
   #########################################################

   /** cron daily, INTERNAL
    * here you can link daily cron jobs
    *
    * @return array results of running crons
    */
   function _cronDaily () {
      $cron_array = array();
      $cron_array[] = $this->_cronPageImpression();
      $cron_array[] = $this->_cronLog();
      $cron_array[] = $this->_cronRoomActivity();
      $cron_array[] = $this->_cronReallyDelete();
      $cron_array[] = $this->_cronRemoveTempExportDirectory();
      $cron_array[] = $this->_cronUnlinkFiles();
      return $cron_array;
   }

   function _cronRemoveTempExportDirectory () {
      global $export_temp_folder;
      if ( !isset($export_temp_folder) ) {
         $export_temp_folder = 'var/temp/zip_export';
      }
      $handle = opendir($export_temp_folder);
      //delete sourcefiles from harddisk
      while (false !== ($dir = readdir($handle))) {
         if (($dir != '.') and ($dir != '..')) {
            if ( is_dir('./'.$export_temp_folder.'/'.$dir) ) {
               $handle2 = opendir('./'.$export_temp_folder.'/'.$dir);
               while (false !== ($file = readdir($handle2))) {
                  if (($file != '.') and ($file != '..')) {
                     unlink('./'.$export_temp_folder.'/'.$dir.'/'.$file);
                  }
               }
               closedir($handle2);
               rmdir('./'.$export_temp_folder.'/'.$dir);
            } else {
               unlink('./'.$export_temp_folder.'/'.$dir);
            }
         }
      }
      closedir($handle);

      $cron_array = array();
      $cron_array['title'] = 'remove temporary directories for export cron';
      $cron_array['description'] = 'free space on hard disk';
      $cron_array['success'] = true;
      $cron_array['success_text'] = 'cron done';

      return $cron_array;
   }

   /** cron log, INTERNAL
    *  daily cron, move old log entries to table log_archive
    *
    * @return array results of running this cron
    */
   function _cronPageImpression () {
      $cron_array = array();
      $cron_array['title'] = 'page impression cron';
      $cron_array['description'] = 'count page impressions';
      $cron_array['success'] = true;
      $cron_array['success_text'] = 'cron failed';

      $log_manager = $this->_environment->getLogManager();

      $portal_list = $this->getPortalList();
      $count_rooms = 0;

      if ( $portal_list->isNotEmpty() ) {
         $portal_item = $portal_list->getFirst();
         while ($portal_item) {
            $room_list = $portal_item->getRoomList();
            if ($room_list->isNotEmpty()) {
               $room_item = $room_list->getFirst();
               while ($room_item) {
                  $log_manager->resetLimits();
                  $log_manager->setContextLimit($room_item->getItemID());
                  $pi = $log_manager->getCountAll();

                  $pi_array = $room_item->getPageImpressionArray();
                  array_pop($pi_array);
                  array_unshift($pi_array,$pi);
                  $room_item->setPageImpressionArray($pi_array);
                  $room_item->saveWithoutChangingModificationInformation();

                  $count_rooms++;
                  unset($room_item);
                  $room_item = $room_list->getNext();
               }
            }
            unset($portal_item);
            $portal_item = $portal_list->getNext();
         }
      }

      $cron_array['success_text'] = 'count page impressions of '.$count_rooms.' rooms';
      unset($log_manager);
      unset($portal_list);

      return $cron_array;
   }

   /** cron log, INTERNAL
    *  daily cron, move old log entries to table log_archive
    *
    * @return array results of running this cron
    */
   function _cronLog () {
      $cron_array = array();
      $cron_array['title'] = 'log cron';
      $cron_array['description'] = 'move old logs to log archive';
      $cron_array['success'] = false;
      $cron_array['success_text'] = 'cron failed';

      // get all old (> 100 days) log data in steps to prevent overloading memory
      $log_DB = $this->_environment->getLogManager();
      $log_DB->resetlimits();
      $log_DB->setContextLimit(0);
      $from = 0;
      $range = 50;
      $log_DB->setRangeLimit($from,$range);
      $data_array = $log_DB->select();
      $count = count($data_array);
      if ($count == 0) {
         $cron_array['success'] = true;
         $cron_array['success_text'] = 'nothing to do';
      } else {
         $count_all = 0;
         $log_archive_manager = $this->_environment->getLogArchiveManager();
         while (count($data_array) > 0 ) {
            // save old logs in log archive
            $success = $log_archive_manager->save($data_array);
            if ($success) {
               // delete old logs
               $success = $log_DB->deleteByArray($data_array);
               if ($success) {
                  $cron_array['success'] = true;
                  $count_all = $count_all + count($data_array);
                  $cron_array['success_text'] = 'move '.$count_all.' log entries';
               }
            }
            unset($data_array);
            $data_array = $log_DB->select();
         }
         unset($log_archive_manager);
      }
      unset($log_DB);
      return $cron_array;
   }

   /** cron room activity, INTERNAL
    *  daily cron, minimize activity points
    *
    * @return array results of running this cron
    */
   function _cronRoomActivity () {
      $quotient = 4;
      $cron_array = array();
      $cron_array['title'] = 'activity points cron';
      $cron_array['description'] = 'minimize activity points';
      $cron_array['success'] = false;
      $cron_array['success_text'] = 'cron failed';

      $room_manager = $this->_environment->getRoomManager();
      $success1 = $room_manager->minimizeActivityPoints($quotient);

      $portal_manager = $this->_environment->getPortalManager();
      $success2 = $portal_manager->minimizeActivityPoints($quotient);

      if ( $success1 and $success2 ) {
         $cron_array['success'] = true;
         $cron_array['success_text'] = '';
         if ( $success1 ) {
            $cron_array['success_text'] .= ' in rooms ';
         }
         if ( $success2 ) {
            $cron_array['success_text'] .= ' in portals ';
         }
      }
      unset($portal_manager);
      unset($room_manager);
      return $cron_array;
   }

   /** cron room activity, INTERNAL
   *  daily cron, minimize activity points
   *
   * @return array results of running this cron
   */
   function _cronReallyDelete () {
      $cron_array = array();
      $cron_array['title'] = 'delete items';
      $cron_array['description'] = 'delete items older than 1 month';
      $cron_array['success'] = true;
      $cron_array['success_text'] = '';

      $item_type_array = array();
      $item_type_array[] = CS_ANNOTATION_TYPE;
      $item_type_array[] = CS_ANNOUNCEMENT_TYPE;
      $item_type_array[] = CS_DATE_TYPE;
      $item_type_array[] = CS_DISCUSSION_TYPE;
      #$item_type_array[] = CS_DISCARTICLE_TYPE; // NO NO NO -> because of closed discussions
      $item_type_array[] = CS_LINKITEMFILE_TYPE;
      $item_type_array[] = CS_FILE_TYPE;
      $item_type_array[] = CS_ITEM_TYPE;
      $item_type_array[] = CS_LABEL_TYPE;
      $item_type_array[] = CS_LINK_TYPE;
      $item_type_array[] = CS_LINKITEM_TYPE;
      $item_type_array[] = CS_MATERIAL_TYPE;
      #$item_type_array[] = CS_PORTAL_TYPE; // not implemented yet because than all data (rooms, data in rooms) should be deleted too
      #$item_type_array[] = CS_ROOM_TYPE; // not implemented yet because than all data in rooms should be deleted too
      $item_type_array[] = CS_SECTION_TYPE;
      $item_type_array[] = CS_TAG_TYPE;
      $item_type_array[] = CS_TAG2TAG_TYPE;
      $item_type_array[] = CS_TASK_TYPE;
      $item_type_array[] = CS_TODO_TYPE;
      #$item_type_array[] = CS_USER_TYPE; // NO NO NO -> because of old entries of user

      foreach ($item_type_array as $item_type) {
         $manager = $this->_environment->getManager($item_type);
         global $c_delete_days;
         if ( !empty($c_delete_days) and is_numeric($c_delete_days) ) {
            $success = $manager->deleteReallyOlderThan($c_delete_days);
            $cron_array['success'] = $success and $cron_array['success'];
            $cron_array['success_text'] = 'delete entries in database marked as deleted older than '.$c_delete_days.' days';
         } else {
            $cron_array['success_text'] = 'nothing to do - please activate etc/commsy/settings.php -> c_delete_days if needed';
         }
         unset($manager);
      }
      unset($item_type_array);

      return $cron_array;
   }

   ####################################################################
   # CRON END
   ####################################################################

   /** get UsageInfos
    * this method returns the usage infos
    *
    * @return array
    */
   function getUsageInfoArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO')) {
       $retour = $this->_getExtra('USAGE_INFO');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      return $retour;
   }

   /** set UsageInfos
    * this method sets the usage infos
    *
    * @param array
    */
   function setUsageInfoArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO',$value_array);
      }
   }

   /** set UsageInfos
    * this method sets the usage infos
    *
    * @param array
    */
   function setUsageInfoFormArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_FORM',$value_array);
      }
   }

   /** get UsageInfos
    * this method returns the usage infos
    *
    * @return array
    */
   function getUsageInfoFormArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO_FORM')) {
       $retour = $this->_getExtra('USAGE_INFO_FORM');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      return $retour;
   }


   function getUsageInfoHeaderArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_HEADER');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      return $retour;
   }

   function setUsageInfoHeaderArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_HEADER',$value_array);
      }
   }

   function getUsageInfoFormHeaderArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO_FORM_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM_HEADER');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      return $retour;
   }

   function setUsageInfoFormHeaderArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_FORM_HEADER',$value_array);
      }
   }


   function getUsageInfoTextArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_TEXT');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      return $retour;
   }

   function setUsageInfoTextArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_TEXT',$value_array);
      }
   }

   function getUsageInfoFormTextArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      return $retour;
   }

   function setUsageInfoFormTextArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_FORM_TEXT',$value_array);
      }
   }

   function getUsageInfoHeaderForRubric($rubric){
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_HEADER');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
         $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      if (isset($retour[strtoupper($rubric)]) and !empty($retour[strtoupper($rubric)])){
         $retour = $retour[strtoupper($rubric)];
      } else {
         $retour = getMessage('USAGE_INFO_HEADER');
      }
      return $retour;
   }

   function setUsageInfoHeaderForRubric($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $value_array = $this->_getExtra('USAGE_INFO_HEADER');
       if ( empty($value_array) ) {
         $value_array = array();
       } elseif ( !is_array($value_array) ) {
            $value_array = XML2Array($value_array);
       }
      } else {
         $value_array = array();
      }
      $value_array[strtoupper($rubric)]=$string;
      $this->_addExtra('USAGE_INFO_HEADER',$value_array);
   }

   function getUsageInfoHeaderForRubricForm($rubric){
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_HEADER');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      if (isset($retour[strtoupper($rubric)]) and !empty($retour[strtoupper($rubric)])){
         $retour = $retour[strtoupper($rubric)];
      } else {
         $retour = getMessage('USAGE_INFO_HEADER');
      }
      return $retour;
   }

   function setUsageInfoHeaderForRubricForm($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_FORM_HEADER')) {
         $value_array = $this->_getExtra('USAGE_INFO_FORM_HEADER');
       if ( empty($value_array) ) {
         $value_array = array();
       } elseif ( !is_array($value_array) ) {
            $value_array = XML2Array($value_array);
       }
      } else {
         $value_array = array();
      }
      $value_array[strtoupper($rubric)]=$string;
      $this->_addExtra('USAGE_INFO_FORM_HEADER',$value_array);
   }

   function setUsageInfoTextForRubric($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_TEXT')) {
         $value_array = $this->_getExtra('USAGE_INFO_TEXT');
       if ( empty($value_array) ) {
         $value_array = array();
       } elseif ( !is_array($value_array) ) {
            $value_array = XML2Array($value_array);
       }
      } else {
         $value_array = array();
      }
      $value_array[strtoupper($rubric)]=$string;
      $this->_addExtra('USAGE_INFO_TEXT',$value_array);
   }

   function setUsageInfoTextForRubricForm($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $value_array = $this->_getExtra('USAGE_INFO_FORM_TEXT');
       if ( empty($value_array) ) {
         $value_array = array();
       } elseif ( !is_array($value_array) ) {
            $value_array = XML2Array($value_array);
       }
      } else {
         $value_array = array();
      }
      $value_array[strtoupper($rubric)]=$string;
      $this->_addExtra('USAGE_INFO_FORM_TEXT',$value_array);
   }


   function getUsageInfoTextForRubricForm($rubric){
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
   if ( empty($retour) ) {
      $retour = array();
   } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
   }
      } else {
         $retour = array();
      }
      if (isset($retour[strtoupper($rubric)]) and !empty($retour[strtoupper($rubric)])){
         $retour = $retour[strtoupper($rubric)];
      } else {
         $link = ahref_curl($this->_environment->getCurrentContextID(),
                            'help',
                            'context',
                            array('module'=>$this->_environment->getCurrentModule(),
                                  'function'=>$this->_environment->getCurrentFunction(),
                                  'context'=>'HELP_COMMON_FORMAT'),
                            getMessage('HELP_COMMON_FORMAT_TITLE'),
                            '',
                            'help',
                            '',
                            '',
                            'onclick="window.open(href,
                                                  target,
                                                  \'toolbar=no,
                                                  location=no,
                                                  directories=no,
                                                  status=no,
                                                  menubar=no,
                                                  scrollbars=yes,
                                                  resizable=yes,
                                                  copyhistory=yes,
                                                  width=600,
                                                  height=400
                                                \'
                                               );
                                     "'
                           );
         $temp = strtoupper($rubric) . '_' . strtoupper($funct);
         $tempMessage      = "";
         switch( $temp )
         {
            case 'CONFIGURATION_BACKUP':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_BACKUP_FORM',$link);
               break;
            case 'CONFIGURATION_COLOR':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_COLOR_FORM',$link);
               break;
            case 'CONFIGURATION_EXTRA':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_EXTRA_FORM',$link);
               break;
            case 'CONFIGURATION_IMS':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_IMS_FORM',$link);
               break;
            case 'CONFIGURATION_LANGUAGE':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_LANGUAGE_FORM',$link);
               break;
            case 'CONFIGURATION_NEWS':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_NEWS_FORM',$link);
               break;
            case 'CONFIGURATION_PREFERENCES':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_PREFERENCES_FORM',$link);
               break;
            case 'CONFIGURATION_SERVICE':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_SERVICE_FORM',$link);
               break;
            case 'CONFIGURATION_OUTOFSERVICE':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_OUTOFSERVICE_FORM',$link);
               break;
            case 'CONFIGURATION_SCRIBD':
               $tempMessage      = getMessage('USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_SCRIBD_FORM',$link);
               break;
            default:
               $tempMessage      = getMessage('COMMON_MESSAGETAG_ERROR')." cs_server_item _FORM";
               break;
         }
         $retour = $tempMessage;
         // if ($retour == 'USAGE_INFO_TEXT_SERVER_FOR_'.strtoupper($rubric).'_'.strtoupper($funct).'_FORM' or $retour == 'tbd') {
         if ($retour == 'USAGE_INFO_TEXT_SERVER_FOR_'.$temp.'_FORM' or $retour == 'tbd') {
            $retour = getMessage('USAGE_INFO_FORM_COMING_SOON');
         }
      }
      return $retour;
   }

   ################################################################
   # Authentication
   ################################################################

   function setAuthDefault ($value) {
      $this->_addExtra('DEFAULT_AUTH',$value);
   }

   function getAuthDefault () {
      $retour = '';
      if ($this->_issetExtra('DEFAULT_AUTH')) {
         $value = $this->_getExtra('DEFAULT_AUTH');
         if ( !empty($value) ) {
            $retour = $value;
         }
      }
      return $retour;
   }

   function getDefaultAuthSourceItem () {
      $retour = NULL;
      $default_auth_item_id = $this->getAuthDefault();
      if ( !empty($default_auth_item_id) ) {
         $manager = $this->_environment->getAuthSourceManager();
         $item = $manager->getItem($default_auth_item_id);
         if ( isset($item) ) {
            $retour = $item;
         }
         unset($item);
         unset($manager);
      }
      return $retour;
   }

   function getAuthSourceList () {
      $manager = $this->_environment->getAuthSourceManager();
      $manager->setContextLimit($this->getItemID());
      $manager->select();
      $retour = $manager->get();
      unset($manager);
      return $retour;
   }

   function getAuthSource ($item_id) {
      $manager = $this->_environment->getAuthSourceManager();
      $retour = $manager->getItem($item_id);
      unset($manager);
      return $retour;
   }

   public function getCurrentCommSyVersion () {
      $retour = '';
      $version = trim(file_get_contents('version'));
      if ( !empty($version) ) {
         $retour = $version;
      }
      return $retour;
   }

   /** get out of service text
    *
    * @return array out of service text in different languages
    */
   function getOutOfServiceArray () {
      $retour = array();
      if ($this->_issetExtra('OUTOFSERVICE')) {
         $retour = $this->_getExtra('OUTOFSERVICE');
      }
      return $retour;
   }

   /** set out of service array
    *
    * @param array value out of service text in different languages
    */
   public function setOutOfServiceArray ($value) {
      $this->_addExtra('OUTOFSERVICE',(array)$value);
   }

   /** get out of service of a context
    * this method returns the out of service of the context
    *
    * @return string out of service of a context
    */
   public function getOutOfServiceByLanguage ($language) {
      $retour = '';
      if ($language == 'browser') {
         $language = $this->_environment->getSelectedLanguage();
      }
      $desc_array = $this->getDescriptionArray();
      if ( !empty($desc_array[cs_strtoupper($language)]) ) {
         $retour = $desc_array[cs_strtoupper($language)];
      }
      return $retour;
   }

   public function getOutOfService () {
      $retour = '';
      $retour = $this->getOutOfServiceByLanguage($this->_environment->getSelectedLanguage());
      if ( empty($retour) ) {
         $retour = $this->getOutOfServiceByLanguage($this->_environment->getUserLanguage());
      }
      if ( empty($retour) ) {
         $retour = $this->getOutOfServiceByLanguage($this->getLanguage());
      }
      if ( empty($retour) ) {
         $desc_array = $this->getOutOfServiceArray();
         foreach ($desc_array as $desc) {
            if (!empty($desc)) {
               $retour = $desc;
               break;
            }
         }
      }
      return $retour;
   }

   /** set OutOfService of a context
    * this method sets the OutOfService of the context
    *
    * @param string value OutOfService of the context
    * @param string value lanugage of the OutOfService
    */
   function setOutOfServiceByLanguage ($value, $language) {
      $desc_array = $this->getOutOfServiceArray();
      $desc_array[strtoupper($language)] = $value;
      $this->setOutOfServiceArray($desc_array);
   }

   function _getOutOfServiceShow () {
      return $this->_getExtra('OUTOFSERVICE_SHOW');
   }

   function showOutOfService () {
      $retour = false;
      $show_oos = $this->_getOutOfServiceShow();
      if ($show_oos == 1) {
         $retour = true;
      }
      return $retour;
   }

   function _setOutOfServiceShow ($value) {
      $this->_setExtra('OUTOFSERVICE_SHOW',$value);
   }

   function setDontShowOutOfService () {
      $this->_setOutOfServiceShow(-1);
   }

   function setShowOutOfService () {
      $this->_setOutOfServiceShow(1);
   }


   function getScribdApiKey () {
      $retour = '';
      if ($this->_issetExtra('SCRIBD_API_KEY')) {
         $retour = $this->_getExtra('SCRIBD_API_KEY');
      }
      return $retour;
   }
   function setScribdApiKey ($value) {
      $this->_addExtra('SCRIBD_API_KEY',$value);
   }

   function getScribdSecret () {
      $retour = '';
      if ($this->_issetExtra('SCRIBD_SECRET')) {
         $retour = $this->_getExtra('SCRIBD_SECRET');
      }
      return $retour;
   }
   function setScribdSecret ($value) {
      $this->_addExtra('SCRIBD_SECRET',$value);
   }
}
?>