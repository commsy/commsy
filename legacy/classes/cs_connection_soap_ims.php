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

class cs_connection_soap_ims {

   private $_environment = null;
   private $_failure = true;  // false
   private $_ims_xml = NULL;

   function __construct ($environment) {
      $this->_environment = $environment;
   }

   private function _encode_input ($value) {
      return utf8_decode($value);
   }

   private function _encode_output ($value) {
      return utf8_encode($value);
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
      unset($log_manager);
   }

   /*
   * Handles user ims-packages
   */
   private function _handleUserPackage($user,$mail,$id_manager) {
      $result = '';
      $portal_id = $user['TARGET'];
      if ( !empty($portal_id) ) {
         include_once('classes/cs_personInfo.php');
         $user_info = new cs_personInfo($user['USERID'],$user['USERID'],$user['GIVEN'].' '.$user['FAMILY'],$user['FAMILY'],$user['GIVEN'],$user['EMAIL'],$portal_id,$user['SOURCE'],$user['PASSWORD'],$user['PWENC']);
         //Create
         if ($user['OP'] == 1) {
            if ( !empty($user['EMAIL']) ) {
               $result = $this->_createUser($mail,$user_info,$id_manager);
            } else {
               $info_text = 'no email address provided for user: '.$user['USERID'].' !';
               $result = array("error" => 1,"value" => $info_text);
            }
         }
         //edit
         elseif ($user['OP'] == 2) {
            $result = $this->_editUser($mail,$user_info,$id_manager);
            if ( $result['error'] == 2 ) {
               $info_text_old = $result['value'];
               if ( !empty($user['EMAIL']) ) {
                  $result = $this->_createUser($mail,$user_info,$id_manager);
                  $result['value'] = $info_text_old.' | '.$result['value'];
                  $result['error'] = 2;
               } else {
                  $info_text = 'no email address provided for user: '.$user['USERID'].' !';
                  $result = array("error" => 1,"value" => $info_text_old.' | '.$info_text);
               }
            }
         }
         //delete
         elseif ($user['OP'] == 3) {
            $result = $this->_deleteUser($mail,$user_info,$id_manager);
         }
         //Add Id of item we are talking about
         $result['TARGET'] = $user['TARGET'];
         $result['USERID'] = $user['USERID'];
         $result['DATASOURCE'] = $user['DATASOURCE'];
         $result['DATETIME'] = $user['DATETIME'];
      } else {
         $info_text = 'Unknown portal in CommSy: '.$user['TARGET'].' !';
         $result = array("error" => 1,"value" => $info_text);
      }
      return $result;
   }

