<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

$this->includeClass(FORM);

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_search_short_form extends cs_form {

  /**
   * object - containing the environment object, set at constructor
   */
   var $_environment = NULL;

   var $_translator = NULL;

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

   var $_form = NULL;


  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function __construct($params) {
      if ( !empty($params['environment']) ) {
         $this->_environment = $params['environment'];
      } else {
         include_once('functions/error_functions.php');
         trigger_error('no environment defined '.__FILE__.' '.__LINE__,E_USER_ERROR);
      }
      $this->_form = new cs_form();
   }

   /** prepare form, init and create it
    * this methods prepare the form: init data and define the form
    *
    * @author CommSy Development Group
    */
   function prepareForm () {
      $this->_initForm();
      $this->_createForm();
   }

   /** check mandatory fields
    * this methods check mandatory fields
    *
    * return boolean is mandatory ?
    *
    * @author CommSy Development Group
    */
   function check () {
      $this->_form->checkMandatory();
      $this->_error_array = $this->_form->getErrorArray();
      $this->_checkValues();
      if (count($this->_error_array) == 0) {
         $retour = true;
      } else {
         $retour = false;
      }
      return $retour;
   }

   function setFailure($name, $type='', $text='') {
      if (!empty($text)) {
         $this->_error_array[] = $text;
      }
      $this->_form->setFailure($name,$type);
   }

   /** set form post data
    * set an array with the form post data
    *
    * @param array array an array: HTTP_POST_VARS
    *
    * @author CommSy Development Group
    */
   function setFormPost ($array) {
      $this->_form_post = $array;
   }

   function setRubricConnections ($array) {
      $this->_rubric_connection_array = (array)$array;
   }

   /** get from elements
    * this methods returns the form elements to be show in the form_view
    *
    * return object list of form elements
    *
    * @author CommSy Development Group
    */
   function getFormElements () { //weg TBD
      return $this->_form->getFormElements();
   }

   /** get error array from form
    * this method returns the error array with error messages
    *
    * @return array an array of error messages
    *
    * @author CommSy Development Group
    */
   function getErrorArray () {
      return $this->_error_array;
   }

   /** prepare values and load it into form
    * this methods prepare the values in an arry and load it into the form
    *
    * @author CommSy Development Group
    */
   function loadValues () {
      $this->_prepareValues();
      $this->_form->loadValues($this->_values);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {

      // headline
      $this->_headline = $this->_translator->getMessage('HOME_SEARCH_SHORT_TITLE');

      $this->_value_rubric_select[0]['value'] = 'all';
      $this->_value_rubric_select[0]['text']  = $this->_translator->getMessage('HOME_SEARCH_ALL_RUBRICS');
      $context_item = $this->_environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  array();
      }
      $first = '';
      $i=1;
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         $display = true;
         if ( $this->_environment->inPrivateRoom() ){
            if ($link_name[0]=='user'){
               $display = false;
            }
         }
         if ( $link_name[1] != 'none' and $display) {
            $this->_value_rubric_select[$i]['value'] = $link_name[0];
            $temp_link = mb_strtoupper($link_name[0], 'UTF-8');
            $tempMessage = "";
            switch ( $temp_link )
            {
               case 'ANNOUNCEMENT':
                  $tempMessage = $this->_translator->getMessage('COMMON_ANNOUNCEMENT_INDEX');
                  break;
               case 'DATE':
                  $tempMessage = $this->_translator->getMessage('COMMON_DATE_INDEX');
                  break;
               case 'DISCUSSION':
                  $tempMessage = $this->_translator->getMessage('COMMON_DISCUSSION_INDEX');
                  break;
               case 'GROUP':
                  $tempMessage = $this->_translator->getMessage('COMMON_GROUP_INDEX');
                  break;
               case 'INSTITUTION':
                  $tempMessage = $this->_translator->getMessage('COMMON_INSTITUTION_INDEX');
                  break;
               case 'MATERIAL':
                  $tempMessage = $this->_translator->getMessage('COMMON_MATERIAL_INDEX');
                  break;
               case 'MYROOM':
                  $tempMessage = $this->_translator->getMessage('COMMON_MYROOM_INDEX');
                  break;
               case 'PROJECT':
                  $tempMessage = $this->_translator->getMessage('COMMON_PROJECT_INDEX');
                  break;
               case 'TODO':
                  $tempMessage = $this->_translator->getMessage('COMMON_TODO_INDEX');
                  break;
               case 'TOPIC':
                  $tempMessage = $this->_translator->getMessage('COMMON_TOPIC_INDEX');
                  break;
               case 'USER':
                  $tempMessage = $this->_translator->getMessage('COMMON_USER_INDEX');
                  break;
               default:
                  $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR' . ' cs_search_short_form(212)');
                  break;
            }
            $this->_value_rubric_select[$i]['text']  = $tempMessage;
            $i++;
         }
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $this->_form->addHeadline('headline',$this->_headline,'');
      $this->_form->addHidden('modus','short');
      $this->_form->addHidden('enter',$this->_translator->getMessage('HOME_SEARCH_SHORT_BUTTON'));
      $this->_form->addEmptyLine();
      $this->_form->addTextfield('search_text','','','',255,30,false,'','','','left',$this->_translator->getMessage('HOME_SEARCH_SHORT_TO'));
      $this->_form->addCheckbox('only_files','1','','',$this->_translator->getMessage('HOME_SEARCH_ONLY_FILES_TEXT'));
      $this->_form->addSelect('selrubric', $this->_value_rubric_select,'','','','',false,'','',$this->_translator->getMessage('HOME_SEARCH_SHORT_BUTTON'),'option','',$this->_translator->getMessage('HOME_SEARCH_SHORT_IN'),'10.8');
      $this->_form->addHidden('form_view','campus_search_short');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
   }
}
?>