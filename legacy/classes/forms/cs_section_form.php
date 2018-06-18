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

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_section_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * id - containing the material_id
   */
   var $_material_id = NULL;

  /** $this->_setValuesForRubricConnections();
   * array - containing the materials of a section
   */
   var $_material_array = array();

  /**
   * array - containing an array of materials form the session
   */
   var $_session_material_array = array();

  /**
   * object list - containing the sections
   */
   var $_section_list = NULL;

   var $_section_array = array();

   var $_other_sections = NULL;

  /** constructor: cs_section_form
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   function setMaterialID($iid){
      $this->_material_id = $iid;
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

   /** set materials from session
    * set an array with the materials from the session
    *
    * @param array array of materials out  $this->_setValuesForRubricConnections();of session
    *
    * @author CommSy Development Group
    */
   function unsetSessionMaterialArray () {
      $this->_session_material_array = array();
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {

      // headline
      if (!empty($this->_item)) {
         $this->_headline = $this->_translator->getMessage('SECTION_EDIT');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = $this->_translator->getMessage('SECTION_EDIT');
         } else {
            $this->_headline = $this->_translator->getMessage('SECTION_ENTER_NEW');
         }
      } else {
         $this->_headline = $this->_translator->getMessage('SECTION_ENTER_NEW');
      }

      //sections
      $material_manager =  $this->_environment->getMaterialManager();
      $material_item = $material_manager->getItem($this->_material_id);
      if ( isset($material_item) ) {
         $this->_section_list = $material_item->getSectionList();
      }
      $section_array = array();
      $other_sections='';
      if (!empty($this->_section_list)){
         $section = $this->_section_list->getFirst();
         $i=0;
         while($section){
            $i++;
            $tmpArray['text'] = (string)($i);
            $section_array[] =  $tmpArray;
            $other_sections.= $i . '. ';
            $other_sections.= $section->getTitle();
            $other_sections.= '<BR>';
            $section = $this->_section_list->getNext();
         }
         if (empty($this->_item)){
            $i++;
            $tmpArray['text'] = (string)($i);
            $section_array[] =  $tmpArray;
         }
         if ( empty($this->_item)
              and empty($this->_form_post['iid'])
            ) {
            $translator = $this->_environment->getTranslationObject();
            $other_sections.= $i . '. ';
            $other_sections.= $translator->getMessage('MATERIAL_NEW_SECTION_INSERT');
            $other_sections.= '<BR>';
         }
      }
      $this->_section_array = $section_array;
      $this->_other_sections = $other_sections;

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
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      // news
      $this->_form->addHidden('iid','');
      $this->_form->addHidden('material_modification_date','');
      $this->_form->addTitleField('title','',$this->_translator->getMessage('COMMON_TITLE'),$this->_translator->getMessage('COMMON_TITLE_DESC'),200,47,true);
      $this->_form->addTextArea('description','',$this->_translator->getMessage('COMMON_CONTENT'),'',60,20);
      $this->_form->addSelect('number',$this->_section_array,(string)count($this->_section_array),$this->_translator->getMessage('SECTION_OTHER_SECTIONS'),'','',false,false,false,'','',$this->_other_sections);

      // rubric connections
      $this->_setFormElementsForConnectedRubrics();

      $this->_form->addEmptyline();

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
      if ($this->_with_multi_upload) {
         // do nothing
      } else {
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

      // buttons
      $id = 0;
      if (isset($this->_item)) {
         $id = $this->_item->getItemID();
      } elseif (isset($this->_form_post)) {
         if (isset($this->_form_post['iid'])) {
            $id = $this->_form_post['iid'];
         }
      }
      if ( $id == 0 )  {
         $this->_form->addButtonBar('option',$this->_translator->getMessage('SECTION_SAVE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','',$this->_translator->getMessage('MATERIAL_VERSION_BUTTON'));
      } else {
         $this->_form->addButtonBar('option',$this->_translator->getMessage('SECTION_CHANGE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','',$this->_translator->getMessage('MATERIAL_VERSION_BUTTON'));
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
         $this->_values = $this->_form_post; // no encode here - encode in form-views
      } elseif (isset($this->_item)) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['vid'] = $this->_item->getVersionID();
         $this->_values['oldnumber'] = $this->_item->getNumber();
         $this->_values['number'] = $this->_item->getNumber();
         $this->_values['title'] = $this->_item->getTitle();
         $this->_values['description'] = $this->_item->getDescription();
         $this->_setValuesForRubricConnections();

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
      }

     if ( empty($this->_form_post['material_modification_date']) ) {
         // Material Modification date
         $material_manager =  $this->_environment->getMaterialManager();
         $material_item = $material_manager->getItem($this->_material_id);
         if ( isset($material_item) ) {
            $this->_values['material_modification_date'] = $material_item->getModificationDate();
         }
     }
   }

   /** specific check the values of the form
    * this methods check the entered values
    *
    * @author CommSy Development Group
    */
   function _checkValues () {
   }
}
?>
