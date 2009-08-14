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

$this->includeClass(RUBRIC_FORM);

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_service_form extends cs_rubric_form {

   var $_initially_enable_email_textfield = false;

   /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function cs_configuration_service_form($params) {
      $this->cs_rubric_form($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      $this->_initially_enable_email_textfield = false;
      $this->_headline = getMessage('CONFIGURATION_SERVICE_TITLE');
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/32x32/config/service.gif" style="vertical-align:bottom;" alt="'.getMessage('COMMON_CONFIGURATION_HTMLTEXTAREA_FORM_TITLE').'"/>';
      } else {
         $image = '<img src="images/commsyicons/32x32/config/service.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_CONFIGURATION_HTMLTEXTAREA_FORM_TITLE').'"/>';
      }
      if ( !empty($image) ) {
         $this->_headline = $image.' '.$this->_headline;
      }
      $this->setHeadline($this->_headline);
      if (isset($this->_item)) {
         if ($this->_item->isServiceLinkActive()) {
            $this->_initially_enable_email_textfield = true;
         }
      } else {
         if (isset($_POST['servicelink']) AND $_POST['servicelink'] == 1) {
            $this->_initially_enable_email_textfield = true;
         }
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      // form fields
      $this->_form->addHidden('iid','');
      $this->_form->addCheckbox('servicelink',1,'',$this->_translator->getMessage('SERVICELINK_PREFERENCES_SERVICELINK',$this->_translator->getMessage('COMMON_MAIL_TO_SERVICE2')),$this->_translator->getMessage('COMMON_SHOW'),'','','','onclick="cs_toggle()"');

      $tier = '';
      if (isset($this->_item) and !empty($this->_item)) {
         if ( $this->_item->isCommunityRoom()
              or $this->_item->isProjectRoom()
              or $this->_item->isGroupRoom()
              or $this->_item->isPrivateRoom()
            ) {
            $tier = 'room';
         } else if ($this->_item->isPortal()) {
            $tier = 'portal';
         } else if ($this->_item->isServer()) {
            $tier = 'server';
         }
      } else {
         $tier = $_POST['tier'];
      }

      $tier_message = '';
      switch ($tier) {
         case 'room': {
            $tier_message = $this->_translator->getMessage('CONFIGURATION_SERVICE_EMAIL_ROOM');
            break;
         }
         case 'portal': {
            $tier_message = $this->_translator->getMessage('CONFIGURATION_SERVICE_EMAIL_PORTAL');
            break;
         }
         case 'server': {
            $tier_message = $this->_translator->getMessage('CONFIGURATION_SERVICE_EMAIL_SERVER');
            break;
         }
      }

      $this->_form->addHidden('tier',$tier);
      $this->_form->addTextfield('serviceemail','',$this->_translator->getMessage('CONFIGURATION_SERVICE_EMAIL').' ('.$tier_message.')','','',50,false,'','','','left','','',!$this->_initially_enable_email_textfield,'');
      $this->_form->addCheckbox('reset','value',false,$this->_translator->getMessage('CONFIGURATION_SERVICE_EMAIL_RESET'),$this->_translator->getMessage('COMMON_YES'),'','',!$this->_initially_enable_email_textfield);

      $this->_form->addEmptyline();
      $this->_form->addCheckbox('moderatorlink','1',false,$this->_translator->getMessage('CONFIGURATION_SERVICE_EMAIL_MODERATOR'),$this->_translator->getMessage('COMMON_SHOW'),'','',!$this->_initially_enable_email_textfield);
      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if (isset($this->_item)) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['servicelink'] = $this->_item->isServiceLinkActive();
         if ( $this->_item->isModeratorLinkActive() ) {
            $this->_values['moderatorlink'] = 1;
         }
         $this->_values['serviceemail'] = $this->_item->getServiceEmail();
         if ( empty($this->_values['serviceemail']) and !$this->_item->isServer() ) {
            $current_context_item = $this->_item->getContextItem();
            $this->_values['serviceemail'] = $current_context_item->getServiceEmail();
            if ( empty($this->_values['serviceemail']) and !$current_context_item->isServer()) {
               $server_item = $current_context_item->getContextItem();
               if ( isset($server_item) ) {
                  $this->_values['serviceemail'] = $server_item->getServiceEmail();
               }
               unset($server_item);
            }
            unset($current_context_item);
         }
      } elseif (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
         if ( !empty($this->_values['reset']) ) {
            $item_manager = $this->_environment->getItemManager();
            $type = $item_manager->getItemType($this->_values['iid']);
            if ( $type != CS_SERVER_TYPE ) {
               $manager = $this->_environment->getManager($type);
               unset($item_manager);
               $context = $manager->getItem($this->_values['iid']);
               unset($manager);
               $context = $context->getContextItem();
               $this->_values['serviceemail'] = $context->getServiceEmail();
               if ( empty($this->_values['serviceemail'])
                    and !$context->isServer() ) {
                  $context = $context->getContextItem();
                  $this->_values['serviceemail'] = $context->getServiceEmail();
               }
               unset($context);
            } else {
               $this->_values['serviceemail'] = '';
            }
         }
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {

      //check emails for validity. Empty fields are accepted, too.
      if (!empty($this->_form_post['serviceemail']) and isEmailValid($this->_form_post['serviceemail']) == false) {
         $this->_error_array[] = getMessage('USER_EMAIL_VALID_ERROR');
         $this->_form->setFailure('email','');
         $this->_form->setFailure('email_confirmation','');
      }
   }

   function getInfoForHeaderAsHTML () {
      $form_name = 'edit';
      $current_context_item = $this->_environment->getCurrentContextItem();
      if ( $this->_environment->inPortal()
           or $this->_environment->inServer()
           or $current_context_item->isDesign6()
         ) {
         $form_name = 'f';
      }
      unset($current_context_item);

      $retour  = '';
      $retour .= '         function cs_toggle() {'.LF;
      $retour .= '            if (document.'.$form_name.'.servicelink.checked) {'.LF;
      $retour .= '               cs_enable();'.LF;
      $retour .= '            } else {'.LF;
      $retour .= '               cs_disable();'.LF;
      $retour .= '            }'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_disable() {'.LF;
      $retour .= '            document.'.$form_name.'.servicelink.value = -1;'.LF;
      $retour .= '            document.'.$form_name.'.serviceemail.disabled = true;'.LF;
      $retour .= '            document.'.$form_name.'.moderatorlink.checked = true;'.LF;
      $retour .= '            document.'.$form_name.'.moderatorlink.disabled = true;'.LF;
      $retour .= '            document.'.$form_name.'.serviceemail.value = "";'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_enable() {'.LF;
      $retour .= '            document.'.$form_name.'.serviceemail.disabled = false;'.LF;
      $retour .= '            document.'.$form_name.'.moderatorlink.disabled = false;'.LF;
      $retour .= '         }'.LF;

   return $retour;
   }
}
?>