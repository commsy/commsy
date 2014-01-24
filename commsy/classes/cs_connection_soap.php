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
      return $value;
   }

   private function _encode_output ($value) {
      return $value;
   }

   private function _htmlTextareaSecurity ( $value ) {
      if ( strlen($value) != strlen(strip_tags($value)) ) {
         $value = preg_replace('~<!-- KFC TEXT -->~u','',$value);
         $value = preg_replace('~<!-- KFC TEXT [a-z0-9]* -->~u','',$value);
         if ( strlen($value) != strlen(strip_tags($value)) ) {
            $text_converter = $this->_environment->getTextConverter();
            if ( isset($text_converter) ) {
               $value = $text_converter->cleanBadCode($value);
            }
         }
         include_once('functions/security_functions.php');
         $fck_text = '<!-- KFC TEXT '.getSecurityHash($value).' -->';
         $value = $fck_text.$value.$fck_text;
      }
      return $value;
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
         $session->setSoapSession();
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

   public function createUser ($session_id,$portal_id,$firstname,$lastname,$mail,$user_id,$user_pwd,$agb = false,$send_email = true) {
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
               return new SoapFault('ERROR','createUser: can not get session_item with query: '.$last_query.' - '.__FILE__.' - '.__LINE__);
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
               include_once('functions/text_functions.php');
               if ( !withUmlaut($user_id) ) {
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
                                 global $c_single_entry_point;
                                 $body .= str_replace('soap.php',$c_single_entry_point,$url);
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
                           if ( $send_email
                                and $current_user->isUser()
                              ) {
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
                              global $c_single_entry_point;
                              $body .= str_replace('soap.php',$c_single_entry_point,$url);
                              $mail->set_message($body);
                              $mail->send();
                           }

                           // login in user
                           #return $this->authenticate($this->_encode_output($user_id),$this->_encode_output($user_pwd),$this->_encode_output($portal_id),$this->_encode_output($auth_source_id));
                           return true;
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
                  return new SoapFault('ERROR','createUser: user_id is not valid: user_id has umlauts! - '.__FILE__.' - '.__LINE__);
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

   public function createMembershipBySession ( $session_id, $context_id, $agb = false ) {
      $session_id = $this->_encode_input($session_id);
      $context_id = $this->_encode_input($context_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');

         // root or guest -> NO
         if ( mb_strtoupper($user_id, 'UTF-8') != 'GUEST'
              and mb_strtoupper($user_id, 'UTF-8') != 'ROOT'
            ) {
            $portal_id = $session->getValue('commsy_id');
            $this->_environment->setCurrentPortalID($portal_id);
            $auth_source = $session->getValue('auth_source');

            // portal: is user valid
            $user_manager = $this->_environment->getUserManager();
            $user_manager->setContextLimit($portal_id);
            $user_manager->setUserIDLimit($user_id);
            $user_manager->setAuthSourceLimit($auth_source);
            $user_manager->select();
            $user_list = $user_manager->get();
            if ($user_list->getCount() == 1) {
               $current_user = $user_list->getFirst();
               $this->_environment->setCurrentUserItem($current_user);

               // room: user allready exist?
               $room_manager = $this->_environment->getRoomManager();
               $room_item = $room_manager->getItem($context_id);
               if ( !empty($room_item) ) {
                  $room_user_item = $room_item->getUserByUserID($current_user->getUserID(),$current_user->getAuthSource());
                  if ( !isset($room_user_item) ) {

                     // now create membership
                     $private_room_user_item = $current_user->getRelatedPrivateRoomUserItem();
                     if ( isset($private_room_user_item) ) {
                        $user_item = $private_room_user_item->cloneData();
                        $picture = $private_room_user_item->getPicture();
                     } else {
                        $user_item = $current_user->cloneData();
                        $picture = $current_user->getPicture();
                     }
                     $user_item->setContextID($context_id);
                     if (!empty($picture)) {
                        $value_array = explode('_',$picture);
                        $value_array[0] = 'cid'.$user_item->getContextID();

                        $new_picture_name = implode('_',$value_array);
                        $disc_manager = $this->_environment->getDiscManager();
                        $disc_manager->copyImageFromRoomToRoom($picture,$user_item->getContextID());
                        $user_item->setPicture($new_picture_name);
                     }

                     //check room_settings
                     if ( !$room_item->checkNewMembersNever()
                          and !$room_item->checkNewMembersWithCode()
                        ) {
                        $user_item->request();
                        $check_message = 'YES'; // for mail body
                     } else {
                        $user_item->makeUser(); // for mail body
                        $check_message = 'NO';
                        // save link to the group ALL
                        $group_manager = $this->_environment->getLabelManager();
                        $group_manager->setExactNameLimit('ALL');
                        $group_manager->setContextLimit($room_item->getItemID());
                        $group_manager->select();
                        $group_list = $group_manager->get();
                        if ($group_list->getCount() == 1) {
                           $group = $group_list->getFirst();
                           $group->setTitle('ALL');
                           $user_item->setGroupByID($group->getItemID());
                        }
                     }

                     if ( $agb ) {
                        $user_item->setAGBAcceptance();
                     }
#                     if ($room_item->checkNewMembersNever()){
#                        $user_item->setStatus(2);
#                     }
                     $user_item->save();
                     $user_item->setCreatorID2ItemID();

                     // save task
                     if ( !$room_item->checkNewMembersNever()
                          and !$room_item->checkNewMembersWithCode()
                        ) {
                        $task_manager = $this->_environment->getTaskManager();
                        $task_item = $task_manager->getNewItem();
                        $task_item->setCreatorItem($current_user);
                        $task_item->setContextID($room_item->getItemID());
                        $task_item->setTitle('TASK_USER_REQUEST');
                        $task_item->setStatus('REQUEST');
                        $task_item->setItem($user_item);
                        $task_item->save();
                     }

                     // send email to moderators if necessary
                     $user_manager = $this->_environment->getUserManager();
                     $user_manager->resetLimits();
                     $user_manager->setModeratorLimit();
                     $user_manager->setContextLimit($room_item->getItemID());
                     $user_manager->select();
                     $user_list = $user_manager->get();
                     $email_addresses = array();
                     $moderator_item = $user_list->getFirst();
                     $recipients = '';
                     while ($moderator_item) {
                        $want_mail = $moderator_item->getAccountWantMail();
                        if (!empty($want_mail) and $want_mail == 'yes') {
                           $email_addresses[] = $moderator_item->getEmail();
                           $recipients .= $moderator_item->getFullname()."\n";
                        }
                        $moderator_item = $user_list->getNext();
                     }

                     // language
                     $language = $room_item->getLanguage();
                     if ($language == 'user') {
                        $language = $user_item->getLanguage();
                        if ($language == 'browser') {
                           $language = $this->_environment->getSelectedLanguage();
                        }
                     }

                     if ( count($email_addresses) > 0 ) {
                        $translator = $this->_environment->getTranslationObject();
                        $save_language = $translator->getSelectedLanguage();
                        $translator->setSelectedLanguage($language);
                        $subject = $translator->getMessage('USER_JOIN_CONTEXT_MAIL_SUBJECT',$user_item->getFullname(),$room_item->getTitle());
                        $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                        $body .= LF.LF;
                        $body .= $translator->getMessage('USER_JOIN_CONTEXT_MAIL_BODY',$user_item->getFullname(),$user_item->getUserID(),$user_item->getEmail(),$room_item->getTitle());
                        $body .= LF.LF;

                        $tempMessage = "";
                        switch ( cs_strtoupper($check_message) ) {
                           case 'YES':
                              $body .= $translator->getMessage('USER_GET_MAIL_STATUS_YES');
                              break;
                           case 'NO':
                              $body .= $translator->getMessage('USER_GET_MAIL_STATUS_NO');
                              break;
                           default:
                              $body .= $translator->getMessage('COMMON_MESSAGETAG_ERROR').' - '.__FILE__.' - '.__LINE__;
                              break;
                        }

                        $body .= LF.LF;
                        $body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
                        $body .= LF;
                        if ( cs_strtoupper($check_message) == 'YES') {
                           $body .= $translator->getMessage('MAIL_USER_FREE_LINK').LF;
                           $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$room_item->getItemID().'&mod=account&fct=index'.'&selstatus=1';
                        } else {
                           $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$room_item->getItemID();
                        }
                        global $c_single_entry_point;
                        $body .= str_replace('soap.php',$c_single_entry_point,$url);

                        include_once('classes/cs_mail.php');
                        $mail = new cs_mail();
                        $mail->set_to(implode(',',$email_addresses));
                        $server_item = $this->_environment->getServerItem();
                        $default_sender_address = $server_item->getDefaultSenderAddress();
                        if (!empty($default_sender_address)) {
                           $mail->set_from_email($default_sender_address);
                        } else {
                           $mail->set_from_email('@');
                        }
                        $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_item->getTitle()));
                        $mail->set_reply_to_name($user_item->getFullname());
                        $mail->set_reply_to_email($user_item->getEmail());
                        $mail->set_subject($subject);
                        $mail->set_message($body);
                        $mail->send();
                        $translator->setSelectedLanguage($save_language);
                     }

                     // send email to user when account is free automatically (PROJECT ROOM)
                     if ($user_item->isUser()) {

                        // get contact moderator (TBD) now first moderator
                        $user_list = $room_item->getModeratorList();
                        $contact_moderator = $user_list->getFirst();

                        // change context to project room
                        $translator = $this->_environment->getTranslationObject();
                        $translator->setEmailTextArray($room_item->getEmailTextArray());
                        $translator->setContext('project');
                        $save_language = $translator->getSelectedLanguage();

                        // language
                        $language = $room_item->getLanguage();
                        if ($language == 'user') {
                           $language = $user_item->getLanguage();
                           if ($language == 'browser') {
                              $language = $this->_environment->getSelectedLanguage();
                           }
                        }

                        $translator->setSelectedLanguage($language);

                        // email texts
                        $subject = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER',$room_item->getTitle());
                        $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                        $body .= LF.LF;
                        $body .= $translator->getEmailMessage('MAIL_BODY_HELLO',$user_item->getFullname());
                        $body .= LF.LF;
                        $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$user_item->getUserID(),$room_item->getTitle());
                        $body .= LF.LF;
                        $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$contact_moderator->getFullname(),$room_item->getTitle());
                        $body .= LF.LF;
                        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$room_item->getItemID();
                        global $c_single_entry_point;
                        $body .= str_replace('soap.php',$c_single_entry_point,$url);

                        // send mail to user
                        include_once('classes/cs_mail.php');
                        $mail = new cs_mail();
                        $mail->set_to($user_item->getEmail());
                        $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_item->getTitle()));
                        $server_item = $this->_environment->getServerItem();
                        $default_sender_address = $server_item->getDefaultSenderAddress();
                        if (!empty($default_sender_address)) {
                           $mail->set_from_email($default_sender_address);
                        } else {
                           $mail->set_from_email('@');
                        }
                        $mail->set_reply_to_email($contact_moderator->getEmail());
                        $mail->set_reply_to_name($contact_moderator->getFullname());
                        $mail->set_subject($subject);
                        $mail->set_message($body);
                        $mail->send();
                     }
                     return true;
                  } else {
                     return new SoapFault('ERROR','createMembershipBySession: user ('.$user_id.' | '.$auth_source.') allready exist in room ('.$context_id.'). - '.__FILE__.' - '.__LINE__);
                  }
               } else {
                  return new SoapFault('ERROR','createMembershipBySession: room ('.$context_id.') does not exist. - '.__FILE__.' - '.__LINE__);
               }
            } elseif ($user_list->getCount() > 1) {
               return new SoapFault('ERROR','createMembershipBySession: user ('.$user_id.' | '.$auth_source.') exists '.$user_list->getCount().' times -> error in database. - '.__FILE__.' - '.__LINE__);
            } else {
               return new SoapFault('ERROR','createMembershipBySession: user ('.$user_id.' | '.$auth_source.') does not exist. - '.__FILE__.' - '.__LINE__);
            }
         } else {
            return new SoapFault('ERROR','createMembershipBySession: root and guest are not allowed to become member in an room. - '.__FILE__.' - '.__LINE__);
         }
      } else {
         return new SoapFault('ERROR','createMembershipBySession: session id ('.$session_id.') is not valid. - '.__FILE__.' - '.__LINE__);
      }
   }

   public function authenticate ($user_id, $password, $portal_id = 99, $auth_source_id = 0) {
   	el('authenticate '. $user_id);
      el('authenticate');
      
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
         el('authenticate 1');
         $info = 'ERROR';
         $info_text = 'user_id or password lost';
      } else {
         el('authenticate 2');
         if ( !isset($this->_environment) ) {
            el('authenticate 3');
            $info = 'ERROR';
            $info_text = 'environment lost';
         } else {
            el('authenticate 4');
            $this->_environment->setCurrentContextID($portal_id);
            $authentication = $this->_environment->getAuthenticationObject();
            if ( isset($authentication) ) {
               el('authenticate 5');
               if ($authentication->isAccountGranted($user_id,$password,$auth_source_id)) {
                  el('authenticate 6');
                  if ($this->_isSessionActive($user_id,$portal_id)) {
                  	el('authenticate 7');
                  	$result = $this->_getActiveSessionID($user_id,$portal_id);
                     if ( empty($result) ) {
                        el('authenticate 8');
                        $info = 'ERROR';
                        $info_text = 'no session id from session manager -> database error';
                     }
                  } else {
                     el('authenticate 9');
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
                     $session->setValue('cookie','0');
                     $session->setSoapSession();

                     // save session
                     $session_manager = $this->_environment->getSessionManager();
                     $session_manager->save($session);

                     $result = $session->getSessionID();
                     
                     
                  }
               } else {
                  $info = 'ERROR';
                  $info_text = 'account not granted for user '.$user_id;
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

   public function authenticateWithLogin ($user_id, $password, $portal_id = 99, $auth_source_id = 0) {
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
                     $session->setValue('cookie','0');
                     $session->setLoginSession();
                     //$session->setSoapSession();

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

   public function authenticateViaSession($session_id) {
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_updateSessionCreationDate($session_id);
         $session_manager = $this->_environment->getSessionManager();
         $session_item = $session_manager->get($session_id);
         return $session_item->getValue('user_id');
      } else {
         return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
      }
   }

   public function wordpressAuthenticateViaSession($session_id) {
      $result = null;
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_updateSessionCreationDate($session_id);
         $this->_environment->setSessionID($session_id);
         $session_item = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($session_item->getValue('commsy_id'));

         // get user data from portal user item
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($session_item->getValue('user_id'),$session_item->getValue('auth_source'));
         $result = array(
                          'login'     => $user_item->getUserID(),
                          'email'     => $user_item->getEmail(),
                          'firstname' => $user_item->getFirstName(),
                          'lastname'  => $user_item->getLastName()
                        );

         // TBD: commsy authentication via soap

         // get md5-password for commsy internal accounts
         $auth_source_id = $session_item->getValue('auth_source');
         $auth_source_manager = $this->_environment->getAuthSourceManager();
         $auth_source_item = $auth_source_manager->getItem($auth_source_id);
         if ( $auth_source_item->isCommSyDefault() ) {
            $user_id = $session_item->getValue('user_id');
            $auth_source = $session_item->getValue('auth_source');
            $commsy_id = $session_item->getValue('commsy_id');
            //$result = array($user_id, $auth_source);
            $authentication = $this->_environment->getAuthenticationObject();
            $authManager = $authentication->getAuthManagerByAuthSourceItem($auth_source_item);
            $authManager->setContextID($commsy_id);
            //$result = array(get_class($authManager));
            $auth_item = $authManager->getItem($user_id);
            $result['password']  = $auth_item->getPasswordMD5();
         } else {
            // dummy password for external accounts
            include_once('functions/date_functions.php');
            $result['password'] = md5(getCurrentDateTimeInMySQL().rand(1,999).$this->_environment->getConfiguration('c_security_key'));
         }
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
      el('_getActiveSessionID '.$user_id);
      if ( !empty($this->_session_id_array[$portal_id][$user_id]) ) {
      	el('_getActiveSessionID !empty');
         $retour = $this->_session_id_array[$portal_id][$user_id];
      } else {
         $session_manager = $this->_environment->getSessionManager();
         $retour = $session_manager->getActiveSOAPSessionID($user_id,$portal_id);
         if ( !empty($retour) ) {
            $this->_session_id_array[$portal_id][$user_id] = $retour;
            $this->_updateSessionCreationDate($retour);
         }
      }
      el('_getActiveSessionID $retour '.$retour);
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

   public function refreshSession ($session_id) {
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_updateSessionCreationDate($session_id);
         return true;
      } else {
         return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
      }
   }

   public function logout ($session_id) {
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $session_manager = $this->_environment->getSessionManager();
         $session_manager->delete($session_id);
         return true;
      } else {
         return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
      }
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
         if ( !isset($room_item)
              or empty($room_item)
            ) {
            $room_manager = $this->_environment->getPrivateRoomManager() ;
            $room_item = $room_manager->getItem( $context_id) ;
         }
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
               if ( !isset($room_item)
                    or empty($room_item)
                  ) {
                  $room_manager = $this->_environment->getPrivateRoomManager();
                  $room_item = $room_manager->getItem( $context_id);
               }
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
               if ( !isset($room_item)
                    or empty($room_item)
                  ) {
                  $room_manager = $this->_environment->getPrivateRoomManager();
                  $room_item = $room_manager->getItem($context_id);
               }
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
               if ( !isset($room_item)
                    or empty($room_item)
                  ) {
                  $room_manager = $this->_environment->getPrivateRoomManager();
                  $room_item = $room_manager->getItem($context_id);
               }
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
               if ( !isset($room_item)
                    or empty($room_item)
                  ) {
                  $room_manager = $this->_environment->getPrivateRoomManager();
                  $room_item = $room_manager->getItem($context_id);
               }
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
            $this->_environment->setCurrentContextID($room_item_id);
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
                           $xml .= '<study_log>'.htmlentities($extra_xml, ENT_NOQUOTES, 'UTF-8').'</study_log>';
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
                     $value = $this->_htmlTextareaSecurity($value);
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
                     $value = $this->_htmlTextareaSecurity($value);
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
               if ( !isset($room_item)
                    or empty($room_item)
                  ) {
                  $room_manager = $this->_environment->getPrivateRoomManager();
                  $room_item = $room_manager->getItem($context_id);
               }
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
               if ( !isset($room_item)
                    or empty($room_item)
                  ) {
                  $room_manager = $this->_environment->getPrivateRoomManager();
                  $room_item = $room_manager->getItem($context_id);
               }
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
                  $item_list_xml = preg_replace('~<context_id>[\d]*</context_id>~u','',$item_list_xml);
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
      if ( !isset($room_item)
           or empty($room_item)
         ) {
         $room_manager = $this->_environment->getPrivateRoomManager();
         $room_item = $room_manager->getItem($context_id);
      }
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

   public function _log_in_file ($params) {
      global $c_commsy_path_file;
      if ( !file_exists($c_commsy_path_file . '/var/soap.log') ) {
         $logFileName = $c_commsy_path_file . '/var/soap.log';
         $logFileHandle = fopen($logFileName, 'w');
         fclose($logFileHandle);
      }
      $file_contents = file_get_contents($c_commsy_path_file . '/var/soap.log');
      foreach ($params as $param) {
         $file_contents =  $file_contents . "\n" . time() . ' - ' . $param[0] . ' - ' . $param[1];
      }
      file_put_contents($c_commsy_path_file . '/var/soap.log', $file_contents);
   }

   public function getUserInfo ($session_id, $context_id) {
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $auth_source = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextLimit($context_id);
         $user_manager->setUserIDLimit($user_id);
         $user_manager->setAuthSourceLimit($auth_source);
         $user_manager->select();
         $user_list = $user_manager->get();
         $user_info = '';
         if ($user_list->getCount() == 1) {
            $user_item = $user_list->getFirst();
            $user_info = $user_item->getDataAsXML();
         }
         $result = $this->_encode_output($user_info);
      } else {
         $info = 'ERROR: GET USER INFO';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function getRSSUrl ($session_id) {
      $retour = '';
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $context_id = $session->getValue('commsy_id');
         $this->_environment->setCurrentContextID($context_id);
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextLimit($context_id);
         $user_manager->setUserIDLimit($user_id);
         $user_manager->setAuthSourceLimit($auth_source_id);
         $user_manager->select();
         $user_list = $user_manager->get();
         if ( $user_list->getCount() == 1 ) {
            $user_item = $user_list->getFirst();
            $user_priv_item = $user_item->getRelatedPrivateRoomUserItem();
            if ( isset($user_priv_item) ) {
               $hash_manager = $this->_environment->getHashManager();
               $retour = $hash_manager->getRSSHashForUser($user_priv_item->getItemID());
               unset($hash_manager);
               if ( !empty($retour) ) {
                  global $c_commsy_domain, $c_commsy_url_path;
                  $retour = $c_commsy_domain.$c_commsy_url_path.'/rss.php?cid='.$user_priv_item->getContextID().'&hid='.$retour;
                  $result = $this->_encode_output($retour);
               } else {
                  $info = 'ERROR: GET RSS URL';
                  $info_text = 'rss hash is empty ('.$user_id.','.$auth_source_id.','.$context_id.')';
                  $result = new SoapFault($info,$info_text);
               }
            } else {
               $info = 'ERROR: GET RSS URL';
               $info_text = 'private room user does not exist ('.$user_id.','.$auth_source_id.','.$context_id.')';
               $result = new SoapFault($info,$info_text);
            }
            unset($user_priv_item);
            unset($user_item);
         } else {
            $info = 'ERROR: GET RSS URL';
            $info_text = 'database error: user ('.$user_id.','.$auth_source_id.','.$context_id.') not equal';
            $result = new SoapFault($info,$info_text);
         }
         unset($user_list);
         unset($user_manager);
         unset($session);
      } else {
         $info = 'ERROR: GET RSS URL';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function getRoomList ($session_id) {
      $retour = '';
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $context_id = $session->getValue('commsy_id');
         $this->_environment->setCurrentContextID($context_id);
         $hash_manager = $this->_environment->getHashManager();
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextLimit($context_id);
         $user_manager->setUserIDLimit($user_id);
         $user_manager->setAuthSourceLimit($auth_source_id);
         $user_manager->select();
         $user_list = $user_manager->get();
         if ( $user_list->getCount() == 1 ) {
            $user_item = $user_list->getFirst();
            if ( !empty($user_item) ) {
               $this->_environment->setCurrentUserItem($user_item);
            }
            $own_room = $user_item->getOwnRoom();
            $list = $own_room->getCustomizedRoomList();
            if ( !(isset($list) and $list->isNotEmpty()) ) {
               $community_list = $user_item->getRelatedCommunityList();
               $project_list = $user_item->getRelatedProjectListForMyArea();
               $group_list = $user_item->getRelatedGroupList();
               
               $class_factory = $this->_environment->getClassFactory();
               include_once('classes/cs_list.php');
               $list = new cs_list();
               if ( !empty($community_list) and $community_list->isNotEmpty() ) {
                  $list->addList($community_list);
               }
               if ( !empty($project_list) and $project_list->isNotEmpty() ) {
                  $list->addList($project_list);
               }
               if ( !empty($group_list) and $group_list->isNotEmpty() ) {
                  $list->addList($group_list);
               }
            }
            unset($user_item);
            if ( isset($list) and $list->isNotEmpty() ) {
               $retour = '<?xml version="1.0" encoding="utf-8"?>'.LF;
               $retour .= '   <list>'.LF;

               // portal
               $item = $this->_environment->getCurrentPortalItem();
               $retour .= '      <item>'.LF;
               $retour .= '         <title><![CDATA['.$item->getTitle().']]></title>'.LF;
               if ( $item->getItemID() > 99 ) {
                  $retour .= '         <id><![CDATA['.$item->getItemID().']]></id>'.LF;
                  global $c_commsy_domain, $c_commsy_url_path;
                  include_once('functions/curl_functions.php');
                  $retour .= '         <url><![CDATA['.$c_commsy_domain.$c_commsy_url_path.'/'._curl(false,$item->getItemID(),'home','index',array()).']]></url>'.LF;
               }
               $retour .= '      </item>'.LF;
               $retour .= '      <item>'.LF;
               $retour .= '         <title>-------------------------------</title>'.LF;
               $retour .= '         <id></id>'.LF;
               $retour .= '      </item>'.LF;

               // own room
               $item = $own_room;
               $retour .= '      <item>'.LF;
               $translator = $this->_environment->getTranslationObject();
               $retour .= '         <title><![CDATA['.$translator->getMessage($item->getTitle()).']]></title>'.LF;
               if ( $item->getItemID() > 99 ) {
                  $retour .= '         <id><![CDATA['.$item->getItemID().']]></id>'.LF;
                  global $c_commsy_domain, $c_commsy_url_path;
                  include_once('functions/curl_functions.php');
                  $retour .= '         <url><![CDATA['.$c_commsy_domain.$c_commsy_url_path.'/'._curl(false,$item->getItemID(),'home','index',array()).']]></url>'.LF;
                                 
                  // rss
                  if ( $item->isRSSOn() ) {
                     $own_room_user_item = $item->getOwnerUserItem();
                     if ( !empty($own_room_user_item) ) {
                        $rss_hash = $hash_manager->getRSSHashForUser($own_room_user_item->getItemID());
                        if ( !empty($rss_hash) ) {
                           global $c_commsy_domain, $c_commsy_url_path;
                           $rss_url = $c_commsy_domain.$c_commsy_url_path.'/rss.php?cid='.$item->getItemID().'&hid='.$rss_hash;
                           $retour .= '         <rss><![CDATA['.$rss_url.']]></rss>'.LF;
                        }
                        unset($rss_hash);
                        unset($rss_url);
                     }
                  }
               }
               $retour .= '      </item>'.LF;
               $retour .= '      <item>'.LF;
               $retour .= '         <title>-------------------------------</title>'.LF;
               $retour .= '         <id></id>'.LF;
               $retour .= '      </item>'.LF;

               $item = $list->getFirst();
               while ( $item ) {
                  $retour .= '      <item>'.LF;
                  $retour .= '         <title><![CDATA['.$item->getTitle().']]></title>'.LF;
                  if ( $item->getItemID() > 99 ) {
                     $retour .= '         <id><![CDATA['.$item->getItemID().']]></id>'.LF;
                     global $c_commsy_domain, $c_commsy_url_path;
                     include_once('functions/curl_functions.php');
                     $retour .= '         <url><![CDATA['.$c_commsy_domain.$c_commsy_url_path.'/'._curl(false,$item->getItemID(),'home','index',array()).']]></url>'.LF;
                                       
                     // rss
                     if ( $item->isRSSOn() ) {
                        $user_room_item = $item->getUserByUserID($user_id,$auth_source_id);
                        if ( !empty($user_room_item)
                             and !empty($hash_manager)
                           ) {
                           $rss_hash = $hash_manager->getRSSHashForUser($user_room_item->getItemID());
                           if ( !empty($rss_hash) ) {
                              global $c_commsy_domain, $c_commsy_url_path;
                              $rss_url = $c_commsy_domain.$c_commsy_url_path.'/rss.php?cid='.$item->getItemID().'&hid='.$rss_hash;
                              $retour .= '         <rss><![CDATA['.$rss_url.']]></rss>'.LF;
                           }
                           unset($rss_hash);
                           unset($rss_url);
                        }
                     }
                  }
                  $retour .= '      </item>'.LF;
                  $item = $list->getNext();
               }
               unset($hash_manager);
               $retour .= '   </list>'.LF;
               unset($list);
               $result = $this->_encode_output($retour);
            }
            if ( !empty($retour) ) {
               $result = $this->_encode_output($retour);
            }
            unset($own_room);
            unset($user_item);
         } else {
            $info = 'ERROR: GET ROOM LIST';
            $info_text = 'database error: user ('.$user_id.','.$auth_source_id.','.$context_id.') not equal';
            $result = new SoapFault($info,$info_text);
         }
         unset($user_list);
         unset($user_manager);
         unset($session);
      } else {
         $info = 'ERROR: GET ROOM LIST';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function getAuthenticationForWiki ($session_id, $context_id, $user_id) {
      #$this->_log_in_file(array(array('$user_id', $user_id)));
      $result = 'notAuthenticated';
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $auth_source = $session->getValue('auth_source');
         $this->_environment->setCurrentContextID($context_id);
         $context_item = $this->_environment->getCurrentContextItem();

         if ( !empty($auth_source)
              and !empty($user_id)
            ) {
            $user_manager = $this->_environment->getUserManager();
            $user_manager->setContextLimit($context_id);
            $user_manager->setUserIDLimit($user_id);
            $user_manager->setAuthSourceLimit($auth_source);
            $user_manager->select();
            $user_list = $user_manager->get();
            if ( $user_list->getCount() >= 1 ) {
               $user_item = $user_list->getFirst();
               if ( $user_item->isModerator() ){
                  $result = 'moderator';
               } elseif ( $user_item->isUser() ) {
                  if ( $context_item->isWikiRoomModWriteAccess() ) {
                     $result = 'read';
                  } else {
                     $result = 'user';
                  }
               }
            } elseif ( $context_item->isWikiPortalReadAccess() ) {
               $portal_id = $session->getValue('commsy_id');
               if ( !empty($portal_id) ) {
                  $user_manager->setContextLimit($portal_id);
                  $user_manager->setUserIDLimit($user_id);
                  $user_manager->setAuthSourceLimit($auth_source);
                  $user_manager->select();
                  $user_list = $user_manager->get();
                  if ( $user_list->getCount() == 1 ) {
                     $user_item = $user_list->getFirst();
                     if ( $user_item->isUser() ) {
                        $result = 'read';
                     }
                  }
               }
            }
            unset($user_manager);
            unset($user_list);
            unset($user_item);
         } else {
            $info = 'ERROR: GET AUTHENTICATION FOR WIKI';
            $info_text = 'session id ('.$session_id.') is not valid: no auth source id or no user_id';
            $result = new SoapFault($info,$info_text);
         }
      } else {
         $info = 'ERROR: GET AUTHENTICATION FOR WIKI';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function savePosForItem ($session_id, $item_id, $x, $y) {
      $result = true;
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $portal_id = $session->getValue('commsy_id');
         $auth_source = $session->getValue('auth_source');

         $item_id = $this->_encode_input($item_id);
         $item_manager = $this->_environment->getItemManager();
         $item_type = $item_manager->getItemType($item_id);
         $manager = $this->_environment->getManager($item_type);
         $item = $manager->getItem($item_id);
         if ( $item->mayEditByUserID($user_id,$auth_source) ) {
            $x = $this->_encode_input($x);
            $y = $this->_encode_input($y);
            $item->setPosX($x);
            $item->setPosY($y);
            $item->save();
            $this->_log('material','SOAP:savePosForItem','SID='.$session_id.'&item_id='.$item_id.'&x='.$x.'&y='.$y);
         } else {
            $info = 'ERROR: SAVE POS FOR ITEM';
            $info_text = 'user ('.$user_id.' / '.$auth_source.') is not allowed to edit item ('.$item_id.')';
            $result = new SoapFault($info,$info_text);
         }
         unset($manager);
         unset($item);
         unset($session);
         unset($item_manager);
         $this->_updateSessionCreationDate($session_id);
      } else {
         $info = 'ERROR: SAVE POS FOR ITEM';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function savePosForLink ($session_id, $item_id, $label_id, $x, $y) {
      $result = true;
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $portal_id = $session->getValue('commsy_id');
         $auth_source = $session->getValue('auth_source');

         $item_id = $this->_encode_input($item_id);
         $item_manager = $this->_environment->getItemManager();
         $item_type = $item_manager->getItemType($item_id);

         if ( $item_type == CS_LINKITEM_TYPE ) {
            $result = $this->savePosForItem($session_id,$item_id,$x,$y);
         } else {
            $label_id = $this->_encode_input($label_id);
            $label_type = $item_manager->getItemType($label_id);
            if ( $label_type == CS_LINKITEM_TYPE ) {
               $result = $this->savePosForItem($session_id,$label_id,$x,$y);
            } elseif ( $label_type == CS_TAG_TYPE ) {
               $link_manager = $this->_environment->getLinkItemManager();
               $link_item = $link_manager->getItemByFirstAndSecondID($item_id,$label_id);
               if ( isset($link_item) ) {
                  $result = $this->savePosForItem($session_id,$link_item->getItemID(),$x,$y);
               }
            } else {
               $manager = $this->_environment->getLinkManager();
               if ( $item_type != CS_BUZZWORD_TYPE ) {
                  $real_item_id = $item_id;
                  $real_item_type = $item_type;
                  $buzz_item_id = $label_id;
                  $buzz_item_type = $label_type;
               } else {
                  $real_item_id = $label_id;
                  $real_item_type = $label_type;
                  $buzz_item_id = $item_id;
                  $buzz_item_type = $item_type;
               }
               $manager_real_item = $this->_environment->getManager($real_item_type);
               $real_item = $manager_real_item->getItem($real_item_id);
               $link_type = 'buzzword_for';
               $link_list = $manager->getLinks($link_type,$real_item);
               foreach ( $link_list as $link_item ) {
                  if ( $link_item['from_item_id'] == $buzz_item_id
                       or $link_item['to_item_id'] == $buzz_item_id
                     ) {
                     $x = $this->_encode_input($x);
                     $y = $this->_encode_input($y);
                     $link_item['x'] = $x;
                     $link_item['y'] = $y;
                     $manager->savePos($link_item);
                     $this->_log('material','SOAP:savePosForLink','SID='.$session_id.'&item_id='.$label_id.'&item_id='.$label_id.'&x='.$x.'&y='.$y);
                     break;
                  }
               }
            }
         }
         unset($manager_real_item);
         unset($real_item);
         unset($manager);
         unset($item_manager);
         $this->_updateSessionCreationDate($session_id);
      } else {
         $info = 'ERROR: SAVE POS FOR ITEM';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function deleteWiki ($session_id, $context_id) {
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $room_manager = $this->_environment->getRoomManager();
         $room_item = $room_manager->getItem($context_id);
         $wiki_manager = $this->_environment->getWikiManager();
         $wiki_manager->deleteWiki($room_item);
      }
   }

   public function createWiki ($session_id, $context_id, $settings) {
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $room_manager = $this->_environment->getRoomManager();
         $room_item = $room_manager->getItem($context_id);

         $item->setWikiSkin();
         $item->setWikiEditPW();
         $item->setWikiAdminPW();
         $item->setWikiEditPW();
         $item->setWikiReadPW();
         $item->setWikiTitle();
         $item->setWikiShowCommSyLogin();
         $item->setWikiWithSectionEdit();
         $item->setWikiWithHeaderForSectionEdit();
         $item->setWikiEnableFCKEditor();
         $item->setWikiEnableSearch();
         $item->setWikiEnableSitemap();
         $item->setWikiEnableStatistic();
         $item->setWikiEnableRss();
         $item->setWikiEnableCalendar();
         $item->setWikiEnableNotice();
         $item->setWikiEnableGallery();
         $item->setWikiEnablePdf();
         $item->setWikiEnableSwf();
         $item->setWikiEnableWmplayer();
         $item->setWikiEnableQuicktime();
         $item->setWikiEnableYoutubeGoogleVimeo();
         $item->setWikiEnableDiscussion();
         //$item->setWikiDiscussionArray();
         $item->setWikiEnableDiscussionNotification();
         $item->setWikiEnableDiscussionNotificationGroups();

         $wiki_manager = $this->_environment->getWikiManager();
         $wiki_manager->deleteWiki($room_item);
      }
   }

   public function changeUserEmail($session_id, $email){
      $result = true;
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $context_id = $session->getValue('commsy_id');
         $this->_environment->setCurrentContextID($context_id);
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextLimit($context_id);
         $user_manager->setUserIDLimit($user_id);
         $user_manager->setAuthSourceLimit($auth_source_id);
         $user_manager->select();
         $user_list = $user_manager->get();
         if ( $user_list->getCount() == 1 ) {
            $user_item = $user_list->getFirst();
            $user_item->setEmail($email);
            $user_item->save();
         }
      } else {
         $info = 'ERROR: CHANGE USER EMAIL';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function changeUserEmailAll($session_id, $email){
      $result = true;
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $context_id = $session->getValue('commsy_id');
         $this->_environment->setCurrentContextID($context_id);
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextLimit($context_id);
         $user_manager->setUserIDLimit($user_id);
         $user_manager->setAuthSourceLimit($auth_source_id);
         $user_manager->select();
         $user_list = $user_manager->get();
         if ( $user_list->getCount() == 1 ) {
            $user_item = $user_list->getFirst();
            $dummy_user = $user_manager->getNewItem();
            $dummy_user->setEmail($email);
            $user_item->changeRelatedUser($dummy_user);
            $user_item->setEmail($email);
            $user_item->save();
         }
      } else {
         $info = 'ERROR: CHANGE USER EMAIL ALL';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function changeUserName($session_id, $firstname, $lastname){
      $result = true;
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $context_id = $session->getValue('commsy_id');
         $this->_environment->setCurrentContextID($context_id);
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextLimit($context_id);
         $user_manager->setUserIDLimit($user_id);
         $user_manager->setAuthSourceLimit($auth_source_id);
         $user_manager->select();
         $user_list = $user_manager->get();
         if ( $user_list->getCount() == 1 ) {
            $user_item = $user_list->getFirst();
            $dummy_user = $user_manager->getNewItem();
            $dummy_user->setFirstname($firstname);
            $dummy_user->setLastname($lastname);
            $user_item->changeRelatedUser($dummy_user);
            $user_item->setFirstname($firstname);
            $user_item->setLastname($lastname);
            $user_item->save();
         }
      } else {
         $info = 'ERROR: CHANGE USER EMAIL ALL';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function changeUserId($session_id, $new_user_id){
      $result = true;
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $context_id = $session->getValue('commsy_id');
         $this->_environment->setCurrentContextID($context_id);
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextLimit($context_id);
         $user_manager->setUserIDLimit($user_id);
         $user_manager->setAuthSourceLimit($auth_source_id);
         $user_manager->select();
         $user_list = $user_manager->get();
         if ( $user_list->getCount() == 1 ) {
            $user_item = $user_list->getFirst();
            $authentication = $this->_environment->getAuthenticationObject();
            $authentication->changeUserID($new_user_id,$user_item);
            $session->setValue('user_id',$new_user_id);
            $session_manager = $this->_environment->getSessionManager();
            $session_manager->save($session);
         }
      } else {
         $info = 'ERROR: CHANGE USER_ID';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   public function setUserExternalId($session_id, $external_id){
      $result = true;
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $context_id = $session->getValue('commsy_id');
         $this->_environment->setCurrentContextID($context_id);
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextLimit($context_id);
         $user_manager->setUserIDLimit($user_id);
         $user_manager->setAuthSourceLimit($auth_source_id);
         $user_manager->select();
         $user_list = $user_manager->get();
         if ( $user_list->getCount() == 1 ) {
            $user_item = $user_list->getFirst();
            $dummy_user = $user_manager->getNewItem();
            $dummy_user->setExternalID($external_id);
            $user_item->changeRelatedUser($dummy_user);
            $user_item->setExternalID($external_id);
            $user_item->save();
         }
      } else {
         $info = 'ERROR: SET USER EXTRA';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }

   function logToFile($msg){
     $fd = fopen('', "a");
     $str = "[" . date("Y/m/d h:i:s", mktime()) . "] " . $msg;
     fwrite($fd, $str . "\n");
     fclose($fd);
   }

   public function updateLastlogin ($session_id, $tool = 'commsy', $room_id = 0) {
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $context_id = $session->getValue('commsy_id');
         $this->_environment->setCurrentContextID($context_id);
         $user_manager = $this->_environment->getUserManager();
         if ( !empty($room_id)
              and $room_id != $context_id
            ) {
            $user_manager->setContextLimit($room_id);
         } else {
            $user_manager->setContextLimit($context_id);
         }
         $user_manager->setUserIDLimit($user_id);
         $user_manager->setAuthSourceLimit($auth_source_id);
         $user_manager->select();
         $user_list = $user_manager->get();
         if ( $user_list->getCount() == 1 ) {
            $user_item = $user_list->getFirst();
            include_once('functions/date_functions.php');
            if ( $tool != 'commsy' ) {
               $user_item->setLastLoginPlugin(getCurrentDateTimeInMySQL(),$tool);
               $user_item->setChangeModificationOnSave(false);
               $user_item->save();
            }
            if ( !empty($room_id)
                 and $room_id != $context_id
               ) {
               $portal_user_item = $user_item->getRelatedCommSyUserItem();
               if ( isset($portal_user_item) ) {
                  if ( $tool != 'commsy' ) {
                     $portal_user_item->setLastLoginPlugin(getCurrentDateTimeInMySQL(),$tool);
                     $portal_user_item->setChangeModificationOnSave(false);
                     $portal_user_item->save();
                     unset($portal_user_item);
                  }
               }
            }
            return true;
         } else {
            return new SoapFault('ERROR: UPDATELASTLOGIN','can not find user ('.$user_id.' | '.$auth_source_id.')!');
         }
      } else {
         return new SoapFault('ERROR: UPDATELASTLOGIN','Session ('.$session_id.') not valid!');
      }
   }

   public function getAGBFromRoom ( $context_id, $language ) {
      $result = '';
      $context_id = $this->_encode_input($context_id);
      $language = $this->_encode_input($language);
      if ( !empty($context_id) ) {
         $room_manager = $this->_environment->getRoomManager();
         $room_item = $room_manager->getItem($context_id);
         unset($room_manager);
         if ( !empty($room_item) ) {
            if ( $room_item->withAGB() ) {
               $agb_text_array = $room_item->getAGBTextArray();
               $language_array = array_keys($agb_text_array);
               if ( !in_array($language,$language_array)
                    and !in_array(mb_strtoupper($language,'UTF-8'),$language_array)
                    and !in_array(mb_strtolower($language,'UTF-8'),$language_array)
                  ) {
                  $language = 'de';
               }
               include_once('functions/text_functions.php');
               $result = $agb_text_array[cs_strtoupper($language)];
            } else {
               $result = new SoapFault('ERROR: getAGBFromRoom','agbs in room ('.$context_id.') are switched off.');
            }
         } else {
            $result = new SoapFault('ERROR: getAGBFromRoom','Context-ID ('.$context_id.') not valid!');
         }
      } else {
         $result = new SoapFault('ERROR: getAGBFromRoom','context_id is empty!');
      }
      return $result;
   }

   public function getStatistics($session_id, $date_start, $date_end){
      $result = '';
      $session_id = $this->_encode_input($session_id);
      if ($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $context_id = $session->getValue('commsy_id');
         $this->_environment->setCurrentContextID($context_id);
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextLimit($context_id);
         $user_manager->setUserIDLimit($user_id);
         $user_manager->setAuthSourceLimit($auth_source_id);
         $user_manager->select();
         $user_list = $user_manager->get();
         if ( $user_list->getCount() == 1 ) {
            $user_item = $user_list->getFirst();
            if ( $user_item->isRoot() ) {
               if ( !empty($date_start) ) {
                  $date_start = $this->_encode_input($date_start);
                  if ( !empty($date_end) ) {
                     $date_end = $this->_encode_input($date_end);
                  } else {
                     $date_end = 'NOW';
                  }
                  if ($date_end == 'NOW') {
                     $date_end = date('Y-m-d').' 23:59:59';
                  }
                  $server_item = $this->_environment->getServerItem();
                  if ( !empty($server_item) ) {
                     include_once('functions/misc_functions.php');
                     $result = array2XML($server_item->getStatistics($date_start,$date_end));
                  } else {
                     $info = 'ERROR: GET STATISTICS';
                     $info_text = 'server_item is empty';
                     $result = new SoapFault($info,$info_text);
                  }
               } else {
                  $info = 'ERROR: GET STATISTICS';
                  $info_text = 'date_start (second parameter) is empty';
                  $result = new SoapFault($info,$info_text);
               }
            } else {
               $info = 'ERROR: GET STATISTICS';
               $info_text = 'only root is allowed to use this function';
               $result = new SoapFault($info,$info_text);
            }
         } else {
            $info = 'ERROR: GET STATISTICS';
            $info_text = 'multiple user ('.$user_id.') with auth source ('.$auth_source_id.')';
            $result = new SoapFault($info,$info_text);
         }
      } else {
         $info = 'ERROR: GET STATISTICS';
         $info_text = 'session id ('.$session_id.') is not valid';
         $result = new SoapFault($info,$info_text);
      }
      return $result;
   }
   
   
   // ----------------------------------------
   //  Additional methods for Typo3 connection
   // ----------------------------------------
   
   public function getActiveRoomListForUser($session_id, $portal_id, $count) {
      if($this->_isSessionValid($session_id)) {
         // TODO: check for authenticated user id
         #if ($this->_isSessionActive('guest',$portal_id)) {
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
         #} else {
         #   return new SoapFault('ERROR','Session not active on portal '.$portal_id.'!');
         #}
      } else {
         return new SoapFault('ERROR','Session not valid!');
      }
      return $xml;
   }
   
   
   // ----------------------------------------
   //  Additional methods for iOS application
   // ----------------------------------------

   public function authenticateForApp ($user_id, $password, $portal_id = 99, $auth_source_id = 0) {
      el('authenticate '. $user_id);
      el('authenticate');
      
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
         el('authenticate 1');
         $info = 'ERROR';
         $info_text = 'user_id or password lost';
      } else {
         el('authenticate 2');
         if ( !isset($this->_environment) ) {
            el('authenticate 3');
            $info = 'ERROR';
            $info_text = 'environment lost';
         } else {
            el('authenticate 4');
            $this->_environment->setCurrentContextID($portal_id);
            $authentication = $this->_environment->getAuthenticationObject();
            if ( isset($authentication) ) {
               el('authenticate 5');
               if ($authentication->isAccountGranted($user_id,$password,$auth_source_id)) {
                  el('authenticate 6');
                  if ($this->_isSessionActiveForApp($user_id,$portal_id)) {
                     el('authenticate 7');
                     $result = $this->_getActiveSessionIDForApp($user_id,$portal_id);
                     if ( empty($result) ) {
                        el('authenticate 8');
                        $info = 'ERROR';
                        $info_text = 'no session id from session manager -> database error';
                     }
                  } else {
                     el('authenticate 9');
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
                     $session->setValue('cookie','0');
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
      el('authenticate: $result '.$result);
      el('authenticate: $info '.$info);
      el('authenticate: $info_text '.$info_text);
      if ( empty($result) and !empty($info) ) {
         $result = new SoapFault($info,$info_text);
      } else {
         $result = $this->_encode_output($result);
      }
      return $result;
   }
   
   private function _isSessionActiveForApp ($user_id, $portal_id) {
      $retour = false;
      if ( !empty($this->_session_id_array[$portal_id][$user_id]) ) {
         $retour = true;
      } else {
         $session_id = $this->_getActiveSessionIDForApp($user_id,$portal_id);
         if ( !empty($session_id) ) {
            $retour = true;
         }
      }
      return $retour;
   }

   private function _getActiveSessionIDForApp ($user_id, $portal_id) {
      $retour = '';
      el('_getActiveSessionID '.$user_id);
      if ( !empty($this->_session_id_array[$portal_id][$user_id]) ) {
         el('_getActiveSessionID !empty');
         $retour = $this->_session_id_array[$portal_id][$user_id];
      } else {
         $session_manager = $this->_environment->getSessionManager();
         $retour = $session_manager->getActiveSOAPSessionIDForApp($user_id,$portal_id);
         if ( !empty($retour) ) {
            $this->_session_id_array[$portal_id][$user_id] = $retour;
            $this->_updateSessionCreationDate($retour);
         }
      }
      el('_getActiveSessionID $retour '.$retour);
      return $retour;
   }
   
   public function getPortalRoomList($session_id, $portal_id) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $context_id = $session->getValue('commsy_id');
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         #$user_room_list = $user_item->getRelatedProjectList();
         $room_manager = $this->_environment->getRoomManager();
         #$room_manager->setContextLimit($portal_id);
         #$room_manager->setRoomTypeLimit(CS_PROJECT_TYPE);
         #$room_manager->setOrder('activity_rev');
         #$room_manager->select();
         #$user_room_list = null;
         $room_list = $room_manager->getRelatedRoomListForUser($user_item);

         #$room_list = $user_room_list;
         
         $room_item = $room_list->getFirst();
         $xml = "<room_list>\n";
         while($room_item) {
            #$user_room_item = $user_room_list->getFirst();
            #$is_room_user = false;
            #while($user_room_item){
            #   if($user_room_item->getItemID() == $room_item->getItemID()){
            #      $is_room_user = true;
            #   }
            #   $user_room_item = $user_room_list->getNext();
            #}
            $is_room_user = true;
            
            #$is_membership_pending = false;
            #if($is_room_user){
            #   $room_user = $room_item->getUserByUserID($user_id, $auth_source_id);
            #   if($room_user->getStatus() == '1'){
            #      $is_membership_pending = true;
            #   }
            #}
            $is_membership_pending = false;
            
            $xml .= "<room_item>";
            $xml .= "<title><![CDATA[".$room_item->getTitle()."]]></title>\n";
            $xml .= "<item_id><![CDATA[".$room_item->getItemID()."]]></item_id>\n";
            $xml .= "<context_id><![CDATA[".$room_item->getContextID()."]]></context_id>\n";
            if($is_room_user and !$is_membership_pending){
               $xml .= "<room_user><![CDATA[is_room_user]]></room_user>\n";
            } else {
               $xml .= "<room_user><![CDATA[is_not_room_user]]></room_user>\n";
            }
            if($is_membership_pending){
               $xml .= "<membership_pending><![CDATA[membership_is_pending]]></membership_pending>\n";
            } else {
               $xml .= "<membership_pending><![CDATA[membership_is_not_pending]]></membership_pending>\n";
            }
            $xml .= "<contact><![CDATA[".$room_item->getContactPersonString()."]]></contact>\n";
            $xml .= "</room_item>\n";
            $room_item = $room_list->getNext();
         }
         $xml .= "</room_list>";
         $xml = $this->_encode_output($xml);
      } else {
         return new SoapFault('ERROR','Session not valid!');
      }
      return $xml;
   }
   
   public function getPortalList() {                                                                    
      $portal_manager = $this->_environment->getPortalManager();
      $portal_manager->select();
      $portal_list = $portal_manager->get();
      $xml = "<portal_list>\n";
      $portal_item = $portal_list->getFirst();
      while($portal_item) {
         $xml .= "<portal_item>\n";
         $xml .= "<portal_id><![CDATA[".$portal_item->getItemID()."]]></portal_id>";
         $xml .= "<portal_title><![CDATA[".$portal_item->getTitle()."]]></portal_title>";
         $xml .= "</portal_item>\n";
         $portal_item = $portal_list->getNext();
      }
      $xml .= "</portal_list>";
      $xml = $this->_encode_output($xml);
      return $xml;
   }
   
   // Dates
   
   public function getDatesList($session_id, $context_id) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $reader_manager = $this->_environment->getReaderManager();
         $dates_manager = $this->_environment->getDatesManager();
         $dates_manager->setContextLimit($context_id);
         $dates_manager->showNoNotActivatedEntries();
         $dates_manager->setDateModeLimit(2);
         $count_all = $dates_manager->getCountAll();
         $dates_manager->select();
         $dates_list = $dates_manager->get();
         $xml = "<dates_list>\n";
         $date_item = $dates_list->getFirst();
         while($date_item) {
            $xml .= "<date_item>\n";
            $xml .= "<date_id><![CDATA[".$date_item->getItemID()."]]></date_id>\n";
            $temp_title = $date_item->getTitle();
            $temp_title = $this->prepareText($temp_title);
            $xml .= "<date_title><![CDATA[".$temp_title."]]></date_title>\n";
            $xml .= "<date_starting_date><![CDATA[".$date_item->getDateTime_start()."]]></date_starting_date>\n";
            $xml .= "<date_ending_date><![CDATA[".$date_item->getDateTime_end()."]]></date_ending_date>\n";
            $reader = $reader_manager->getLatestReaderForUserByID($date_item->getItemID(), $user_item->getItemID());
            if ( empty($reader) ) {
               $xml .= "<date_read><![CDATA[new]]></date_read>\n";
            } elseif ( $reader['read_date'] < $date_item->getModificationDate() ) {
               $xml .= "<date_read><![CDATA[changed]]></date_read>\n";
            } else {
               $xml .= "<date_read><![CDATA[]]></date_read>\n";
            }
            if($date_item->mayEdit($user_item)){
               $xml .= "<date_edit><![CDATA[edit]]></date_edit>\n";
            } else {
               $xml .= "<date_edit><![CDATA[non_edit]]></date_edit>\n";
            }
            $xml .= "</date_item>\n";
            $date_item = $dates_list->getNext();
         }
         $xml .= "</dates_list>";
         #debugToFile($xml);
         $xml = $this->_encode_output($xml);
         return $xml;
      }
   }
   
   public function getDateDetails($session_id, $context_id, $item_id) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $this->_environment->setCurrentUser($user_item);
         $reader_manager = $this->_environment->getReaderManager();
         $noticed_manager = $this->_environment->getNoticedManager();
         $dates_manager = $this->_environment->getDatesManager();
         $date_item = $dates_manager->getItem($item_id);
         $xml  = "<date_item>\n";
         $xml .= "<date_id><![CDATA[".$date_item->getItemID()."]]></date_id>\n";
         $temp_title = $date_item->getTitle();
         $temp_title = $this->prepareText($temp_title);
         $xml .= "<date_title><![CDATA[".$temp_title."]]></date_title>\n";
         $xml .= "<date_starting_date><![CDATA[".$date_item->getDateTime_start()."]]></date_starting_date>\n";
         $xml .= "<date_ending_date><![CDATA[".$date_item->getDateTime_end()."]]></date_ending_date>\n";
         $xml .= "<date_place><![CDATA[".$date_item->getPlace()."]]></date_place>\n";
         $temp_description = $date_item->getDescription();
         $allow_edit = true;
         if(stristr($temp_description, '<table')){
            $allow_edit = false;
         }
         $temp_description = $this->prepareText($temp_description);
         $xml .= "<date_description><![CDATA[".$temp_description."]]></date_description>\n";
         $reader = $reader_manager->getLatestReaderForUserByID($date_item->getItemID(), $user_item->getItemID());
         if ( empty($reader) ) {
            $xml .= "<date_read><![CDATA[new]]></date_read>\n";
         } elseif ( $reader['read_date'] < $date_item->getModificationDate() ) {
            $xml .= "<date_read><![CDATA[changed]]></date_read>\n";
         } else {
            $xml .= "<date_read><![CDATA[]]></date_read>\n";
         }
         if($date_item->mayEdit($user_item) && $allow_edit){
            $xml .= "<date_edit><![CDATA[edit]]></date_edit>\n";
         } else {
            $xml .= "<date_edit><![CDATA[non_edit]]></date_edit>\n";
         }
         $modifier_user = $date_item->getModificatorItem();
         $xml .= "<date_last_modifier><![CDATA[".$modifier_user->getFullname()."]]></date_last_modifier>\n";
         $xml .= "<date_last_modification_date><![CDATA[".$date_item->getModificationDate()."]]></date_last_modification_date>\n";
         $xml .= "<date_files>\n";
         $file_list = $date_item->getFileList();
         $temp_file = $file_list->getFirst();
         while($temp_file){
            $xml .= "<date_file>\n";
            $xml .= "<date_file_name><![CDATA[".$temp_file->getFileName()."]]></date_file_name>\n";
            $xml .= "<date_file_id><![CDATA[".$temp_file->getFileID()."]]></date_file_id>\n";
            $xml .= "<date_file_size><![CDATA[".$temp_file->getFileSize()."]]></date_file_size>\n";
            $xml .= "<date_file_mime><![CDATA[".$temp_file->getMime()."]]></date_file_mime>\n";
            //if($temp_file->getMime() == 'image/gif' || $temp_file->getMime() == 'image/jpeg' || $temp_file->getMime() == 'image/png'){
            //   $xml .= "<date_file_data><![CDATA[".$temp_file->getBase64()."]]></date_file_data>\n";
            //   debugToFile($temp_file->getBase64());
            //}
            $xml .= "</date_file>\n";
            $temp_file = $file_list->getNext();
         }
         $xml .= "</date_files>\n";
         $xml .= "</date_item>\n";
         $xml = $this->_encode_output($xml);
         $reader = $reader_manager->getLatestReaderForUserByID($date_item->getItemID(), $user_item->getItemID());
         if ( empty($reader) or $reader['read_date'] < $date_item->getModificationDate() ) {
            $reader_manager->markRead($date_item->getItemID(),0);
         }
         $noticed = $noticed_manager->getLatestNoticedForUserByID($date_item->getItemID(), $user_item->getItemID());
         if ( empty($noticed) or $noticed['read_date'] < $date_item->getModificationDate() ) {
            $noticed_manager->markNoticed($date_item->getItemID(),0);
         }
         return $xml;
      }
   }
   
   public function saveDate($session_id, $context_id, $item_id, $title, $place, $description, $startingDate, $startingTime, $endingDate, $endingTime, $uploadFiles, $deleteFiles) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $this->_environment->setCurrentUser($user_item);
         
         $dates_manager = $this->_environment->getDatesManager();
         debugToFile($item_id);
         if($item_id != 'NEW'){
            $date_item = $dates_manager->getItem($item_id);
         } else {
            debugToFile('is NEW');
            $date_item = $dates_manager->getNewItem();
            $date_item->setContextID($context_id);
            $date_item->setCreatorItem($user_item);
            $date_item->setCreationDate(getCurrentDateTimeInMySQL());
         }
         $title = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
         $date_item->setTitle($title);
         $place = html_entity_decode($place, ENT_COMPAT, 'UTF-8');
         $date_item->setPlace($place);
         $description = html_entity_decode($description, ENT_COMPAT, 'UTF-8');
         $date_item->setDescription(str_ireplace("\n", "\n".'<br />', $description));
         debugToFile($description);
         $date_item->setStartingDay($startingDate);
         $date_item->setStartingTime($startingTime);
         $date_item->setDateTime_start($startingDate.' '.$startingTime);
         $date_item->setEndingDay($endingDate);
         $date_item->setEndingTime($endingTime);
         $date_item->setDateTime_end($endingDate.' '.$endingTime);
         $date_item->save();
         
         $reader_manager = $this->_environment->getReaderManager();
         $noticed_manager = $this->_environment->getNoticedManager();
         $reader = $reader_manager->getLatestReaderForUserByID($date_item->getItemID(), $user_item->getItemID());
         $reader_manager->markRead($date_item->getItemID(),0);
         $noticed_manager->markNoticed($date_item->getItemID(),0);
         
         $this->_uploadFiles($uploadFiles, $date_item);
         
         $this->_deleteFiles($session_id, $deleteFiles, $date_item);
      }
   }
   
   function _uploadFiles($uploadFiles, $item){
      $uploadFilesArray = explode(',', $uploadFiles);
      $new_id_array = array();
      foreach($uploadFilesArray as $uploadFileData){
         if($uploadFileData != ''){
            $temp_file_name = 'upload_'.time().'.jpg';
            $disc_manager = $this->_environment->getDiscManager();
            $bin = base64_decode($uploadFileData);
            file_put_contents($disc_manager->getTempFolder().'/'.$temp_file_name, $bin);
            $file_manager = $this->_environment->getFileManager();
            $new_file = $file_manager->getNewItem();
            $new_file->setFileName($temp_file_name);
            $new_file->setTempName($disc_manager->getTempFolder().'/'.$temp_file_name);
            $new_file->setTempKey($temp_file_name);
            $new_file->save();
            $new_id_array[] = $new_file->getFileID();
         }
      }
      $old_id_array = $item->getFileIDArray();
      $merge_id_array = array_merge($old_id_array, $new_id_array);
      $item->setFileIDArray($merge_id_array);
      $item->save();
   }
   
   function _deleteFiles($session_id, $deleteFiles, $item){
      $deleteFilesArray = explode(',', $deleteFiles);
      foreach($deleteFilesArray as $deleteFile){
         if($deleteFile != ''){
            $this->deleteFileItem($session_id, $deleteFile);
         }
      }
   }
   
   public function updateDate($session_id, $item_id, $title, $place, $starting_date, $ending_date, $description) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         
      }
   }
   
   public function deleteDate($session_id, $context_id, $item_id) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $dates_manager = $this->_environment->getDatesManager();
         $date_item = $dates_manager->getItem($item_id);
         $date_item->delete();
      }
   }
   
   
   // Materials
   
   public function getMaterialsList($session_id, $context_id) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $reader_manager = $this->_environment->getReaderManager();
         $material_manager = $this->_environment->getMaterialManager();
         $material_manager->setContextLimit($context_id);
         $material_manager->showNoNotActivatedEntries();
         $material_manager->select();
         $material_list = $material_manager->get();
         $xml = "<material_list>\n";
         $material_item = $material_list->getFirst();
         while($material_item) {
            if($material_item->maySee($user_item)){
               $xml .= "<material_item>\n";
               $xml .= "<material_id><![CDATA[".$material_item->getItemID()."]]></material_id>\n";
               $temp_title = $material_item->getTitle();
               $temp_title = $this->prepareText($temp_title);
               $xml .= "<material_title><![CDATA[".$temp_title."]]></material_title>\n";
               $reader = $reader_manager->getLatestReaderForUserByID($material_item->getItemID(), $user_item->getItemID());
               if ( empty($reader) ) {
                  $xml .= "<material_read><![CDATA[new]]></material_read>\n";
               } elseif ( $reader['read_date'] < $material_item->getModificationDate() ) {
                  $xml .= "<material_read><![CDATA[changed]]></material_read>\n";
               } else {
                  $xml .= "<material_read><![CDATA[]]></material_read>\n";
               }
               if($material_item->mayEdit($user_item)){
                  $xml .= "<material_edit><![CDATA[edit]]></material_edit>\n";
               } else {
                  $xml .= "<material_edit><![CDATA[non_edit]]></material_edit>\n";
               }
               $xml .= "</material_item>\n";
            }
            $material_item = $material_list->getNext();
         }
         $xml .= "</material_list>";
         $xml = $this->_encode_output($xml);
         return $xml;
      }
   }
   
   public function getMaterialDetails($session_id, $context_id, $item_id) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $this->_environment->setCurrentUser($user_item);
         $reader_manager = $this->_environment->getReaderManager();
         $noticed_manager = $this->_environment->getNoticedManager();
         $material_manager = $this->_environment->getMaterialManager();
         $material_item = $material_manager->getItem($item_id);
         $xml  = "<material_item>\n";
         $xml .= "<material_id><![CDATA[".$material_item->getItemID()."]]></material_id>\n";
         $temp_title = $material_item->getTitle();
         $temp_title = $this->prepareText($temp_title);
         $xml .= "<material_title><![CDATA[".$temp_title."]]></material_title>\n";
         $temp_description = $material_item->getDescription();
         $allow_edit = true;
         if(stristr($temp_description, '<table')){
            $allow_edit = false;
         }
         $temp_description = $this->prepareText($temp_description);
         $xml .= "<material_description><![CDATA[".$temp_description."]]></material_description>\n";
         $reader = $reader_manager->getLatestReaderForUserByID($material_item->getItemID(), $user_item->getItemID());
         if ( empty($reader) ) {
            $xml .= "<material_read><![CDATA[new]]></material_read>\n";
         } elseif ( $reader['read_date'] < $material_item->getModificationDate() ) {
            $xml .= "<material_read><![CDATA[changed]]></material_read>\n";
         } else {
            $xml .= "<material_read><![CDATA[]]></material_read>\n";
         }
         if($material_item->mayEdit($user_item) && $allow_edit){
            $xml .= "<material_edit><![CDATA[edit]]></material_edit>\n";
         } else {
            $xml .= "<material_edit><![CDATA[non_edit]]></material_edit>\n";
         }
         $modifier_user = $material_item->getModificatorItem();
         $xml .= "<material_last_modifier><![CDATA[".$modifier_user->getFullname()."]]></material_last_modifier>\n";
         $xml .= "<material_last_modification_date><![CDATA[".$material_item->getModificationDate()."]]></material_last_modification_date>\n";
         $xml .= "<material_files>\n";
         $file_list = $material_item->getFileList();
         $temp_file = $file_list->getFirst();
         while($temp_file){
            $xml .= "<material_file>\n";
            $xml .= "<material_file_name><![CDATA[".$temp_file->getFileName()."]]></material_file_name>\n";
            $xml .= "<material_file_id><![CDATA[".$temp_file->getFileID()."]]></material_file_id>\n";
            $xml .= "<material_file_size><![CDATA[".$temp_file->getFileSize()."]]></material_file_size>\n";
            $xml .= "<material_file_mime><![CDATA[".$temp_file->getMime()."]]></material_file_mime>\n";
            $xml .= "</material_file>\n";
            $temp_file = $file_list->getNext();
         }
         $xml .= "</material_files>\n";
         
         $section_manager = $this->_environment->getSectionManager();
         $section_manager->setMaterialItemIDLimit($item_id);
         $section_manager->select();
         $section_list = $section_manager->get();
         $section_item = $section_list->getFirst();
         $xml .= "<material_sections>\n";
         while($section_item){
            $xml .= "<material_section>\n";
            $xml .= "<material_section_id>".$section_item->getItemID()."</material_section_id>\n";
            $temp_title = $section_item->getTitle();
            $temp_title = $this->prepareText($temp_title);
            $xml .= "<material_section_title>".$temp_title."</material_section_title>\n";
            $temp_description = $section_item->getDescription();
            $temp_description = $this->prepareText($temp_description);
            $xml .= "<material_section_description>".$temp_description."</material_section_description>\n";
            $modifier_user = $section_item->getModificatorItem();
            $xml .= "<material_section_last_modifier><![CDATA[".$modifier_user->getFullname()."]]></material_section_last_modifier>\n";
            $xml .= "<material_section_last_modification_date><![CDATA[".$section_item->getModificationDate()."]]></material_section_last_modification_date>\n";
            $xml .= "<material_section_files>\n";
            $file_list = $section_item->getFileList();
            $temp_file = $file_list->getFirst();
            while($temp_file){
               $xml .= "<material_section_file>\n";
               $xml .= "<material_section_file_name><![CDATA[".$temp_file->getFileName()."]]></material_section_file_name>\n";
               $xml .= "<material_section_file_id><![CDATA[".$temp_file->getFileID()."]]></material_section_file_id>\n";
               $xml .= "<material_section_file_size><![CDATA[".$temp_file->getFileSize()."]]></material_section_file_size>\n";
               $xml .= "<material_section_file_mime><![CDATA[".$temp_file->getMime()."]]></material_section_file_mime>\n";
               $xml .= "</material_section_file>\n";
               $temp_file = $file_list->getNext();
            }
            $xml .= "</material_section_files>\n";
            $xml .= "<material_section_number>".$section_item->getNumber()."</material_section_number>\n";
             $xml .= "</material_section>\n";
            $section_item = $section_list->getNext();
         }
         $xml .= "</material_sections>\n";
         
         $xml .= "</material_item>\n";
         $xml = $this->_encode_output($xml);
         $reader = $reader_manager->getLatestReaderForUserByID($material_item->getItemID(), $user_item->getItemID());
         if ( empty($reader) or $reader['read_date'] < $material_item->getModificationDate() ) {
            $reader_manager->markRead($material_item->getItemID(),0);
         }
         $noticed = $noticed_manager->getLatestNoticedForUserByID($material_item->getItemID(), $user_item->getItemID());
         if ( empty($noticed) or $noticed['read_date'] < $material_item->getModificationDate() ) {
            $noticed_manager->markNoticed($material_item->getItemID(),0);
         }
         return $xml;
      }
   }
   
   public function saveMaterial($session_id, $context_id, $item_id, $title, $description, $uploadFiles, $deleteFiles) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $this->_environment->setCurrentUser($user_item);
         
         $material_manager = $this->_environment->getMaterialManager();
         debugToFile($item_id);
         if($item_id != 'NEW'){
            $material_item = $material_manager->getItem($item_id);
         } else {
            debugToFile('is NEW');
            $material_item = $material_manager->getNewItem();
            $material_item->setContextID($context_id);
            $material_item->setCreatorItem($user_item);
            $material_item->setCreationDate(getCurrentDateTimeInMySQL());
         }
         
         $title = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
         $material_item->setTitle($title);
         $description = html_entity_decode($description, ENT_COMPAT, 'UTF-8');
         $material_item->setDescription(str_ireplace("\n", "\n".'<br />', $description));
         $material_item->save();
         
         $reader_manager = $this->_environment->getReaderManager();
         $noticed_manager = $this->_environment->getNoticedManager();
         $reader = $reader_manager->getLatestReaderForUserByID($material_item->getItemID(), $user_item->getItemID());
         $reader_manager->markRead($material_item->getItemID(),0);
         $noticed_manager->markNoticed($material_item->getItemID(),0);
         
         $this->_uploadFiles($uploadFiles, $material_item);
         
         $this->_deleteFiles($session_id, $deleteFiles, $material_item);
      }
   }
   
   public function deleteMaterial($session_id, $context_id, $item_id) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $material_manager = $this->_environment->getMaterialManager();
         $material_item = $material_manager->getItem($item_id);
         $material_item->delete();
      }
   }
   
   public function saveSection($session_id, $context_id, $item_id, $title, $description, $number, $uploadFiles, $deleteFiles, $material_item_id) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $this->_environment->setCurrentUser($user_item);
         
         $material_manager = $this->_environment->getMaterialManager();
         $section_manager = $this->_environment->getSectionManager();
         debugToFile($item_id);
         if($item_id != 'NEW'){
            $section_item = $section_manager->getItem($item_id);
         } else {
            debugToFile('is NEW');
            $section_item = $section_manager->getNewItem();
            $section_item->setContextID($context_id);
            $section_item->setCreatorItem($user_item);
            $section_item->setCreationDate(getCurrentDateTimeInMySQL());
            $section_item->setLinkedItemID($material_item_id);
         }
         $section_item->setTitle($title);
         $section_item->setDescription(str_ireplace("\n", "\n".'<br />', $description));
         $section_item->setNumber($number);
         
         $material_item = $material_manager->getItem($material_item_id);
         $section_list = $material_item->getSectionList();
         
         $section_list->set($section_item);
         $material_item->setSectionList($section_list);
         $material_item->setSectionSaveID($section_item->getItemId());

         $material_item->save();
         
         $reader_manager = $this->_environment->getReaderManager();
         $noticed_manager = $this->_environment->getNoticedManager();
         $reader = $reader_manager->getLatestReaderForUserByID($section_item->getItemID(), $user_item->getItemID());
         $reader_manager->markRead($section_item->getItemID(),0);
         $noticed_manager->markNoticed($section_item->getItemID(),0);
         
         $this->_uploadFiles($uploadFiles, $section_item);
         
         $this->_deleteFiles($session_id, $deleteFiles, $section_item);
      }
   }
   
   public function deleteSection($session_id, $context_id, $item_id) {
      include_once('functions/development_functions.php');
      include_once('functions/date_functions.php');
      if($this->_isSessionValid($session_id)) {
         $section_manager = $this->_environment->getSectionManager();
         $section_item = $section_manager->getItem($item_id);
    		$section_item->deleteVersion();
    				
    		$material_item = $section_item->getLinkedItem();
    		$material_item->setModificationDate(getCurrentDateTimeInMySQL());
    		$material_item->save();
      }
   }
   
   // Discussions
   
   public function getDiscussionList($session_id, $context_id) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $reader_manager = $this->_environment->getReaderManager();
         $discussion_manager = $this->_environment->getDiscussionManager();
         $discussion_manager->setContextLimit($context_id);
         $discussion_manager->showNoNotActivatedEntries();
         $discussion_manager->select();
         $discussion_list = $discussion_manager->get();
         $xml = "<discussion_list>\n";
         $discussion_item = $discussion_list->getFirst();
         while($discussion_item) {
            $xml .= "<discussion_item>\n";
            $xml .= "<discussion_id><![CDATA[".$discussion_item->getItemID()."]]></discussion_id>\n";
            $temp_title = $discussion_item->getTitle();
            $temp_title = $this->prepareText($temp_title);
            $xml .= "<discussion_title><![CDATA[".$temp_title."]]></discussion_title>\n";
            $reader = $reader_manager->getLatestReaderForUserByID($discussion_item->getItemID(), $user_item->getItemID());
            if ( empty($reader) ) {
               $xml .= "<discussion_read><![CDATA[new]]></discussion_read>\n";
            } elseif ( $reader['read_date'] < $discussion_item->getModificationDate() ) {
               $xml .= "<discussion_read><![CDATA[changed]]></discussion_read>\n";
            } else {
               $xml .= "<discussion_read><![CDATA[]]></discussion_read>\n";
            }
            if($discussion_item->mayEdit($user_item)){
               $xml .= "<discussion_edit><![CDATA[edit]]></discussion_edit>\n";
            } else {
               $xml .= "<discussion_edit><![CDATA[non_edit]]></discussion_edit>\n";
            }
            $xml .= "</discussion_item>\n";
            $discussion_item = $discussion_list->getNext();
         }
         $xml .= "</discussion_list>";
         $xml = $this->_encode_output($xml);
         return $xml;
      }
   }
   
   public function getDiscussionDetails($session_id, $context_id, $item_id) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $this->_environment->setCurrentUser($user_item);
         $reader_manager = $this->_environment->getReaderManager();
         $noticed_manager = $this->_environment->getNoticedManager();
         $discussion_manager = $this->_environment->getDiscussionManager();
         $discussion_item = $discussion_manager->getItem($item_id);
         $xml  = "<discussion_item>\n";
         $xml .= "<discussion_id><![CDATA[".$discussion_item->getItemID()."]]></discussion_id>\n";
         $temp_title = $discussion_item->getTitle();
         $temp_title = $this->prepareText($temp_title);
         $xml .= "<discussion_title><![CDATA[".$temp_title."]]></discussion_title>\n";
         $modifier_user = $discussion_item->getModificatorItem();
         $xml .= "<discussion_last_modifier><![CDATA[".$modifier_user->getFullname()."]]></discussion_last_modifier>\n";
         $xml .= "<discussion_last_modification_date><![CDATA[".$discussion_item->getModificationDate()."]]></discussion_last_modification_date>\n";
         $reader = $reader_manager->getLatestReaderForUserByID($discussion_item->getItemID(), $user_item->getItemID());
         if ( empty($reader) ) {
            $xml .= "<discussion_read><![CDATA[new]]></discussion_read>\n";
         } elseif ( $reader['read_date'] < $discussion_item->getModificationDate() ) {
            $xml .= "<discussion_read><![CDATA[changed]]></discussion_read>\n";
         } else {
            $xml .= "<discussion_read><![CDATA[]]></discussion_read>\n";
         }
         if($discussion_item->mayEdit($user_item)){
            $xml .= "<discussion_edit><![CDATA[edit]]></discussion_edit>\n";
         } else {
            $xml .= "<discussion_edit><![CDATA[non_edit]]></discussion_edit>\n";
         }
         
         if($discussion_item->getDiscussionType() == 'threaded'){
            $xml .= "<discussion_threaded><![CDATA[threaded]]></discussion_threaded>\n";
         } else {
            $xml .= "<discussion_threaded><![CDATA[non_threaded]]></discussion_threaded>\n";
         }

         $xml .= "<discussion_articles>\n";
         
         $disc_articles_manager = $this->_environment->getDiscussionArticlesManager();
			$disc_articles_manager->setDiscussionLimit($discussion_item->getItemID(), array());

			$discussion_type = $discussion_item->getDiscussionType();
			if($discussion_type == 'threaded') {
				$disc_articles_manager->setSortPosition();
			}
			if(isset($_GET['status']) && $_GET['status'] == 'all_articles') {
				$disc_articles_manager->setDeleteLimit(false);
			}

			$disc_articles_manager->select();
			$articles_list = $disc_articles_manager->get();
         
         //$articles_list = $discussion_item->getAllArticles();
         $temp_article = $articles_list->getFirst();
         while($temp_article){
            $xml .= "<discussion_article>\n";
            $xml .= "<discussion_article_id><![CDATA[".$temp_article->getItemID()."]]></discussion_article_id>\n";
            $temp_title = $temp_article->getTitle();
            $temp_title = $this->prepareText($temp_title);
            $xml .= "<discussion_article_title><![CDATA[".$temp_title."]]></discussion_article_title>\n";
            $temp_description = $temp_article->getDescription();
            $allow_edit = true;
            if(stristr($temp_description, '<table')){
               $allow_edit = false;
            }
            $temp_description = $this->prepareText($temp_description);
            $xml .= "<discussion_article_description><![CDATA[".$temp_description."]]></discussion_article_description>\n";
            $xml .= "<discussion_article_files>\n";
            $article_file_list = $temp_article->getFileList();
            $temp_article_file = $article_file_list->getFirst();
            while($temp_article_file){
               $xml .= "<discussion_article_file>\n";
               $xml .= "<discussion_article_file_name><![CDATA[".$temp_article_file->getFileName()."]]></discussion_article_file_name>\n";
               $xml .= "<discussion_article_file_id><![CDATA[".$temp_article_file->getFileID()."]]></discussion_article_file_id>\n";
               $xml .= "<discussion_article_file_size><![CDATA[".$temp_article_file->getFileSize()."]]></discussion_article_file_size>\n";
               $xml .= "<discussion_article_file_mime><![CDATA[".$temp_article_file->getMime()."]]></discussion_article_file_mime>\n";
               $xml .= "</discussion_article_file>\n";
               $temp_article_file = $article_file_list->getNext();
            }
            $xml .= "</discussion_article_files>\n";
            $modifier_user = $temp_article->getModificatorItem();
            $xml .= "<discussion_article_last_modifier><![CDATA[".$modifier_user->getFullname()."]]></discussion_article_last_modifier>\n";
            $xml .= "<discussion_article_last_modification_date><![CDATA[".$temp_article->getModificationDate()."]]></discussion_article_last_modification_date>\n";
            if($temp_article->mayEdit($user_item) && $allow_edit){
               $xml .= "<discussion_article_edit><![CDATA[edit]]></discussion_article_edit>\n";
            } else {
               $xml .= "<discussion_article_edit><![CDATA[non_edit]]></discussion_article_edit>\n";
            }
            $xml .= "</discussion_article>\n";
            $temp_article = $articles_list->getNext();
         }
         $xml .= "</discussion_articles>\n";
         
         $xml .= "</discussion_item>\n";
         $xml = $this->_encode_output($xml);
         $reader = $reader_manager->getLatestReaderForUserByID($discussion_item->getItemID(), $user_item->getItemID());
         if ( empty($reader) or $reader['read_date'] < $discussion_item->getModificationDate() ) {
            $reader_manager->markRead($discussion_item->getItemID(),0);
         }
         $noticed = $noticed_manager->getLatestNoticedForUserByID($discussion_item->getItemID(), $user_item->getItemID());
         if ( empty($noticed) or $noticed['read_date'] < $discussion_item->getModificationDate() ) {
            $noticed_manager->markNoticed($discussion_item->getItemID(),0);
         }
         return $xml;
      }
   }
   
   public function saveDiscussionArticle($session_id, $context_id, $item_id, $title, $description, $uploadFiles, $deleteFiles, $discussion_item_id, $answerTo) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $this->_environment->setCurrentUser($user_item);
         
         $discussion_manager = $this->_environment->getDiscussionManager();
         $discussion_article_manager = $this->_environment->getDiscussionArticleManager();
         debugToFile($item_id);
         if($item_id != 'NEW'){
            $discarticle_item = $discussion_article_manager->getItem($item_id);
         } else {
            debugToFile('is NEW');
            $discarticle_item = $discussion_article_manager->getNewItem();
            $discarticle_item->setContextID($context_id);
            $discarticle_item->setCreatorItem($user_item);
            $discarticle_item->setCreationDate(getCurrentDateTimeInMySQL());
            $discarticle_item->setDiscussionID($discussion_item_id);
            
            if($answerTo != "NEW") {
					$discussionManager = $this->_environment->getDiscussionManager();
					$discussionItem = $discussionManager->getItem($discussion_item_id);
					
					// get the position of the discussion article this is a response to
					$answerToItem = $discussion_article_manager->getItem($answerTo);
					$answerToPosition = $answerToItem->getPosition();
					
					// load discussion articles
					$discussion_article_manager->reset();
					
					$discussion_article_manager->setDiscussionLimit($discussion_item_id, "");
					$discussion_article_manager->select();
					
					$discussionArticlesList = $discussion_article_manager->get();
					
					// build an array with all positions > $answerToPosition
					$positionArray = array();
					$discussionArticle = $discussionArticlesList->getFirst();
					while ($discussionArticle) {
						$articlePosition = $discussionArticle->getPosition();
						
						if ($articlePosition > $answerToPosition) {
							$positionArray[] = $articlePosition;
						}
						
						$discussionArticle = $discussionArticlesList->getNext();
					}
					sort($positionArray);
					
					// check if there is at least one direct answer to the $answerToItem
					$hasChild = in_array($answerToPosition . ".1001", $positionArray);
					
					// if there is none, this article will be the first child
					if (!$hasChild) {
						$discarticle_item->setPosition($answerToPosition . ".1001");
					}
					
					// otherwise we need do determ the correct position for appending
					else {
						// explode all sub-positions
						$answerToPositionArray = explode(".", $answerToPosition);
						
						$compareArray = array();
						$end = count($positionArray) - 1;
						for ($i = 0; $i <= $end; $i++) {
							$valueArray = explode(".", $positionArray[$i]);
							
							$in = true;
							$end2 = count($answerToPositionArray) - 1;
							for ($j = 0; $j <= $end2; $j++) {
								if (isset($valueArray[$j]) && $answerToPositionArray[$j] != $valueArray[$j]) {
									$in = false;
								}
							}
							
							if ($in && count($valueArray) == count($answerToPositionArray) + 1) {
								$compareArray[] = $valueArray[count($answerToPositionArray)];
							}
						}
						
						$length = count($compareArray) - 1;
						$result = $compareArray[$length];
						$endResult = $result + 1;
						
						$discarticle_item->setPosition($answerToPosition . "." . $endResult);
					}
				} else {
					$discarticle_item->setPosition("1");
				}
         }
         $title = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
         $discarticle_item->setSubject($title);
         $description = html_entity_decode($description, ENT_COMPAT, 'UTF-8');
         $discarticle_item->setDescription(str_ireplace("\n", "\n".'<br />', $description));
         
         $discarticle_item->save();
         
         $reader_manager = $this->_environment->getReaderManager();
         $noticed_manager = $this->_environment->getNoticedManager();
         $reader = $reader_manager->getLatestReaderForUserByID($discarticle_item->getItemID(), $user_item->getItemID());
         $reader_manager->markRead($discarticle_item->getItemID(),0);
         $noticed_manager->markNoticed($discarticle_item->getItemID(),0);
         
         $this->_uploadFiles($uploadFiles, $discarticle_item);
         
         $this->_deleteFiles($session_id, $deleteFiles, $discarticle_item);
      }
   }
   
   public function saveDiscussionWithInitialArticle($session_id, $context_id, $item_id, $title, $item_id_article, $title_article, $description_article, $uploadFiles, $deleteFiles) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $this->_environment->setCurrentUser($user_item);
         
         $discussion_manager = $this->_environment->getDiscussionManager();
         $discussion_item = $discussion_manager->getNewItem();
         $discussion_item->setContextID($context_id);
         $discussion_item->setCreatorItem($user_item);
         $discussion_item->setCreationDate(getCurrentDateTimeInMySQL());
         $title = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
         $discussion_item->setTitle($title);
         $discussion_item->save();
         
         $discussion_article_manager = $this->_environment->getDiscussionArticleManager();
         $discarticle_item = $discussion_article_manager->getNewItem();
         $discarticle_item->setContextID($context_id);
         $discarticle_item->setCreatorItem($user_item);
         $discarticle_item->setCreationDate(getCurrentDateTimeInMySQL());
         $discarticle_item->setDiscussionID($discussion_item->getItemID());
			$discarticle_item->setPosition("1");
			$title_article = html_entity_decode($title_article, ENT_COMPAT, 'UTF-8');
         $discarticle_item->setSubject($title_article);
         $description_article = html_entity_decode($description_article, ENT_COMPAT, 'UTF-8');
         $discarticle_item->setDescription(str_ireplace("\n", "\n".'<br />', $description_article));
         $discarticle_item->save();
         
         $reader_manager = $this->_environment->getReaderManager();
         $noticed_manager = $this->_environment->getNoticedManager();
         $reader = $reader_manager->getLatestReaderForUserByID($discarticle_item->getItemID(), $user_item->getItemID());
         $reader_manager->markRead($discussion_item->getItemID(),0);
         $noticed_manager->markNoticed($discussion_item->getItemID(),0);
         $reader_manager->markRead($discarticle_item->getItemID(),0);
         $noticed_manager->markNoticed($discarticle_item->getItemID(),0);
         
         $this->_uploadFiles($uploadFiles, $discarticle_item);
         
         $this->_deleteFiles($session_id, $deleteFiles, $discarticle_item);
      }
   }
   
   public function saveDiscussion($session_id, $context_id, $item_id, $title) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $this->_environment->setCurrentUser($user_item);
         
         $discussion_manager = $this->_environment->getDiscussionManager();
         $discussion_item = $discussion_manager->getItem($item_id);
         $title = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
         $discussion_item->setTitle($title);
         $discussion_item->save();
         
         $reader_manager = $this->_environment->getReaderManager();
         $noticed_manager = $this->_environment->getNoticedManager();
         $reader = $reader_manager->getLatestReaderForUserByID($discussion_item->getItemID(), $user_item->getItemID());
         $reader_manager->markRead($discussion_item->getItemID(),0);
         $noticed_manager->markNoticed($discussion_item->getItemID(),0);
      }
   }
   
   public function deleteDiscussion($session_id, $context_id, $item_id) {
      include_once('functions/development_functions.php');
      include_once('functions/date_functions.php');
      if($this->_isSessionValid($session_id)) {
         $discussion_manager = $this->_environment->getDiscussionManager();
         $discussion_item = $discussion_manager->getItem($item_id);
         $discussion_item->delete();
      }
   }
   
   public function deleteDiscussionArticle($session_id, $context_id, $item_id) {
      include_once('functions/development_functions.php');
      include_once('functions/date_functions.php');
      if($this->_isSessionValid($session_id)) {
         $discarticle_manager = $this->_environment->getDiscussionArticleManager();
         $discarticle_item = $discarticle_manager->getItem($item_id);
         $discarticle_item->delete();
      }
   }
   
   // User
   
   public function getUserList($session_id, $context_id) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $reader_manager = $this->_environment->getReaderManager();
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextLimit($context_id);
         $user_manager->setUserLimit();
         $user_manager->select();
         $user_list = $user_manager->get();
         $xml = "<user_list>\n";
         $user_list_item = $user_list->getFirst();
         while($user_list_item) {
            $xml .= "<user_item>\n";
            $xml .= "<user_id><![CDATA[".$user_list_item->getItemID()."]]></user_id>\n";
            $temp_title = $user_list_item->getFullname();
            $temp_title = $this->prepareText($temp_title);
            $xml .= "<user_title><![CDATA[".$temp_title."]]></user_title>\n";
            $reader = $reader_manager->getLatestReaderForUserByID($user_list_item->getItemID(), $user_item->getItemID());
            if ( empty($reader) ) {
               $xml .= "<user_read><![CDATA[new]]></user_read>\n";
            } elseif ( $reader['read_date'] < $user_list_item->getModificationDate() ) {
               $xml .= "<user_read><![CDATA[changed]]></user_read>\n";
            } else {
               $xml .= "<user_read><![CDATA[]]></user_read>\n";
            }
            $xml .= "</user_item>\n";
            $user_list_item = $user_list->getNext();
         }
         $xml .= "</user_list>";
         #debugToFile($xml);
         $xml = $this->_encode_output($xml);
         return $xml;
      }
   }
   
   public function getUserDetails($session_id, $context_id, $item_id) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $this->_environment->setCurrentUser($user_item);
         $reader_manager = $this->_environment->getReaderManager();
         $noticed_manager = $this->_environment->getNoticedManager();
         //$user_manager = $this->_environment->getUserManager();
         $user_details_item = $user_manager->getItem($item_id);
         $xml = "<user_item>\n";
         $xml .= "<user_id><![CDATA[".$user_details_item->getItemID()."]]></user_id>\n";
         $xml .= "<user_title><![CDATA[".$user_details_item->getFullname()."]]></user_title>\n";
         $xml .= "<user_firstname><![CDATA[".$user_details_item->getFirstname()."]]></user_firstname>\n";
         $xml .= "<user_name><![CDATA[".$user_details_item->getLastname()."]]></user_name>\n";
         if($user_details_item->isEmailVisible()){
            $xml .= "<user_email><![CDATA[".$user_details_item->getEmail()."]]></user_email>\n";
         }
         $xml .= "<user_phone1><![CDATA[".$user_details_item->getTelephone()."]]></user_phone1>\n";
         $xml .= "<user_phone2><![CDATA[".$user_details_item->getCellularphone()."]]></user_phone2>\n";
         $temp_description = $user_details_item->getDescription();
         $temp_description = $this->prepareText($temp_description);
         $xml .= "<discussion_description><![CDATA[".$temp_description."]]></discussion_description>\n";
         $reader = $reader_manager->getLatestReaderForUserByID($user_details_item->getItemID(), $user_item->getItemID());
         if ( empty($reader) ) {
            $xml .= "<user_read><![CDATA[new]]></user_read>\n";
         } elseif ( $reader['read_date'] < $user_details_item->getModificationDate() ) {
            $xml .= "<user_read><![CDATA[changed]]></user_read>\n";
         } else {
            $xml .= "<user_read><![CDATA[]]></user_read>\n";
         }
         if($user_details_item->mayEdit($user_item)){
            $xml .= "<user_edit><![CDATA[edit]]></user_edit>\n";
         } else {
            $xml .= "<user_edit><![CDATA[non_edit]]></user_edit>\n";
         }
         $user_image = $user_details_item->getPicture();
         if($user_image){
            #$user_image_handle = fopen('var');
            $disc_manager = $this->_environment->getDiscManager();
            $user_image_handle = fopen($disc_manager->getFilePath($this->_environment->getCurrentPortalID(), $this->_environment->getCurrentContextID()).$user_image, 'r');
            $user_image_file = fread($user_image_handle, filesize($disc_manager->getFilePath($this->_environment->getCurrentPortalID(), $this->_environment->getCurrentContextID()).$user_image));
            $xml .= "<user_image>\n";
            $xml .= "<user_image_data><![CDATA[".base64_encode($user_image_file)."]]></user_image_data>\n";
            $xml .= "</user_image>\n";
         }
         $xml .= "</user_item>\n";
         $xml = $this->_encode_output($xml);
         $reader = $reader_manager->getLatestReaderForUserByID($user_details_item->getItemID(), $user_item->getItemID());
         if ( empty($reader) or $reader['read_date'] < $user_details_item->getModificationDate() ) {
            $reader_manager->markRead($user_details_item->getItemID(),0);
         }
         $noticed = $noticed_manager->getLatestNoticedForUserByID($user_details_item->getItemID(), $user_item->getItemID());
         if ( empty($noticed) or $noticed['read_date'] < $user_details_item->getModificationDate() ) {
            $noticed_manager->markNoticed($user_details_item->getItemID(),0);
         }
         debugToFile($xml);
         return $xml;
      }
   }
   
   public function saveUser($session_id, $context_id, $item_id, $name, $firstname, $email, $phone1, $phone2) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $this->_environment->setCurrentUser($user_item);
         
         $user_item_save = $user_manager->getItem($item_id);
         $name = html_entity_decode($name, ENT_COMPAT, 'UTF-8');
         $user_item_save->setLastname($name);
         $firstname = html_entity_decode($firstname, ENT_COMPAT, 'UTF-8');
         $user_item_save->setFirstname($firstname);
         $email = html_entity_decode($email, ENT_COMPAT, 'UTF-8');
         $user_item_save->setEmail($email);
         $phone1 = html_entity_decode($phone1, ENT_COMPAT, 'UTF-8');
         $user_item_save->setTelephone($phone1);
         $phone2 = html_entity_decode($phone2, ENT_COMPAT, 'UTF-8');
         $user_item_save->setCellularphone($phone2);
         $user_item_save->save();
         
         $reader_manager = $this->_environment->getReaderManager();
         $noticed_manager = $this->_environment->getNoticedManager();
         $reader = $reader_manager->getLatestReaderForUserByID($user_item_save->getItemID(), $user_item->getItemID());
         $reader_manager->markRead($user_item_save->getItemID(),0);
         $noticed_manager->markNoticed($user_item_save->getItemID(),0);
      }
   }
   
   
   // Files
   
   public function uploadFile($session_id, $context_id, $file_id, $file_data) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         
         $temp_file_name = 'upload_'.time().'.jpg';
         
         $disc_manager = $this->_environment->getDiscManager();
         $bin = base64_decode($file_data);
         file_put_contents($disc_manager->getTempFolder().'/'.$temp_file_name, $bin);
         
         $file_manager = $this->_environment->getFileManager();
         $new_file = $file_manager->getNewItem();
         $new_file->setFileName($temp_file_name);
         $new_file->setTempName($disc_manager->getTempFolder().'/'.$temp_file_name);
         $new_file->setTempKey($temp_file_name);
         $new_file->save();
         
         $xml = '<upload_file_id>'.$new_file->getItemID().'</upload_file_id>';
         $xml = $this->_encode_output($xml);
         
         return $xml;
      }
   }
   
   
   // Room
   
   public function getRoomReadCounter($session_id, $context_id){
      el('getRoomReadCounter');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $reader_manager = $this->_environment->getReaderManager();
         
         $dates_manager = $this->_environment->getDatesManager();
         $dates_manager->setContextLimit($context_id);
         $dates_manager->setDateModeLimit(2);
         $dates_manager->select();
         $dates_list = $dates_manager->get();
         $date_item = $dates_list->getFirst();
         $date_counter = 0;
         while($date_item) {
            $reader = $reader_manager->getLatestReaderForUserByID($date_item->getItemID(), $user_item->getItemID());
            if ( empty($reader) ) {
               $date_counter++;
            } elseif ( $reader['read_date'] < $date_item->getModificationDate() ) {
               $date_counter++;
            }
            $date_item = $dates_list->getNext();
         }
         
         $material_manager = $this->_environment->getMaterialManager();
         $material_manager->setContextLimit($context_id);
         $material_manager->showNoNotActivatedEntries();
         $material_manager->select();
         $material_list = $material_manager->get();
         $material_item = $material_list->getFirst();
         $material_counter = 0;
         while($material_item) {
            $reader = $reader_manager->getLatestReaderForUserByID($material_item->getItemID(), $user_item->getItemID());
            if ( empty($reader) ) {
               $material_counter++;
            } elseif ( $reader['read_date'] < $material_item->getModificationDate() ) {
               $material_counter++;
            }
            $material_item = $material_list->getNext();
         }
         
         $discussion_manager = $this->_environment->getDiscussionManager();
         $discussion_manager->setContextLimit($context_id);
         $discussion_manager->select();
         $discussion_list = $discussion_manager->get();
         $discussion_item = $discussion_list->getFirst();
         $discussion_counter = 0;
         while($discussion_item) {
            $reader = $reader_manager->getLatestReaderForUserByID($discussion_item->getItemID(), $user_item->getItemID());
            if ( empty($reader) ) {
               $discussion_counter++;
            } elseif ( $reader['read_date'] < $discussion_item->getModificationDate() ) {
               $discussion_counter++;
            }
            $discussion_item = $discussion_list->getNext();
         }
         
         $user_counter_manager = $this->_environment->getUserManager();
         $user_counter_manager->setContextLimit($context_id);
         $user_manager->setUserLimit();
         $user_counter_manager->select();
         $user_counter_list = $user_counter_manager->get();
         $user_counter_item = $user_counter_list->getFirst();
         $user_counter = 0;
         while($user_counter_item) {
            $reader = $reader_manager->getLatestReaderForUserByID($user_counter_item->getItemID(), $user_item->getItemID());
            if ( empty($reader) ) {
               $user_counter++;
            } elseif ( $reader['read_date'] < $user_counter_item->getModificationDate() ) {
               $user_counter++;
            }
            $user_counter_item = $user_counter_list->getNext();
         }
         
         $xml  = '<read_counter>';
         $xml .= '<counter_dates>'.$date_counter.'</counter_dates>';
         $xml .= '<counter_materials>'.$material_counter.'</counter_materials>';
         $xml .= '<counter_discussions>'.$discussion_counter.'</counter_discussions>';
         $xml .= '<counter_users>'.$user_counter.'</counter_users>';
         $xml .= '</read_counter>';
         
         $xml = $this->_encode_output($xml);
         
         return $xml;
      }
   }
   
   public function getModerationUserList($session_id) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $context_id = $session->getValue('commsy_id');
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $room_manager = $this->_environment->getRoomManager();
         $room_list = $room_manager->getRelatedRoomListForUser($user_item);

         $room_item = $room_list->getFirst();
         $xml = "<moderation_user_list>\n";
         while($room_item) {
            $is_moderator = false;
            $room_user = $room_item->getUserByUserID($user_id, $auth_source_id);
            if($room_user->getStatus() == '3'){
               $is_moderator = true;
            }

            if($is_moderator){
               $user_manager->resetLimits();
               $user_manager->setContextLimit($room_item->getItemID());
               $user_manager->setRegisteredLimit();
               $user_manager->select();
               $user_list = $user_manager->get();
               $temp_user_item = $user_list->getFirst();
               while($temp_user_item){
                  if($temp_user_item->getStatus() == '1'){
                     $xml .= "<moderation_user_item>";
                     $xml .= "<firstname><![CDATA[".$temp_user_item->getFirstname()."]]></firstname>\n";
                     $xml .= "<lastname><![CDATA[".$temp_user_item->getLastname()."]]></lastname>\n";
                     $xml .= "<item_id><![CDATA[".$temp_user_item->getItemID()."]]></item_id>\n";
                     $xml .= "<context_id><![CDATA[".$room_item->getItemID()."]]></context_id>\n";
                     $xml .= "<context_name><![CDATA[".$room_item->getTitle()."]]></context_name>\n";
                     $xml .= "</moderation_user_item>\n";
                  }
                  $temp_user_item = $user_list->getNext();
               }
            }
            
            $room_item = $room_list->getNext();
         }
         $xml .= "</moderation_user_list>";
         $xml = $this->_encode_output($xml);
      } else {
         return new SoapFault('ERROR','Session not valid!');
      }
      return $xml;
   }
   
   public function activateUser($session_id, $activate_user_id, $with_email) {
      include_once('functions/development_functions.php');
      if($this->_isSessionValid($session_id)) {
         $this->_environment->setSessionID($session_id);
         $session = $this->_environment->getSessionItem();
         $context_id = $session->getValue('commsy_id');
         $this->_environment->setCurrentContextID($context_id);
         $user_id = $session->getValue('user_id');
         $auth_source_id = $session->getValue('auth_source');
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItemByUserIDAuthSourceID($user_id, $auth_source_id);
         $activate_user_item = $user_manager->getItem($activate_user_id);
         $activate_user_item->makeUser();
         $activate_user_item->save();
         
         if($with_email == 'true' && false){
            $translator = $this->_environment->getTranslationObject();
            $room_manager = $this->_environment->getRoomManager();

            $temp_room = $room_manager->getItem($activate_user_item->getContextID());
            if($temp_room->getRoomType() == "project"){
               $body  = $translator->getMessage('MAIL_BODY_USER_STATUS_USER_PR', $activate_user_item->getUserID(), $temp_room->getTitle());
            } else if($temp_room->getRoomType() == "group"){
               $body  = $translator->getMessage('MAIL_BODY_USER_STATUS_USER_GR', $activate_user_item->getUserID(), $temp_room->getTitle());
            } else if($temp_room->getRoomType() == "community") {
               $body  = $translator->getMessage('MAIL_BODY_USER_STATUS_USER_GP', $activate_user_item->getUserID(), $temp_room->getTitle());
            }

            include_once('classes/cs_mail.php');
            $mail = new cs_mail();
            $mail->set_to($activate_user_item->getEmail());
            $server_item = $this->_environment->getServerItem();
            $default_sender_address = $server_item->getDefaultSenderAddress();
            if (!empty($default_sender_address)) {
               $mail->set_from_email($default_sender_address);
            } else {
               $mail->set_from_email('@');
            }
            $current_context = $this->_environment->getCurrentContextItem();
            $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_context->getTitle()));
            $mail->set_reply_to_name($user_item->getFullname());
            $mail->set_reply_to_email($user_item->getEmail());
            $mail->set_subject($subject);
            $mail->set_message($body);
            $mail->send();
         }
         
         $xml = "<activateUser>\n";
         $xml .= "</activateUser>";
         $xml = $this->_encode_output($xml);
      } else {
         return new SoapFault('ERROR','Session not valid!');
      }
      return $xml;
   }
   
   function prepareText($text){
      $text = preg_replace('~<!-- KFC TEXT [a-z0-9]* -->~u','',$text);
      $text = html_entity_decode($text, ENT_COMPAT, 'UTF-8');
      $text = str_ireplace("<li>", "CS_LI", $text);
      $text = str_ireplace("\n", "CS_NEWLINE", $text);
      $text = str_ireplace("\r", "", $text);
      $text = str_ireplace("\t", "", $text);
      $text = str_ireplace("CS_LICS_NEWLINE", "CS_BULL ", $text);
      $text = str_ireplace("CS_LI", "CS_BULL ", $text);
      $text = str_ireplace("CS_NEWLINE", "\n", $text);
      $text = str_ireplace("<br />", "", $text);
      $current_encoding = mb_detect_encoding($text, 'auto');
      $text = iconv($current_encoding, 'UTF-8', $text);
      $text = strip_tags($text);
      $text =  htmlentities($text, ENT_QUOTES, 'UTF-8');
      $text = str_ireplace("CS_BULL", "&bull;", $text);
      el($text);
      $text = trim($text);
      if(empty($text)){
         $text = ' ';
      }
      $text = base64_encode($text);
      return $text;
   }
   
   /*
    * for plugin soap methods
    */
   public function __call ($name, $arguments) {
   	
   	// maybe plugin method
   	// first argument = session id or json-string with session id
   	$sid = '';
   	if ( !empty($arguments[0]) ) {
   		// 32 = length of md5 hash
   		if ( strlen($arguments[0]) == 32 ) {
   			$sid = $arguments[0];
   		}
   		// maybe json
   		else {
   			$arg_array = json_decode($arguments[0],true);
   			if ( empty($arg_array) ) {
   				$arg_array = json_decode(str_replace('\'','"',$arguments[0]),true);
   			}
   			if ( !empty($arg_array['SID']) ) {
   				$sid = $arg_array['SID'];
   			} elseif ( !empty($arg_array['sid']) ) {
   				$sid = $arg_array['sid'];
   			}
   		}
   	}
   	
   	// now the context
   	if ( !empty($sid) ) {
   		// session valid ?
   		if ( $this->_isSessionValid($sid) ) {
   			// get session item
            $session_manager = $this->_environment->getSessionManager();
            $session_item = $session_manager->get($sid);
            if ( $session_item->issetValue('commsy_id') ) {
            	$portal_id = $session_item->getValue('commsy_id');
               $this->_environment->setCurrentPortalID($portal_id);
               $this->_environment->setCurrentContextID($portal_id);
   	
               // plugin function 
               $retour = plugin_hook_output_all($name,$arguments);
            } else {
            	return new SoapFault('ERROR','can not find portal id in session item');
            }
   		} else {
   			return new SoapFault('ERROR','Session ('.$sid.') not valid!');
   		}
   	}
   	
   	// return
   	if ( !empty($retour) ) {
   		return $retour;
    	} else {
    		return new SoapFault('ERROR','SOAP function ('.$name.') is not defined');
    	}
   }
   public static function __callStatic($name, $arguments) {
   	$this->__call($name, $arguments);
   }
   
   // portal2portal
   public function getSessionIdFromConnectionKey ($session_id, $portal_id, $user_key, $server_key) {
   	if ($this->_isSessionValid($session_id)) {
   		$connection_obj = $this->_environment->getCommSyConnectionObject();   		 
   		$this->_updateSessionCreationDate($session_id);
   		return $connection_obj->getSessionIdFromConnectionKeySOAP($session_id, $portal_id, $user_key, $server_key);
   	} else {
   		return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
   	}
   }

   public function getRoomListAsJson ($session_id) {
   	if ($this->_isSessionValid($session_id)) {
   		$connection_obj = $this->_environment->getCommSyConnectionObject();   		 
   		$this->_updateSessionCreationDate($session_id);
   		return $connection_obj->getRoomListAsJsonSOAP($session_id);
   	} else {
   		return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
   	}
   }
   
   public function getPortalListAsJson () {
   	$connection_obj = $this->_environment->getCommSyConnectionObject();   		 
   	return $connection_obj->getPortalListAsJsonSOAP();
   }
   
   public function saveExternalConnectionKey ($session_id, $user_key) {
   	if ($this->_isSessionValid($session_id)) {
   		$connection_obj = $this->_environment->getCommSyConnectionObject();   		 
   		$this->_updateSessionCreationDate($session_id);
   		return $connection_obj->saveExternalConnectionKeySOAP($session_id, $user_key);
   	} else {
   		return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
   	}
   }

   public function getOwnConnectionKey ($session_id) {
   	if ($this->_isSessionValid($session_id)) {
   		$connection_obj = $this->_environment->getCommSyConnectionObject();   		 
   		$this->_updateSessionCreationDate($session_id);
   		return $connection_obj->getOwnConnectionKeySOAP($session_id);
   	} else {
   		return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
   	}
   }
   
   public function setPortalConnectionInfo ($session_id, $server_key, $portal_id, $tab_id) {
      	if ($this->_isSessionValid($session_id)) {
   		$connection_obj = $this->_environment->getCommSyConnectionObject();   		 
   		$this->_updateSessionCreationDate($session_id);
   		return $connection_obj->setPortalConnectionInfoSOAP($session_id, $server_key, $portal_id, $tab_id);
   	} else {
   		return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
   	}
   }

   public function deleteConnection ($session_id, $tab_id) {
      	if ($this->_isSessionValid($session_id)) {
   		$connection_obj = $this->_environment->getCommSyConnectionObject();   		 
   		$this->_updateSessionCreationDate($session_id);
   		return $connection_obj->deleteConnectionSOAP($session_id, $tab_id);
   	} else {
   		return new SoapFault('ERROR','Session ('.$session_id.') not valid!');
   	}
   }
}
?>