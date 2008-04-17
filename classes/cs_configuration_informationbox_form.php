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

class cs_configuration_informationbox_form extends cs_rubric_form {


   var $_show_information_box_array = array();

   function cs_configuration_informationbox_form ($environment) {
      $this->cs_rubric_form($environment);
   }


   function _initForm () {
      $current_context= $this->_environment->getCurrentContextItem();
      $_show_information_box_array = array();
      $temp_array['text']  = getMessage('COMMON_SHOW_INFORMATION_BOX_YES');
      $temp_array['value'] = 1;
      $_show_information_box_array[] = $temp_array;
      $temp_array['text']  = getMessage('COMMON_SHOW_INFORMATION_BOX_NO');
      $temp_array['value'] = 0;
      $_show_information_box_array[] = $temp_array;
      $this->_show_information_box_array = $_show_information_box_array;
   }


   function _createForm () {
      $current_context= $this->_environment->getCurrentContextItem();
      $this->_form->addTextField('item_id','',getMessage('COMMON_ATTACHED_ANNOUNCEMENT_ID'),'',200,35,true);
      $this->_form->combine('vertical');
      $this->_form->addText('max_size','',getMessage('COMMON_INFORMATION_BOX_ID_ENTRY'));
      $this->_form->addRadioGroup('show_information_box',getMessage('COMMON_SHOW_INFORMATION_BOX'),'',$this->_show_information_box_array);

      // buttons
      $this->_form->addButtonBar('option',getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   function _prepareValues () {
      $current_context= $this->_environment->getCurrentContextItem();
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
         if ( !isset($this->_values['show_information_box']) ) {
            $this->_values['show_information_box'] = '0';
         }
      } else {
         $this->_values['item_id'] = $current_context->getInformationBoxEntryID();
         if ( $current_context->withInformationBox() ) {
            $this->_values['show_information_box'] = '1';
         } else {
            $this->_values['show_information_box'] = '0';
         }
      }
   }

    /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
     $id = $this->_form_post['item_id'];
     $current_context = $this->_environment->getCurrentContextItem();
     $manager = $this->_environment->getItemManager();
     $item = $manager->getItem($id);
     $is_entry = false;
     if ( $item ) {
          switch ($item->getItemType()) {
            case 'announcement':
               $is_entry = true;
               break;
            case 'date':
               $is_entry = true;
               break;
            case 'material':
               $is_entry = true;
               break;
            case 'discussion':
               $is_entry = true;
               break;
            case 'todo':
               $is_entry = true;
               break;
            default:
               $is_entry = false;
               break;
          }
     }
     if(!$is_entry or $item->isDeleted()){
        $this->_form->setFailure('item_id','mandatory');
        $this->_error_array[] = getMessage('COMMON_ERROR_INFORMATION_BOX_ID_ENTRY',getMessage('COMMON_ATTACHED_ANNOUNCEMENT_ID'));
     }
   }


}
?>