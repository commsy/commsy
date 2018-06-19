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

/** class for commsy form: group
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_autoaccounts_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;



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
    * this methods init the data for the form
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      $this->_headline = $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_FORM_HEADLINE');
      $this->setHeadline($this->_headline);
      $this->seperators = array(array('text' => $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SEPERATOR_AUTO_SELECT'), 'value' => $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SEPERATOR_AUTO_SELECT')), array('text' => ';', 'value' => ';'), array('text' => ',', 'value' => ','));
      $this->auth_source = array();
      $portal = $this->_environment->getCurrentPortalItem();
      $auth_source_list = $portal->getAuthSourceList();
      $temp_auth_source = $auth_source_list->getFirst();
      $selected_auth_id = '';
      $auth_default = $portal->getAuthDefault();
      while($temp_auth_source){
         if($temp_auth_source->getItemID() == $auth_default){
            $text = $temp_auth_source->getTitle() . ' (' . $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_AUTH_SOURCE_SELECT') . ')';
            //$text = $temp_auth_source->getTitle();

            $this->auth_source[] = array('text' => $text, 'value' => $temp_auth_source->getItemID());
            $selected_auth_id = $temp_auth_source->getItemID();
         } else {
            $this->auth_source[] = array('text' => $temp_auth_source->getTitle(), 'value' => $temp_auth_source->getItemID());
         }
         $temp_auth_source = $auth_source_list->getNext();
      }
      $index = 0;
      $this->selected_auth_entry = 0;
      foreach($this->auth_source as $auth_source){
         if($auth_source['value'] == $selected_auth_id){
            $this->selected_auth_entry = $index;
         }
         $index++;
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {
      $this->_form->addImage('dates_upload','',$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_UPLOADFILE'),'');
      $this->_form->addSelect('autoaccounts_seperator',$this->seperators,$this->seperators[0],$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SEPERATOR_AUTO_SELECT_DESCRIPTION'),$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SEPERATOR_AUTO_SELECT_DESCRIPTION'), 1, false,false,false,'','','','',15.3);
      $this->_form->addSelect('autoaccounts_auth_source',$this->auth_source,$this->auth_source[$this->selected_auth_entry],$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_AUTH_SOURCE_SELECT_DESCRIPTION'),$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_AUTH_SOURCE_SELECT_DESCRIPTION'), 1, false,false,false,'','','','',15.3);
      $this->_form->addButtonBar('option',$this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_UPLOAD_FILE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
      $this->_form->addText(null, null, $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_UPLOAD_FILE_TEMPLATES'));
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      if(isset($_GET['seperator_not_found'])){
         $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_AUTOACCOUNTS_SEPERATOR_NOT_FOUND');
         $this->_form->setFailure('autoaccounts_seperator','');
      }
      $this->_values = array();
      if ( isset($this->_form_post) ) {
         $this->_values = $this->_form_post;
      }
   }


   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      $error = $this->_check_file_format();
   }

   function _check_file_format() {
      $error = false;
      //$environment = $this->_environment;
      $file = $this->_form_post['dates_upload']['name'];
      $file_elements =  explode('.',$file);
      if ( isset($file_elements[1]) and !empty($file_elements[1]) ){
         $file_type = mb_strtoupper( $file_elements[1] , 'UTF-8');
         if ($file_type != 'CSV') {
            $this->_error_array[] = $this->_translator->getMessage('DATES_WRONG_FILE_FORMAT');
            $error = true;
         }
      }elseif ( !isset($file_elements[1]) ){
         $this->_error_array[] = $this->_translator->getMessage('NO_DATES_FILE_FOUND');
         $error = true;
      }
      return $error;
   }
}
?>