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

$this->includeClass(RUBRIC_FORM);

class cs_account_action_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

   var $_warning = NULL;

   var $_name = NULL;

   var $_language = NULL;

   var $_user_id = NULL;

   var $_subject = NULL;

   var $_content = NULL;

   var $_action_obj = NULL;

   var $_cc_bcc_values = array();

   var $_with_copy_mod = false;

   /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /**
    * Set the cs_action_index_obj to init the form
    */
   function setActionArray ($value) {
      $this->_action_array = $value;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      if ( isset($this->_action_array) ) {

         // prepare some variables
         $translator = $this->_environment->getTranslationObject();
         $user_manager = $this->_environment->getUserManager();
         $admin = $user_manager->getItem($this->_action_array['user_item_id']);
         if ( !isset($admin) ) {
            $admin = $this->_environment->getCurrentUserItem();
         }
         $room = $this->_environment->getCurrentContextItem();
         $url_to_room = LF.LF;
         $url_to_room .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$this->_environment->getCurrentContextID();

         $array_user_item_id = $this->_action_array['selected_ids'];
         $this->_name = '';
         $first = true;
         foreach ($array_user_item_id as $id) {
            if ($first) {
               $first = false;
            } else {
               $this->_name .= LF;
            }
            $user = $user_manager->getItem($id);
            if ( !empty($user) ) {
               if($user->isEmailVisible()){
                  $this->_name .= $user->getFullname().' ('.$user->getEmail().')';
               } else {
                  $this->_name .= $user->getFullname().' ('.$translator->getMessage('USER_EMAIL_HIDDEN').')';
               }
            }
         }
         $translate = false;
         if ( count($array_user_item_id) == 1 ) {
            $translate = true;
         }
         
         if( count($array_user_item_id) <= 0){
         	redirect($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index');
         }
         
         // Datenschutz
         if ( !empty($user) ) {
	         if($this->_environment->getCurrentPortalItem()->getHideAccountname()){
	         	$userid = 'XXX '.$translator->getMessage('COMMON_DATASECURITY_NAME', $user->getFullname());
	         } else {
	         	$userid = $user->getUserID();
	         }
         }

         if ( $translate ) {
            $this->_content  = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
         } else {
            $this->_content  = $translator->getEmailMessage('MAIL_BODY_HELLO');
         }
         $this->_content .= LF.LF;

         // now prepare for each action separately
         if ( $this->_action_array['action'] == 'USER_ACCOUNT_DELETE' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_ACCOUNT_DELETE');

            // datenschutz: overwrite or not (03.09.2012 IJ)
            $overwrite = true;
            global $symfonyContainer;
            $disable_overwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');
            if ( !empty($disable_overwrite) and $disable_overwrite === 'TRUE' ) {
            	$overwrite = false;
            }            
            if ($overwrite) {
               $this->_warning  = $translator->getMessage('INDEX_ACTION_FORM_USER_ACCOUNT_DELETE_WARNING');
            }

            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_DELETE',$room->getTitle());
            if ( $translate ) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_DELETE',$userid,$room->getTitle());
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_DELETE');
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
               $this->_content .= $content_temp;
            }
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         } elseif ( $this->_action_array['action'] == 'USER_ACCOUNT_LOCK' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_ACCOUNT_LOCK');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_LOCK',$room->getTitle());
            if ( $translate ) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_LOCK',$userid,$room->getTitle());
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_LOCK');
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
               $this->_content .= $content_temp;
            }
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         } elseif ( $this->_action_array['action'] == 'USER_ACCOUNT_FREE' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_ACCOUNT_FREE');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_FREE',$room->getTitle());
            if ($translate) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$userid,$room->getTitle());
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER');
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
               $this->_content .= $content_temp;
            }
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         } elseif ( $this->_action_array['action'] == 'USER_STATUS_USER' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_STATUS_USER');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER',$room->getTitle());
            if ($translate) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$userid,$room->getTitle());
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER');
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
               $this->_content .= $content_temp;
            }
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         } elseif ( $this->_action_array['action'] == 'USER_STATUS_MODERATOR' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_STATUS_MODERATOR');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_MODERATOR',$room->getTitle());
            if ($translate) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_MODERATOR',$userid,$room->getTitle());
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_STATUS_MODERATOR');
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
               $this->_content .= $content_temp;
            }
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         } elseif ( $this->_action_array['action'] == 'USER_MAKE_CONTACT_PERSON' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_MAKE_CONTACT_PERSON');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_MAKE_CONTACT_PERSON',$room->getTitle());
            if ($translate) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_MAKE_CONTACT_PERSON',$userid,$room->getTitle());
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_MAKE_CONTACT_PERSON');
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
               $this->_content .= $content_temp;
            }
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         } elseif ( $this->_action_array['action'] == 'USER_UNMAKE_CONTACT_PERSON' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_UNMAKE_CONTACT_PERSON');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_UNMAKE_CONTACT_PERSON',$room->getTitle());
            if ($translate) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_UNMAKE_CONTACT_PERSON',$userid,$room->getTitle());
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_UNMAKE_CONTACT_PERSON');
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
               $this->_content .= $content_temp;
            }
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         } elseif ( $this->_action_array['action'] == 'USER_EMAIL_SEND' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_EMAIL_SEND');
            $this->_subject  = '';
            $this->_content  = LF.LF.LF;
            if ( $this->_environment->getCurrentModule() == 'account' ) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            } else { // user: send mail
               $this->_content .= $translator->getMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            }
            $this->_content .= $url_to_room;
            $this->_with_copy_mod = true;
         } elseif ( $this->_action_array['action'] == 'USER_EMAIL_ACCOUNT_PASSWORD' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_ACCOUNT_PASSWORD');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_PASSWORD',$room->getTitle());
            if ($translate) {
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_PASSWORD',$room->getTitle(),$userid);
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_PASSWORD');
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
               $this->_content .= $content_temp;
            }
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         } elseif ( $this->_action_array['action'] == 'USER_EMAIL_ACCOUNT_MERGE' ) {
            $this->_headline = $translator->getMessage('INDEX_ACTION_FORM_HEADLINE_USER_ACCOUNT_MERGE');
            $this->_subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_MERGE',$room->getTitle());
            if ($translate) {
               $account_text = '';
               $user_manager->resetLimits();
               $user_manager->setContextLimit($this->_environment->getCurrentContextID());
               $user_manager->setUserLimit();
               $user_manager->setSearchLimit($user->getEmail());
               $user_manager->select();
               $user_list = $user_manager->get();
               if (!$user_list->isEmpty()) {
                  if ($user_list->getCount() > 1) {
                     $first = true;
                     $user_item = $user_list->getFirst();
                     while ($user_item) {
                        if ($first) {
                           $first = false;
                        } else {
                           $account_text .= LF;
                        }
                        if($this->_environment->getCurrentPortalItem()->getHideAccountname()){
                        	$userID = 'XXX '.$translator->getMessage('COMMON_DATASECURITY_NAME', $user_item->getFullname());
                        } else {
                        	$userID = $user_item->getUserID();
                        }
                        $account_text .= $userID;
                        $user_item = $user_list->getNext();
                     }
                  } else {
                     include_once('functions/error_functions.php');
                     trigger_error('that is impossible, list must be greater than one',E_USER_WARNING);
                  }
               } else {
                  include_once('functions/error_functions.php');
                  trigger_error('that is impossible, list must be greater than one',E_USER_WARNING);
               }
               $this->_content .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_MERGE',$user->getEMail(),$room->getTitle(),$account_text);
            } else {
               $content_temp = $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_MERGE');
               $content_temp = str_replace('%3','%4',$content_temp);
               $content_temp = str_replace('%2','%3',$content_temp);
               $content_temp = str_replace('%1','%2',$content_temp);
            }
            $this->_content .= $content_temp;
            $this->_content .= LF.LF;
            $this->_content .= $translator->getEmailMessage('MAIL_BODY_CIAO',$admin->getFullname(),$room->getTitle());
            $this->_content .= $url_to_room;
         }
      } else {
         $admin = $this->_environment->getCurrentUserItem();
      }

      // cc / bcc - values
      $this->_cc_bcc_values[0]['text']  = $this->_translator->getMessage('INDEX_ACTION_FORM_CC');
      $this->_cc_bcc_values[0]['value'] = 'cc';
      $this->_cc_bcc_values[1]['text']  = $this->_translator->getMessage('INDEX_ACTION_FORM_BCC',$admin->getFullname());
      $this->_cc_bcc_values[1]['value'] = 'bcc';
      $this->_cc_bcc_values[2]['text']  = $this->_translator->getMessage('INDEX_ACTION_FORM_CC_MODERATOR');
      $this->_cc_bcc_values[2]['value'] = 'cc_moderator';
      $this->_cc_bcc_values[3]['text']  = $this->_translator->getMessage('INDEX_ACTION_FORM_BCC_MODERATOR');
      $this->_cc_bcc_values[3]['value'] = 'bcc_moderator';
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      $this->setHeadline($this->_headline);

      if ( isset($this->_warning) ) {
         $this->_form->addWarning('warning',$this->_warning);
      }

      if ( $this->_user_id != NULL ) {
         $this->_form->addText('user_id',$this->_translator->getMessage('USER_USER_ID'),$this->_user_id);
      }
      if ( $this->_name != NULL ) {
         $this->_form->addText('name',$this->_translator->getMessage('COMMON_NAME'),$this->_name);
      }
      if ( $this->_language != NULL) {
         $this->_form->addText('language',$this->_translator->getMessage('COMMON_LANGUAGE'),$this->_language);
      }

      if ( $this->_action_array['action'] != 'USER_EMAIL_SEND'
           and $this->_action_array['action'] != 'USER_EMAIL_ACCOUNT_PASSWORD'
           and $this->_action_array['action'] != 'USER_EMAIL_ACCOUNT_MERGE'
           ) {
         $this->_form->addCheckbox('with_mail','1',true,$this->_translator->getMessage('INDEX_ACTION_FORM_MAIL'),$this->_translator->getMessage('INDEX_ACTION_FORM_MAIL_OPTION'),'','','','onclick="cs_toggle();"');
      } else {
         $this->_form->addHidden('with_mail','1');
      }

      if ( $this->_with_copy_mod ) {
         $this->_form->combine();
         $this->_form->addCheckbox('copy','copy',false,'',$this->_translator->getMessage('MAILCOPY_TO_SENDER'),'','','','');
      } else {
         if ( isset($this->_cc_bcc_values[2]) and isset($this->_cc_bcc_values[3]) ) {
            $this->_form->addCheckbox('cc_moderator','cc_moderator',false,$this->_translator->getMessage('INDEX_ACTION_FORM_CC_BCC'),$this->_cc_bcc_values[2]['text'],'','','','');
            $this->_form->combine('horizontal');
            $this->_form->addCheckbox('bcc_moderator','bcc_moderator',false,$this->_translator->getMessage('INDEX_ACTION_FORM_CC_BCC'),$this->_cc_bcc_values[3]['text'],'','','','');
         }
         $this->_form->combine();
         $this->_form->addCheckbox('cc','cc',false,$this->_translator->getMessage('INDEX_ACTION_FORM_CC_BCC'),$this->_cc_bcc_values[0]['text'],'','','','');
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('bcc','bcc',false,$this->_translator->getMessage('INDEX_ACTION_FORM_CC_BCC'),$this->_cc_bcc_values[1]['text'],'','','','');
      }
      $this->_form->addTextField('subject','',$this->_translator->getMessage('COMMON_MAIL_SUBJECT'),'',200,'',false);
      $this->_form->addTextArea('content','',$this->_translator->getMessage('COMMON_CONTENT'),'',60,10, '', true,false,false);

      // buttons
      if ( $this->_action_array['action'] == 'USER_EMAIL_SEND'
           or $this->_action_array['action'] == 'USER_EMAIL_ACCOUNT_PASSWORD'
           or $this->_action_array['action'] == 'USER_EMAIL_ACCOUNT_MERGE') {
         $this->_form->addButtonBar('option',$this->_translator->getMessage('INDEX_ACTION_SEND_MAIL_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','','');
      } else {
         $tempMessage = "";
         switch( $this->_action_array['action'] )
         {
            case 'USER_ACCOUNT_DELETE':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_ACCOUNT_DELETE_BUTTON');
               break;
            case 'USER_ACCOUNT_FREE':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_ACCOUNT_FREE_BUTTON');
               break;
            case 'USER_ACCOUNT_LOCK':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_ACCOUNT_LOCK_BUTTON');
               break;
            case 'USER_MAKE_CONTACT_PERSON':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_MAKE_CONTACT_PERSON_BUTTON');
               break;
            case 'USER_STATUS_EDITOR':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_STATUS_EDITOR_BUTTON');
               break;
            case 'USER_STATUS_MODERATOR':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_STATUS_MODERATOR_BUTTON');
               break;
            case 'USER_STATUS_ORGANIZER':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_STATUS_ORGANIZER_BUTTON');
               break;
            case 'USER_STATUS_USER':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_STATUS_USER_BUTTON');
               break;
            case 'USER_UNMAKE_CONTACT_PERSON':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_UNMAKE_CONTACT_PERSON_BUTTON');
               break;
            case 'USER_HIDE_MAIL_DEFAULT':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_UNMAKE_CONTACT_PERSON_BUTTON');
               break;
            case 'USER_HIDE_MAIL_ALL_ROOMS':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_UNMAKE_CONTACT_PERSON_BUTTON');
               break;
            case 'USER_SHOW_MAIL_DEFAULT':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_UNMAKE_CONTACT_PERSON_BUTTON');
               break;
            case 'USER_SHOW_MAIL_ALL_ROOMS':
               $tempMessage = $this->_translator->getMessage('INDEX_ACTION_PERFORM_USER_UNMAKE_CONTACT_PERSON_BUTTON');
               break;
            default:
               $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_account_action_form(539) ');
               break;
         }
         $this->_form->addButtonBar('option', $tempMessage, $this->_translator->getMessage('COMMON_CANCEL_BUTTON'), '', '', '', '');
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the annotation item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if (!empty($this->_form_post)) {
         $this->_values = $this->_form_post;
      } else {
         $this->_values['subject'] = $this->_subject;
         $this->_values['content'] = $this->_content;
      }
   }

   function getInfoForHeaderAsHTML () {
      $retour  = '';
      $retour .= '         function cs_toggle() {'.LF;
      $retour .= '            if (document.f.with_mail.checked) {'.LF;
      $retour .= '               cs_enable();'.LF;
      $retour .= '            } else {'.LF;
      $retour .= '               cs_disable();'.LF;
      $retour .= '            }'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_disable() {'.LF;
      $retour .= '            document.f.subject.disabled = true;'.LF;
      $retour .= '            document.f.content.disabled = true;'.LF;
      $retour .= '            document.f.cc.disabled = true;'.LF;
      $retour .= '            document.f.bcc.disabled = true;'.LF;
      $retour .= '            document.f.cc_moderator.disabled = true;'.LF;
      $retour .= '            document.f.bcc_moderator.disabled = true;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_enable() {'.LF;
      $retour .= '            document.f.subject.disabled = false;'.LF;
      $retour .= '            document.f.content.disabled = false;'.LF;
      $retour .= '            document.f.cc.disabled = false;'.LF;
      $retour .= '            document.f.bcc.disabled = false;'.LF;
      $retour .= '            document.f.cc_moderator.disabled = false;'.LF;
      $retour .= '            document.f.bcc_moderator.disabled = false;'.LF;
      $retour .= '         }'.LF;
      return $retour;
   }
}
?>