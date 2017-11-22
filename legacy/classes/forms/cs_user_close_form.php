<?PHP
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
include_once('functions/text_functions.php');

/** class for commsy form: user
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_user_close_form extends cs_rubric_form {

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
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      $this->setHeadline($this->_translator->getMessage('USER_CLOSE_FORM'));
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      // user
      $this->_form->addHidden('iid','');
      if ($this->_environment->inProjectRoom()){
         $this->_form->addText('text','<span style="color:red;">'.$this->_translator->getMessage('RUBRIC_WARN_CHANGER').'</span>',$this->_translator->getMessage('USER_CLOSE_FORM_DESCRIPTION'),'');
      }else{
         $this->_form->addText('text','<span style="color:red;">'.$this->_translator->getMessage('RUBRIC_WARN_CHANGER').'</span>',$this->_translator->getMessage('USER_CLOSE_FORM_DESCRIPTION2'),'');
      }
      $this->_form->addButtonBar('option',$this->_translator->getMessage('COMMON_USER_REJECT_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),$this->_translator->getMessage('COMMON_USER_AND_ENTRIES_DELETE_BUTTON'));
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();
      $user = $this->_environment->getCurrentUser();

      $this->_values['iid']= $user->getItemID();
   }
}
?>