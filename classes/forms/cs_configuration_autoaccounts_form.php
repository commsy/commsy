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
   function cs_configuration_autoaccounts_form($params) {
      $this->cs_rubric_form($params);
   }


   /** init data for form, INTERNAL
    * this methods init the data for the form
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      $this->_headline = getMessage('COMMON_CONFIGURATION_AUTOACCOUNTS_FORM_HEADLINE');
      $this->setHeadline($this->_headline);
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {
      $this->_form->addImage('dates_upload','',getMessage('COMMON_CONFIGURATION_AUTOACCOUNTS_UPLOADFILE'),'');
      $seperators = array(array('text' => getMessage('COMMON_CONFIGURATION_AUTOACCOUNTS_SEPERATOR_AUTO_SELECT'), 'value' => getMessage('COMMON_CONFIGURATION_AUTOACCOUNTS_SEPERATOR_AUTO_SELECT')), array('text' => ';', 'value' => ';'), array('text' => ',', 'value' => ','));
      $this->_form->addSelect('autoaccounts_seperator',$seperators,$seperators[0],getMessage('COMMON_CONFIGURATION_AUTOACCOUNTS_SEPERATOR_AUTO_SELECT_DESCRIPTION'),getMessage('COMMON_CONFIGURATION_AUTOACCOUNTS_SEPERATOR_AUTO_SELECT_DESCRIPTION'), 1, false,false,false,'','','','',15.3);
      $this->_form->addButtonBar('option',getMessage('COMMON_CONFIGURATION_AUTOACCOUNTS_UPLOAD_FILE_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'));
      $this->_form->addText(null, null, getMessage('COMMON_CONFIGURATION_AUTOACCOUNTS_UPLOAD_FILE_TEMPLATES'));
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      if(isset($_GET['seperator_not_found'])){
         $this->_error_array[] = $this->_translator->getMessage('COMMON_CONFIGURATION_AUTOACCOUNTS_SEPERATOR_NOT_FOUND');
         $this->_form->setFailure('autoaccounts_seperator','');
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
      $environment = $this->_environment;
      $file = $this->_form_post['dates_upload']['name'];
      $file_elements =  explode('.',$file);
      if ( isset($file_elements[1]) and !empty($file_elements[1]) ){
         $file_type = mb_strtoupper( $file_elements[1] , 'UTF-8');
         if ($file_type != 'CSV') {
            $this->_error_array[] = getMessage('DATES_WRONG_FILE_FORMAT');
	   $error = true;
         }
      }elseif ( !isset($file_elements[1]) ){
            $this->_error_array[] = getMessage('NO_DATES_FILE_FOUND');
	   $error = true;
      }
      return $error;
   }
}
?>