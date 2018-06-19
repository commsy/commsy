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

/** class for commsy form: institution
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_institution_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing the materials of a institution
   */
   var $_material_array = array();

  /**
   * array - containing an array of institutions in the context
   */
   var $_institution_array = array();

  /**
   * array - containing an array of materials form the session
   */
   var $_session_material_array = array();

   /*
    * bool - does the group have a picture?
    */
   var $_has_picture;

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

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example institutions
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      // public
      if ( isset($this->_item) ) {
         $creator_item = $this->_item->getCreatorItem();
         $fullname = $creator_item->getFullname();
      } elseif (!empty($this->_form_post['iid'])) {
         $manager = $this->_environment->getManager(CS_INSTITUTION_TYPE);
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

      // picture
        if ( !empty($this->_item) ) {
         $this->_has_picture = $this->_item->getPicture();
      } else {
         $this->_has_picture = false;
      }

      // headline
      if (!empty($this->_item)) {
         $this->_headline = $this->_translator->getMessage('INSTITUTION_EDIT');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = $this->_translator->getMessage('INSTITUTION_EDIT');
         } else {
            $this->_headline = $this->_translator->getMessage('INSTITUTION_ENTER_NEW');
            $new='';
            $context_item = $this->_environment->getCurrentContextItem();
            $rubric_array = $context_item->_getRubricArray(CS_INSTITUTION_TYPE);
            if (isset($rubric_array[mb_strtoupper($this->_environment->getSelectedLanguage(), 'UTF-8')]['GENUS']) ){
              $genus = $rubric_array[mb_strtoupper($this->_environment->getSelectedLanguage(), 'UTF-8')]['GENUS'];
            }else{
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
      } else {
         $this->_headline = $this->_translator->getMessage('INSTITUTION_ENTER_NEW');
         $new='';
         $context_item = $this->_environment->getCurrentContextItem();
         $rubric_array = $context_item->_getRubricArray(CS_INSTITUTION_TYPE);
         if (isset($rubric_array[mb_strtoupper($this->_environment->getSelectedLanguage(), 'UTF-8')]['GENUS']) ){
           $genus = $rubric_array[mb_strtoupper($this->_environment->getSelectedLanguage(), 'UTF-8')]['GENUS'];
         }else{
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
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      // institution
      $this->_form->addHidden('iid','');
      $this->_form->addTitleField('name','',$this->_translator->getMessage('COMMON_NAME'),$this->_translator->getMessage('COMMON_NAME_DESC'),200,45,true);
      $this->_form->addTextArea('description','',$this->_translator->getMessage('COMMON_CONTENT'),'',60);
      $this->_form->addImage('picture_upload','',$this->_translator->getMessage('USER_PICTURE_UPLOADFILE'), $this->_translator->getMessage('INSTITUTION_PICTURE_FILE_DESC'));

      //delete picture
      if ( $this->_has_picture) {
         $this->_form->combine();
         $this->_form->addCheckbox('deletePicture',$this->_translator->getMessage('USER_DEL_PIC'),false,$this->_translator->getMessage('USER_DEL_PIC'),$this->_translator->getMessage('USER_DEL_PIC_BUTTON'),'');
      }
      $this->_form->addHidden('picture_hidden','');

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
      // rubric connections
      $this->_setFormElementsForConnectedRubrics();

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
         $this->_form->addButtonBar('option',$this->_translator->getMessage('INSTITUTION_SAVE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
      } else {
         $this->_form->addButtonBar('option',$this->_translator->getMessage('INSTITUTION_CHANGE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','');
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
         if ( !isset($this->_values['public']) ) {
            $this->_values['public'] = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'1':'0'; //In projectrooms everybody can edit the item by default, else default is creator only
         }
       if ( isset($this->_values['picture_hidden']) and !empty($this->_values['picture_hidden']) ) {
          $this->_values['picture_upload'] = $this->_values['picture_hidden'];
       }
      } elseif (isset($this->_item)) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['name'] = $this->_item->getName();
         $this->_values['description'] = $this->_item->getDescription();
         $this->_values['picture_upload'] = $this->_item->getPicture();
         $this->_values['picture_hidden'] = $this->_item->getPicture();
         $this->_values['public'] = $this->_item->isPublic();
         $this->_setValuesForRubricConnections();
      } else {
         $this->_values['public'] = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'1':'0'; //In projectrooms everybody can edit the item by default, else default is creator only
      }
   }
}
?>