<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

/** class for commsy form: topic
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_topic_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing the materials of a topic
   */
   var $_material_array = array();

  /**
   * array - containing an array of materials form the session
   */
   var $_session_material_array = array();

  /**
   * array - containing an array of existing institution in the context
   */
   var $_institution_array = array();

  /**
   * boolean - true  -> institutions will be displayed
   *           false -> institutions will NOT be displayed
   */
   var $_institution_with = true;

   /**
   * array - containing the values for the edit status for the item (everybody or creator)
   */
   var $_public_array = array();

   var $_path_activated = false;

   var $_link_item_array = array();

   var $_link_item_place_array = array();

   var $_link_item_check_array = array();

   var $_path_button_disable = true;

   private $_path_reset_items = false;

   private $_path_new_id_array = array();

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

   /** set materials from session
    * set an array with the materials from the session
    *
    * @param array array of materials out of session
    *
    * @author CommSy Development Group
    */
   function setSessionMaterialArray ($value) {
      $this->_session_material_array = (array)$value;
   }

   function activatePath () {
     $this->_path_activated = true;
   }

   function deactivatePath () {
     $this->_path_activated = false;
   }

   function setPathItems($items){
      $this->_link_item_array = array();
      $item_manager = $this->_environment->getItemManager();
      foreach($items as $item){
         $temp_item = $item_manager->getItem($item);
         $temp_manager = $this->_environment->getManager($temp_item->getItemType());
         $temp_item = $temp_manager->getItem($item);
         $temp_array = array();
         $temp_item_type = mb_strtoupper($temp_item->getItemType(), 'UTF-8');
         switch ( $temp_item_type )
         {
            case 'ANNOUNCEMENT':
               $temp_array['text'] = $this->_translator->getMessage('COMMON_ANNOUNCEMENT');
               break;
            case 'DATES':
               $temp_array['text'] = $this->_translator->getMessage('COMMON_DATES');
               break;
            case 'DATE':
               $temp_array['text'] = $this->_translator->getMessage('COMMON_DATES');
               break;
            case 'INSTITUTION':
               $temp_array['text'] = $this->_translator->getMessage('COMMON_INSTITUTION');
               break;
            case 'DISCUSSION':
               $temp_array['text'] = $this->_translator->getMessage('COMMON_DISCUSSION');
               break;
            case 'USER':
               $temp_array['text'] = $this->_translator->getMessage('COMMON_USER');
               break;
            case 'GROUP':
               $temp_array['text'] = $this->_translator->getMessage('COMMON_GROUP');
               break;
            case 'MATERIAL':
               $temp_array['text'] = $this->_translator->getMessage('COMMON_MATERIAL');
               break;
            case 'PROJECT':
               $temp_array['text'] = $this->_translator->getMessage('COMMON_PROJECT');
               break;
            case 'TODO':
               $temp_array['text'] = $this->_translator->getMessage('COMMON_TODO');
               break;
            case 'TOPIC':
               $temp_array['text'] = $this->_translator->getMessage('COMMON_TOPIC');
               break;
            default:
               $temp_array['text'] = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_topic_form('.__LINE__.') ');
               break;
         }
         $temp_array['text'] .= ': '.$temp_item->getTitle();
         $temp_array['value'] = $item;
         $this->_link_item_array[] = $temp_array;
      }
      if(!empty($items)){
         $this->_path_button_disable = false;
      }
   }

   public function resetPathItems () {
      $this->_path_reset_items = true;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example topics
    *
    * @author CommSy Development Group
    */
   function _initForm () {

      // public
      if ( isset($this->_item) ) {
         $creator_item = $this->_item->getCreatorItem();
         $fullname = $creator_item->getFullname();
      } elseif ( !empty($this->_form_post['iid'])
                 and mb_strtolower($this->_form_post['iid'], 'UTF-8') != 'new'
               ) {
         $manager = $this->_environment->getManager(CS_TOPIC_TYPE);
         $item = $manager->getItem($this->_form_post['iid']);
         $creator_item = $item->getCreatorItem();
         $fullname = $creator_item->getFullname();
      } else {
         $current_user = $this->_environment->getCurrentUser();
         $fullname = $current_user->getFullname();
      }
      $public_array = array();
      $temp_array['text']  = $this->_translator->getMessage('RUBRIC_PUBLIC_YES');
      $temp_array['value'] = 1;
      $public_array[] = $temp_array;
      $temp_array['text']  = $this->_translator->getMessage('RUBRIC_PUBLIC_NO', $fullname);
      $temp_array['value'] = 0;
      $public_array[] = $temp_array;
      $this->_public_array = $public_array;

      if (!empty($this->_item)) {
         $this->_headline = $this->_translator->getMessage('TOPIC_EDIT');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = $this->_translator->getMessage('TOPIC_EDIT');
         } else {
            $this->_headline = $this->_translator->getMessage('TOPIC_ENTER_NEW');
            $new='';
            $context_item = $this->_environment->getCurrentContextItem();
            $rubric_array = $context_item->_getRubricArray(CS_TOPIC_TYPE);
            if (isset($rubric_array[mb_strtoupper($this->_environment->getSelectedLanguage(), 'UTF-8')]['GENUS']) ){
              $genus = $rubric_array[mb_strtoupper($this->_environment->getSelectedLanguage(), 'UTF-8')]['GENUS'];
            } else {
               $genus = $rubric_array['EN']['GENUS'];
            }
            if ($genus =='M'){
               $new = $this->_translator->getMessage('COMMON_NEW_M_BIG').' ';
            }
            elseif ($genus =='F'){
               $new = $this->_translator->getMessage('COMMON_NEW_F_BIG').' ';
            }
            else {
               $new = $this->_translator->getMessage('COMMON_NEW_N_BIG').' ';
            }

            $this->_headline = $new.$this->_headline;
         }
      } else {
         $this->_headline = $this->_translator->getMessage('TOPIC_ENTER_NEW');
         $new='';
         $context_item = $this->_environment->getCurrentContextItem();
         $rubric_array = $context_item->_getRubricArray(CS_TOPIC_TYPE);
         if (isset($rubric_array[mb_strtoupper($this->_environment->getSelectedLanguage(), 'UTF-8')]['GENUS']) ){
           $genus = $rubric_array[mb_strtoupper($this->_environment->getSelectedLanguage(), 'UTF-8')]['GENUS'];
         } else {
            $genus = $rubric_array['EN']['GENUS'];
         }
         if ($genus =='M'){
            $new = $this->_translator->getMessage('COMMON_NEW_M_BIG').' ';
         }
         elseif ($genus =='F'){
            $new =  $this->_translator->getMessage('COMMON_NEW_F_BIG').' ';
         }
         else {
            $new = $this->_translator->getMessage('COMMON_NEW_N_BIG').' ';
         }
         $this->_headline = $new.$this->_headline;
      }
      $this->setHeadline($this->_headline);

      // files
      $file_array = array();
      if (!empty($this->_session_file_array)) {
         foreach ( $this->_session_file_array as $file ) {
            $temp_array['text'] = $file['name'];
            $temp_array['value'] = $file['file_id'];
            $file_array[] = $temp_array;
         }
      } elseif (isset($this->_item)) {
         $file_list = $this->_item->getFileList();
         if ($file_list->getCount() > 0) {
            $file_item = $file_list->getFirst();
            while ($file_item) {
               $temp_array['text'] = $file_item->getDisplayname();
               $temp_array['value'] = $file_item->getFileID();
               $file_array[] = $temp_array;
               $file_item = $file_list->getNext();
            }
         }
      }
      $this->_file_array = $file_array;

      // PATH
      if($this->_environment->getCurrentContextItem()->withPath()){
         if ( isset($this->_item)
              or isset($item)
              or $this->_path_reset_items
            ) {
            $link_manager = $this->_environment->getLinkItemManager();
            $link_manager->setContextLimit($this->_environment->getCurrentContextID());
            if ( isset($this->_item) ) {
               $link_manager->setLinkedItemLimit($this->_item);
               $topic_item = $this->_item;
            } elseif ( isset($item) ) {
               $link_manager->setLinkedItemLimit($item);
               $topic_item = $item;
            }  else {
               $topic_manager = $this->_environment->getManager(CS_TOPIC_TYPE);
               $topic_item = $topic_manager->getNewItem();
            }
            $link_manager->sortbySortingPlace();
            $link_manager->select();
            $link_item_list = $link_manager->get();

            if ( !$link_item_list->isEmpty() ) {
               $counter = 1;
               $link_item = $link_item_list->getFirst();
               while ($link_item) {
                  $this->_link_item_place_array[$counter] = $link_item->getItemID();
                  if ( $link_item->getSortingPlace() ) {
                     $this->_link_item_check_array[] = $link_item->getItemID();
                  }
                  $linked_item = $link_item->getLinkedItem($topic_item);
                  $temp_array = array();
                  $item_type = $linked_item->getItemType();
                  if ($item_type == 'date') {
                     $item_type .= 's';
                  }

                  $temp_item_type = mb_strtoupper($item_type, 'UTF-8');
                  switch ( $temp_item_type )
                  {
                     case 'ANNOUNCEMENT':
                        $temp_array['text'] = $this->_translator->getMessage('COMMON_ANNOUNCEMENT');
                        break;
                     case 'DATES':
                        $temp_array['text'] = $this->_translator->getMessage('COMMON_DATES');
                        break;
                     case 'INSTITUTION':
                        $temp_array['text'] = $this->_translator->getMessage('COMMON_INSTITUTION');
                        break;
                     case 'DISCUSSION':
                        $temp_array['text'] = $this->_translator->getMessage('COMMON_DISCUSSION');
                        break;
                     case 'USER':
                        $temp_array['text'] = $this->_translator->getMessage('COMMON_USER');
                        break;
                     case 'GROUP':
                        $temp_array['text'] = $this->_translator->getMessage('COMMON_GROUP');
                        break;
                     case 'MATERIAL':
                        $temp_array['text'] = $this->_translator->getMessage('COMMON_MATERIAL');
                        break;
                     case 'PROJECT':
                        $temp_array['text'] = $this->_translator->getMessage('COMMON_PROJECT');
                        break;
                     case 'TODO':
                        $temp_array['text'] = $this->_translator->getMessage('COMMON_TODO');
                        break;
                     case 'TOPIC':
                        $temp_array['text'] = $this->_translator->getMessage('COMMON_TOPIC');
                        break;
                     default:
                        $temp_array['text'] = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_topc_form('.__LINE__.') ');
                        break;
                  }
                  $temp_array['text'] .= ': '.$linked_item->getTitle();

                  $link_item_sort_array[] = $link_item->getItemID();
                  $temp_array['value'] = $link_item->getItemID();
                  $this->_link_item_array[] = $temp_array;
                  $link_item = $link_item_list->getNext();
                  $counter++;
               }
            }
            if ( isset($this->_form_post['place_array'])
                 and !empty($this->_form_post['place_array']) ) {
               $temp_array = array();
               $place_array_inv = array_flip($this->_form_post['place_array']);
               foreach ($this->_link_item_array as $item) {
                  $temp_array[$place_array_inv[$item['value']]-1] = $item;
               }
               ksort($temp_array);
               $this->_link_item_array = $temp_array;
            }
            if ( $this->_path_reset_items ) {
               $session = $this->_environment->getSessionItem();
               if ( $session->issetValue('cid'.$this->_environment->getCurrentContextID().'_linked_items_index_selected_ids')) {
                  $entry_array = $session->getValue('cid'.$this->_environment->getCurrentContextID().'_linked_items_index_selected_ids');
                  $entry_link_array = array();
                  $link_manager = $this->_environment->getLinkItemManager();
                  foreach ( $entry_array as $entry_id ) {
                     $link_item = $link_manager->getItemByFirstAndSecondID($topic_item->getItemID(),$entry_id);
                     if ( !empty($link_item) ) {
                        $entry_link_array[$entry_id] = $link_item->getItemID();
                     }
                     unset($link_item);
                  }
                  unset($link_manager);
                  $temp_link_item_array = array();
                  $temp_link_value_array = array();
                  foreach ( $this->_link_item_array as $link_item ) {
                     if ( in_array($link_item['value'],$entry_link_array) ) {
                        $temp_link_item_array[] = $link_item;
                        $temp_link_value_array[] = $link_item['value'];
                     }
                  }
                  foreach ( $entry_array as $value ) {
                     if ( empty($entry_link_array[$value]) ) {
                        $item_manager = $this->_environment->getItemManager();
                        $item_type = $item_manager->getItemType($value);
                        $manager = $this->_environment->getManager(type2Module($item_type));
                        $item = $manager->getItem($value);
                        $temp_item = array();
                        $temp_item['text'] = $item->getTitle();
                        $temp_item['value'] = $item->getItemID();
                        $this->_path_new_id_array[] = $item->getItemID();
                        $temp_link_item_array[] = $temp_item;
                        unset($temp_item);
                        unset($item);
                        unset($manager);
                        unset($item_manager);
                     }
                  }
               }
               unset($session);
               $this->_link_item_array = $temp_link_item_array;
            }
            if ( isset($this->_link_item_array) and !empty($this->_link_item_array) ) {
               $this->_path_button_disable = false;
            }
         }
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      // topic
      $this->_form->addHidden('iid','');
      $this->_form->addHidden('path_new_id_array',$this->_path_new_id_array);
      $this->_form->addTitleField('name','',$this->_translator->getMessage('COMMON_NAME'),$this->_translator->getMessage('COMMON_NAME_DESC'),200,45,true);
      $this->_form->addTextArea('description','',$this->_translator->getMessage('COMMON_CONTENT'),'',60);

      // files
      $this->_form->addAnchor('fileupload');
      $val = $this->_environment->getCurrentContextItem()->getMaxUploadSizeInBytes();
      $meg_val = round($val/1048576);
      if ( !empty($this->_file_array) ) {
         $this->_form->addCheckBoxGroup('filelist',$this->_file_array,'',$this->_translator->getMessage('MATERIAL_FILES'),$this->_translator->getMessage('MATERIAL_FILES_DESC', $meg_val),false,false);
         $this->_form->combine('vertical');
      }
      $this->_form->addHidden('MAX_FILE_SIZE', $val);
      $this->_form->addFilefield('upload', $this->_translator->getMessage('MATERIAL_FILES'), $this->_translator->getMessage('MATERIAL_UPLOAD_DESC',$meg_val), 12, false, $this->_translator->getMessage('MATERIAL_UPLOADFILE_BUTTON'),'option',$this->_with_multi_upload);
      $this->_form->combine('vertical');
      //global $c_new_upload;
      $use_new_upload = false;
      $session = $this->_environment->getSession();
      if($session->issetValue('javascript') and $session->issetValue('flash')){
         if(($session->getValue('javascript') == '1') and ($session->getValue('flash') == '1')){
            $use_new_upload = true;
         }
      }
      if ($this->_with_multi_upload or $use_new_upload) {
         // do nothing
      } else {
         #$px = '245';
         $px = '331';
         $browser = $this->_environment->getCurrentBrowser();
         if ($browser == 'MSIE') {
            $px = '351';
         } elseif ($browser == 'OPERA') {
            $px = '321';
         } elseif ($browser == 'KONQUEROR') {
            $px = '361';
         } elseif ($browser == 'SAFARI') {
            $px = '380';
         } elseif ($browser == 'FIREFOX') {
            $operation_system = $this->_environment->getCurrentOperatingSystem();
            if (mb_strtoupper($operation_system, 'UTF-8') == 'LINUX') {
               $px = '360';
            } elseif (mb_strtoupper($operation_system, 'UTF-8') == 'MAC OS') {
               $px = '352';
            }
         } elseif ($browser == 'MOZILLA') {
            $operation_system = $this->_environment->getCurrentOperatingSystem();
            if (mb_strtoupper($operation_system, 'UTF-8') == 'MAC OS') {
               $px = '336'; // camino
            }
         }
         $this->_form->addButton('option',$this->_translator->getMessage('MATERIAL_BUTTON_MULTI_UPLOAD_YES'),'','',$px.'px');
      }
      $this->_form->combine('vertical');
      $this->_form->addText('max_size',$val,$this->_translator->getMessage('MATERIAL_MAX_FILE_SIZE',$meg_val));

      $session = $this->_environment->getSession();
      $new_upload = false;
      if($session->issetValue('javascript') and $session->issetValue('flash')) {
      	if(($session->getValue('javascript') == '1') and ($session->getValue('flash') == '1')) {
      	   $new_upload = true;
      	}
      }
      if(!$new_upload) $this->_form->addText('old_upload', '', $this->_translator->getMessage('COMMON_UPLOAD_OLD'));

      $current_context = $this->_environment->getCurrentContextItem();
      if ($current_context->withPath()){
         // PATH - BEGIN
         $this->_form->addEmptyline();
         if ( !$this->_path_activated ) {
            $this->_form->addHidden('path_active',-1);
            $this->_form->addButton('option',$this->_translator->getMessage('TOPIC_ACTIVATE_PATH'),'','','',$this->_path_button_disable);
            $this->_form->combine('vertical');
            $this->_form->addText('activate_path','',$this->_translator->getMessage('TOPIC_ACTIVATE_PATH_DESCRIPTION'));
         } else {
            $this->_form->addHidden('path_active',1);
            $this->_form->addHidden('place_array',$this->_link_item_place_array);
            $this->_form->addButton('option',$this->_translator->getMessage('TOPIC_DEACTIVATE_PATH'),'','','',$this->_path_button_disable);
            $this->_form->combine('vertical');
            $this->_form->addText('activate_path','',$this->_translator->getMessage('TOPIC_ACTIVATE_PATH_SELECT_DESCRIPTION'));
            $this->_form->addCheckboxGroup('sorting',$this->_link_item_array,$this->_link_item_check_array,$this->_translator->getMessage('TOPIC_PATH'),'','','','','','','',50,true,false,true);
         }
         // PATH - END
      }

      if ( !$this->_environment->inPrivateRoom() ){
         // public radio-buttons
         if ($current_context->withActivatingContent()){
            $this->_form->addCheckbox('private_editing',1,'',$this->_translator->getMessage('COMMON_RIGHTS'),$this->_public_array[1]['text'],$this->_translator->getMessage('COMMON_RIGHTS_DESCRIPTION'),false,false,'','',true,false);
            $this->_form->combine();
            $this->_form->addCheckbox('hide',1,'',$this->_translator->getMessage('COMMON_HIDE'),$this->_translator->getMessage('COMMON_HIDE'),'');
            $this->_form->combine('horizontal');
            $this->_form->addDateTimeField('start_date_time','','dayStart','timeStart',9,4,$this->_translator->getMessage('DATES_HIDING_DAY'),'('.$this->_translator->getMessage('DATES_HIDING_DAY'),$this->_translator->getMessage('DATES_HIDING_TIME'),$this->_translator->getMessage('DATES_TIME_DAY_START_DESC'),FALSE,FALSE,100,100,true,'left','',FALSE);
            $this->_form->combine('horizontal');
            $this->_form->addText('hide_end2','',')');
         }else{
             // public radio-buttons
             if ( !isset($this->_item) ) {
                $this->_form->addRadioGroup('public',$this->_translator->getMessage('RUBRIC_PUBLIC'),$this->_translator->getMessage('RUBRIC_PUBLIC_DESC'),$this->_public_array);
             } else {
                $current_user = $this->_environment->getCurrentUser();
                $creator = $this->_item->getCreatorItem();
                if ($current_user->getItemID() == $creator->getItemID() or $current_user->isModerator()) {
                   $this->_form->addRadioGroup('public',$this->_translator->getMessage('RUBRIC_PUBLIC'),$this->_translator->getMessage('RUBRIC_PUBLIC_DESC'),$this->_public_array);
                } else {
                   $this->_form->addHidden('public','');
                }
             }
         }
      } else {
         $this->_form->addHidden('public','');
      }

      // buttons
      $id = 0;
      if (isset($this->_item)) {
         $id = $this->_item->getItemID();
      } elseif (isset($this->_form_post)) {
         if (isset($this->_form_post['iid'])) {
            $id = $this->_form_post['iid'];
         }
      }
      // rubric connections
      $this->_setFormElementsForConnectedRubrics();

      if ( $id == 0 )  {
         $this->_form->addButtonBar('option',$this->_translator->getMessage('TOPIC_SAVE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','','','','','','','');
      } else {
         $this->_form->addButtonBar('option',$this->_translator->getMessage('TOPIC_CHANGE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','','','','','','',' onclick="saveData()"');
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $current_context = $this->_environment->getCurrentContextItem();
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
         if ( !isset($this->_values['public']) ) {
            $this->_values['public'] = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'1':'0'; //In projectrooms everybody can edit the item by default, else default is creator only
         }
      } elseif (isset($this->_item)) {
         // file
         $file_array = array();
         $file_list = $this->_item->getFileList();
         if ($file_list->getCount() > 0) {
            $file_item = $file_list->getFirst();
            while ($file_item) {
               $file_array[] = $file_item->getFileID();
               $file_item = $file_list->getNext();
            }
         }
         if (isset($this->_form_post['filelist'])) {
            $this->_values['filelist'] = $this->_form_post['filelist'];
         } else {
            $this->_values['filelist'] = $file_array;
         }
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['name'] = $this->_item->getName();
         $this->_values['description'] = $this->_item->getDescription();
         if ($current_context->withActivatingContent()){
            if ($this->_item->isPrivateEditing()){
               $this->_values['private_editing'] = 1;
            }else{
               $this->_values['private_editing'] = $this->_item->isPrivateEditing();
            }
         }else{
            $this->_values['public'] = $this->_item->isPublic();
         }
         $this->_values['hide'] = $this->_item->isNotActivated()?'1':'0';
         if ($this->_item->isNotActivated()){
            $activating_date = $this->_item->getActivatingDate();
            if (!strstr($activating_date,'9999-00-00')){
               $array = array();
               $array['dayStart'] = getDateInLang($activating_date);
               $array['timeStart'] = getTimeInLang($activating_date);
               $this->_values['start_date_time'] = $array;
            }
         }
         $this->_setValuesForRubricConnections();
      } else {
         $this->_values['public'] = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'1':'0'; //In projectrooms everybody can edit the item by default, else default is creator only
      }
   }
}
?>