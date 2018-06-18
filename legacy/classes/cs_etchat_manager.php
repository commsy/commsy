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

/** class for database connection to the database etchat
 * this class implements a database manager for the external application etchat
 */
class cs_etchat_manager extends cs_manager {

   /** constructor: cs_etchat_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
   public function __construct ($environment) {
      cs_manager::__construct($environment);
      global $db;
      $this->_db_connector = new db_mysql_connector($db['etchat']);
   }

   public function insertRoom ( $context ) {
      $retour = false;

      // chat room exists?
      $sql = 'SELECT etchat_roomname FROM etchat_rooms WHERE etchat_id_room="'.$context->getItemID().'";';
      $result = $this->_db_connector->performQuery($sql);
      if ( empty($result[0])
           or $result[0]['etchat_roomname'] != htmlentities($context->getTitle(), ENT_NOQUOTES, 'UTF-8')
         ) {
         // no -> enter chat room
         if ( empty($result[0]) ) {
            $sql = 'INSERT INTO etchat_rooms VALUES ('.$context->getItemID().',"'.addslashes(htmlentities($context->getTitle(), ENT_NOQUOTES, 'UTF-8')).'");';
         }

         // new name -> change room
         else {
            $sql = 'UPDATE etchat_rooms SET etchat_roomname="'.addslashes(htmlentities($context->getTitle(), ENT_NOQUOTES, 'UTF-8')).'" WHERE etchat_id_room='.$context->getItemID().';';
         }
         $result = $this->_db_connector->performQuery($sql);
         if ( !empty($result) ) {
            $retour = true;
         }
      } else {
         $retour = true;
      }
      return $retour;
   }

   public function insertUser ( $user ) {
      $retour = false;

      // user exists?
      $user_item_id = $user->getItemID();
      if ( empty($user_item_id) ) {
         $user_item_id = 42;
      }
      $sql = 'SELECT etchat_username FROM etchat_user WHERE etchat_user_id="'.$user_item_id.'";';
      $result = $this->_db_connector->performQuery($sql);
      $user_fullname = $user->getFullname();
      if ( empty($user_fullname)
           or $user_fullname == 'GUEST'
         ) {
         $translator = $this->_environment->getTranslationObject();
         $user_fullname = $translator->getMessage('COMMON_GUEST');
      }
      if ( empty($result[0])
           or empty($result[0]['etchat_username'])
           or $result[0]['etchat_username'] != htmlentities($user_fullname, ENT_NOQUOTES, 'UTF-8')
         ) {
         // no -> enter user
         if ( empty($result[0]) ) {
            $sql = 'INSERT INTO etchat_user VALUES ('.$user_item_id.',"'.addslashes(htmlentities($user_fullname, ENT_NOQUOTES, 'UTF-8')).'",NULL,"gast");';
         } else {
            $sql = 'UPDATE etchat_user SET etchat_username="'.addslashes(htmlentities($user_fullname, ENT_NOQUOTES, 'UTF-8')).'" WHERE etchat_user_id='.$user_item_id.';';
         }
         $result = $this->_db_connector->performQuery($sql);
         if ( !empty($result) ) {
            $retour = true;
         }
      } else {
         $retour = true;
      }

      // user message
      $translator = $this->_environment->getTranslationObject();
      $message_etchat_enter_chatroom = $translator->getMessage('ETCHAT_USER_ENTER_CHATROOM',htmlentities($user_fullname, ENT_NOQUOTES, 'UTF-8'));
      $time = date('U')-2;
      $sql = 'SELECT count(*) FROM etchat_messages WHERE etchat_user_fid="'.$user_item_id.'" AND etchat_text="'.addslashes($message_etchat_enter_chatroom).'" AND etchat_fid_room="'.$user->getContextID().'" AND etchat_timestamp>="'.$time.'";';
      $result = $this->_db_connector->performQuery($sql);
      if ( empty($result[0]['count'])
           or $result[0]['count'] == 0
         ) {
         $sql = "INSERT INTO etchat_messages ( etchat_user_fid, etchat_text, etchat_text_css, etchat_timestamp, etchat_fid_room ) VALUES ( ".$user_item_id.", '".addslashes($message_etchat_enter_chatroom)."', 'color:#000000;font-weight:normal;font-style:normal;', '".date('U')."',".$user->getContextID().")";
         $result = $this->_db_connector->performQuery($sql);
      }

      // user in room exists?
      $sql = 'SELECT count(*) AS count FROM etchat_useronline WHERE etchat_fid_room="'.$user->getContextID().'" AND etchat_onlineuser_fid="'.$user_item_id.'";';
      $result = $this->_db_connector->performQuery($sql);
      if ( empty($result[0]['count'])
           or $result[0]['count'] == 0
         ) {
         // no -> enter user in room
         $context = $user->getContextItem();
         
         global $symfonyContainer;
         $c_proxy_ip = $symfonyContainer->getParameter('commsy.settings.proxy_ip');

         if ( !empty($c_proxy_ip) ) {
            $user_param_all = $_SERVER['REMOTE_ADDR']."@".$_SERVER['REMOTE_ADDR']."@".@getenv('HTTP_X_FORWARDED_FOR');
         } else {
            $user_param_all = $_SERVER['REMOTE_ADDR']."@".@gethostbyaddr($_SERVER['REMOTE_ADDR'])."@".@getenv('HTTP_X_FORWARDED_FOR');
         }
         $sql = "INSERT INTO etchat_useronline ( etchat_onlineuser_fid, etchat_onlinetimestamp, etchat_onlineip, etchat_fid_room, etchat_user_online_room_name, etchat_user_online_user_name, etchat_user_online_user_priv) VALUES ( '".$user_item_id."', ".date('U').", '".$user_param_all."', ".$user->getContextID()." ,'".addslashes(htmlentities($context->getTitle(), ENT_NOQUOTES, 'UTF-8'))."', '".addslashes(htmlentities($user_item_id, ENT_NOQUOTES, 'UTF-8'))."', 'gast')";
         $result = $this->_db_connector->performQuery($sql);
         if ( empty($result)
              or !is_numeric($result)
            ) {
            $retour = false;
         }
      }

      return $retour;
   }
}
?>