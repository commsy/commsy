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
class cs_labels_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing the materials of a news
   */
   var $_label_array = array();

  /**
   * array - containing an array of materials form the session
   */
   var $_session_material_array = array();

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function cs_news_form($environment) {
      $this->cs_rubric_form($environment);
   }


   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      // headline
      $this->_headline = getMessage('LABELS_EDIT_HEADER');
      $this->setHeadline($this->_headline);
      // Get available labels
      $label_manager = $this->_environment->getLabelManager();
      $label_manager->resetLimits();
      $label_manager->setContextLimit($this->_environment->getCurrentContextID());
      $label_manager->setTypeLimit('label');
      $label_manager->select();
      $label_list = $label_manager->get();
      $label_array = array();
      if ($label_list->getCount() > 0) {
         $label_item =  $label_list->getFirst();
         while ($label_item) {
            $temp_array['text'] = $label_item->getName();
            $temp_array['value'] = $label_item->getItemID();
            $label_array[] = $temp_array;
            $label_item =  $label_list->getNext();
         }
      }
      $this->_label_array = $label_array;
   }
   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {
      // news
      $i=0;
      $this->_form->addTextField('new_label','','','','',45,'');
      $this->_form->combine();
      $this->_form->addButton('option',getMessage('LABELS_NEW_BUTTON'));
      $this->_form->addSelect('sel1',$this->_label_array,'','','', 1, false,false,false,'','','','',13);
      $this->_form->combine('horizontal');
      $this->_form->addSelect('sel2',$this->_label_array,'','','', 1, false,false,false,'','','','',13);
      $this->_form->combine('horizontal');
      $this->_form->addButton('option',getMessage('LABELS_COMBINE_BUTTON'));
      foreach ($this->_label_array as $label){
         $i++;
         $this->_form->addTextField('label'.'#'.$label['value'],$label['text'],$i.'.','','',50);
         $this->_form->combine('horizontal');
         $this->_form->addButton('option'.'#'.$label['value'],getMessage('LABELS_CHANGE_BUTTON'));
         $this->_form->combine('horizontal');
         $this->_form->addButton('option'.'#'.$label['value'],getMessage('COMMON_DELETE_BUTTON'));
      }
#      $this->_form->addButtonBar('option',getMessage('LABELS_SAVE_BUTTON'),getMessage('COMMON_BACK_BUTTON'));
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post; // no encode here - encode in form-views
      }
   }
}
?>