<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Fabian Gebert (Mediabird), Dr. Iver Jackewitz (CommSy),
//                   Frank Wolf (Mediabird)
//
// This file is part of the mediabird plugin for CommSy.
//
// This plugin is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This plugin is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You have received a copy of the GNU General Public License
// along with the plugin.

/**
 * Implementatio of the default auth manager
 * Uses cookie driven PHP session management to store the user id
 */
class CommsyAuthManager {
   public $userId;
   public $userSubfolder; //to determine the sub folder in the uploads folder
   private $_environment = NULL;
   private $_system = 'mediabird';
   private $_commsy_user_item_id = '';
   private $_commsy_user_item = NULL;
   private $_plugin_class = NULL;
   function __construct($environment) {
      $this->_environment = $environment;
      $this->_setUserItem();
      $this->_setUserID();
      $this->_setUploadFolder();
      $this->_plugin_class = $this->_environment->getPluginClass($this->_system);
   }

   private function _setUserItem () {
      $this->_commsy_user_item = $this->_environment->getCurrentUserItem();
   }

   private function _setUserID () {
      $portal_user_item = $this->_commsy_user_item->getRelatedCommSyUserItem();
      $this->_commsy_user_item_id = $portal_user_item->getItemID();
      $external_id_manager = $this->_environment->getExternalIDManager();
      $this->userId = $external_id_manager->getExternalId($this->_system,$this->_commsy_user_item_id);
      unset($external_id_manager);
   }

   private function _setUploadFolder () {
      $current_user_item = $this->_environment->getCurrentUserItem();
      $own_room = $this->_commsy_user_item->getOwnRoom();
      if ( isset($own_room) ) {
         $upload_folder_id = $own_room->getItemID();
         unset($own_room);
         if ( !empty($upload_folder_id) ) {
            $uploads_folder2 = 'var/mediabird/'.$upload_folder_id.'/';
            if ( !file_exists($uploads_folder2) ) {
               mkdir($uploads_folder2);
               if ( !file_exists($uploads_folder2) ) {
                  include_once('functions/error_functions.php');
                  trigger_error('can not make uploads directory for mediabird plugin',E_USER_WARNING);
               }
            }
         }
      }
      MediabirdConfig::$uploads_folder = 'var/mediabird/';
      $this->userSubfolder = $upload_folder_id;
   }

   /**
    * Retrieve all known users for the current CommSy user
    * @return array Array of objects featuring name and CommSy id
    */
   function getKnownUsers () {
      $knownUsers = array();
      if ($this->_commsy_user_item) {
         $room_id_array = array();
         $project_list = $this->_commsy_user_item->getRelatedProjectList();
         if ( isset($project_list)
              and $project_list->isNotEmpty()
            ) {
            $project_room_item = $project_list->getFirst();
            while ( $project_room_item ) {
               $room_id_array[] = $project_room_item->getItemID();
               $project_room_item = $project_list->getNext();
            }
         }
         // maybe add communityrooms
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextArrayLimit($room_id_array);
         $user_manager->setUserLimit();
         $user_manager->setOnlyUserFromPortal();
         $user_manager->select();
         $user_list = $user_manager->get();
         $current_portal_user_item = $this->_commsy_user_item->getRelatedCommSyUserItem();
         if ( isset($user_list)
              and $user_list->isNotEmpty()
            ) {
            $user_item = $user_list->getFirst();
            while ($user_item) {
               $user_item = $user_item->getRelatedCommSyUserItem();
               if ( $user_item->getItemID() != $current_portal_user_item->getItemID() ) {
                  $knownUser = (object) null;
                  $knownUser->name = $user_item->getFullname();
                  $knownUser->id = intval($user_item->getItemID());
                  if ( $user_item->isEmailVisible() ) {
                     $knownUser->email = $user_item->getEMail();
                  }
                  $knownUser->mb_id = intval($this->_getMediabirdUserItemID($user_item->getItemID()));
                  #$knownUser->pic_url = $user_item->getPictureUrl(true);
                  #if ( !empty($user_item) ) {
                  #   $knownUser->pic_url = $this->_plugin_class->getPictureUrlForCommSyUserByItem($user_item);
                  #}
                  array_push($knownUsers,$knownUser);
               }
               $user_item = $user_list->getNext();
            }
         }
      }
      return $knownUsers;
   }

   /**
    * Database link
    * @var MediabirdDbo
    */
   var $db;

   function setDb($db) {
      $this->db = $db;
   }

