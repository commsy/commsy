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

include_once('classes/cs_rubric_form.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_buzzwords_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing the materials of a news
   */
   var $_buzzword_array = array();

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
   function cs_buzzwords_form($environment) {
      $this->cs_rubric_form($environment);
   }


   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      // headline
      $this->_headline = getMessage('BUZZWORDS_EDIT_HEADER');
      $this->setHeadline($this->_headline);

      // Get available buzzwords
      $buzzword_manager = $this->_environment->getLabelManager();
      $buzzword_manager->resetLimits();
      $buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
      $buzzword_manager->setTypeLimit('buzzword');
      $buzzword_manager->select();
      $buzzword_list = $buzzword_manager->get();
      $buzzword_array = array();
      if ($buzzword_list->getCount() > 0) {
         $buzzword_item =  $buzzword_list->getFirst();
         while ($buzzword_item) {
            $temp_array['text'] = $buzzword_item->getName();
            $temp_array['value'] = $buzzword_item->getItemID();
            $buzzword_array[] = $temp_array;
            $buzzword_item =  $buzzword_list->getNext();
         }
      }
      $this->_buzzword_array = $buzzword_array;
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {
      // news
      $i = 0;
      $this->_form->addSubHeadline('headline1',ucfirst($this->_translator->getMessage('COMMON_ADD_BUTTON')),'','',3);
      $this->_form->addTextField('new_buzzword','','','','',46,'');
      $this->_form->combine('horizontal');
      $this->_form->addButton('option',getMessage('BUZZWORDS_NEW_BUTTON'),'','',(strlen($this->_translator->getMessage('BUZZWORDS_NEW_BUTTON'))*7));

      $this->_form->addSubHeadline('headline3',ucfirst($this->_translator->getMessage('BUZZWORDS_COMBINE_BUTTON')),'','',3);
      $this->_form->addSelect('sel1',$this->_buzzword_array,'','','', 1, false,false,false,'','','','',13.2);
      $this->_form->combine('horizontal');
      $this->_form->addSelect('sel2',$this->_buzzword_array,'','','', 1, false,false,false,'','','','',13.2);
      $this->_form->combine('horizontal');
      $this->_form->addButton('option',getMessage('BUZZWORDS_COMBINE_BUTTON'),'','',(strlen($this->_translator->getMessage('BUZZWORDS_COMBINE_BUTTON'))*9));
      $this->_form->addEmptyline();
      $this->_form->addSubHeadline('headline2',ucfirst($this->_translator->getMessage('COMMON_EDIT')),'','',3);
      foreach ($this->_buzzword_array as $buzzword){
         $i++;
         $this->_form->addTextField('buzzword'.'#'.$buzzword['value'],$buzzword['text'],$i.'.','','',32);
         $this->_form->combine('horizontal');
         $this->_form->addButton('option'.'#'.$buzzword['value'],getMessage('BUZZWORDS_CHANGE_BUTTON'),'','',(strlen($this->_translator->getMessage('BUZZWORDS_CHANGE_BUTTON'))*9));
         $this->_form->combine('horizontal');
         $this->_form->addButton('option'.'#'.$buzzword['value'],getMessage('BUZZWORDS_ASSIGN_ENTRIES'),'','',(strlen($this->_translator->getMessage('BUZZWORDS_ASSIGN_ENTRIES'))*7));
         $this->_form->combine('horizontal');
         $this->_form->addButton('option'.'#'.$buzzword['value'],getMessage('COMMON_DELETE_BUTTON'),'','',(strlen($this->_translator->getMessage('COMMON_DELETE_BUTTON'))*9));
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
      }
   }
}
?>