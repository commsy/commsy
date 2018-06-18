<?PHP
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Manuel Gonzalez Vazquez, Johannes Schultze
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

$_skin_array = array();

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_limesurvey_form extends cs_rubric_form
{

   /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function __construct ($params) {
      cs_rubric_form::__construct($params);
      $this->_translator = $this->_environment->getTranslationObject();
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      $this->_item = $this->_environment->getCurrentContextItem();
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm ()
   {
   		$this->_form->addCheckbox(	'ls_activate',
   									1,
   									'',
   									$this->_translator->getMessage('LIMESURVEY_CONFIGURATION_ACTIVATE'),
   									'',
   									'',
   									true );
   		$this->_form->addTextfield(	'ls_remote_url',
   									'',
   									$this->_translator->getMessage('LIMESURVEY_CONFIGURATION_REMOTE_URL'),
   									'',
   									200,
   									28,
   									true );
   		$this->_form->addEmptyline();
   		
   		$this->_form->addTextField(	'ls_admin_user',
   									'',
   									$this->_translator->getMessage('LIMESURVEY_CONFIGURATION_ADMIN_USER'),
   									'',
   									200,
   									28,
   									true );
   		
   		$this->_form->addPassword(	'ls_admin_pw',
   									'',
   									$this->_translator->getMessage('LIMESURVEY_CONFIGURATION_ADMIN_PW'),
   									'',
   									200,
   									28,
   									true );
   		
      // buttons
   	  $this->_form->addButtonBar('option',$this->_translator->getMessage('LIMESURVEY_SAVE_BUTTON'));
   }



   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the context item or the form_post data
    */
   function _prepareValues ()
   {
      $this->_values = array();
      
      if ( isset($this->_form_post) )
      {
         $this->_values = $this->_form_post;
      }
      elseif ( isset($this->_item) )
      {
      		if ( $this->_item->isLimeSurveyActive() )
      		{
      			$this->_values['ls_activate'] = 1;
      		}
      		
      		$this->_values['ls_remote_url'] = $this->_item->getLimeSurveyJsonRpcUrl();
      		$this->_values['ls_admin_user'] = $this->_item->getLimeSurveyAdminUser();
      		$this->_values['ls_admin_pw'] = $this->_item->getLimeSurveyAdminPassword();
      }
   }

   function _checkValues()
   {
   		// if active 
   		if ( !empty($this->_form_post['ls_activate']) && $this->_form_post['ls_activate'] == "1" )
   		{
   			if ( empty($this->_form_post['ls_remote_url']) )
   			{
   				$this->_error_array[] = $this->_translator->getMessage('LIMESURVEY_CONFIGURATION_MISSING_URL');
   				$this->_form->setFailure('ls_remote_url','');
   			}
   			
   			if ( empty($this->_form_post['ls_admin_user']) )
   			{
   				$this->_error_array[] = $this->_translator->getMessage('LIMESURVEY_CONFIGURATION_MISSING_ADMIN_USER');
   				$this->_form->setFailure('ls_admin_user','');
   			}
   			
   			if ( empty($this->_form_post['ls_admin_pw']) )
   			{
   				$this->_error_array[] = $this->_translator->getMessage('LIMESURVEY_CONFIGURATION_MISSING_ADMIN_PW');
   				$this->_form->setFailure('ls_admin_pw','');
   			}
   		}
   }
}
?>