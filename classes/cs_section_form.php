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
   function cs_section_form($environment,$material_id) {
      $this->cs_rubric_form($environment);
      $this->_material_id = $material_id;
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
         $this->_headline = getMessage('SECTION_EDIT');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = getMessage('SECTION_EDIT');
         } else {
            $this->_headline = getMessage('SECTION_ENTER_NEW');
         }
      } else {
         $this->_headline = getMessage('SECTION_ENTER_NEW');
      }

      //sections
      $material_manager =  $this->_environment->getMaterialManager();
      $material_item = $material_manager->getItem($this->_material_id);
      $this->_section_list = $material_item->getSectionList();
      $section_array = array();
      $other_sections='';
      if (!empty($this->_section_list)){
         $section = $this->_section_list->getFirst();
         $i=0;
         while($section){
            $i++;
            $tmpArray['text'] = (string)($i);
            $section_array[] =  $tmpArray;
            $other_sections.= $section->getTitle();
            $other_sections.= '<BR>';
            $section = $this->_section_list->getNext();
         }
         if (empty($this->_item)){
            $i++;
            $tmpArray['text'] = (string)($i);
            $section_array[] =  $tmpArray;
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
      $this->_form->addTitleField('title','',getMessage('COMMON_TITLE'),getMessage('COMMON_TITLE_DESC'),200,47,true);
      $format_help_link = ahref_curl($this->_environment->getCurrentContextID(), 'help', 'context',
                  array('module'=>$this->_environment->getCurrentModule(),'function'=>$this->_environment->getCurrentFunction(),'context'=>'HELP_COMMON_FORMAT'),
                  getMessage('HELP_COMMON_FORMAT_TITLE'), '', '_help', '', '',
                  'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"');
      $this->_form->addTextArea('description','',getMessage('COMMON_CONTENT'),getMessage('COMMON_CONTENT_DESC',$format_help_link),60,20);
      $this->_form->addSelect('number',$this->_section_array,(string)count($this->_section_array),getMessage('SECTION_OTHER_SECTIONS'),'','',false,false,false,'','',$this->_other_sections);

      // rubric connections
      $this->_setFormElementsForConnectedRubrics();

      $this->_form->addEmptyline();

      // files
      $this->_form->addAnchor('fileupload');
      $val = ini_get('upload_max_filesize');
      $val = trim($val);
      $last = $val[strlen($val)-1];
      switch($last) {
         case 'k':
         case 'K':
            $val = $val * 1024;
            break;
         case 'm':
         case 'M':
            $val = $val * 1048576;
            break;
      }
      $meg_val = round($val/1048576);
      if ( !empty($this->_file_array) ) {
         $this->_form->addCheckBoxGroup('filelist',$this->_file_array,'',getMessage('MATERIAL_FILES'),getMessage('MATERIAL_FILES_DESC', $meg_val),false,false);
         $this->_form->combine('vertical');
      }
      $this->_form->addHidden('MAX_FILE_SIZE', $val);
      $this->_form->addFilefield('upload', getMessage('MATERIAL_FILES'), getMessage('MATERIAL_UPLOAD_DESC',$meg_val), 12, false, getMessage('MATERIAL_UPLOADFILE_BUTTON'),'option',$this->_with_multi_upload);
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
            if (strtoupper($operation_system) == 'LINUX') {
               $px = '360';
            } elseif (strtoupper($operation_system) == 'MAC OS') {
               $px = '352';
            }
         } elseif ($browser == 'MOZILLA') {
            $operation_system = $this->_environment->getCurrentOperatingSystem();
            if (strtoupper($operation_system) == 'MAC OS') {
               $px = '336'; // camino
            }
         }
         $this->_form->addButton('option',getMessage('MATERIAL_BUTTON_MULTI_UPLOAD_YES'),'','',$px.'px');
      }
      $this->_form->combine('vertical');
      $this->_form->addText('max_size',$val,getMessage('MATERIAL_MAX_FILE_SIZE',$meg_val));

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
         $this->_form->addButtonBar('option',getMessage('SECTION_SAVE_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'),'','','',getMessage('MATERIAL_VERSION_BUTTON'));
      } else {
         $this->_form->addButtonBar('option',getMessage('SECTION_CHANGE_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'),'','','',getMessage('MATERIAL_VERSION_BUTTON'));
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
         $this->_values['material_modification_date'] = $material_item->getModificationDate();
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
