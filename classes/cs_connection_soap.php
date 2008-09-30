<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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

include_once('cs_personInfo.php');
include_once('cs_membershipInfo.php');
include_once('cs_roomInfo.php');
include_once('cs_session_item.php');
include_once('cs_session_manager.php');
include_once('cs_portal_item.php');
include_once('cs_portal_manager.php');

class cs_connection_soap {
   private $_environment = null;

   private $_session_id_array = array();

   private $_valid_session_id_array = array();

   private $_material_limit_array = array();

   private $_soap_fault = null;

   public function __construct ($environment) {
      $this->_environment = $environment;
   }

   private function _encode_input ($value) {
      return utf8_decode($value);
   }

   private function _encode_output ($value) {
      return utf8_encode($value);
   }

   public function getGuestSession($portal_id) {
      if ( empty($portal_id) ) {
         return new SoapFault('ERROR','portal_id is empty!');
      } else {
         $portal_id = $this->_encode_input($portal_id);
         $this->_environment->setCurrentContextID($portal_id);
         // make session
         include_once('classes/cs_session_item.php');
         $session = new cs_session_item();
         $session->createSessionID('guest');
         $session->setValue('portal_id',$portal_id);
         $session_manager = $this->_environment->getSessionManager();
         $session_manager->save($session);
         $retour = $session->getSessionID();
         return $this->_encode_output($retour);
      }
   }

   public function getCountUser($session_id, $portal_id) {
      $portal_id = $this->_encode_input($portal_id);
      $session_id = $this->_encode_input($session_id);
      $user_count =-1;

      if($this->_isSessionValid($session_id)) {
         if ($this->_isSessionActive('guest',$portal_id)) {
            $portal_manager = $this->_environment->getPortalManager();
            $portal_item = $portal_manager->getItem($portal_id);
            $user_count = $portal_item->getCountMembers();
         } else {
            return new SoapFault('ERROR','Session not active on portal '.$portal_id.'!');
         }
      } else {
         return new SoapFault('ERROR','Session not valid!');
      }

      return $this->_encode_output($user_count);
   }

   //public function getCountRooms($session_id, $portal_id) {
   public function getCountRooms($session_id, $portal_id) {
      $portal_id = $this->_encode_input($portal_id);
      $session_id = $this->_encode_input($session_id);
      $room_count =-1;

      if($this->_isSessionValid($session_id)) {
         if ($this->_isSessionActive('guest',$portal_id)) {
            $portal_manager = $this->_environment->getPortalManager();
            $portal_item = $portal_manager->getItem($portal_id);
            $date_current = date("Y-m-d H:i:s");
            $room_count = $portal_item->getCountRooms('',$date_current);
         } else {
            return new SoapFault('ERROR','Session not active on portal '.$portal_id.'!');
         }
      } else {
         return new SoapFault('ERROR','Session not valid!');
      }
      return $this->_encode_output($room_count);
   }

   public function getActiveRoomList($session_id, $portal_id, $count) {
      if($this->_isSessionValid($session_id)) {
         if ($this->_isSessionActive('guest',$portal_id)) {
            $room_manager = $this->_environment->getRoomManager();
            $room_manager->setContextLimit($portal_id);
            $room_manager->setRoomTypeLimit(CS_PROJECT_TYPE);
            $room_manager->setOrder('activity_rev');
            $room_manager->setIntervalLimit(0,$count);
            $room_manager->select();
            $test = $room_manager->getLastQuery();
            $room_list = $room_manager->get();


            $room_item = $room_list->getFirst();
            $xml = "<room_list>\n";
            while($room_item) {
               $xml .= $room_item->getXMLData();
               $room_item = $room_list->getNext();
            }
            $xml .= "</room_list>";
            $xml = $this->_encode_output($xml);
         } else {
            return new SoapFault('ERROR','Session not active on portal '.$portal_id.'!');
         }
      } else {
         return new SoapFault('ERROR','Session not valid!');
      }
      return $xml;
   }

