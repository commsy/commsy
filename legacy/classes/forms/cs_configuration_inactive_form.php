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

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_inactive_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

   var $_yes_no_array = array();


  /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function cs_configuration_authentication_form ($params) {
      cs_rubric_form::__construct($params);
   }


   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
   	
   	$context_item = $this->_environment->getCurrentContextItem();
   	
   	$this->_headline = $this->_translator->getMessage('CONFIGURATION_INACTIVITY');

   	// portal option choice
   	$this->_array_portal[0]['text']  = '*'.$this->_translator->getMessage('CONFIGURATION_EXTRA_CHOOSE_NO_PORTAL');
   	$this->_array_portal[0]['value'] = -1;
   	
   	$server_item = $this->_environment->getServerItem();
   	$portal_list = $server_item->getPortalList();
   	if ( $portal_list->isNotEmpty() ) {
   		$this->_array_portal[1]['text']  = '----------------------';
   		$this->_array_portal[1]['value'] = 'disabled';
   		$portal_item = $portal_list->getFirst();
   		while ( $portal_item ) {
   			$temp_array = array();
   			$temp_array['text']  = $portal_item->getTitle();
   			$temp_array['value'] = $portal_item->getItemID();
   			$this->_array_portal[] = $temp_array;
   	
   			$portal_item = $portal_list->getNext();
   		}
   	}

    // auth text choice
    $this->_array_auth_source[0]['text']  = '*'.$this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_CHOICE_CHOOSE_TEXT');
    $this->_array_auth_source[0]['value'] = -1;

    // yes no array
    $this->_yes_no_array[0]['text'] = $this->_translator->getMessage('COMMON_YES');
    $this->_yes_no_array[0]['value'] = 1;
    $this->_yes_no_array[1]['text'] = $this->_translator->getMessage('COMMON_NO');
    $this->_yes_no_array[1]['value'] = 2;
    
    // show checkboxes
    if ( !empty($this->_form_post['portal'])
    and $this->_form_post['portal'] > 99
    ) {
    	$this->_show_checkboxes = $this->_form_post['portal'];
    }


   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
    function _createForm()
    {
        $translator = $this->_environment->getTranslationObject();

        $this->setHeadline($this->_headline);

        $context_item = $this->_environment->getCurrentContextItem();
        if($context_item->isPortal()){
            $this->_form->addText('overwrite_content_info', '', $translator->getMessage('CONFIGURATION_INACTIVITY_OVERWRITE_INFO'));
            $this->_form->addEmptyline();
            $this->_form->addTextfield('lock_user','',$translator->getMessage('CONFIGURATION_INACTIVITY_LOCK'),'',3,10,false,'','','','','','',false);
            $this->_form->addTextfield('email_before_lock','',$translator->getMessage('CONFIGURATION_INACTIVITY_EMAIL_LOCK'),'',3,10,false,'','','','','','',false);
            $this->_form->addTextfield('delete_user','',$translator->getMessage('CONFIGURATION_INACTIVITY_DELETE'),'',3,10,false,'','','','','','',false);
            $this->_form->addTextfield('email_before_delete','',$translator->getMessage('CONFIGURATION_INACTIVITY_EMAIL_DELETE'),'',3,10,false,'','','','','','',false);
        }

        // buttons
        $this->_form->addButtonBar('option',$translator->getMessage('PREFERENCES_SAVE_BUTTON'),'','','','','','');
    }

    /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
    function _prepareValues()
    {
        $this->_values = array();

        if (!empty($this->_form_post)) {
            $this->_values = $this->_form_post;
        } elseif (!empty($this->_item)) {
            $this->_values['lock_user'] = $this->_item->getInactivityLockDays();
            $this->_values['email_before_lock'] = $this->_item->getInactivitySendMailBeforeLockDays();
            $this->_values['delete_user'] = $this->_item->getInactivityDeleteDays();
            $this->_values['email_before_delete'] = $this->_item->getInactivitySendMailBeforeDeleteDays();
        }
    }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      // check choosen auth source
      if ( !empty($this->_form_post['lock_user'])
      	  and !is_numeric($this->_form_post['lock_user'])
      	) {
      	$this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_PASSWORD_LENGTH_ERROR',$this->_translator->getMessage('CONFIGURATION_INACTIVITY_LOCK'));
      	$this->_form->setFailure('lock_user');
      }     
      if ( !empty($this->_form_post['email_before_lock'])
      		and !is_numeric($this->_form_post['email_before_lock'])
         ) {
      	$this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_PASSWORD_LENGTH_ERROR',$this->_translator->getMessage('CONFIGURATION_INACTIVITY_EMAIL_LOCK'));
      	$this->_form->setFailure('email_before_lock');
      }
      if ( !empty($this->_form_post['delete_user'])
      		and !is_numeric($this->_form_post['delete_user'])
         ) {
      	$this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_PASSWORD_LENGTH_ERROR',$this->_translator->getMessage('CONFIGURATION_INACTIVITY_DELETE'));
      	$this->_form->setFailure('delete_user');
      }
      if ( !empty($this->_form_post['email_before_delete'])
      		and !is_numeric($this->_form_post['email_before_delete'])
         ) {
      	$this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_PASSWORD_LENGTH_ERROR',$this->_translator->getMessage('CONFIGURATION_INACTIVITY_EMAIL_DELETE'));
      	$this->_form->setFailure('email_before_delete');
      }
   }
}
?>