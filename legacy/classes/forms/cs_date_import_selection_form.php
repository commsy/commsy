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
class cs_date_import_selection_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;
   var $_array = NULL;



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

   function setArray($array){
      $this->_array = $array;
      $temp_array = array();
      foreach($this->_array as $key =>  $data){
        $new_array= array();
        $new_array['text']= $key;
        $new_array['value']= $key;
        $temp_array[]= $new_array;
      }
      $this->_array = $temp_array;
   }
   /** init data for form, INTERNAL
    * this methods init the data for the form
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      $this->setHeadline($this->_translator->getMessage('DATE_IMPORT_FORM2'));
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      $this->_form->addSelect('title',$this->_array,'',$this->_translator->getMessage('COMMON_TITLE'),$this->_translator->getMessage('DATE_TITLE_DESC'), 1, false,false,false,'','','','',15.3);
      $this->_form->addSelect('description',$this->_array,'',$this->_translator->getMessage('COMMON_DESCRIPTION'),'', 1, false,false,false,'','','','',15.3);
      $this->_form->addSelect('starttime',$this->_array,'',$this->_translator->getMessage('DATE_STARTTIME'),'', 1, false,false,false,'','','','',15.3);
      $this->_form->addSelect('startday',$this->_array,'',$this->_translator->getMessage('DATE_STARTDAY'),'', 1, false,false,false,'','','','',15.3);
      $this->_form->addSelect('endtime',$this->_array,'',$this->_translator->getMessage('DATE_ENDTIME'),'', 1, false,false,false,'','','','',15.3);
      $this->_form->addSelect('endday',$this->_array,'',$this->_translator->getMessage('DATE_ENDDAY'),'', 1, false,false,false,'','','','',15.3);
      $this->_form->addSelect('location',$this->_array,'',$this->_translator->getMessage('DATE_LOCATION'),'', 1, false,false,false,'','','','',15.3);
      $this->_form->addEmptyline();

      $this->_form->addCheckbox('mode',$this->_translator->getMessage('DATE_IMPORT_PUBLIC'),'',$this->_translator->getMessage('DATE_IMPORT_PUBLIC'),$this->_translator->getMessage('DATE_PUBLIC_NO'),''); //PREFERENCES_SHOW_TITLE_OPTION

      $this->_form->addButtonBar('option',$this->_translator->getMessage('DATES_SELECTION_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
   }


   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
   }
}
?>