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

$this->includeClass(RUBRIC_FORM);

class cs_user_mail_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

   var $_receiver_array = NULL;

   var $_limit_array = array();

   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function cs_mail_to_user_form($environment) {
      $this->cs_rubric_form($environment);
   }

   function setSearchLimit ($value) {
      $this->_limit_array['search'] = $value;
   }

   function setGroupLimit ($value) {
      $this->_limit_array['group'] = $value;
   }

   function setTopicLimit ($value) {
      $this->_limit_array['topic'] = $value;
   }

   function setInsitutionLimit ($value) {
      $this->_limit_array['institution'] = $value;
   }

   function setStatusLimit ($value) {
      $this->_limit_array['status'] = $value;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {

      // headline
      $this->_headline = getMessage('GROUPS_EMAIL_TO_GROUP');

      // get user
      $user_manager = $this->_environment->getUserManager();
      $user_manager->reset();
      $user_manager->setContextLimit($this->_environment->getCurrentContextID());
      $user_manager->setUserLimit();
      if ( !empty($this->_limit_array['search']) ) {
         $user_manager->setSearchLimit($this->_limit_array['search']);
      }
      if ( !empty($this->_limit_array['group']) ) {
         $user_manager->setGroupLimit($this->_limit_array['group']);
      }
      if ( !empty($this->_limit_array['topic']) ) {
         $user_manager->setTopicLimit($this->_limit_array['topic']);
      }
      if ( !empty($this->_limit_array['institution']) ) {
         $user_manager->setInstitutionLimit($this->_limit_array['institution']);
      }
      if ( !empty($this->_limit_array['status']) ) {
         if ($this->_limit_array['status'] == 2) {
            $user_manager->setUserLimit();
         } elseif ($this->_limit_array['status'] == 3) {
            $user_manager->setModeratorLimit();
         } elseif ($this->_limit_array['status'] == 4) {
            $user_manager->setOrganisatorStatusLimit();
         } elseif ($this->_limit_array['status'] == 11) {
            $user_manager->setUserInProjectLimit();
         } elseif ($this->_limit_array['status'] == 12) {
            $user_manager->setContactModeratorInProjectLimit();
         }

      }
      $user_manager->select();
      $user_list = $user_manager->get();

      $this->_receiver_array = array();
      if (!$user_list->isEmpty()) {
         $user_item = $user_list->getFirst();
         while ($user_item) {
            $temp_array = array();
            $temp_array['value'] = $user_item->getEmail();
			if ($user_item->isEmailVisible()) {
               $temp_array['text'] = $user_item->getFullName().' ('.$user_item->getEmail().')';
			} else {
               $temp_array['text'] = $user_item->getFullName().' ('.$this->_translator->getMessage('USER_EMAIL_HIDDEN2').')';
			}
            $this->_receiver_array[] = $temp_array;
            $user_item = $user_list->getNext();
         }
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      // annotation
      $this->_form->addHeadline('headline',$this->_headline);
      $this->_form->addHidden('search','');
      $this->_form->addHidden('selgroup','');
      $this->_form->addHidden('seltopic','');
      $this->_form->addHidden('selinstitution','');
      $this->_form->addHidden('selstatus','');
      $current_user = $this->_environment->getCurrentUserItem();
      if (!$current_user->isUser()) {
         $this->_form->addTextField('sender_name','',getMessage('MAIL_SENDER_NAME'),'',200,'',true);
         $this->_form->addTextField('sender_email','',getMessage('MAIL_SENDER_EMAIL'),'',200,'',true);
      } else {
         $this->_form->addHidden('sender_name','');
         $this->_form->addHidden('sender_email','');
         $this->_form->addHidden('sender_text_hidden','');
         $this->_form->addText('sender_text',getMessage('MAIL_SENDER'),'');
      }
      if ( sizeof($this->_receiver_array) > 1 ) {
         $this->_form->addCheckBoxGroup('receivers',$this->_receiver_array,'',getMessage('COMMON_MAIL_RECEIVER'),getMessage('COMMON_MAIL_RECEIVER_DESC'), true, false);
      } else {
         $this->_form->addText('receiver',getMessage('COMMON_MAIL_RECEIVER'),$this->_receiver_array[0]['text']);
         $this->_form->addHidden('receiver_email',$this->_receiver_array[0]['value']);
      }
      $this->_form->addTextField('subject','',getMessage('COMMON_MAIL_SUBJECT'),getMessage('COMMON_MAIL_SUBJECT_DESC'),200,'',true);

      $context_item = $this->_environment->getCurrentContextItem();
      if ( $context_item->isCommunityRoom() ) {
         $body_message = getMessage('RUBRIC_EMAIL_ADDED_BODY_COMMUNITY', $context_item->getTitle());
      } elseif ( $context_item->isProjectRoom() ) {
         $body_message = getMessage('RUBRIC_EMAIL_ADDED_BODY_PROJECT', $context_item->getTitle());
      } elseif ( $context_item->isPortal() ) {
         $body_message = getMessage('RUBRIC_EMAIL_ADDED_BODY_PORTAL', $context_item->getTitle());
      } elseif ( $context_item->isServer() ) {
         $body_message = getMessage('RUBRIC_EMAIL_ADDED_BODY_SERVER', $context_item->getTitle());
      }
      $this->_form->addTextArea('content',$body_message,getMessage('COMMON_CONTENT'),getMessage('COMMON_MAIL_CONTENT_DESC'),'60','15','',true,false,false);

      // buttons
      $this->_form->addButtonBar('option',getMessage('MAIL_SEND_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'));
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the annotation item or the form_post data
    */
   function _prepareValues () {
      if (isset($this->_form_post)) {
          $this->_values = $this->_form_post; // no encode here - encode in form-views
          if (!empty($this->_form_post['sender_text_hidden'])) {
             $this->_values['sender_text'] = $this->_form_post['sender_text_hidden'];
          }
      } else {
         foreach ($this->_receiver_array as $receiver) {
            $this->_values['receivers'][] = $receiver['value'];
         }
         $current_user = $this->_environment->getCurrentUserItem();
         $this->_values['sender_name']         = $current_user->getFullName();
         $this->_values['sender_email']        = $current_user->getEmail();
		 if ($current_user->isEmailVisible()) {
            $this->_values['sender_text_hidden']  = $current_user->getFullName().' ('.$current_user->getEmail().')';
		 } else {
            $this->_values['sender_text_hidden']  = $current_user->getFullName().' ('.$this->_translator->getMessage('USER_EMAIL_HIDDEN2').')';
		 }
         $this->_values['sender_text']         = $this->_values['sender_text_hidden'];
         if (!empty($this->_limit_array['search'])) {
            $this->_values['search']           = $this->_limit_array['search'];
         }
         if (!empty($this->_limit_array['group'])) {
            $this->_values['selgroup']         = $this->_limit_array['group'];
         }
         if (!empty($this->_limit_array['topic'])) {
            $this->_values['seltopic']         = $this->_limit_array['topic'];
         }
         if (!empty($this->_limit_array['institution'])) {
            $this->_values['selinstitution']   = $this->_limit_array['institution'];
         }
         if (!empty($this->_limit_array['status'])) {
            $this->_values['selstatus']   = $this->_limit_array['status'];
         }
      }
   }
}
?>