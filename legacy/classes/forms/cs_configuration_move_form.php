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
include_once('functions/text_functions.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_move_form extends cs_rubric_form {

   var $_portal_array = array();

   var $_with_linked_checkbox = false;

  /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
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
      $server_item = $this->_environment->getServerItem();
     $current_portal = $this->_environment->getCurrentPortalItem();
     $portal_list = $server_item->getPortalList();
     if ($portal_list->isNotEmpty()) {
       $portal_item = $portal_list->getFirst();
       while ($portal_item) {
         if ($portal_item->getItemID() != $current_portal->getItemID()) {
            $temp_array = array();
            $temp_array['value'] = $portal_item->getItemID();
            $temp_array['text']  = $portal_item->getTitle();
            $this->_portal_array[] = $temp_array;
             unset($temp_array);
         }
          $portal_item = $portal_list->getNext();
       }
     }

     if ( ( isset($this->_item) and $this->_item->isCommunityRoom() )
          or (isset($this->_form_post['type']) and $this->_form_post['type'] == 'community')
        ) {
        $this->_with_linked_checkbox = true;
     }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
     $this->setHeadline($this->_translator->getMessage('CONTEXT_MOVE_ROOM'));
     $this->_form->addHidden('type','');
     $this->_form->addHidden('iid','');
     $this->_form->addHidden('name_hidden','');
     $this->_form->addText('name_title',$this->_translator->getMessage('COMMON_ROOM'),'');
     $this->_form->addRadioGroup('portal_id',$this->_translator->getMessage('COMMON_PORTAL'),$this->_translator->getMessage('CONFIGURATION_MOVE_PORTAL_DESC'),$this->_portal_array,'',true,false);
     if ($this->_with_linked_checkbox) {
       $this->_form->addCheckbox('with_linked_rooms',1,'',$this->_translator->getMessage('CONFIGURATION_MOVE_LINKED_ROOM_TITLE'),$this->_translator->getMessage('CONFIGURATION_MOVE_LINKED_ROOM_VALUE'),'');
     }

      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PORTAL_MOVE_ROOM_REGISTER_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();
      if (isset($this->_item)) {
       $this->_values['iid'] = $this->_item->getItemID();
       $this->_values['name_hidden'] = $this->_item->getTitle();
       $this->_values['name_title'] = $this->_item->getTitle();
       if ($this->_item->isProjectRoom()) {
         $this->_values['type'] = 'project';
       } elseif ($this->_item->isCommunityRoom()) {
         $this->_values['type'] = 'community';
       }
      } elseif (isset($this->_form_post)) {
         $this->_values = $this->_form_post; // no encode here - encode in form-views
       $this->_values['name_title'] = $this->_values['name_hidden'];
      }
   }
}
?>