   /**
    * Invite known user
    * @param $id Id of known CommSy user
    * @return Mediabird user ID
    */
   function inviteKnownUser($commsy_user_item_id, &$unkownInvitee) {
      $retour = NULL;
      if ( !empty($commsy_user_item_id) ) {
         $mbuser = $this->_getMediabirdUserItemID($commsy_user_item_id);
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItem($commsy_user_item_id);

         global $plugin_dir;
         include_once($plugin_dir.'/server/helper.php');
         $helper = new MediabirdHtmlHelper();

         if ( !empty($mbuser) ) {
            $unkownInvitee = false;
         } else {
            #$pic_url = $user_item->getPictureUrl(true);
            if ( !empty($user_item) ) {
               $pic_url = $this->_plugin_class->getPictureUrlForCommSyUserByItem($user_item);
            }
            $fullname = $user_item->getFullname();
            $email = null;
            if ( $user_item->isEmailVisible() ) {
               $email = $user_item->getEMail();
            }

            $mbuser = $helper->registerUser($fullname, 0, $email, $pic_url, $this->db);
            $external_id_manager = $this->_environment->getExternalIDManager();
            $external_id_manager->addIDsToDB($this->_system,$mbuser,$commsy_user_item_id);
            unset($external_id_manager);
            $unkownInvitee = true;
         }

         if ( !empty($mbuser) ) {
            $retour = $mbuser;
         }
      }

      return $retour;
   }

   /**
    * Called to invite a user to Mediabird
    * Should return a Mediabird user ID or null on failure (email not unique or does not exist)
    * @param $email String Email of user to be invited
    * @return int Mediabird user id
    */
   function inviteUser ($email) {
      $retour = NULL;
      if ( !empty($email) ) {
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextLimit($this->_environment->getCurrentPortalID());
         $user_manager->setEMailLimit($email);
         $user_manager->select();
         $user_list = $user_manager->get();
         if ( isset($user_list)
              and $user_list->isNotEmpty()
              and $user_list->getCount() == 1
            ) {
            $user_item = $user_list->getFirst();
            if ( isset($user_item)
                 and $user_item->isEmailVisible()
               ) {
               $external_id_manager = $this->_environment->getExternalIDManager();
               $mbuser = $external_id_manager->getExternalId($this->_system,$user_item->getItemID());
               $fullname = $user_item->getFullname();

               global $plugin_dir;
               include($plugin_dir.'/server/helper.php');
               $helper = new MediabirdHtmlHelper();

               if ( empty($mbuser) ) {
                  #$pic_url = $user_item->getPictureUrl(true);
                  if ( !empty($user_item) ) {
                     $pic_url = $this->_plugin_class->getPictureUrlForCommSyUserByItem($user_item);
                  }
                  $email = null;

                  if ( $user_item->isEmailVisible() ) {
                     $email = $user_item->getEMail();
                  }

                  $mbuser = $helper->registerUser($fullname, 0, $email, $pic_url, $this->db);
                  $external_id_manager->addIDsToDB($this->_system,$mbuser,$user_item->getItemID());
               }
               unset($external_id_manager);
               $retour = intval($mbuser);
            }
         }
      }
      return $retour;
   }

   /**
    * Send's an anonymous email to some address, preferably the Mediabird team or a user
    * @param $to Mediabird id of user to which to deliver email
    * @param $subject Subject of email
    * @param $body Body of email
    * @return bool Success
    */
   function sendMail($to,$subject,$body) {
      if(!MediabirdConfig::$disable_mail) {
         $address=null;
         $cc = '';
         if($to==-1) {
            $address=MediabirdConfig::$webmaster_address;
            if ( !empty(MediabirdConfig::$developer_address) ) {
               $cc = MediabirdConfig::$developer_address;
            }
         }
         else {
            $query="SELECT `email` FROM ".MediabirdConfig::tableName('User')." WHERE `id`=$to";
            if($result=mysql_query($query)) {
               $row=mysql_fetch_row($result);
               $address=$row[0];
            }
         }
         if(isset($address)) {
            $headers = "From: ".MediabirdConfig::$no_reply_address."\r\n".
               "Reply-To: ".MediabirdConfig::$no_reply_address."\r\n".
               "X-Mailer: PHP/".phpversion();
            if ( !empty($cc) ) {
               $headers .= "\r\n"."Cc: ".$cc;
            }
            return mail($address, $subject, $body, $headers);
         }
      }
      return false;
   }

   public function setCommSyUserItemID ($value) {
      $this->_commsy_user_item_id = (int)$value;
      $this->userSubfolder = $value;
   }

   public function setCommSyUserItem ($value) {
      $this->_commsy_user_item = $value;
   }

   public function setCommSyEnvironment ($value) {
      $this->_environment = $value;
   }

   private function _getMediabirdUserItemID ( $commsy_user_item_id ) {
      $retour = '';
      $external_id_manager = $this->_environment->getExternalIDManager();
      if ( isset($external_id_manager) ) {
         $commsy_user_item_id = $external_id_manager->getExternalId($this->_system,$commsy_user_item_id);
         if ( !empty($commsy_user_item_id) ) {
            $retour = $commsy_user_item_id;
         }
      }
      $retour = intval($retour);
      return $retour;
   }

   public function search ($search_word, $parts_array) {
      $system = 'mediabird';
      $commsy_mediabird = $this->_environment->getPluginClass($system);
      return $commsy_mediabird->search($search_word,$parts_array);
   }
}
?>
