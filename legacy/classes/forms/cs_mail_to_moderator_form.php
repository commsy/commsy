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

class cs_mail_to_moderator_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

   var $_receiver_array = NULL;

   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {

      // headline
      $this->_headline = $this->_translator->getMessage('MAIL_TO_MODERATOR_HEADLINE');

      $context_item = $this->_environment->getCurrentContextItem();
      $mod_list = $context_item->getModeratorList();
      $this->_receiver_array = array();
      if (!$mod_list->isEmpty()) {
         $mod_item = $mod_list->getFirst();
         while ($mod_item) {
            $temp_array = array();
            $temp_array['value'] = $mod_item->getEmail();
         if ($mod_item->isEmailVisible()) {
               $temp_array['text'] = $mod_item->getFullName().' ('.$mod_item->getEmail().')';
         } else {
               $temp_array['text'] = $mod_item->getFullName().' ('.$this->_translator->getMessage('USER_EMAIL_HIDDEN2').')';
         }
            $this->_receiver_array[] = $temp_array;
            $mod_item = $mod_list->getNext();
         }
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      // annotation
      $this->_form->addHeadline('headline',$this->_headline);
      $current_user = $this->_environment->getCurrentUserItem();
      if (!$current_user->isUser()) {
         $this->_form->addTextField('sender_name','',$this->_translator->getMessage('MAIL_SENDER_NAME'),'',200,'',true);
         $this->_form->addTextField('sender_email','',$this->_translator->getMessage('MAIL_SENDER_EMAIL'),'',200,'',true);
      } else {
         $this->_form->addHidden('sender_name','');
         $this->_form->addHidden('sender_email','');
         $this->_form->addHidden('sender_text_hidden','');
         $this->_form->addText('sender_text',$this->_translator->getMessage('MAIL_SENDER'),'');
      }
      if ( sizeof($this->_receiver_array) > 1 ) {
         $this->_form->addCheckBoxGroup('receivers',$this->_receiver_array,'',$this->_translator->getMessage('COMMON_MAIL_RECEIVER'),$this->_translator->getMessage('COMMON_MAIL_RECEIVER_DESC'), true, false);
      } else {
         $this->_form->addText('receiver',$this->_translator->getMessage('COMMON_MAIL_RECEIVER'),$this->_receiver_array[0]['text']);
         $this->_form->addHidden('receiver_email',$this->_receiver_array[0]['value']);
      }
      $this->_form->addTextField('subject','',$this->_translator->getMessage('COMMON_MAIL_SUBJECT'),$this->_translator->getMessage('COMMON_MAIL_SUBJECT_DESC'),200,'',true);

      $context_item = $this->_environment->getCurrentContextItem();
      $context_title = str_ireplace('&amp;', '&', $context_item->getTitle());
      if ( $context_item->isCommunityRoom() ) {
         $body_message = $this->_translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_COMMUNITY', $context_title);
      } elseif ( $context_item->isProjectRoom() ) {
         $body_message = $this->_translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_PROJECT', $context_title);
      } elseif ( $context_item->isGroupRoom() ) {
         $body_message = $this->_translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_GROUPROOM', $context_title);
      } elseif ( $context_item->isPortal() ) {
         $body_message = $this->_translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_PORTAL', $context_title);
      } elseif ( $context_item->isServer() ) {
         $body_message = $this->_translator->getMessage('RUBRIC_EMAIL_ADDED_BODY_SERVER', $context_title);
      }
      $this->_form->addTextArea('content',$body_message,$this->_translator->getMessage('COMMON_CONTENT'),$this->_translator->getMessage('COMMON_MAIL_CONTENT_DESC'),'60','15','',true,false,false);

      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('MAIL_SEND_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the annotation item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      if (isset($this->_form_post)) {
          $this->_values = $this->_form_post; // no encode here - encode in form-views
          if (!empty($this->_form_post['sender_text_hidden'])) {
             $this->_values['sender_text'] = $this->_form_post['sender_text_hidden'];
          }
      }
   }
}
?>