   private function _createUser($mail,$user_info,$id_manager) {
      $return_array = array("error" => 0,"value" => '');
      $manager = $this->_environment->getUserManager();
      $source = $user_info->getSourceSystem();
      $stine_user_id = $user_info->getStineId();
      $commsy_user_id = $id_manager->getCommsyID($source,$stine_user_id);
      $context_id = $id_manager->getCommsyId($source,$user_info->getPortalId());
      $this->_environment->setCurrentContextId($context_id);
      $auth_object = $this->_environment->getAuthenticationObject();

      if ( !empty($auth_object) ) {
         $auth_object->setCommSyIdLimit($context_id);
         if ( empty($commsy_user_id ) ) {
            $stine_portal_id = $user_info->getPortalId();

            //check if provided id is a valid portal id
            $portal_manager = $this->_environment->getPortalManager();
            if ($portal_manager->getItem($stine_portal_id) != NULL) {
               $commsy_portal_id = $stine_portal_id;
            } else {
               $commsy_portal_id = $id_manager->getCommsyId($source,$stine_portal_id);
            }
            $portal_item = $portal_manager->getItem($commsy_portal_id);
            //No user, so wrong portal id, so fix it
            $auth_object->setCommSyIDLimit($commsy_portal_id);
            $this->_environment->setCurrentContextId($commsy_portal_id);
            if ( !empty($commsy_portal_id) ) {
               include_once('functions/text_functions.php');
               $user_id_to_check = $user_info->getUserID();
               if ( !withUmlaut($user_id_to_check) ) {
                  include_once('classes/cs_auth_item.php');
                  $auth_item = new cs_auth_item();
                  $auth_item->setUserID($user_info->getUserID());
                  $auth_item->setFirstname($user_info->getGivenName());
                  $auth_item->setLastname($user_info->getFamilyName());
                  $auth_item->setEmail($user_info->getEmail());
                  $auth_item->setPortalID($commsy_portal_id);
                  $auth_item->setAuthSourceID($portal_item->getAuthIMS());
                  $password = $user_info->getPassword();
                  if (!empty($password)) {
                     $encryption_method = $user_info->getPasswordEncryptionMethod();
                     if (empty($encryption_method)) {
                        //Plain text PW, MD5 it
                        $auth_item->setPassword($user_info->getPassword());
                     } elseif ($encryption_method == 'MD5') {
                        //just set it
                        $auth_item->setPasswordMD5($user_info->getPassword());
                     } else {
                        //unknown encryption, produce error
                        $info_text = 'Could not set Password. Only accepted encryption method is MD5, plaintext is possible but not recommended! User not created!';
                        $return_array = array("error" => 1,"value" => $info_text);
                     }
                  }
                  if ($return_array['error'] == 0) {
                     //crate user if no error occured
                     $auth_object->save($auth_item);
                     $user_item = $auth_object->getUserItem();
                     if ( !empty($user_item) ) {
                        $user_item->makeUser();
                        $user_item->save();
                        $return_array = array("error" => 0,"value" => 'User succesfully created! CommSy Id: '.$user_item->getItemId().', external-id: '.$stine_user_id);
                        $this->_log('IMS','createUser','User succesfully created! CommSy Id: '.$user_item->getItemId().', external-id: '.$stine_user_id);
                        $id_manager->addIDsToDB($source,$stine_user_id,$user_item->getItemId());

                        //Mail handling for user
                        $portal_user = $user_item;

                        $translator = $this->_environment->getTranslationObject();
                        $translator->initFromContext($portal_item);
                        $contact_list = $portal_item->getContactModeratorList();
                        $contact = $contact_list->getFirst();
                        $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$portal_item->getTitle()));
                        $mail->set_to($user_item->getEmail());
                        $mail->set_reply_to_name($contact->getFullname());
                        $mail->set_reply_to_email($contact->getEmail());
                        $mail->set_subject($translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_FREE',$portal_item->getTitle()));
                        $link = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
                        global $c_single_entry_point;
                        $link = str_replace ( 'soap.php', $c_single_entry_point.'?cid='.$portal_item->getItemId(), $link);

                        $body = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                        $body .= LF.LF;
                        $body .= $translator->getEmailMessage('MAIL_BODY_HELLO',$portal_user->getFullname());
                        $body .= LF.LF;
                        $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$portal_user->getUserID(),$portal_item->getTitle());
                        $body .= LF.LF;
                        $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$contact->getFullname(),$portal_item->getTitle());
                        $body .= LF.LF;
                        $body .= $link;
                        $mail->set_message($body);
                        $mail->send();

                        // mail handling for portal moderators
                        $user_list = $portal_item->getModeratorList();
                        $email_addresses = array();
                        $user_item = $user_list->getFirst();
                        $recipients = '';
                        $language = $portal_item->getLanguage();
                        while ($user_item) {
                           $want_mail = $user_item->getAccountWantMail();
                           if (!empty($want_mail) and $want_mail == 'yes') {
                              if ($language == 'user' and $user_item->getLanguage() != 'browser') {
                                 $email_addresses[$user_item->getLanguage()][] = $user_item->getEmail();
                              } elseif ($language == 'user' and $user_item->getLanguage() == 'browser') {
                                 $email_addresses[$this->_environment->getSelectedLanguage()][] = $user_item->getEmail();
                              } else {
                                 $email_addresses[$language][] = $user_item->getEmail();
                              }
                              $recipients .= $user_item->getFullname().LF;
                           }
                           $user_item = $user_list->getNext();
                        }
                        $save_language = $translator->getSelectedLanguage();
                        foreach ($email_addresses as $key => $value) {
                           $translator->setSelectedLanguage($key);
                           if (count($value) > 0) {
                              include_once('classes/cs_mail.php');
                              $mail = new cs_mail();
                              $mail->set_to(implode(',',$value));

                               global $symfonyContainer;
                               $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                               $mail->set_from_email($emailFrom);

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
                              $body .= $translator->getMessage('USER_GET_MAIL_BODY',$portal_user->getFullname(),$portal_user->getUserID(),$portal_user->getEmail(),$translator->getMessage('COMMON_UNKNOWN'));
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
                              $body .= $translator->getMessage('MAIL_COMMENT_BY','IMS',$translator->getMessage('MAIL_COMMENT_IMS',$source));
                              $body .= LF.LF;
                              $body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
                              $body .= LF;
                              $body .= $link;
                              $mail->set_message($body);
                              $mail->send();
                           }
                        }
                        $translator->setSelectedLanguage($save_language);
                     } else {
                        $info_text = 'Can not save user item! - '.__FILE__.' - '.__LINE__;
                        $return_array = array("error" => 1,"value" => $info_text);
                     }
                  }
               } else {
                  $info_text = 'user id is not valid: user id has umlauts '.$stine_user_id.'!';
                  $return_array = array("error" => 1,"value" => $info_text);
               }
            } else {
               $info_text = 'Trying to add a person to an unknown portal: '.$stine_portal_id.' !';
               $return_array = array("error" => 1,"value" => $info_text);
            }
         } else {
            $info_text = 'Trying to add an allready created person: '.$stine_user_id.'!';
            $return_array = array("error" => 1,"value" => $info_text);
         }
      } else {
         $info_text = 'Can not get auth_object - '.__FILE__.' - '.__LINE__;
         $return_array = array("error" => 1,"value" => $info_text);
      }
      return $return_array;
   }

   private function _deleteUser($mail,$user_info,$id_manager) {
      $return_array = array("error" => 0,"value" => '');
      $manager = $this->_environment->getUserManager();
      $source = $user_info->getSourceSystem();
      $stine_user_id = $user_info->getStineId();
      $commsy_user_id = $id_manager->getCommsyId($source,$stine_user_id);
      $context_id = $id_manager->getCommsyId($source,$user_info->getPortalId());
      $this->_environment->setCurrentContextId($context_id);
      $auth_object = $this->_environment->getAuthenticationObject();
      $auth_object->setCommSyIdLimit($context_id);
      if ( !empty($commsy_user_id ) ) {
         //Mail handling
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItem($commsy_user_id);
         unset($user_manager);
         $portal_user = $user_item->getRelatedCommSyUserItem();
         $portal_id = $portal_user->getContextId();
         $portal_manager = $this->_environment->getPortalManager();
         $portal_item = $portal_manager->getItem($portal_id);
         $translator = $this->_environment->getTranslationObject();
         $translator->initFromContext($portal_item);

         $contact_list = $portal_item->getContactModeratorList();
         $contact = $contact_list->getFirst();
         $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$portal_item->getTitle()));
         $mail->set_to($user_item->getEmail());
         $mail->set_reply_to_name($contact->getFullname());
         $mail->set_reply_to_email($contact->getEmail());
         $link = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
         global $c_single_entry_point;
         $link = str_replace ( 'soap.php', $c_single_entry_point.'?cid='.$portal_item->getItemId(), $link);
         $mail->set_subject($translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_DELETE',$portal_item->getTitle()));

         $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
         $body .= LF.LF;
         $body .= $translator->getEmailMessage('MAIL_BODY_HELLO',$user_item->getFullname());
         $body .= LF.LF;
         $body .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_DELETE',$portal_user->getUserID(),$portal_item->getTitle());
         $body .= LF.LF;
         $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$contact->getFullname(),$portal_item->getTitle());
         $body .= LF.LF;
         $body .= $link;
         $mail->set_message($body);

         $mail->send();
         // mail handling for portal moderators
         $user_list = $portal_item->getModeratorList();
         $email_addresses = array();
         $user_item = $user_list->getFirst();
         $recipients = '';
         $language = $portal_item->getLanguage();
         while ($user_item) {
            $want_mail = $user_item->getAccountWantMail();
            if (!empty($want_mail) and $want_mail == 'yes') {
               if ($language == 'user' and $user_item->getLanguage() != 'browser') {
                  $email_addresses[$user_item->getLanguage()][] = $user_item->getEmail();
               } elseif ($language == 'user' and $user_item->getLanguage() == 'browser') {
                  $email_addresses[$this->_environment->getSelectedLanguage()][] = $user_item->getEmail();
               } else {
                  $email_addresses[$language][] = $user_item->getEmail();
               }
               $recipients .= $user_item->getFullname().LF;
            }
            $user_item = $user_list->getNext();
         }
         $save_language = $translator->getSelectedLanguage();
         foreach ($email_addresses as $key => $value) {
            $translator->setSelectedLanguage($key);
            if (count($value) > 0) {
               include_once('classes/cs_mail.php');
               $mail = new cs_mail();
               $mail->set_to(implode(',',$value));

                global $symfonyContainer;
                $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                $mail->set_from_email($emailFrom);

               $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$portal_item->getTitle()));
               $mail->set_reply_to_name($portal_user->getFullname());
               $mail->set_reply_to_email($portal_user->getEmail());
               $mail->set_subject($translator->getMessage('USER_DELETE_MAIL_SUBJECT',$portal_user->getFullname()));
               $body = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
               $body .= LF.LF;
               $temp_language = $portal_user->getLanguage();
               if ($temp_language == 'browser') {
                  $temp_language = $this->_environment->getSelectedLanguage();
               } elseif (empty($temp_language)) {
                  $temp_language = 'COMMON_UNKNOWN';
               }
               $body .= $translator->getMessage('USER_DELETE_MAIL_BODY',
                                                $portal_user->getFullname(),
                                                $portal_user->getUserID(),
                                                $portal_user->getEmail(),
                                                $translator->getLanguageLabelTranslated($temp_language)
                                               );
               unset($temp_language);
               $body .= LF.LF;
               $body .= $translator->getMessage('MAIL_COMMENT_BY','IMS',$translator->getMessage('MAIL_COMMENT_IMS',$source));
               $body .= LF.LF;
               $body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
               $body .= LF;
               $body .= $link;
               $mail->set_message($body);
               $mail->send();
               unset($mail);
            }
         }
         $translator->setSelectedLanguage($save_language);
         $auth_object->delete($commsy_user_id);
         $id_manager->deleteByExternalId($stine_user_id,$source);
         unset($id_manager);
         $return_array = array("error" => 0,"value" => 'User succesfully deleted: CommSy-Id: '.$commsy_user_id. ', external-id: '.$stine_user_id);
         $this->_log('IMS','deleteUser','User succesfully deleted: CommSy-Id: '.$commsy_user_id. ', external-id: '.$stine_user_id);
      } else {
         $info_text = 'Trying to delete an unknown user: '.$stine_user_id.' !';
         $return_array = array("error" => 1,"value" => $info_text);
      }
      return $return_array;
   }

   private function _editUser($mail,$user_info,$id_manager) {
      $return_array = array("error" => 0,"value" => '');
      $manager = $this->_environment->getUserManager();
      $source = $user_info->getSourceSystem();
      $stine_user_id = $user_info->getStineId();
      $commsy_user_id = $id_manager->getCommsyId($source,$stine_user_id);
      if ( !empty($commsy_user_id) ) {
         $context_id = $user_info->getPortalId();
         $portal_id = $context_id;
         $user_manager = $this->_environment->getUserManager();
         $user_item = $user_manager->getItem($commsy_user_id);
         $this->_environment->setCurrentContextId($context_id);
         $auth_object = $this->_environment->getAuthenticationObject();
         if ( !empty($auth_object) ) {
            $auth_object->setCommSyIDLimit($context_id);

            $portal_manager = $this->_environment->getPortalManager();
            $portal_item = $portal_manager->getItem($portal_id);

            $ims_auth_id = $portal_item->getAuthIMS();
            $manager->setContextLimit($context_id);
            $user_item = $manager->getItem($commsy_user_id);
            $user_id = $user_item->getUserID();
            $auth_item = $auth_object->getNewItem();

            $auth_item->setAuthSourceID($ims_auth_id);

            $auth_item->setPortalID($context_id);
            $auth_item->setUserId($user_id);

            $given_name = $user_info->getGivenName();
            if (!empty($given_name)) {
               $auth_item->setFirstname($given_name);
            }
            $family_name = $user_info->getFamilyName();
            if (!empty($family_name)) {
               $auth_item->setLastname($family_name);
            }
            $email = $user_info->getEmail();
            if (!empty($email)) {
               $auth_item->setEmail($email);
            }
            $password = $user_info->getPassword();
            if (!empty($password)) {
               $encryption_method = $user_info->getPasswordEncryptionMethod();
               if ( empty($encryption_method)
                    or mb_strtoupper($encryption_method, 'UTF-8') == 'PLAIN'
                  ) {
                  //Plain text PW, MD5 it
                  $auth_item->setPassword($user_info->getPassword());
               } elseif ( mb_strtoupper($encryption_method, 'UTF-8') == 'MD5' ) {
                  //just set it
                  $auth_item->setPasswordMD5($user_info->getPassword());
               } else {
                  //unknown encryption, produce error
                  $info_text = 'Could not change Password. Only accepted encryption method is MD5, plaintext is possible but not recommended! User not changed!';
                  $return_array = array("error" => 1,"value" => $info_text);
               }
            }
         } else {
            $info_text = 'Can not get auth_object - '.__FILE__.' - '.__LINE__;
            $return_array = array("error" => 1,"value" => $info_text);
         }

         if ($return_array['error'] == 0) {
            //On error- don't change user
            $auth_object->save($auth_item);
            if ($user_id != $user_info->getUserID()) {
               $id_change_status = $auth_object->changeUserId($user_info->getUserID(),$user_item);
               if (!$id_change_status) {
                  $info_text = 'Could not set new user id for stine user: '.$stine_user_id.' !';
                  $return_array = array("error" => 1,"value" => $info_text);
               }
            }
            $return_array = array("error" => 0,"value" => 'User successfully edited: CommSy-Id: '.$user_item->getItemId().', external-Id:'.$stine_user_id);
            $this->_log('IMS','editUser','User successfully edited: CommSy-Id: '.$user_item->getItemId().', external-Id:'.$stine_user_id);
         }
      } else {
         $info_text = 'Trying to edit an unknown user: '.$stine_user_id.'!';
         $return_array = array("error" => 2,"value" => $info_text);
      }
      return $return_array;
   }

   /*
   * Handles room ims-packages
   */
   private function _handleRoomPackage($room,$mail,$id_manager,&$person_values,&$membership_values) {
      $portal_id = $room['TARGET'];
      if ( !empty($portal_id) ) {
         include_once('classes/cs_roomInfo.php');
         $room_info = new cs_roomInfo($room['GROUPID'],$room['TITLE'],$room['FULL'],$portal_id,$room['INSTITUTION'],$room['FACULTY'],$room['SOURCE']);

         //delete room
         if ($room['OP'] == 3) {
            $return_array = $this->_deleteRoom($mail,$room_info,$id_manager);
         } elseif ($room['OP'] == 1) {
            foreach ($membership_values['op1'] as $membership_array) {
               if ( $membership_array['ROLETYPE'] == 2
                    and $membership_array['GROUPID'] ==  $room['GROUPID']
                  ) {
                  $room_creator = $membership_array;
                  $room_creator_id = $membership_array['USERID'];
                  break;
               }
            }
            if (!empty($room_creator_id))  {
               //mark this person as room creator so it won't be processed later
               $person_values['op1'][$room_creator_id]['room_creator'] = true;
               global $ims_number_roletype_moderator;
               $membership_values['op1'][$room_creator_id.'_'.$ims_number_roletype_moderator.'_'.$room_info->getStineId()]['room_creator'] = true;
               include_once('classes/cs_membershipInfo.php');
               $membership_info = new cs_membershipInfo($room_creator['GROUPID'],$room_creator['USERID'],$room_creator['ROLETYPE'],$room_creator['DATASOURCE']);
               $return_array = $this->_createRoom($mail,$room_info,$membership_info,$id_manager);
            } else {
               $info_text = 'No creator for room '.$room_info->getStineId().' found!';
               $return_array = array('error'=>1,'value' => $info_text);
            }
         } elseif ($room['OP'] == 2) {
            $return_array = $this->_editRoom($mail,$room_info,$id_manager);
         } else {
            $info_text = 'Unknown operation ['.$room['OP'].'] for '.$room['GROUPID'].' !';
            $return_array = array("error" => 1,"value" => $info_text);
         }
         //Add Id of item we are talking about
         $return_array['TARGET'] = $room['TARGET'];
         $return_array['GROUPID'] = $room['GROUPID'];
         $return_array['DATASOURCE'] = $room['DATASOURCE'];
         $return_array['DATETIME'] = $room['DATETIME'];
      } else {
         $info_text = 'Unknown portal: '.$room['TARGET'].' !';
         $return_array = array("error" => 1,"value" => $info_text);
      }
      return $return_array;
   }

   private function _deleteRoom($mail,$room_info,$id_manager) {
      $return_array = array("error" => 0,"value" => '');
      $source = $room_info->getSourceSystem();
      $stine_room_id = $room_info->getStineId();
      $stine_portal_id = $room_info->getPortalId();
      $manager = $this->_environment->getProjectManager();
      $commsy_room_id = $id_manager->getCommsyId($source,$stine_room_id);
      $portal_manager = $this->_environment->getPortalManager();
      $portal_item = $portal_manager->getItem($stine_portal_id);
      if ($portal_item != NULL) {
         $commsy_portal_id = $stine_portal_id;
      } else {
         $commsy_portal_id = $id_manager->getCommsyId($source,$stine_portal_id);
         $portal_item = $portal_manager->getItem($commsy_portal_id);
      }
      if (!empty($commsy_room_id)) {
         //Delete
         $manager->delete($commsy_room_id);
         $id_manager->deleteByExternalId($stine_room_id,$source);
         $return_array = array('error' => 0,'value' => 'Room "'.$stine_room_id.'" succesfully deleted: CommSy-id: '.$commsy_room_id);
         $this->_log('IMS','deleteRoom','Room "'.$stine_room_id.'" succesfully deleted: CommSy-id: '.$commsy_room_id);
      } else {
         $info_text = 'Trying to delete an unknown room: '.$stine_room_id.' !';
         $return_array = array("error" => 1,"value" => $info_text);
      }
      return $return_array;
   }

   private function _editRoom($mail,$room_info,$id_manager) {
      $return_array = array("error" => 0,"value" => '');
      $source = $room_info->getSourceSystem();
      $stine_room_id = $room_info->getStineId();
      $stine_portal_id = $room_info->getPortalId();
      $manager = $this->_environment->getProjectManager();
      $commsy_room_id = $id_manager->getCommsyID($source,$stine_room_id);
      if (!empty($commsy_room_id)) {
         //edit
         $room_item = $manager->getItem($commsy_room_id);
         if ( !empty($room_item) ) {
            if ($room_info->getDescriptionShort() != "") {
               $room_item->setTitle($room_info->getDescriptionShort());
            }
            if ($room_info->getDescriptionFull() != "") {
               $descriptionArray = $room_item->getDescriptionArray();
               $descriptionArray['DE'] = $room_info->getDescriptionFull();
               $descriptionArray['DE'] = $this->_htmlTextareaSecurity($descriptionArray['DE']);
               $room_item->setDescriptionArray($descriptionArray);
            }
            $room_item->save();
            $return_array = array('error' => 0,'value' => 'Room "'.$stine_room_id.'" succesfully edit: CommSy-id: '.$commsy_room_id.' | title: '.$room_item->getTitle());
            $this->_log('IMS','editRoom','Room "'.$stine_room_id.'" succesfully edit: CommSy-id: '.$commsy_room_id.' | title: '.$room_item->getTitle());
         } else {
            $info_text = 'Can not identify room item! - '.__FILE__.' - '.__LINE__;
            $return_array = array("error" => 1,"value" => $info_text);
         }
      } else {
         $info_text = 'Trying to edit an unknown room: '.$stine_room_id.' !';
         $return_array = array("error" => 1,"value" => $info_text);
      }
      return $return_array;
   }

   private function _createRoom($mail,$room_info,$membership_info,$id_manager) {
      $return_array = array("error" => 0,"value" => 0);
      $source = $room_info->getSourceSystem();
      $stine_room_id = $room_info->getStineId();
      $commsy_portal_id = $room_info->getPortalId();
      $this->_environment->setCurrentContextId($commsy_portal_id);
      $manager = $this->_environment->getProjectManager();
      $commsy_room_id = $id_manager->getCommsyId($source,$stine_room_id);
      $portal_manager = $this->_environment->getPortalManager();
      $portal_item = $portal_manager->getItem($commsy_portal_id);

      if ( empty($commsy_room_id) and !empty($commsy_portal_id) ) {
         $stine_user_id = $membership_info->getSourceItemId();
         $commsy_user_id = $id_manager->getCommsyId($source,$stine_user_id);
         if (!empty($commsy_user_id)) {
            //get private room for user and the moderator in it
            $user_manager = $this->_environment->getUserManager();
            $user_item = $user_manager->getItem($commsy_user_id);
            $user_id = $user_item->getUserID();

            $private_room_manager = $this->_environment->getPrivateRoomManager();
            $private_room = $private_room_manager->getRelatedOwnRoomForUser($user_item,$commsy_portal_id);
            $moderator_list = $private_room->getModeratorList();

            if ($moderator_list->getCount() == 1) {
               //user to copy, make mod etc... done by making him current_user, $room->save() does the rest
               $template_user = $moderator_list->getFirst();

               //create new room
               $room_item = $manager->getNewItem();
               if ( !empty($room_item) ) {
                  $room_item->setTitle($room_info->getDescriptionShort());
                  if ($room_info->getDescriptionFull() != "") {
                     $descriptionArray = $room_item->getDescriptionArray();
                     $descriptionArray['DE'] = $room_info->getDescriptionFull();
                     $descriptionArray['DE'] = $this->_htmlTextareaSecurity($descriptionArray['DE']);
                     $room_item->setDescriptionArray($descriptionArray);
                  }
                  $room_item->setContextId($commsy_portal_id);
                  $room_item->open();

                  $this->_environment->setCurrentUserItem($template_user);
                  $room_item->save();
                  $id_manager->addIDsToDB($source,$stine_room_id,$room_item->getItemId());
                  $return_array = array("error" => 0,"value" => 'Room "'.$stine_room_id.'" succesfully created. CommSy Id: '.$room_item->getItemId());
                  $this->_log('IMS','createRoom','Room "'.$stine_room_id.'" succesfully created. CommSy Id: '.$room_item->getItemId());
               } else {
                  $info_text = 'Can not identify room item! - '.__FILE__.' - '.__LINE__;
                  $return_array = array("error" => 1,"value" => $info_text);
               }
            } else {
               $info_text = 'More than one or no user(s) in private room for user '.$membership_info->getSourceItemId().' !';
               $return_array = array("error" => 1,"value" => $info_text);
            }
         } else {
            $info_text = 'Trying to enroll unknown person ['.$stine_user_id.'] to new group ['.$stine_room_id.'] !';
            $return_array = array("error" => 1,"value" => $info_text);
         }
      } else {
         $info_text = '';
         if (!empty($commsy_room_id)) {
            $info_text = 'Trying to create an existing group: '.$stine_room_id.' !';
         } else {
            $info_text = 'Trying to create a group on an unknown portal: '.$commsy_portal_id.' !';
         }
         $return_array = array("error" => 1,"value" => $info_text);
      }
      return $return_array;
   }

   /*
   * Handles membership ims-packages
   */
   private function _handleMembershipPackage($member,$mail,$id_manager) {
      include_once('classes/cs_membershipInfo.php');
      $membership_info = new cs_membershipInfo($member['GROUPID'],$member['USERID'],$member['ROLETYPE'],$member['SOURCE']);

      //Delete
      if ($member['OP'] == 3) {
         $result = $this->_deleteMembership($mail,$membership_info,$id_manager);
      }

      //Create
      elseif ($member['OP'] == 1) {
         $result = $this->_createMembership($mail,$membership_info,$id_manager);
      }

      //Add Id(s) of item we are talking about
      $result['TARGET'] = $member['TARGET'];
      $result['GROUPID'] = $member['GROUPID'];
      $result['USERID'] = $member['USERID'];
      $result['DATASOURCE'] = $member['DATASOURCE'];
      $result['DATETIME'] = $member['DATETIME'];
      return $result;
   }

   private function _createMembership($mail,$membership_info,$id_manager) {
      $return_array = array("error" => 0,"value" => '');
      $source = $membership_info->getSourceSystem();
      $manager = $this->_environment->getUserManager();
      $stine_room_id = $membership_info->getRoomId();
      $stine_user_id = $membership_info->getSourceItemId();
      $commsy_user_id = $id_manager->getCommSyId($source,$stine_user_id);
      $commsy_room_id = $id_manager->getCommSyId($source,$stine_room_id);
      if (!empty($commsy_user_id)) {
         $user_item = $manager->getItem($commsy_user_id);
         if (!empty($commsy_room_id)) {
            $manager->setContextLimit($commsy_room_id);
            $manager->setUserIDLimit($user_item->getUserId());
            $manager->setAuthSourceLimit($user_item->getAuthSource());
            $manager->setDeleteLimit(true);
            $manager->select();
            $user_list = $manager->get();
            //check if user is allready in room
            if ($user_list->isEmpty()) {
               $user_item = $user_item->cloneData();
               if ( !empty($user_item) ) {
                  $user_item->setContextID($commsy_room_id);
                  $role = $membership_info->getRoleType();
                  if ($role == '05') {
                     $user_item->makeModerator();
                  } elseif ($role == '04') {
                     $user_item->makeUser();
                  }
                  $user_item->save();
                  $user_item->setCreatorID2ItemID();
                  $created_id = $user_item->getItemId();

                  //find  group 'all'
                  $group_manager = $this->_environment->getLabelManager();
                  $group_manager->setContextLimit($commsy_room_id);
                  $group_manager->setTypeLimit(CS_GROUP_TYPE);
                  $group_manager->setNameLimit('ALL');
                  $group_manager->select();
                  $group_list = $group_manager->get();
                  $group_all = $group_list->getFirst();

                  // link user 2 group all
                  $user_item->setGroupByID($group_all->getItemID());
                  $user_item->setChangeModificationOnSave(false);
                  $user_item->save();

                  $new_room_member = $user_item;

                  //Mail to created member
                  $translator = $this->_environment->getTranslationObject();
                  $room_manager = $this->_environment->getRoomManager();
                  $room_item = $room_manager->getItem($commsy_room_id);
                  $translator->initFromContext($room_item);

                  $contact_list_room = $room_item->getContactModeratorList();
                  $contact_room = $contact_list_room->getFirst();

                  $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_item->getTitle()));
                  $mail->set_to($user_item->getEmail());
                  $mail->set_reply_to_name($contact_room->getFullname());
                  $mail->set_reply_to_email($contact_room->getEmail());
                  $link = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
                  global $c_single_entry_point;
                  $link = str_replace ( 'soap.php', $c_single_entry_point.'?cid='.$room_item->getItemId(), $link);
                  $mail->set_subject($translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER',$room_item->getTitle()));

                  $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                  $body .= LF.LF;
                  $body .= $translator->getEmailMessage('MAIL_BODY_HELLO',$user_item->getFullname());
                  $body .= LF.LF;
                  $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$new_room_member->getUserID(),$room_item->getTitle());
                  $body .= LF.LF;
                  $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$contact_room->getFullname(),$room_item->getTitle());
                  $body .= LF.LF;
                  $body .= $link;
                  $mail->set_message($body);
                  $mail->send();

                  // mail handling for room moderators
                  $user_list = $room_item->getModeratorList();
                  $user_item = $user_list->getFirst();

                  $language = $room_item->getLanguage();
                  $recipients = '';

                  while ($user_item) {
                     $want_mail = $user_item->getAccountWantMail();
                     if (!empty($want_mail) and $want_mail == 'yes') {
                        if ($language == 'user' and $user_item->getLanguage() != 'browser') {
                           $email_addresses[$user_item->getLanguage()][] = $user_item->getEmail();
                        } elseif ($language == 'user' and $user_item->getLanguage() == 'browser') {
                           $email_addresses[$this->_environment->getSelectedLanguage()][] = $user_item->getEmail();
                        } else {
                           $email_addresses[$language][] = $user_item->getEmail();
                        }
                        $recipients .= $user_item->getFullname().LF;
                     }
                     $user_item = $user_list->getNext();
                  }

                  $save_language = $translator->getSelectedLanguage();
                  foreach ($email_addresses as $key => $value) {
                     $translator->setSelectedLanguage($key);
                     if (count($value) > 0) {
                        include_once('classes/cs_mail.php');
                        $mail = new cs_mail();
                        $mail->set_to(implode(',',$value));

                         global $symfonyContainer;
                         $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                         $mail->set_from_email($emailFrom);

                        $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_item->getTitle()));
                        $mail->set_reply_to_name($new_room_member->getFullname());

                        $mail->set_reply_to_email($new_room_member->getEmail());
                        $mail->set_subject($translator->getMessage('USER_JOIN_CONTEXT_MAIL_SUBJECT',$new_room_member->getFullname(),$room_item->getTitle()));
                        $body = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                        $body .= LF.LF;
                        $temp_language = $new_room_member->getLanguage();
                        $body .= $translator->getMessage('USER_JOIN_CONTEXT_MAIL_BODY',$new_room_member->getFullname(),$new_room_member->getUserId(),$new_room_member->getEmail(),$room_item->getTitle());
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
                        $body .= $translator->getMessage('MAIL_COMMENT_BY','IMS',$translator->getMessage('MAIL_COMMENT_IMS',$source));
                        $body .= LF.LF;
                        $body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
                        $body .= LF;
                        $body .= $link;
                        $mail->set_message($body);
                        $mail->send();
                     }
                  }
                  $translator->setSelectedLanguage($save_language);
                  $return_array  = array('error'=>0,'value'=>'User "'.$stine_user_id.'" is now enrolled in room "'.$stine_room_id.'". (CommSy-Id of user: '.$created_id.')');
                  $this->_log('IMS','createMembership','User "'.$stine_user_id.'" is now enrolled from room "'.$stine_room_id.'". (CommSy-Id of user: '.$created_id.')');
               } else {
                  $info_text = 'Can not clone user item! - '.__FILE__.' - '.__LINE__;
                  $return_array = array("error" => 1,"value" => $info_text);
               }
            } else {
               $info_text = 'User "'.$stine_user_id.'" allready in room "'.$stine_room_id.'!"';
               $return_array = array("error" => 1,"value" => $info_text);
            }
         } else {
            $info_text = 'Trying to enroll an person to an unknown group:  '.$stine_room_id.' !';
            $return_array = array("error" => 1,"value" => $info_text);
         }
      } elseif ( !empty($commsy_room_id) ) {
         //we know the room, but not the person... look if persons id is known on portal of the room and enroll person this way
         $room_manager = $this->_environment->getRoomManager();
         $room_item = $room_manager->getItem($commsy_room_id);
         if ( isset($room_item) ) {
            $portal_id = $room_item->getContextId();
            $this->_environment->setCurrentContextId($portal_id);
            $auth_object = $this->_environment->getAuthenticationObject();
            $auth_manager = $auth_object->getIMSAuthManager();
            $auth_manager->setCommSyIdLimit($portal_id);
            $auth_item = $auth_manager->getItem($membership_info->getUserId());
            $auth_object->save($auth_item,true);
            $user_item = $auth_object->getUserItem();
            $user_item->makeUser();
            $user_item->save();
            if (empty($auth_item)) {
               $info = 'ERROR: IMS';
               $info_text = 'Unknown user:  '.$stine_user_id.' !';
            } else {
               $user_item = $manager->getNewItem();
               $user_item->setContextID($commsy_room_id);
               $user_item->setUserId($auth_item->getUserId());
               $user_item->setFirstname($auth_item->getFirstname());
               $user_item->setLastname($auth_item->getLastname());
               $user_item->setEmail($auth_item->getEmail());
               $user_item->setLanguage($auth_item->getLanguage());
               $role = $membership_info->getRoleType();
               if ($role == '05') {
                  $user_item->makeModerator();
               } elseif ($role == '04') {
                  $user_item->makeUser();
               }
               $user_item->save();
               $user_item->setCreatorID2ItemID();

               //link to group 'all'
               $group_manager = $this->_environment->getLabelManager();
               $group_manager->setContextLimit($commsy_room_id);
               $group_manager->setTypeLimit(CS_GROUP_TYPE);
               $group_manager->setNameLimit('ALL');
               $group_manager->select();
               $group_list = $group_manager->get();
               $group_all = $group_list->getFirst();

               // link user 2 group all
               $user_item->setGroupByID($group_all->getItemID());
               $user_item->setChangeModificationOnSave(false);
               $user_item->save();

               $result = $user_item->getItemId();
               //save found user_id to DB
               $id_manager->addIDsToDB($source,$stine_user_id,$result);
            }
         } else {
            include_once('functions/error_functions.php');
            trigger_error('don\'t know the context '.$commsy_room_id);
            $info_text = 'Failure enrolling person: '.$stine_user_id.' to room: '.$stine_room_id.': Unknown room and person!';
            $return_array = array("error" => 1,"value" => $info_text);
         }
      } else {
         $info_text = 'Failure enrolling person: '.$stine_user_id.' to room: '.$stine_room_id.': Unknown room and person!';
         $return_array = array("error" => 1,"value" => $info_text);
      }
      return $return_array;
   }

   private function _deleteMembership($mail,$membership_info,$id_manager) {
      $return_array = array("error" => 0,"value" => '');
      $source = $membership_info->getSourceSystem();
      $manager = $this->_environment->getUserManager();
      $stine_room_id = $membership_info->getRoomId();
      $stine_user_id = $membership_info->getSourceItemId();
      $commsy_user_id = $id_manager->getCommSyId($source,$stine_user_id);
      $commsy_room_id = $id_manager->getCommSyId($source,$stine_room_id);
      if (!empty($commsy_user_id)) {
         $user_item = $manager->getItem($commsy_user_id);
         $user_email = $user_item->getEmail();

         $manager->setUserIdLimit($user_item->getUserId());
         $manager->setAuthSourceLimit($user_item->getAuthSource());
         $manager->setContextLimit($commsy_room_id);
         $manager->setDeleteLimit(true);
         $manager->select();

         $user_list = $manager->get();
         $commsy_user_id_delete = 0;
         $delete_user = $user_list->getFirst();
         if (!empty($delete_user)) {

            // Mail to deleted member
            $portal_user = $user_item->getRelatedCommSyUserItem();
            $portal_id = $portal_user->getContextId();
            $portal_manager = $this->_environment->getPortalManager();
            $portal_item = $portal_manager->getItem($portal_id);
            $translator = $this->_environment->getTranslationObject();
            $room_manager = $this->_environment->getRoomManager();
            $room_item = $room_manager->getItem($commsy_room_id);
            $language = $room_item->getLanguage();
            $translator->initFromContext($room_item);

            $contact_list = $room_item->getContactModeratorList();
            $contact = $contact_list->getFirst();
            $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_item->getTitle()));
            $mail->set_to($delete_user->getEmail());
            $mail->set_reply_to_name($contact->getFullname());
            $mail->set_reply_to_email($contact->getEmail());
            $link = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
            global $c_single_entry_point;
            $link = str_replace ( 'soap.php', $c_single_entry_point.'?cid='.$room_item->getItemId(), $link);
            $mail->set_subject($translator->getMessage('MAIL_SUBJECT_USER_MEMBERSHIP_DELETE',$room_item->getTitle()));

            $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
            $body .= LF.LF;
            $body .= $translator->getEmailMessage('MAIL_BODY_HELLO',$delete_user->getFullname());
            $body .= LF.LF;
            $body .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_DELETE',$delete_user->getUserID(),$room_item->getTitle());
            $body .= LF.LF;
            $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$contact->getFullname(),$room_item->getTitle());
            $body .= LF.LF;
            $body .= $link;
            $mail->set_message($body);
            $mail->send();

            // mail handling for room moderators
            $user_list = $room_item->getModeratorList();
            $user_item = $user_list->getFirst();

            while ($user_item) {
               $want_mail = $user_item->getAccountWantMail();
               if (!empty($want_mail) and $want_mail == 'yes') {
                  if ($language == 'user' and $user_item->getLanguage() != 'browser') {
                     $email_addresses[$user_item->getLanguage()][] = $user_item->getEmail();
                  } elseif ($language == 'user' and $user_item->getLanguage() == 'browser') {
                     $email_addresses[$this->_environment->getSelectedLanguage()][] = $user_item->getEmail();
                  } else {
                     $email_addresses[$language][] = $user_item->getEmail();
                  }
                  $recipients .= $user_item->getFullname().LF;
               }
               $user_item = $user_list->getNext();
            }

            $save_language = $translator->getSelectedLanguage();
            foreach ($email_addresses as $key => $value) {
               $translator->setSelectedLanguage($key);
               if (count($value) > 0) {
                  include_once('classes/cs_mail.php');
                  $mail = new cs_mail();
                  $mail->set_to(implode(',',$value));
                   global $symfonyContainer;
                   $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                   $mail->set_from_email($emailFrom);

                  $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_item->getTitle()));
                  $mail->set_reply_to_name($delete_user->getFullname());
                  $mail->set_reply_to_email($delete_user->getEmail());
                  $mail->set_subject($translator->getMessage('USER_QUIT_CONTEXT_MAIL_SUBJECT',$delete_user->getFullname(),$room_item->getTitle()));
                  $body = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                  $body .= LF.LF;
                  $temp_language = $portal_user->getLanguage();
                  if ($temp_language == 'browser') {
                     $temp_language = $this->_environment->getSelectedLanguage();
                  }
                  $body .= $translator->getMessage('USER_QUIT_PROJECT_MAIL_BODY',$delete_user->getFullname(),$delete_user->getUserId(),$delete_user->getEmail(),$room_item->getTitle());
                  unset($temp_language);
                  $body .= LF.LF;
                  $body .= $translator->getMessage('MAIL_COMMENT_BY','IMS',$translator->getMessage('MAIL_COMMENT_IMS',$source));
                  $body .= LF.LF;
                  $body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
                  $body .= LF;
                  $body .= $link;
                  $mail->set_message($body);
                  $mail->send();
               }
            }
            $translator->setSelectedLanguage($save_language);

            //Delete User
            $commsy_user_id_delete = $delete_user->getItemId();
            $delete_user->delete();
            $return_array = array("error" => 0,"value" => 'User "'.$stine_user_id.'" is now un-enrolled from room "'.$stine_room_id.'". (CommSy-Id of unenrolled user: '.$commsy_user_id_delete.')');
            $this->_log('IMS','deleteMembership','User "'.$stine_user_id.'" is now un-enrolled from room "'.$stine_room_id.'". (CommSy-Id of unenrolled user: '.$commsy_user_id_delete.')');
         } else {
            $info_text = 'User:  '.$stine_user_id.' is not enrolled in group '.$stine_room_id.'!';
            $return_array = array("error" => 1,"value" => $info_text);
         }
      } else {
         $info_text = 'Trying to unenroll an unknown person:  '.$stine_user_id.' !';
         $return_array = array("error" => 1,"value" => $info_text);
      }
      return $return_array;
   }
    /* Checks if all variables supplied yield a result
       If yes- pathfile was ok, if not, not

       @params $categorie: one of "person","group", "membership"
    */
    private function _checkPathfile($path_array,$categorie) {
       global $test_package_folder;
       $error_string = '';

       //get test packages - if we get all values in the respective package, all other packages should
       // work, too -- "add" is the most "demanding" package
       $success = true;
       $test_package = file_get_contents($test_package_folder.'/add_'.$categorie.'.xml');

       if ($test_package == false) {
          $success = false;
       }

       $xml = simplexml_load_string($test_package);
       foreach ($path_array as $path) {
          if ($success) {
             $value = $this->_getValueFromXMLPath($path,$xml);
          } else {break;}

          if (empty($value)) {
             $success = false;
             $error_string = $path.' failed to retrieve a value!';
          }
       }
       return array('success'=>$success,'error'=>$error_string);
    }

    private function _getDataForCategorie($categorie) {

    //FROM CS_CONFIG.php
      //Where to get a specific pathxml file (person, group, membership)
      global $url_for_path_file_person;
      global $url_for_path_file_group;
      global $url_for_path_file_membership;

      global $url_for_fallback_path_file_membership;
      global $url_for_fallback_path_file_group;
      global $url_for_fallback_path_file_person;

      global $path_file_url;
      global $path_xml_backup_folder;

      $base_data = array('remote_base_folder' => $path_file_url, 'backup_base_folder' => $path_xml_backup_folder);
      $categorie_data = array();
      if ($categorie == 'person') {
         $categorie_data = array('url' => $url_for_path_file_person, 'backup' => $url_for_fallback_path_file_person);
      } else if ($categorie == 'group') {
         $categorie_data = array('url' => $url_for_path_file_group, 'backup' => $url_for_fallback_path_file_group);
      } else if ($categorie == 'membership') {
         $categorie_data = array('url' => $url_for_path_file_membership, 'backup' => $url_for_fallback_path_file_membership);
      }

      return array_merge($base_data,$categorie_data);
    }

   private function _checkAndBackupPathfile($categorie) {
      $return_array = array();
      $categorie_data = $this->_getDataForCategorie($categorie);
      if ( !$this->_failure ) {
         $path_array_categorie = $this->_getPathFileFromServer($categorie_data['url']);
      } else {
         $path_array_categorie = FALSE;
      }
      //Save backup of most recent path_xml
      $remote_pathfile_ok = true;
      if ($path_array_categorie != FALSE) {
         $categorie_pathxml = file_get_contents($categorie_data['url']);
         $check_result = $this->_checkPathfile($path_array_categorie,$categorie);
         $remote_pathfile_ok = $check_result['success'];

         if ($remote_pathfile_ok) {
            if (!is_dir($categorie_data['backup_base_folder'])) {
               mkdir($categorie_data['backup_base_folder']);
            }
            $backup_success = file_put_contents($categorie_data['backup'],$categorie_pathxml);

            if ($backup_success == FALSE) {
              $return_array[] = array('error'=>2,'value'=> 'Backup of "categorie.path.xml" to "'.$categorie_data['backup'].'" failed! Could not write file!');
            }
         } else {
            $return_array[] = array('error'=>2,'value'=> 'File from "'.$categorie_data['url'].'" is not valid! '.$check_result['error']);
         }
      }
      if (!$remote_pathfile_ok or $path_array_categorie == FALSE) {
         $this->_failure = true;
         if ( isset($check_result) and $check_result['error'] != '') {
            $return_array[] = array('error'=>2,'value'=> 'Content of file under "'.$categorie_data['url'].'"" was not valid!. Trying stored path.xml!');
         } else {
            $return_array[] = array('error'=>2,'value'=> '"'.$categorie_data['url'].'" could not be found!. Trying stored pathxml!');
         }
         $path_array_categorie = $this->_getPathFileFromServer($categorie_data['backup']);

         if ($path_array_categorie == FALSE) {
            $return_array[] = array('error'=>1,'value'=> 'Neither url to "'.$categorie.'.path.xml" nor backup [url: '.$categorie_data['backup'].'] is availlable! Could not process request!');
         }
      }
      return array('error_array'=>$return_array,'path_array'=>$path_array_categorie);
   }

   private function _logXMLPackage ($ims_xml) {
      $folder_string = 'var/IMS';
      $folder = @opendir($folder_string);
      if (!$folder) {
         mkdir($folder_string);
      }
      $folder_string .= '/log';
      $folder = @opendir($folder_string);
      if (!$folder) {
         mkdir($folder_string);
      }
      $file_name = $folder_string.'/'.date('YmdHis').'.xml';
      $log_success = file_put_contents($file_name,$this->_encode_input($ims_xml));
      if ( !$log_success ) {
         include_once('functions/error_functions.php');
         trigger_error('can\' log xml package at '.$file_name,E_USER_NOTICE);
      }
   }

   public function IMS ($session_id, $ims_xml) {

      global $c_ims_log_package;
      if ( isset($c_ims_log_package) and $c_ims_log_package) {
         $this->_logXMLPackage($ims_xml);
      }

      $this->_ims_xml = $ims_xml;
      $session_id = $this->_encode_input($session_id);
      $return_array = array();
      $properties = array();
      if (true) {
         $this->_environment->setSessionID($session_id);
         $session_item = $this->_environment->getSessionItem();
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
               $ims_xml = $this->_encode_input($ims_xml);

               //check session
               if (true) {
                  $id_manager = $this->_environment->getExternalIdManager();

                  //Prepare an notification email
                  include_once('classes/cs_mail.php');
                  $mail = new cs_mail();
                  $server_item = $this->_environment->getServerItem();

                   global $symfonyContainer;
                   $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                   $mail->set_from_email($emailFrom);

                  $mail->set_from_name($server_item->getTitle());

                  //Split Message in functional parts
                  //Properties
                  $properties = '';
                  $properties_array = array();
                  preg_match('~<properties.+?</properties>~isu',$ims_xml,$properties);
                  if ( isset($properties[0]) ) {
                     $properties = $properties[0];
                     preg_match('~<targets>.+?</targets>~isu',$ims_xml,$targets);
                     if ( isset($targets[0]) ) {
                        $targets = $targets[0];
                        $server_item = $this->_environment->getServerItem();
                        $portal_list = $server_item->getPortalList();
                        if ( $portal_list->isNotEmpty() ) {
                           $portal_item = $portal_list->getFirst();
                           $name_array = array();
                           while ($portal_item) {
                              if ( $targets = '*'
                                   or strstr($portal_item->getTitle(),$targets)
                                 ) {
                                 $name_array[] = $portal_item->getItemID();
                              }
                              $portal_item = $portal_list->getNext();
                           }
                           if ( !empty($name_array) ) {
                              foreach ($name_array as $portal_target) {
                                 $properties_array[] = preg_replace('~<targets>.+?</targets>~isu','<targets>'.$portal_target.'</targets>',$properties);
                              }
                           }
                        }
                        unset($portal_list);
                        unset($server_item);
                     } else {
                        $return_array[] = array('error' => '1', 'value' => 'IMS ERROR: no targets in properties defined');
                     }
                  } else {
                     include_once('functions/error_functions.php');
                     trigger_error('no properties in XML package found',E_USER_WARNING);
                  }

                  if ( !empty($properties_array) ) {
                     //PERSON
                     $check_result = $this->_checkAndBackupPathfile('person');
                     $path_array_person =  $check_result['path_array'];
                     $return_array = array_merge($check_result['error_array'],$return_array);

                     preg_match_all('~<person.+?</person>~isu',$ims_xml,$person_results);
                     $person_results = $person_results[0];
                     $person_values = array();
                     foreach ($person_results as $person) {
                        foreach ( $properties_array as $properties_item ) {
                           $person_xml = simplexml_load_string(utf8_encode('<dummytag>'.$properties_item.' '.$person.'</dummytag>'));
                           $temp_array = array();
                           foreach ($path_array_person as $variable => $path) {
                              $value = $this->_getValueFromXMLPath($path,$person_xml);
                              $temp_array[$variable] = $value;
                           }
                           if ( !empty($temp_array['OP']) ) {
                              $person_values['op'.$temp_array['OP']][$temp_array['USERID']] = $temp_array;
                           } else {
                              $return_array[] = array('error' => '1', 'value' => 'IMS ERROR: recstatus for person ('.$temp_array['USERID'].') is not set');
                           }
                        }
                     }

                     //GROUP
                     $check_result = $this->_checkAndBackupPathfile('group');
                     $path_array_group =  $check_result['path_array'];
                     $return_array = array_merge($check_result['error_array'],$return_array);

                     preg_match_all('~<group.+?</group>~isu',$ims_xml,$group_results);
                     $group_results = $group_results[0];
                     $group_values = array();
                     foreach ($group_results as $group) {
                        foreach ( $properties_array as $properties_item ) {
                           $group_xml = simplexml_load_string('<dummytag>'.$properties_item.' '.$group.'</dummytag>');
                           $temp_array = array();
                           foreach ($path_array_group as $variable => $path) {
                              $value = $this->_getValueFromXMLPath($path,$group_xml);
                              $temp_array[$variable] = $value;
                           }
                           if ( !empty($temp_array['OP']) ) {
                              $group_values['op'.$temp_array['OP']][$temp_array['GROUPID']] = $temp_array;
                           } else {
                              if ( !empty($temp_array) ) {
                                 $temp_array_for_message = serialize();
                              } else {
                                 $temp_array_for_message = 'NO DATA FOUND';
                              }
                              $return_array[] = array('error' => '1', 'value' => 'IMS ERROR: recstatus for group ('.$temp_array['GROUPID'].') is not set. Data array for group: '.$temp_array_for_message);
                           }
                        }
                     }

                     //MEMBERSHIP
                     $check_result = $this->_checkAndBackupPathfile('membership');
                     $path_array_membership =  $check_result['path_array'];
                     $return_array = array_merge($check_result['error_array'],$return_array);

                     preg_match_all('~<membership.+?</membership>~isu',$ims_xml,$membership_results);
                     $membership_results = $membership_results[0];
                     $membership_values = array();
                     foreach ($membership_results as $membership) {
                        foreach ( $properties_array as $properties_item ) {
                           $membership_xml = simplexml_load_string('<dummytag>'.$properties_item.' '.$membership.'</dummytag>');
                           $temp_array = array();
                           foreach ($path_array_membership as $variable => $path) {
                              $value = $this->_getValueFromXMLPath($path,$membership_xml);
                              $temp_array[$variable] = $value;
                           }
                           if ( !empty($temp_array['OP']) ) {
                              if (!array_key_exists($temp_array['USERID'].'_'.$temp_array['ROLETYPE'].'_'.$temp_array['GROUPID'],$temp_array)) {
                                 $membership_values['op'.$temp_array['OP']][$temp_array['USERID'].'_'.$temp_array['ROLETYPE'].'_'.$temp_array['GROUPID']] = $temp_array;
                              } else {
                                 $membership_values['op'.$temp_array['OP']][] = $temp_array;
                              }
                           } else {
                              $return_array[] = array('error' => '1', 'value' => 'IMS ERROR: recstatus for membership ('.$temp_array['USERID'].'_'.$temp_array['ROLETYPE'].'_'.$temp_array['GROUPID'].') is not set');
                           }
                        }
                     }

                     // Order of actions is important!
                     // 1-Create person(s)
                     // 2-Create group(s)
                     // 3-Create membership(s)
                     // 4-Edit person(s)
                     // 5-Edit group(s)
                     // 6-Delete membership(s)
                     // 7-Delete group(s)
                     // 8-Delete person(s)

                     //Create Persons
                     if ( !empty($person_values['op1']) ) {
                        foreach ($person_values['op1'] as $person) {
                           //every room needs an creator... if $person is marked as such, it is a copy of an existing
                           // person, not a new one. It will be created while creating the room, so skip it here
                           if ( !(isset($person['room_creator'])) ) {
                              $return_array[] = $this->_handleUserPackage($person,$mail,$id_manager);
                           }
                        }
                     }

                     //Create groups
                     if ( !empty($group_values['op1']) ) {
                        foreach ($group_values['op1'] as $room) {
                           $return_array[] = $this->_handleRoomPackage($room,$mail,$id_manager,$person_values,$membership_values);
                        }
                     }

                     //Edit Persons
                     if ( !empty($person_values['op2']) ) {
                        foreach ($person_values['op2'] as $person){
                           $return_array[] = $this->_handleUserPackage($person,$mail,$id_manager);
                        }
                     }

                     //Edit groups
                     if ( !empty($group_values['op2']) ) {
                        foreach ($group_values['op2'] as $room){
                           $return_array[] = $this->_handleRoomPackage($room,$mail,$id_manager,$person_values,$membership_values);
                        }
                     }

                     //Create memberships
                     if ( !empty($membership_values['op1']) ) {
                        foreach ($membership_values['op1'] as $membership) {
                           //every room needs an initial member (= creator)... if $membership
                           //as such, this membership has allready been processed while creating a room
                           if ( !(isset($membership['room_creator'])) ) {
                              $return_array[] = $this->_handleMembershipPackage($membership,$mail,$id_manager);
                           } else {
                             $return_array[] = array('error'=>0,'value'=>'User "'.$membership['USERID'].'" is now enrolled in room "'.$membership['GROUPID'].'"');
                           }
                        }
                     }

                     //Delete memberships
                     if ( !empty($membership_values['op3']) ) {
                        foreach ($membership_values['op3'] as $membership) {
                           $return_array[] = $this->_handleMembershipPackage($membership,$mail,$id_manager);
                        }
                     }

                     //Delete groups
                     if ( !empty($group_values['op3']) ) {
                        foreach ($group_values['op3'] as $room) {
                           $return_array[] = $this->_handleRoomPackage($room,$mail,$id_manager,$person_values,$membership_values);
                        }
                     }

                     //Delete Persons
                     if ( !empty($person_values['op3']) ) {
                        foreach ($person_values['op3'] as $person){
                           $return_array[] = $this->_handleUserPackage($person,$mail,$id_manager);
                        }
                     }
                     $this->_log('IMS','IMS','SID='.$session_id.',IMS_XML=NOT_LOGGED');
                  } else {
                     $return_array[] = array('error' => '1', 'value' => 'IMS ERROR: targets defined in IMS paket are not available in CommSy');
                  }
               } else {
                  $return_array[] = array('error' => '1', 'value' => 'IMS ERROR: Session id ('.$session_id.') is not valid');
               }
            } else {
               $return_array[] = array('error' => '1', 'value' => 'IMS ERROR: Logged in user is not allowed to send IMS messages to CommSy!');
            }
         } else {
            $return_array[] = array('error' => '1', 'value' => 'IMS ERROR: can not identify current user');
         }
      } else {
         $return_array[] = array('error' => '1', 'value' => 'IMS ERROR: Session id ('.$session_id.') is not valid');
      }
      return $this->_encode_output($this->_create_ims_return_string($return_array));
   }

   private function _create_ims_return_string($return_array) {
      $log_error = false;
      $datetime = '';
      $i=0;
      while ( $datetime == '' and $i < count($return_array)) {
         if ( !empty($return_array[0]['DATETIME']) ) {
            $datetime = $return_array[0]['DATETIME'];
         } else {
            $i++;
         }
      }

      // get target
      $target = '';
      foreach ($return_array as $result) {
         if ( !empty($result['TARGET']) ) {
            $target = $result['TARGET'];
            break;
         }
      }

      $xml_string =  '<?xml version="1.0" encoding="UTF-8"?>';
      $xml_string .= '   <enterprise>';
      $xml_string .= '         <properties>';
      $xml_string .= '            <datasource>CommSy</datasource>';
      $xml_string .= '            <datetime>'.$datetime.'</datetime>';
      $xml_string .= '            <extension>';
      foreach ($return_array as $result) {
         $success_indicator = '';
         $id = '?';
         if (!empty($result['GROUPID']) and empty($result['USERID'])) {
            //Group was handled here
            $id = $result['GROUPID'];
         } elseif (empty($result['GROUPID']) and !empty($result['USERID'])) {
            //User was handled here
            $id = $result['USERID'];
         } elseif (!empty($result['GROUPID']) and !empty($result['USERID'])) {
            //Membership was handled here
            $id = $result['GROUPID'].';'.$result['USERID'];
         }

         if ($result['error'] == 1) {
            $success_indicator = 'ERROR';
            $log_error = true;
         } elseif ($result['error'] == 0) {
            $success_indicator = 'SUCCESS';
         } elseif ($result['error'] == 2) {
            $success_indicator = 'WARNING';
         } elseif ($result['error'] == 3) {
            $success_indicator = 'INFO';
         } elseif ($result['error'] == 4) {
            $success_indicator = 'ACTION';
         }
         // target = 'ZIELSYSTEM, wo die nachricht hingeht.';
         // * = ALL
         // RETURN = RETURN
         // id = id der Inhalte
         // id = user_id oder kurse_id oder das Paket mit @ oder ? als unbekannt
         $xml_string .= '               <message code="'.$success_indicator.'" target="RETURN" ref="'.$id.'" source="'.$target.'">'.$result['value'].'</message>';
      }
      $xml_string .= '            </extension>';
      $xml_string .= '         </properties>';
      $xml_string .= '   </enterprise>';

      if ( $log_error ) {
         $error_array = array();
         $error_array['number'] = 1;
         $error_array['type'] = 'IMS_ERROR';
         $error_array['message'] = $this->_ims_xml.LF.LF.$xml_string;
         $error_array['file'] = __FILE__;
         $error_array['line'] = __LINE__;
         $error_array['context'] = $this->_environment->getCurrentContextID();
         $error_array['user'] = 'IMS_USER';
         $log_error_manager = $this->_environment->getLogErrorManager();
         $log_error_manager->saveArray($error_array);
         unset($log_error_manager);
      }

      return $xml_string;
   }

   private function _getValueFromXMLPath($path,$simple_xml_object) {
      //shorten path from outermost tag (simple_xml works imlpicitly on outermost tag, so it's not needed)
      //$path = strstr($path,'/');
      //eval is dangerous- check $path to see if it seems suspicious
      $path_valid = preg_match('~^\w+(/\w+)*(\[@\w+\]?)?$~u',$path);
      //Convert to simple_xml notation
      $path = str_replace ( '/', '->', $path);
      $result = '';
      $at_number = mb_substr_count($path,'@');
      if ($at_number == 0 AND $path_valid == 1) {
         eval('$result = (string) $simple_xml_object->'.$path.';');
         $result = utf8_decode($result);
      } else if ($at_number == 1 AND $path_valid == 1) {
         $path = str_replace ( '[@', '[\'', $path);
         $path = str_replace ( ']', '\']', $path);
         eval('$result = (string) $simple_xml_object->'.$path.';');
         $result = utf8_decode($result);
      } else {
         $result = 'Error processing path: Wrong path format! '.$path;
      }
      return $result;
   }

   private function _getPathFileFromServer($url) {
      $result = array();
      $pathfile = file_get_contents($url);

      /*
      Matches a structure like:
      <var name="KEY">
         <path>PATH</path><path>PATH2</path><path>PATH3</path>...
      </var>
      with "KEY" beeing one item of interest and "PATH" beeing the second
      (PATH2/PATH3/etc are not interesting, since the value of the KEY is the same under each path!)
      */
      if ($pathfile == FALSE) {
         $result = FALSE;
      } else {
         $regexp = '~<var name="(.+?)">(\s|\n)*?<path>(.+?)</path>(\s|\n)*?(<path>(.+?)</path>(\s|\n)*?)*</var>~u';
         $match_results = array();
         preg_match_all($regexp,$pathfile,$match_results);
         $keys =  $match_results[1];
         $values = $match_results[3];
         $result = array_combine($keys,$values);
         return $result;
      }
   }
}
?>