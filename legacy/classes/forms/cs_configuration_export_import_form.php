<?PHP
//
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
class cs_configuration_export_import_form extends cs_rubric_form {
  /**
   * array - containing the rooms to export
   */
   var $_array_rooms = NULL;

   /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function cs_configuration_portal_upload_form($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      $this->_headline = $this->_translator->getMessage('CONFIGURATION_EXPORT_IMPORT');
      $this->setHeadline($this->_headline);
      
      // portal option choice
      $this->_array_rooms[]['text']  = '*'.$this->_translator->getMessage('CONFIGURATION_EXTRA_CHOOSE_NO_ROOM');
      $this->_array_rooms[]['value'] = -1;

      $portal_item = $this->_environment->getCurrentPortalItem();
      $room_list = $portal_item->getRoomList();
      if ($room_list->isNotEmpty()) {
         $this->_array_rooms[]['text']  = '----------------------';
         $this->_array_rooms[]['value'] = 'disabled';
         $room_item = $room_list->getFirst();
         while ( $room_item ) {
            $temp_array = array();
            $temp_array['text']  = $room_item->getTitle().' ('.$room_item->getItemID().')';
            $temp_array['value'] = $room_item->getItemID();
            $this->_array_rooms[] = $temp_array;
            $room_item = $room_list->getNext();
         }
      }
      
      /* $private_room_manager = $this->_environment->getPrivateRoomManager();
      $private_room_manager->select();
      $private_room_list = $private_room_manager->get();
      if ($private_room_list->isNotEmpty()) {
         $this->_array_rooms[]['text']  = '----------------------';
         $this->_array_rooms[]['value'] = 'disabled';
         $private_room_item = $private_room_list->getFirst();
         while ($private_room_item) {
            $user_item = $private_room_item->getOwnerUserItem();
            if ($user_item) {
               $temp_array = array();
               $temp_array['text']  = $this->_translator->getMessage('PRIVATE_ROOM_USER_EXPORT_IMPORT').' '.$user_item->getUserId();
               $temp_array['value'] = $private_room_item->getItemID();
               $this->_array_rooms[] = $temp_array;
            }
            $private_room_item = $private_room_list->getNext();
         }
      } */
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $session = $this->_environment->getSession();
      if($session->issetValue('javascript') and $session->getValue('javascript') == '1') {
         $isJSEnabled = true;
      }
      
      $translator = $this->_environment->getTranslationObject();
      $this->_form->addSelect( 'room',
                               $this->_array_rooms,
                               '',
                               $translator->getMessage('PREFERENCES_EXPORT_ROOM'),
                               '',
                               '',
                               '',
                               '',
                               false,
                               $translator->getMessage('COMMON_CHOOSE_BUTTON'),
                               'option',
                               '',
                               '',
                               '20',
                               true);
      $this->_form->addButton('option',$this->_translator->getMessage('PREFERENCES_EXPORT_IMPORT_EXPORT_BUTTON'),'','',128);
      $this->_form->addFilefield('upload', $this->_translator->getMessage('PREFERENCES_EXPORT_IMPORT_UPLOAD'), $this->_translator->getMessage('PREFERENCES_EXPORT_IMPORT_DESC'), 12, false, $this->_translator->getMessage('PREFERENCES_EXPORT_COMMON_UPLOAD'),'option',false);
      //$this->_form->addButtonBar('option',$translator->getMessage('PREFERENCES_EXPORT_IMPORT_EXPORT_BUTTON'));
   }
   
   public function setRoomSelection($room_id) {
      $this->room_selection = $room_id;
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      if (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
      }else{
         // Portallimit
      }
   }
   
   function _checkValues () {
      if ($_POST['option'] == $this->_translator->getMessage('PREFERENCES_EXPORT_IMPORT_EXPORT_BUTTON')) {
         if ($_POST['room'] == '-1') {
            $this->_error_array[] = $this->_translator->getMessage('PREFERENCES_EXPORT_ERROR_NO_ROOM_SELECTED');
         }
      } else if ($_POST['option'] == $this->_translator->getMessage('PREFERENCES_EXPORT_COMMON_UPLOAD')) {
         if (empty($_FILES['upload']['name'])) {
            $this->_error_array[] = $this->_translator->getMessage('PREFERENCES_EXPORT_ERROR_NO_FILE_SELECTED');
         } else {
            if (!stristr($_FILES['upload']['name'], '.zip')) {
               $this->_error_array[] = $this->_translator->getMessage('PREFERENCES_EXPORT_ERROR_NO_ZIP_FILE_SELECTED');
            }
         }
      }
   }
}
?>