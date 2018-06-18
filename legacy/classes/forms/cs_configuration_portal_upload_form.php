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
class cs_configuration_portal_upload_form extends cs_rubric_form {
   private $room_array = array();
   private $room_limits  = array();
   private $room_selection = 0;
   
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
    */
   function _initForm () {
      $this->_headline = $this->_translator->getMessage('CONFIGURATION_PORTAL_UPLOAD');
      $this->setHeadline($this->_headline);
      
//       $portal = $this->_environment->getCurrentPortalItem();
//       $room_list = $portal->getRoomList();
//       $room_list->reset();
//       $item = $room_list->getFirst();
//       while($item) {
//          // add room name to name array
//          $limit_text = '';
//          if($item->getMaxUploadSizeExtraOnly() != '') {
//             $limit_text = '(' . $item->getMaxUploadSizeExtraOnly() . ')';
//          }
//          $this->room_array[] = array(   'text'   =>   $item->getTitle() . $limit_text,
//                                         'value'	 =>   $item->getItemId());
         
//          // save room limits
//          $this->room_limits[] = array(   'id'      =>   $item->getItemId(),
//                                          'limit'   =>   $item->getMaxUploadSizeExtraOnly());
         
//          $item = $room_list->getNext();
//       }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $session = $this->_environment->getSession();
      if($session->issetValue('javascript') and $session->getValue('javascript') == '1') {
         $isJSEnabled = true;
      }
      
      // form fields
      $this->_form->addCheckbox(   'use_portal_value',
                                   '1',
                                   false,
                                   $this->_translator->getMessage('CONFIGURATION_PORTAL_UPLOAD_PVALUE'),
                                   $this->_translator->getMessage('CONFIGURATION_PORTAL_UPLOAD_PVALUE_DESC'));
      $this->_form->addTextfield(	'portal_value',
                                    '',
                               		$this->_translator->getMessage('CONFIGURATION_PORTAL_UPLOAD_PVALUE_THRESHOLD'),
                                    '',
                                    255,
                                    20,
                                    false,
                                    '',
                                    '',
                                    '',
                                    '',
                                    '',
                                    '',
                                    false,
                                    $this->_translator->getMessage('CONFIGURATION_PORTAL_UPLOAD_INPUT_DESC'));
      $this->_form->addEmptyLine();                   
      $this->_form->addText(	'room_limit_desc',
      							$this->_translator->getMessage('CONFIGURATION_PORTAL_UPLOAD_RVALUE_THRESHOLD'),
      							$this->_translator->getMessage('CONFIGURATION_PORTAL_UPLOAD_RVALUE_DESC'),
      							'');
      
      if(!empty($_POST['room_search'])) {
      	$room_search = $_POST['room_search'];
      } else {
      	$room_search = '';
      }
      
      $this->_form->addTextfield(	'room_search',
      		$room_search,
      		'Suche nach Raum',
      		'',
      		255,
      		20,
      		false,
      		'Suchen',
      		'submit',
      		'',
      		'',
      		'',
      		'',
      		false,
      		'');
      
      if(!empty($_POST['room_search'])) {
      	// search for room
      	$project_manager = $this->_environment->getProjectManager();
      	$room_search = $_POST['room_search'];
      	$items = $project_manager->getRoomsByTitle($room_search, $this->_environment->getCurrentPortalID());
      	#pr($items);
      	
      	$item = $items->getFirst();
      	while($item) {
      		$limit_text = '';
      		if($item->getMaxUploadSizeExtraOnly() != '') {
      			$limit_text = ' (' . $item->getMaxUploadSizeExtraOnly() . ')';
      		}
      		$this->room_array[] = array(   'text'   =>   $item->getTitle() . $limit_text,
      			                           'value'	=>   $item->getItemId());
      		
      		$item = $items->getNext();
      	}
      	$room_array = $this->room_array;
      	
      	$this->_form->addSelect(   'configuration_data_upload_room_select',
      			$room_array,
      			'',
      			$this->_translator->getMessage('CONFIGURATION_PORTAL_UPLOAD_ROOM_SELECT'),
      			'');
      	
      	$this->_form->addTextfield(   'configuration_data_upload_room_value',
      			'',
      			'',
      			'',
      			255,
      			20,
      			false,
      			'',
      			'',
      			'',
      			'',
      			'',
      			'',
      			false,
      			$this->_translator->getMessage('CONFIGURATION_PORTAL_UPLOAD_INPUT_DESC'));
      	
      }
      
      
      
            // buttons
            $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');
      
//       $room_array = $this->room_array;
//       $selected = array();
//       if(!$isJSEnabled) {
//          $room_array = array_merge(array(array('text' => "---------------", 'value' => -1)), $room_array);
         
//          if($this->room_selection != 0) {
// 	         foreach($this->room_array as $room) {
// 	            if($room['value'] == $this->room_selection) {
// 	               $selected = array($room['value']);
// 	               break;
// 	            }
// 	         }
//          }
//       }
//       $this->_form->addSelect(   'configuration_data_upload_room_select',
//                                  $room_array,
//                                  $selected,
//                                  $this->_translator->getMessage('CONFIGURATION_PORTAL_UPLOAD_ROOM_SELECT'),
//                                  '');
//       if(!$isJSEnabled || $this->room_selection != 0) {
//          $this->_form->combine('horizontal');
//          $this->_form->addButton('configuration_data_upload_room_select_confirm', $this->_translator->getMessage('COMMON_CHOOSE_BUTTON'));
//       }
//       if($isJSEnabled || $this->room_selection != 0) {
//          $value = '';
//          if($isJSEnabled) {
//             $value = $this->room_limits[0]['limit'];
//          } else {
//             foreach($this->room_limits as $limit) {
//                if($limit['id'] == $this->room_selection) {
//                   $value = $limit['limit'];
//                   break;
//                }
//             }
//          }
         
//          $this->_form->combine('vertical');
//          $this->_form->addTextfield(   'configuration_data_upload_room_value',
// 	                                   $value,
// 	                                   '',
// 	                                   '',
// 	                                   255,
// 	                                   20,
// 	                                   false,
// 	                                   '',
// 	                                   '',
// 	                                   '',
// 	                                   '',
// 	                                   '',
// 	                                   '',
// 	                                   false,
// 	                                   $this->_translator->getMessage('CONFIGURATION_PORTAL_UPLOAD_INPUT_DESC'));
//       }
      
//       // create hidden fields for room limits
// //       if($isJSEnabled) {
// // 	      foreach($this->room_limits as $room) {
// // 	         if ( !empty($room['limit']) ) {
// // 	            $this->_form->addHidden('room_limit_' . $room['id'], $room['limit']);
// // 	         }
// // 	      }
// //       }
      
//       // buttons
//       $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');
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
         $portal = $this->_environment->getCurrentPortalItem();
         $portal_maxuploadsize = $portal->getMaxUploadSizeExtraOnly();
         if($portal_maxuploadsize != '') {
            $this->_values['use_portal_value'] = '1';
            $this->_values['portal_value'] = $portal_maxuploadsize;
         } else {
            $this->_values['use_portal_value'] = '';
            $this->_values['portal_value'] = '';
         }
      }
   }

}
?>