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

class cs_annotation_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing the materials of an annotation
   */
   var $_material_array = array();

  /**
   * array - containing an array of materials form the session
   */
   var $_session_material_array = array();

  /**
   * int - this is the ref_iid of this annotation
   */
   var $_ref_iid = NULL;     // sollte nicht mehr verwendet werden, sondern die Methode $item->getLinkedItemID()

  /**
   * int - this is the ref_version id of this annotation (item must be a material)
   */
   var $_version = NULL;    // sollte nicht mehr verwendet werden, sondern die Methode $item->getLinkedVersionID()

   /** constructor: cs_annotation_form
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function cs_annotation_form($params) {
      $this->cs_rubric_form($params);
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

   /**
    * Set the refId for this annotation
    *
    * @param int an unique refId of the item
    *
    * @author CommSy Development Group
    */
   function setRefId($value) {
      $this->_ref_iid = $value;
   }

   function setVersion($value) {
      $this->_version = $value;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      // headline
      if (!empty($this->_item)) {
         $this->_headline = getMessage('ANNOTATION_EDIT');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = getMessage('ANNOTATION_EDIT');
         } else {
            $this->_headline = getMessage('ANNOTATION_ENTER_NEW');
         }
      } else {
         $this->_headline = getMessage('ANNOTATION_ENTER_NEW');
      }

//      // materials
//      $material_array = array();
//      if (isset($this->_session_material_array)) {
//         $material_manager = $this->_environment->getMaterialManager();
//         foreach ( $this->_session_material_array as $material ) {
//            $material_item = $material_manager->getItem($material['iid']);
//            $temp_array['text'] = $material_item->getTitle();
//            $temp_array['value'] = '<VALUE><ID>'.$material['iid'].'</ID><VERSION>'.$material['vid'].'</VERSION></VALUE>';
//            $material_array[] = $temp_array;
//         }
//      } elseif (isset($this->_item)) {
//         $material_list = $this->_item->getMaterialList();
//         $material_array_for_session = array();
//         if ($material_list->getCount() > 0) {
//            $material_item = $material_list->getFirst();
//            while ($material_item) {
//               $temp_array['text'] = $material_item->getTitle();
//               $temp_array['value'] = '<VALUE><ID>'.$material_item->getItemID().'</ID><VERSION>'.$material_item->getVersionID().'</VERSION></VALUE>';
//               $material_array[] = $temp_array;
//               $material_item = $material_list->getNext();
//            }
//         }
//      }
//      $this->_material_array = $material_array;

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
    */
   function _createForm () {
      // annotation
      $this->_form->addHidden('iid','');
      $this->_form->addHidden('ref_iid','');
      $this->_form->addHidden('version','');
      $this->_form->addTitleField('title','',getMessage('COMMON_TITLE'),'',200,46);
      $format_help_link = ahref_curl($this->_environment->getCurrentContextID(), 'help', 'context',
                  array('module'=>$this->_environment->getCurrentModule(),'function'=>$this->_environment->getCurrentFunction(),'context'=>'HELP_COMMON_FORMAT'),
                  getMessage('HELP_COMMON_FORMAT_TITLE'), '', '_help', '', '',
                  'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"');
      $this->_form->addTextArea('description','',getMessage('COMMON_CONTENT'),getMessage('COMMON_CONTENT_DESC',$format_help_link),60);

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
         $this->_form->addButtonBar('option',getMessage('ANNOTATION_SAVE_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'));
      } else {
         $this->_form->addButtonBar('option',getMessage('ANNOTATION_CHANGE_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'),'','','');
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the annotation item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();
      if (isset($this->_item)) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['title'] = $this->_item->getTitle(); // no encode here - encode in form-views
         $this->_values['description'] = $this->_item->getDescription();
         $this->_values['ref_iid'] = $this->_item->getLinkedItemID();
         $this->_values['version'] = $this->_item->getLinkedVersionID();
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
      } elseif (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
      }
      if ( isset($this->_ref_iid) ) {
         $this->_values['ref_iid'] = $this->_ref_iid;
      }
      if ( isset($this->_version) ) {
         $this->_values['version'] = $this->_version;
      }
   }
}
?>