   public function createUser ($session_id,$portal_id,$firstname,$lastname,$mail,$user_id,$user_pwd,$agb = false) {
      $session_id = $this->_encode_input($session_id);
      $portal_id = $this->_encode_input($portal_id);
      if ( is_numeric($session_id) ) {
         $temp = $session_id;
         $session_id = $portal_id;
         $portal_id = $temp;
      }
      if ( !empty($session_id) ) {
         $this->_environment->setSessionID($session_id);
         $session_item = $this->_environment->getSessionItem();
         if ( !isset($session_item)  ) {
            $session_manager = $this->_environment->getSessionManager();
            $session_item = $session_manager->get($session_id);
            if ( !isset($session_item)  ) {
               $last_query = $session_manager->getLastQuery();
               return new SoapFault('ERROR','createUser: can not get session_item with query: '.$last_query.' - '.__FILE__.' - '.__FILE__);
            }
         }
         $current_user_id = $session_item->getValue('user_id');
         $user_manager = $this->_environment->getUserManager();
         if ($current_user_id == 'root') {
            $current_user = $this->_environment->getCurrentUserItem();
         } else {
            $current_server_id = $session_item->getValue('commsy_id');
            $auth_source_id = $session_item->getValue('auth_source');
            $user_manager->setContextLimit($current_server_id);
            $user_manager->setUserIdLimit($current_user_id);
            $user_manager->setAuthSourceLimit($auth_source_id);
            $user_manager->select();
            $current_user_list = $user_manager->get();
            $current_user = $current_user_list->getFirst();
         }
         if ( !empty($current_user) ) {
            $this->_environment->setCurrentUserItem($current_user);
            if ( $current_user->isRoot()
                 or ( $current_server_id == $this->_environment->getServerID()
                      and $current_user->getUserID() == 'IMS_USER'
                    )
               ) {
               $user_id = $this->_encode_input($user_id);
               $user_pwd = $this->_encode_input($user_pwd);
               $portal_id = $this->_encode_input($portal_id);
               $firstname = $this->_encode_input($firstname);
               $lastname = $this->_encode_input($lastname);
               $mail = $this->_encode_input($mail);
               $agb = $this->_encode_input($agb);
               if ( empty($agb) ) {
                  $agb = false;
               }
               $language = 'DE'; // (TBD)
               // $language = $this->_environment->getSelectedLanguage();
               $auth_source_id = ''; // (TBD)

               $portal_manager = $this->_environment->getPortalManager();
               $portal_item = $portal_manager->getItem($portal_id);
               if ( !empty($portal_item) ) {
                  $this->_environment->setCurrentContextID($portal_id);
                  $authentication = $this->_environment->getAuthenticationObject();
                  $current_portal = $this->_environment->getCurrentPortalItem();
                  $auth_source_id = $current_portal->getAuthDefault();

                  if ( $authentication->is_free($user_id,$auth_source_id) ) {
                     // Create new item
                     $new_account = $authentication->getNewItem();
                     $new_account->setUserID($user_id);
                     $new_account->setPassword($user_pwd);
                     $new_account->setFirstname($firstname);
                     $new_account->setLastname($lastname);
                     $new_account->setLanguage($language);
                     $new_account->setEmail($mail);
                     $new_account->setPortalID($portal_id);
                     if ( !empty($auth_source_id) ) {
                        $new_account->setAuthSourceID($auth_source_id);
                     } else {
                        $current_portal = $this->_environment->getCurrentPortalItem();
                        $new_account->setAuthSourceID($current_portal->getAuthDefault());
                        $auth_source_id = $current_portal->getAuthDefault();
                        unset($current_portal);
                     }
                     $save_only_user = false;
                     $authentication->save($new_account,$save_only_user);

                     $portal_user = $authentication->getUserItem();
                     $error = $authentication->getErrorMessage();
                     if ( empty($error) ) {
                        // portal: send mail to moderators in different languages
                        $portal_item = $this->_environment->getCurrentPortalItem();
                        $user_list = $portal_item->getModeratorList();
                        $email_addresses = array();
                        $user_item = $user_list->getFirst();
                        $recipients = '';
                        $language = $portal_item->getLanguage();
                        while ($user_item) {
                           $want_mail = $user_item->getAccountWantMail();
                           if (!empty($want_mail) and $want_mail == 'yes') {
                              if ($language == 'user'  and $user_item->getLanguage() != 'browser') {
                                 $email_addresses[$user_item->getLanguage()][] = $user_item->getEmail();
                              } elseif ($language == 'user' and $user_item->getLanguage() == 'browser') {
                                 $email_addresses[$this->_environment->getSelectedLanguage()][] = $user_item->getEmail();
                              } else {
                                 $email_addresses[$language][] = $user_item->getEmail();
                              }
                              $recipients .= $user_item->getFullname().LF;
                           }
                           unset($user_item);
                           $user_item = $user_list->getNext();
                        }
                        $translator = $this->_environment->getTranslationObject();
                        $save_language = $translator->getSelectedLanguage();
                        unset($user_item);
                        unset($user_list);
                        foreach ($email_addresses as $key => $value) {
                           $translator->setSelectedLanguage($key);
                           if (count($value) > 0) {
                              include_once('classes/cs_mail.php');
                              $mail = new cs_mail();
                              $mail->set_to(implode(',',$value));
                              $server_item = $this->_environment->getServerItem();
                              $default_sender_address = $server_item->getDefaultSenderAddress();
                              if (!empty($default_sender_address)) {
                                 $mail->set_from_email($default_sender_address);
                              } else {
                                 $mail->set_from_email('@');
                              }
                              $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$portal_item->getTitle()));
                              $mail->set_reply_to_name($portal_user->getFullname());
                              $mail->set_reply_to_email($portal_user->getEmail());
                              $mail->set_subject($translator->getMessage('USER_GET_MAIL_SUBJECT',$portal_user->getFullname()));
                              $body = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                              $body .= LF.LF;
                              $temp_language = $portal_user->getLanguage();
                              if ($temp_language == 'browser') {
                                 $temp_language = $this->_environment->getSelectedLanguage();
                              }
                              $body .= $translator->getMessage('USER_GET_MAIL_BODY',
                                                               $portal_user->getFullname(),
                                                               $portal_user->getUserID(),
                                                               $portal_user->getEmail(),
                                                               $translator->getLanguageLabelTranslated($temp_language)
                                                              );
                              unset($temp_language);
                              $body .= LF.LF;
                              $check_message = 'NO';

                              switch ( $check_message )
                              {
                                 case 'YES':
                                    $body .= $translator->getMessage('USER_GET_MAIL_STATUS_YES');
                                    break;
                                 case 'NO':
                                    $body .= $translator->getMessage('USER_GET_MAIL_STATUS_NO');
                                    break;
                                 default:
                                    break;
                              }
                              $body .= LF.LF;
                              $body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
                              $body .= LF;

                              $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$portal_item->getItemID().'&mod=account&fct=index'.'&selstatus=1';
                              $body .= str_replace('soap.php','commsy.php',$url);
                              $mail->set_message($body);
                              $mail->send();
                           }
                        }
                        $translator->setSelectedLanguage($save_language);

                        // activate user
                        $portal_user->makeUser();
                        if ( $agb ) {
                           $portal_user->setAGBAcceptance();
                        }
                        $portal_user->save();
                        $current_user = $portal_user;
                        $this->_environment->setCurrentUserItem($current_user);

                        // send email to user
                        if ($current_user->isUser()) {
                           $mod_text = '';
                           $mod_list = $portal_item->getContactModeratorList();
                           if (!$mod_list->isEmpty()) {
                              $mod_item = $mod_list->getFirst();
                              $contact_moderator = $mod_item;
                              while ($mod_item) {
                                 if (!empty($mod_text)) {
                                    $mod_text .= ','.LF;
                                 }
                                 $mod_text .= $mod_item->getFullname();
                                 $mod_text .= ' ('.$mod_item->getEmail().')';
                                 unset($mod_item);
                                 $mod_item = $mod_list->getNext();
                              }
                           }
                           unset($mod_item);
                           unset($mod_list);

                           $language = $this->_environment->getSelectedLanguage();
                           $translator->setSelectedLanguage($language);
                           include_once('classes/cs_mail.php');
                           $mail = new cs_mail();
                           $mail->set_to($current_user->getEmail());
                           $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$portal_item->getTitle()));
                           $server_item = $this->_environment->getServerItem();
                           $default_sender_address = $server_item->getDefaultSenderAddress();
                           if (!empty($default_sender_address)) {
                               $mail->set_from_email($default_sender_address);
                           } else {
                              $user_manager = $this->_environment->getUserManager();
                              $root_user = $user_manager->getRootUser();
                              $root_mail_address = $root_user->getEmail();
                              if ( !empty($root_mail_address) ) {
                                 $mail->set_from_email($root_mail_address);
                              } else {
                                 $mail->set_from_email('@');
                              }
                           }
                           if (!empty($contact_moderator)) {
                              $mail->set_reply_to_email($contact_moderator->getEmail());
                              $mail->set_reply_to_name($contact_moderator->getFullname());
                           }
                           $mail->set_subject($translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_FREE',$portal_item->getTitle()));
                           $body = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                           $body .= LF.LF;
                           $body .= $translator->getEmailMessage('MAIL_BODY_HELLO',$current_user->getFullname());
                           $body .= LF.LF;
                           $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$portal_user->getUserID(),$portal_item->getTitle());
                           $body .= LF.LF;
                           if ( empty($contact_moderator) ) {
                              $body .= $translator->getMessage('SYSTEM_MAIL_REPLY_INFO').LF;
                              $body .= $mod_text;
                              $body .= LF.LF;
                           } else {
                              $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$contact_moderator->getFullname(),$portal_item->getTitle());
                              $body .= LF.LF;
                           }
                           $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$this->_environment->getCurrentContextID();
                           $body .= str_replace('soap.php','commsy.php',$url);
                           $mail->set_message($body);
                           $mail->send();
                        }

                        // login in user
                        return $this->authenticate($this->_encode_output($user_id),$this->_encode_output($user_pwd),$this->_encode_output($portal_id),$this->_encode_output($auth_source_id));
                     } else {
                        return new SoapFault('ERROR','createUser: error while saving user account ('.$error.')! - '.__FILE__.' - '.__LINE__);
                     }
                  } else {
                     return new SoapFault('ERROR','createUser: account is not free! - ('.$user_id.')'.__FILE__.' - '.__LINE__);
                  }
               } else {
                  return new SoapFault('ERROR','createUser: Portal ID is not valid! - '.__FILE__.' - '.__LINE__);
               }
            } else {
               return new SoapFault('ERROR','createUser: Logged in user is not allowed to create accounts. - '.__FILE__.' - '.__LINE__);
            }
         } else {
            return new SoapFault('ERROR','createUser: can not identify current user. - '.__FILE__.' - '.__LINE__);
         }
      } else {
         return new SoapFault('ERROR','createUser: session id ('.$session_id.') is not set. - '.__FILE__.' - '.__LINE__);
      }
   }

   public function authenticate ($user_id, $password, $portal_id = 99, $auth_source_id = 0) {
      $user_id = $this->_encode_input($user_id);
      $password = $this->_encode_input($password);
      $portal_id = $this->_encode_input($portal_id);
      if ( !empty($auth_source_id) and $auth_source_id != 0 ) {
         $auth_source_id = $this->_encode_input($auth_source_id);
      }
      $result = '';

      $info = 'ERROR';
      $info_text = 'default-error';
      if ( empty($user_id) or empty($password) ) {
         $info = 'ERROR';
         $info_text = 'user_id or password lost';
      } else {
         if ( !isset($this->_environment) ) {
            $info = 'ERROR';
            $info_text = 'environment lost';
         } else {
            $this->_environment->setCurrentContextID($portal_id);
            $authentication = $this->_environment->getAuthenticationObject();
            if ( isset($authentication) ) {
               if ($authentication->isAccountGranted($user_id,$password,$auth_source_id)) {
                  if ($this->_isSessionActive($user_id,$portal_id)) {
                     $result = $this->_getActiveSessionID($user_id,$portal_id);
                     if ( empty($result) ) {
                        $info = 'ERROR';
                        $info_text = 'no session id from session manager -> database error';
                     }
                  } else {
                     // make session
                     include_once('classes/cs_session_item.php');
                     $session = new cs_session_item();
                     $session->createSessionID($user_id);
                     // save portal id in session to be sure, that user didn't
                     // switch between portals
                     $session->setValue('user_id',$user_id);
                     $session->setValue('commsy_id',$portal_id);
                     if ( empty($auth_source_id) or $auth_source_id == 0 ) {
                        $auth_source_id = $authentication->getAuthSourceItemID();
                     }
                     $session->setValue('auth_source',$auth_source_id);
                     $session->setValue('javascript',-1);
                     $session->setSoapSession();

                     // save session
                     $session_manager = $this->_environment->getSessionManager();
                     $session_manager->save($session);

                     $result = $session->getSessionID();
                  }
               } else {
                  $info = 'ERROR';
                  $info_text = 'account not granted '.$user_id.' - '.$password.' - '.$portal_id;
               }
            } else {
               $info = 'ERROR';
               $info_text = 'authentication object lost';
            }
         }
      }
      if ( empty($result) and !empty($info) ) {
         $result = new SoapFault($info,$info_text);
      } else {
         $result = $this->_encode_output($result);
      }
      return $result;
   }

   private function _isSessionActive ($user_id, $portal_id) {
      $retour = false;
      if ( !empty($this->_session_id_array[$portal_id][$user_id]) ) {
         $retour = true;
      } else {
         $session_id = $this->_getActiveSessionID($user_id,$portal_id);
         if ( !empty($session_id) ) {
            $retour = true;
         }
      }
      return $retour;
   }

   private function _getActiveSessionID ($user_id, $portal_id) {
      $retour = '';
      if ( !empty($this->_session_id_array[$portal_id][$user_id]) ) {
         $retour = $this->_session_id_array[$portal_id][$user_id];
      } else {
         $session_manager = $this->_environment->getSessionManager();
         $retour = $session_manager->getActiveSOAPSessionID($user_id,$portal_id);
         if ( !empty($retour) ) {
            $this->_session_id_array[$portal_id][$user_id] = $retour;
            $this->_updateSessionCreationDate($retour);
         }
      }
      return $retour;
   }

   private function _isSessionValid ($session_id) {
      $retour = false;
      if ( !empty($this->_valid_session_id_array[$session_id]) ) {
         $retour = true;
      } else {
         $session_manager = $this->_environment->getSessionManager();
         $session_item = $session_manager->get($session_id);
         if ( isset($session_item) and $session_item->issetValue('user_id') ) {
            $this->_valid_session_id_array[$session_id] = $session_id;
            $retour = true;
         }
      }
      return $retour;
   }

   private function _updateSessionCreationDate ($session_id) {
      $session_manager = $this->_environment->getSessionManager();
      $session_manager->updateSessionCreationDate($session_id);
   }

   public function IMS ($session_id, $ims_xml) {
      if ($this->_isSessionValid($session_id)) {
         include_once('classes/cs_connection_soap_ims.php');
         $ims_object = new cs_connection_soap_ims($this->_environment);
         $this->_updateSessionCreationDate($session_id);
         return $ims_object->IMS($session_id, $ims_xml);
      } else {
         return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
      }
   }

   public function getMaterialList ($session_id, $context_id) {
      $session_id = $this->_encode_input($session_id);
      $context_id = $this->_encode_input($context_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $portal_id = $session->getValue('commsy_id');
         $auth_source = $session->getValue('auth_source');
         $room_manager = $this->_environment->getRoomManager();
         $room_item = $room_manager->getItem($context_id);
         $room_context_id = $room_item->getContextID();
         if ( $room_context_id != $portal_id ) {
            $info = 'ERROR: GET MATERIAL LIST';
            $info_text = 'room with id ('.$context_id.') is not on the commsy portal form session with id ('.$portal_id.')';
            $result = new SoapFault($info,$info_text);
         } elseif ( !$room_item->mayEnterByUserID($user_id,$auth_source) ) {
            $info = 'ERROR: GET MATERIAL LIST';
            $info_text = 'user with user_id ('.$user_id.') is not allowed to enter the room with id ('.$context_id.')';
            $result = new SoapFault($info,$info_text);
         } else {
            $result = $this->_getMaterialListAsXML($room_item->getItemID(),$session_id);
            $result = $this->_encode_output($result);
         }
         $this->_updateSessionCreationDate($session_id);
      } else {
         $info = 'ERROR: GET MATERIAL LIST';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function getPrivateRoomMaterialList ($session_id) {
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $portal_id = $session->getValue('commsy_id');
         $auth_source = $session->getValue('auth_source');
         $room_manager = $this->_environment->getPrivateRoomManager();
         $room_item_id = $room_manager->getItemIDOfRelatedOwnRoomForUser($user_id,$auth_source,$portal_id);
         if ( isset($room_item_id) and !empty($room_item_id) ) {
            $result = $this->_getMaterialListAsXML($room_item_id,$session_id);
            $result = $this->_encode_output($result);
         }
         $this->_updateSessionCreationDate($session_id);
         $this->_log('material','SOAP:getPrivateMaterialList','SID='.$session_id);
      } else {
         $info = 'ERROR: GET MATERIAL LIST';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   private function _getMaterialListAsXML ($room_id,$session_id) {
      $retour = '';
      $material_manager = $this->_environment->getMaterialManager();
      $material_manager->resetLimits();
      $material_manager->setContextLimit($room_id);
       // set limits
      $session_manager = $this->_environment->getSessionManager();
      $session = $session_manager->get($session_id);
      $this->_material_limit_array = $session->getValue('material_limit_array');

      if(isset($this->_material_limit_array['group_limit'])) {
        $material_manager->setGroupLimit ($this->_material_limit_array['group_limit']);
      }
      if(isset($this->_material_limit_array['topic_limit'])) {
        $material_manager->setTopicLimit($this->_material_limit_array['topic_limit']);
      }
      if(isset($this->_material_limit_array['label_limit'])) {
        $material_manager->setLabelLimit($this->_material_limit_array['label_limit']);
      }
      if(isset($this->_material_limit_array['buzzword_limit'])) {
        $material_manager->setBuzzwordLimit($this->_material_limit_array['buzzword_limit']);
      }
      $material_manager->select();
      $material_list = $material_manager->get();
      $retour .= '<material_list>';
      if (!$material_list->isEmpty()) {
         $material_item = $material_list->getFirst();
         while ($material_item) {
            $retour .= $material_item->getDataAsXML();
            $material_item = $material_list->getNext();
         }
      }
      $retour .= '</material_list>';
      return $retour;
   }

   public function getFileListFromMaterial ($session_id, $material_id) {
      return $this->getFileListFromItem($session_id,$material_id);
   }

   public function getFileListFromItem ($session_id, $item_id) {
      $session_id = $this->_encode_input($session_id);
      $item_id = $this->_encode_input($item_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $portal_id = $session->getValue('commsy_id');
         $this->_environment->setCurrentPortalID($portal_id);
         $auth_source = $session->getValue('auth_source');
         $item_manager = $this->_environment->getItemManager();
         $commsy_item = $item_manager->getItem($item_id);
         if ( isset($commsy_item) and !empty($commsy_item) ) {
            $context_id = $commsy_item->getContextID();
            if ( !empty($context_id) ) {
               $this->_environment->setCurrentContextID($context_id);
               $room_manager = $this->_environment->getRoomManager();
               $room_item = $room_manager->getItem($context_id);
               if ( isset($room_item) and !empty($room_item) ) {
                  if ( $room_item->mayEnterByUserID($user_id,$auth_source) ) {
                     $real_manager = $this->_environment->getManager($commsy_item->getItemType());
                     if ( isset($real_manager) and !empty($real_manager) ) {
                        $real_item = $real_manager->getItem($item_id);
                        if ( isset($real_item) and !empty($real_item) ) {
                           $result  = '<file_list>';
                           if ( method_exists($real_item,'getFileList') ) {
                              $file_list = $real_item->getFileList();
                              if (!$file_list->isEmpty()) {
                                 $file_item = $file_list->getFirst();
                                 while ($file_item) {
                                    $result .= $file_item->getDataAsXML();
                                    $file_item = $file_list->getNext();
                                 }
                              }
                           }
                           $result .= '</file_list>';
                           $result = $this->_encode_output($result);
                        } else {
                           $info = 'ERROR: GET FILE LIST';
                           $info_text = 'item ('.$item_id.') does not exists';
                           $result = new SoapFault($info,$info_text);
                        }
                     } else {
                        $info = 'ERROR: GET FILE LIST';
                        $info_text = 'lost item type of the item ('.$item_id.')';
                        $result = new SoapFault($info,$info_text);
                     }
                  } else {
                     $info = 'ERROR: GET FILE LIST';
                     $info_text = 'user_id ('.$user_id.') don\'t have the permission to get the item ('.$item_id.')';
                     $result = new SoapFault($info,$info_text);
                  }
               } else {
                  $info = 'ERROR: GET FILE LIST';
                  $info_text = 'context ('.$context_id.') of item ('.$item_id.') does not exits';
                  $result = new SoapFault($info,$info_text);
               }
            } else {
               $info = 'ERROR: GET FILE LIST';
               $info_text = 'context of item ('.$item_id.') lost';
               $result = new SoapFault($info,$info_text);
            }
         } else {
            $info = 'ERROR: GET FILE LIST';
            $info_text = 'material id ('.$item_id.') does not exist';
            $result = new SoapFault($info,$info_text);
         }
         $this->_updateSessionCreationDate($session_id);
      } else {
         $info = 'ERROR: GET FILE LIST';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function getSectionListFromMaterial ($session_id, $material_id) {
      $session_id = $this->_encode_input($session_id);
      $material_id = $this->_encode_input($material_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $portal_id = $session->getValue('commsy_id');
         $this->_environment->setCurrentPortalID($portal_id);
         $auth_source = $session->getValue('auth_source');
         $material_manager = $this->_environment->getMaterialManager();
         $material_item = $material_manager->getItem($material_id);
         if ( isset($material_item) and !empty($material_item) ) {
            $context_id = $material_item->getContextID();
            if ( !empty($context_id) ) {
               $this->_environment->setCurrentContextID($context_id);
               $room_manager = $this->_environment->getRoomManager();
               $room_item = $room_manager->getItem($context_id);
               if ( isset($room_item) and !empty($room_item) ) {
                  if ( $room_item->mayEnterByUserID($user_id,$auth_source) ) {
                     $result  = '<section_list>';
                     $section_list = $material_item->getSectionList();
                     if (!$section_list->isEmpty()) {
                        $section_item = $section_list->getFirst();
                        while ($section_item) {
                           $result .= $section_item->getDataAsXML();
                           $section_item = $section_list->getNext();
                        }
                     }
                     $result .= '</section_list>';
                     $result = $this->_encode_output($result);
                  } else {
                     $info = 'ERROR: GET MATERIAL LIST';
                     $info_text = 'user_id ('.$user_id.') don\'t have the permission to get the material ('.$material_id.')';
                     $result = new SoapFault($info,$info_text);
                  }
               } else {
                  $info = 'ERROR: GET MATERIAL LIST';
                  $info_text = 'context ('.$context_id.') of material ('.$material_id.') does not exits';
                  $result = new SoapFault($info,$info_text);
               }
            } else {
               $info = 'ERROR: GET MATERIAL LIST';
               $info_text = 'context of material ('.$material_id.') lost';
               $result = new SoapFault($info,$info_text);
            }
         } else {
            $info = 'ERROR: GET MATERIAL LIST';
            $info_text = 'material id ('.$material_id.') does not exist';
            $result = new SoapFault($info,$info_text);
         }
         $this->_updateSessionCreationDate($session_id);
      } else {
         $info = 'ERROR: GET MATERIAL LIST';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function getFileItem ($session_id, $file_id) {
      $session_id = $this->_encode_input($session_id);
      $file_id = $this->_encode_input($file_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $portal_id = $session->getValue('commsy_id');
                         $auth_source = $session->getValue('auth_source');
         $file_manager = $this->_environment->getFileManager();
         $file_item = $file_manager->getItem($file_id);
         if ( isset($file_item) and !empty($file_item) ) {
            $context_id = $file_item->getContextID();
            if ( !empty($context_id) ) {
               $room_manager = $this->_environment->getRoomManager();
               $room_item = $room_manager->getItem($context_id);
               if ( isset($room_item) and !empty($room_item) ) {
                  if ( $room_item->mayEnterByUserID($user_id,$auth_source) ) {
                     $file_item->setPortalID($portal_id);
                     $result = $file_item->getDataAsXML(true);
                     $result = $this->_encode_output($result);
                  } else {
                     $info = 'ERROR: GET FILE ITEM';
                     $info_text = 'user_id ('.$user_id.') don\'t have the permission to get the file ('.$file_id.')';
                     $result = new SoapFault($info,$info_text);
                  }
               } else {
                  $info = 'ERROR: GET FILE ITEM';
                  $info_text = 'context ('.$context_id.') of file ('.$file_id.') does not exits';
                  $result = new SoapFault($info,$info_text);
               }
            } else {
               $info = 'ERROR: GET FILE ITEM';
               $info_text = 'context of file ('.$file_id.') lost';
               $result = new SoapFault($info,$info_text);
            }
         } else {
            $info = 'ERROR: GET FILE ITEM';
            $info_text = 'file id ('.$file_id.') does not exist';
            $result = new SoapFault($info,$info_text);
         }
         $this->_updateSessionCreationDate($session_id);
      } else {
         $info = 'ERROR: GET FILE ITEM';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function deleteFileItem ($session_id, $file_id) {
      $session_id = $this->_encode_input($session_id);
      $file_id = $this->_encode_input($file_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $portal_id = $session->getValue('commsy_id');
         $auth_source = $session->getValue('auth_source');
         $file_manager = $this->_environment->getFileManager();
         $file_item = $file_manager->getItem($file_id);
         if ( isset($file_item) and !empty($file_item) ) {
            $context_id = $file_item->getContextID();
            if ( !empty($context_id) ) {
               $room_manager = $this->_environment->getRoomManager();
               $room_item = $room_manager->getItem($context_id);
               if ( isset($room_item) and !empty($room_item) ) {
                  if ( $room_item->mayEnterByUserID($user_id,$auth_source) ) {
                     if ( $file_item->mayEditByUserID($user_id,$auth_source) ) {
                        $file_item->delete();
                        $result = 'success';
                        $result = $this->_encode_output($result);
                     } else {
                        $info = 'ERROR: DELETE FILE ITEM';
                        $info_text = 'user_id ('.$user_id.') don\'t have the permission to delete the file ('.$file_id.')';
                        $result = new SoapFault($info,$info_text);
                     }
                  } else {
                     $info = 'ERROR: DELETE FILE ITEM';
                     $info_text = 'user_id ('.$user_id.') don\'t have the permission to enter the room ('.$room_item->getTitle().')';
                     $result = new SoapFault($info,$info_text);
                  }
               } else {
                  $info = 'ERROR: DELETE FILE ITEM';
                  $info_text = 'context ('.$context_id.') of file ('.$file_id.') does not exits';
                  $result = new SoapFault($info,$info_text);
               }
            } else {
               $info = 'ERROR: DELETE FILE ITEM';
               $info_text = 'context of file ('.$file_id.') lost';
               $result = new SoapFault($info,$info_text);
            }
         } else {
            $info = 'ERROR: DELETE FILE ITEM';
            $info_text = 'file id ('.$file_id.') does not exist';
            $result = new SoapFault($info,$info_text);
         }
         $this->_updateSessionCreationDate($session_id);
      } else {
         $info = 'ERROR: DELETE FILE ITEM';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function addPrivateRoomMaterialList ($session_id, $material_list_xml) {
      $session_id = $this->_encode_input($session_id);
      $result_array = array();
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $portal_id = $session->getValue('commsy_id');
         $auth_source = $session->getValue('auth_source');
         $room_manager = $this->_environment->getPrivateRoomManager();
         $room_item_id = $room_manager->getItemIDOfRelatedOwnRoomForUser($user_id,$auth_source,$portal_id);
         if ( isset($room_item_id) and !empty($room_item_id) ) {
            $material_xml_object = simplexml_load_string($material_list_xml);
            $user_manager = $this->_environment->getUserManager();
            $user_manager->setContextLimit($room_item_id);
            $user_manager->setUserIDLimit($user_id);
            $user_manager->setAuthSourceLimit($auth_source);
            $user_manager->select();
            $user_list = $user_manager->get();
            if ($user_list->getCount() == 1) {
               $user_item = $user_list->getFirst();
               $material_manager = $this->_environment->getMaterialManager();
               foreach ($material_xml_object->material_item as $material_xml_item) {
                  $material_item = $material_manager->getNewItem();
                  $material_item->setContextID($room_item_id);
                  $material_item->setCreatorID($user_item->getItemID());
                  $material_item->setModifierID($user_item->getItemID());
                  $title = $this->_encode_input((string)$material_xml_item->title);
                  if ( isset($title) and !empty($title) ) {
                     $material_item->setTitle($title);
                  }
                  $year = $this->_encode_input((int)$material_xml_item->date->year);
                  if ( isset($year) and !empty($year) ) {
                     $material_item->setPublishingDate($year);
                  }
                  if ( isset($material_xml_item->author_list) and !empty($material_xml_item->author_list) ) {
                     $author_list_string = '';
                     $first = true;
                     foreach ($material_xml_item->author_list->author_item as $author_xml_item) {
                        if ($first) {
                           $first = false;
                        } else {
                           $author_list_string .= '; ';
                        }
                        $author_list_string .= $this->_encode_input((string)$author_xml_item);
                     }
                     if ( !empty($author_list_string) ) {
                        $material_item->setAuthor($author_list_string);
                     }
                  }

                  // study_log information
                  if ( isset($material_xml_item->extras) and !empty($material_xml_item->extras) ) {
                     $extra_xml_string = $this->_encode_input($material_xml_item->extras->asXML());
                     $extra_xml_object = simplexml_load_string($extra_xml_string);
                     $xml = '';
                     foreach ($extra_xml_object->children() as $key => $extra_xml) {
                        $extra_xml = $this->_encode_input($extra_xml);
                        if ( $key == 'study_log' ) {
                           $xml .= '<study_log>'.htmlentities($extra_xml).'</study_log>';
                        }
                     }
                     if ( !empty($xml) ) {
                        $extra_array = XML2Array('<extras>'.$xml.'</extras>');
                        $material_item->setExtraInformation($extra_array);
                     }
                  }

                  // bib stuff
                  $value = $this->_encode_input((string)$material_xml_item->description);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setDescription($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->label);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setLabel($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->bib_kind);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setBibkind($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->common);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setBibliographicValues($value);
                  }
                  if ( isset($material_xml_item->editor_list) and !empty($material_xml_item->editor_list) ) {
                     $editor_list_string = '';
                     $first = true;
                     foreach ($material_xml_item->editor_list->editor_item as $editor_xml_item) {
                        if ($first) {
                           $first = false;
                        } else {
                           $editor_list_string .= '; ';
                        }
                        $editor_list_string .= $this->_encode_input((string)$editor_xml_item);
                     }
                     if ( !empty($editor_list_string) ) {
                        $material_item->setEditor($editor_list_string);
                     }
                  }
                  $value = $this->_encode_input((string)$material_xml_item->booktitle);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setBooktitle($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->publisher);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setPublisher($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->edition);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setEdition($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->volume);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setVolume($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->series);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setSeries($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->isbn);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setISBN($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->issn);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setISSN($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->pages);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setPages($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->journal);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setJournal($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->issue);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setIssue($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->university);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setUniversity($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->faculty);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setFaculty($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->thesis_kind);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setThesiskind($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->url);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setURL($value);
                  }
                  $value = $this->_encode_input((string)$material_xml_item->url_date);
                  if ( isset($value) and !empty($value) ) {
                     $material_item->setURLDate($value);
                  }

                  $material_item->save();
                  $item_id = (int)$material_xml_item->item_id;
                  $result_array[$item_id] = $material_item->getItemID();
               }
               $result  = '<link_list>'.LF;
               foreach ($result_array as $key => $value) {
                  $result .= '<link>'.LF;
                  $result .= '<original_id>'.$key.'</original_id>'.LF;
                  $result .= '<commsy_id>'.$value.'</commsy_id>'.LF;
                  $result .= '</link>'.LF;
               }
               $result .= '</link_list>'.LF;
               $result = $this->_encode_output($result);
            } else {
               $info = 'ERROR: ADD PRIVATE ROOM MATERIAL LIST';
               $info_text = 'user id ('.$user_id.') is not valid';
               $result = new SoapFault($info,$info_text);
            }
         }
         $this->_updateSessionCreationDate($session_id);
      } else {
         $info = 'ERROR: ADD PRIVATE ROOM MATERIAL LIST';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function addFileForMaterial ($session_id, $material_id, $file_item_xml) {
      $session_id = $this->_encode_input($session_id);
      $material_id = $this->_encode_input($material_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $portal_id = $session->getValue('commsy_id');
         $auth_source = $session->getValue('auth_source');
         $material_manager = $this->_environment->getMaterialManager();
         $material_item = $material_manager->getItem($material_id);
         if ( isset($material_item) and !empty($material_item) ) {
            $context_id = $material_item->getContextID();
            if ( !empty($context_id) ) {
               $room_manager = $this->_environment->getRoomManager();
               $room_item = $room_manager->getItem($context_id);
               if ( isset($room_item) and !empty($room_item) ) {
                  if ( $room_item->mayEnterByUserID($user_id,$auth_source) ) {
                     $file_xml_object = simplexml_load_string($file_item_xml);
                     $file_name = $this->_encode_input((string)$file_xml_object->filesname);
                     $file_base64 = (string)$file_xml_object->base64;
                     $disc_manager = $this->_environment->getDiscManager();
                     $temp_file = $disc_manager->saveFileFromBase64($file_name,$file_base64);
                     if ( isset($temp_file) and !empty($temp_file) ) {
                        $file_manager = $this->_environment->getFileManager();
                        $file_item = $file_manager->getNewItem();
                        $file_item->setFilename(rawurlencode(rawurldecode(basename($file_name))));
                        $file_item->setContextID($context_id);
                        $file_item->setPortalID($portal_id);
                        $file_item->setTempName($temp_file);
                        $file_item->save();
                        $file_id_array = $material_item->getFileIDArray();
                        $file_id_array[] = $file_item->getFileID();
                        $material_item->setFileIDArray($file_id_array);
                        $material_item->save();
                        unlink($temp_file);
                        $result = $file_item->getFileID();
                        $result = $this->_encode_output($result);
                     } else {
                        $info = 'ERROR: ADD FILE FOR MATERIAL';
                        $info_text = 'don\'t have the permission to save the file';
                        $result = new SoapFault($info,$info_text);
                     }
                  } else {
                     $info = 'ERROR: ADD FILE FOR MATERIAL';
                     $info_text = 'user_id ('.$user_id.') don\'t have the permission to get the material ('.$material_id.')';
                     $result = new SoapFault($info,$info_text);
                  }
               } else {
                  $info = 'ERROR: ADD FILE FOR MATERIAL';
                  $info_text = 'context ('.$context_id.') of material ('.$material_id.') does not exits';
                  $result = new SoapFault($info,$info_text);
               }
            } else {
               $info = 'ERROR: ADD FILE FOR MATERIAL';
               $info_text = 'context of material ('.$material_id.') lost';
               $result = new SoapFault($info,$info_text);
            }
         } else {
            $info = 'ERROR: ADD FILE FOR MATERIAL';
            $info_text = 'material id ('.$material_id.') does not exist';
            $result = new SoapFault($info,$info_text);
         }
         $this->_updateSessionCreationDate($session_id);
      } else {
         $info = 'ERROR: ADD FILE FOR MATERIAL';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function linkFileToMaterial ($session_id, $material_id, $file_id) {
      $session_id = $this->_encode_input($session_id);
      $material_id = $this->_encode_input($material_id);
      $file_id = $this->_encode_input($file_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $portal_id = $session->getValue('commsy_id');
         $auth_source = $session->getValue('auth_source');
         $material_manager = $this->_environment->getMaterialManager();
         $material_item = $material_manager->getItem($material_id);
         if ( isset($material_item) and !empty($material_item) ) {
            $context_id = $material_item->getContextID();
            if ( !empty($context_id) ) {
               $room_manager = $this->_environment->getRoomManager();
               $room_item = $room_manager->getItem($context_id);
               if ( isset($room_item) and !empty($room_item) ) {
                  if ( $room_item->mayEnterByUserID($user_id,$auth_source) ) {
                     $file_manager = $this->_environment->getFileManager();
                     $file_item = $file_manager->getItem($file_id);
                     if ( isset($file_item) and !empty($file_item) ) {
                        $context_id2 = $file_item->getContextID();
                        if ( !empty($context_id2) ) {
                           if ( $context_id == $context_id2) {
                              $user_manager = $this->_environment->getUserManager();
                              $user_manager->setContextLimit($context_id);
                              $user_manager->setUserIDLimit($user_id);
                              $user_manager->setAuthSourceLimit($auth_source);
                              $user_manager->select();
                              $user_list = $user_manager->get();
                              if ($user_list->getCount() == 1) {
                                 $user_item = $user_list->getFirst();
                                 if ( $material_item->mayEdit($user_item) ) {
                                    $file_item->setPortalID($portal_id);
                                    $file_manager = $this->_environment->getFileManager();
                                    $new_file_item = $file_manager->getNewItem();
                                    $new_file_item->setFilename(rawurlencode(rawurldecode($file_item->getFilename())));
                                    $new_file_item->setContextID($context_id);
                                    $new_file_item->setPortalID($portal_id);
                                    $new_file_item->setTempName($file_item->getDiskFilename());
                                    $new_file_item->save();
                                    $file_id_array = $material_item->getFileIDArray();
                                    $file_id_array[] = $new_file_item->getFileID();
                                    $material_item->setFileIDArray($file_id_array);
                                    $material_item->save();
                                    $result = $file_item->getFileID();
                                    $result = $this->_encode_output($result);
                                 } else {
                                    $info = 'ERROR: LINK FILE TO MATERIAL';
                                    $info_text = 'user with user_id ('.$user_id.') is not allowed to edit material ('.$material_id.')';
                                    $result = new SoapFault($info,$info_text);
                                 }
                              } else {
                                 $info = 'ERROR: LINK FILE TO MATERIAL';
                                 $info_text = 'multiple users with user_id ('.$user_id.') in context ('.$context_id.')';
                                 $result = new SoapFault($info,$info_text);
                              }
                           } else {
                              $info = 'ERROR: LINK FILE TO MATERIAL';
                              $info_text = 'context ('.$context_id2.') of file ('.$file_id.') and context ('.$context_id.') of material are not equal';
                              $result = new SoapFault($info,$info_text);
                           }
                        } else {
                           $info = 'ERROR: LINK FILE TO MATERIAL';
                           $info_text = 'context ('.$context_id2.') of file ('.$file_id.') does not exits';
                           $result = new SoapFault($info,$info_text);
                        }
                     } else {
                        $info = 'ERROR: LINK FILE TO MATERIAL';
                        $info_text = 'file id ('.$file_id.') does not exist';
                        $result = new SoapFault($info,$info_text);
                     }
                  } else {
                     $info = 'ERROR: LINK FILE TO MATERIAL';
                     $info_text = 'user_id ('.$user_id.') don\'t have the permission to get the material ('.$material_id.')';
                     $result = new SoapFault($info,$info_text);
                  }
               } else {
                  $info = 'ERROR: LINK FILE TO MATERIAL';
                  $info_text = 'context ('.$context_id.') of material ('.$material_id.') does not exits';
                  $result = new SoapFault($info,$info_text);
               }
            } else {
               $info = 'ERROR: LINK FILE TO MATERIAL';
               $info_text = 'context of material ('.$material_id.') lost';
               $result = new SoapFault($info,$info_text);
            }
         } else {
            $info = 'ERROR: LINK FILE TO MATERIAL';
            $info_text = 'material id ('.$material_id.') does not exist';
            $result = new SoapFault($info,$info_text);
         }
         $this->_updateSessionCreationDate($session_id);
      } else {
         $info = 'ERROR: ADD FILE FOR MATERIAL';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function getItemsSinceLastLogin ($session_id) {
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $portal_id = $session->getValue('commsy_id');
         $this->_environment->setCurrentContextID($portal_id);
         $portal_item = $this->_environment->getCurrentPortalItem();
         $result  = '<portal_item>';
         $result .= '<name>'.$portal_item->getTitle().'</name>'.LF;
         $result .= '<item_id>'.$portal_item->getItemID().'</item_id>'.LF;
         $result .= '<type>ROOM_TYPE_PORTAL</type>'.LF;
         $auth_source = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextLimit($portal_id);
         $user_manager->setUserIDLimit($user_id);
         $user_manager->setAuthSourceLimit($auth_source);
         $user_manager->select();
         $user_list = $user_manager->get();
         if ($user_list->getCount() == 1) {
            $user_item = $user_list->getFirst();
            $result .= '<room_list>'.LF;
            $room_list = $user_item->getRelatedCommunityList();
            $room_list->addList($user_item->getRelatedProjectList());
            $room_item = $room_list->getFirst();
            $item_manager = $this->_environment->getItemManager();
            while ($room_item) {
               $result .= '<room_item>'.LF;
               $result .= '<name><![CDATA['.$room_item->getTitle().']]></name>'.LF;
               $result .= '<item_id><![CDATA['.$room_item->getItemID().']]></item_id>'.LF;
               if ( $room_item->getItemType() == CS_PROJECT_TYPE ) {
                  $result .= '<type><![CDATA[ROOM_TYPE_PROJECT]]></type>'.LF;
               } elseif ( $room_item->getItemType() == CS_GROUP_TYPE ) {
                  $result .= '<type><![CDATA[ROOM_TYPE_GROUP]]></type>'.LF;
               } else {
                  $result .= '<type><![CDATA[ROOM_TYPE_COMMUNITY]]></type>'.LF;
               }
               $item_manager->setContextLimit($room_item->getItemID());
               $item_manager->setUserUserIDLimit($user_item->getUserID());
               $item_manager->setUserAuthSourceIDLimit($user_item->getAuthSource());
               $item_manager->setUserSinceLastloginLimit();
               $item_manager->setOutputLimitToXML();
               $item_manager->select();
               $item_list_xml = $item_manager->get();
               if ($item_list_xml != '<items_list></items_list>') {
                  $item_list_xml = str_replace('<deletion_date></deletion_date>','',$item_list_xml);
                  $item_list_xml = str_replace('<deleter_id></deleter_id>','',$item_list_xml);
                  $item_list_xml = preg_replace('$<context_id>[\d]*</context_id>$','',$item_list_xml);
                  $result .= $item_list_xml;
               }
               $result .= '</room_item>'.LF;
               $room_item = $room_list->getNext();
            }
            $result .= '</room_list>'.LF;
         } else {
            $info = 'ERROR: GET ITEMS SINCE LASTLOGIN';
            $info_text = 'multiple users with user_id ('.$user_id.') in context ('.$portal_id.')';
            $result = new SoapFault($info,$info_text);
         }
         $result .= '</portal_item>';
         $result = $this->_encode_output($result);
      } else {
         $info = 'ERROR: GET ITEMS SINCE LASTLOGIN';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   private function _checkUserInfoForCompletness($user_info) {
      $valid = true;
   }

  public function addMaterialLimit($session_id, $key, $value) {
    $key = $this->_encode_input($key);
    $value = $this->_encode_input($value);
    $session_id = $this->_encode_input($session_id);
    $this->_material_limit_array[$key] = $value;
    $session_manager = $this->_environment->getSessionManager();
    $session = $session_manager->get($session_id);
    $session->setValue('material_limit_array', $this->_material_limit_array);
    $session_manager->save($session);
    return $this->_encode_output($this->_material_limit_array);
  }

  public function getBuzzwordList($session_id, $context_id) {
    $session_id = $this->_encode_input($session_id);
    $context_id = $this->_encode_input($context_id);
    if($this->_isAccessValid($session_id, $context_id)) {
      $retour = '';
      $buzzword_manager = $this->_environment->getLabelManager();
      $buzzword_manager->resetLimits();
      $buzzword_manager->setContextLimit($context_id);
      $buzzword_manager->setTypeLimit('buzzword');
      $buzzword_manager->select();
      $buzzword_list = $buzzword_manager->get();
      $retour .= '<buzzword_list>';
      if (!$buzzword_list->isEmpty()) {
        $buzzword_item = $buzzword_list->getFirst();
        while ($buzzword_item) {
          $retour .= $buzzword_item->getDataAsXML();
          $buzzword_item = $buzzword_list->getNext();
        }
      }
      $retour .= '</buzzword_list>';
      $retour = $this->_encode_output($retour);
      return $retour;
    } else {
      return $this->_soap_fault;
    }
  }

  public function getLabelList($session_id, $context_id) {
    $session_id = $this->_encode_input($session_id);
    $context_id = $this->_encode_input($context_id);
    if($this->_isAccessValid($session_id, $context_id)) {
      $label_manager =  $this->_environment->getLabelManager();
      $label_manager->resetLimits();
      $label_manager->setContextLimit($context_id);
      $label_manager->setTypeLimit('label');
      $label_manager->select();
      $label_list = $label_manager->get();
      $retour = '<label_list>';
      if (!$label_list->isEmpty()) {
        $label_item = $label_list->getFirst();
        while ($label_item) {
          $retour .= $label_item->getDataAsXML();
          $label_item = $label_list->getNext();
        }
      }
      $retour .= '</label_list>';
      $retour = $this->_encode_output($retour);
      return $retour;
    } else {
      return $this->_soap_fault;
    }
  }

  public function getGroupList($session_id, $context_id) {
    $session_id = $this->_encode_input($session_id);
    $context_id = $this->_encode_input($context_id);
    if($this->_isAccessValid($session_id, $context_id)) {
      $retour = '';
      $group_manager =  $this->_environment->getGroupManager();
      $group_manager->resetLimits();
      $group_manager->setContextLimit($context_id);
      $group_manager->select();
      $group_list = $group_manager->get();
      $retour .= '<group_list>';
      if (!$group_list->isEmpty()) {
        $group_item = $group_list->getFirst();
        while ($group_item) {
          $retour .= $group_item->getDataAsXML();
          $group_item = $group_list->getNext();
        }
      }
      $retour .= '</group_list>';
      $retour = $this->_encode_output($retour);
      return $retour;
    } else {
      return $this->_soap_fault;
    }
  }

  public function getTopicList($session_id, $context_id) {
    $session_id = $this->_encode_input($session_id);
    $context_id = $this->_encode_input($context_id);
    if($this->_isAccessValid($session_id, $context_id)) {
      $retour = '';
      $topic_manager =  $this->_environment->getTopicManager();
      $topic_manager->resetLimits();
      $topic_manager->setContextLimit($context_id);
      $topic_manager->select();
      $topic_list = $topic_manager->get();
      $retour .= '<topic_list>';
      if (!$topic_list->isEmpty()) {
        $topic_item = $topic_list->getFirst();
        while ($topic_item) {
          $retour .= $topic_item->getDataAsXML();
          $topic_item = $topic_list->getNext();
        }
      }
      $retour .= '</topic_list>';
      $retour = $this->_encode_output($retour);
      return $retour;
    } else {
      return $this->_soap_fault;
    }
  }

  private function _isAccessValid($session_id, $context_id) {
    $result = false;
    $this->_soap_fault = new SoapFault('UNKNOWN','an unknown error occured');
    if ($this->_isSessionValid($session_id)) {
      $this->_environment->setSessionID($session_id);
      $session = $this->_environment->getSessionItem();
      $user_id = $session->getValue('user_id');
      $portal_id = $session->getValue('commsy_id');
      $auth_source = $session->getValue('auth_source');
      $room_manager = $this->_environment->getRoomManager();
      $room_item = $room_manager->getItem($context_id);
      $room_context_id = $room_item->getContextID();
      if ( $room_context_id != $portal_id ) {
        $result = false;
        $info = 'ERROR: GET MATERIAL LIST';
        $info_text = 'room with id ('.$context_id.') is not on the commsy portal form session with id ('.$portal_id.')';
        $this->_soap_fault = new SoapFault($info,$info_text);
      } elseif ( !$room_item->mayEnterByUserID($user_id,$auth_source) ) {
        $result = false;
        $info = 'ERROR: GET MATERIAL LIST';
        $info_text = 'user with user_id ('.$user_id.') is not allowed to enter the room with id ('.$context_id.')';
        $this->_soap_fault = new SoapFault($info,$info_text);
      } else {
        $result = true;
        $this->_soap_fault = null;
      }
      $this->_updateSessionCreationDate($session_id);
    } else {
      $result = false;
      $info = 'ERROR: GET MATERIAL LIST';
      $info_text = 'session id ('.$session_id.') is not valid';
      $this->_soap_fault = new SoapFault($info,$info_text);
    }
    return $result;
  }

   public function _log ($mod,$fct,$params) {
      $array = array();
      $array['iid'] = -1;

      if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
         $array['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
      } else {
         $array['user_agent'] = 'SOAP: No Info';
      }
      if ( !empty($_POST) ) {
         $array['post_content'] = implode(',',$_POST);
      } else {
         $array['post_content'] = '';
      }

      $array['remote_addr']    = $_SERVER['REMOTE_ADDR'];
      $array['script_name']    = $_SERVER['SCRIPT_NAME'];
      $array['query_string']   = $_SERVER['QUERY_STRING'];
      $array['request_method'] = $_SERVER['REQUEST_METHOD'];

      $current_user = $this->_environment->getCurrentUserItem();
      $array['user_item_id'] = $current_user->getItemID();
      $array['user_user_id'] = $current_user->getUserID();
      unset($current_user);

      $array['context_id'] = $this->_environment->getCurrentContextID();
      $array['module'] = $mod;
      $array['function'] = $fct;
      $array['parameter_string'] = $params;

      $log_manager = $this->_environment->getLogManager();
      $log_manager->saveArray($array);
   }
}
?>
