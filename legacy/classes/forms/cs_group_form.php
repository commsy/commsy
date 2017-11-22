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

/** class for commsy form: group
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_group_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing the materials of a group
   */
   var $_material_array = array();

  /**
   * array - containing an array of groups in the context
   */
   var $_group_array = array();

  /**
   * array - containing an array of materials form the session
   */
   var $_session_material_array = array();

   /*
    * bool - does the group have a picture?
    */
   var $_has_picture;

   /**
   * array - containing the values for the edit status for the item (everybody or creator)
   */
   var $_public_array = array();

   var $_discussion_notification_array = array();

   var $_shown_discussion_notification_array = array();

   var $_session_discussion_notification_array = array();

   #############################################
   # FLAG: group rooms
   #############################################
   private $_with_group_room = false;
   private $_exists_group_room = false;

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** set materials from session
    * set an array with the materials from the session
    *
    * @param array array of materials out of session
    */
   function setSessionMaterialArray ($value) {
      $this->_session_material_array = (array)$value;
   }

   function setSessionDiscussionNotificationArray ($value) {
      $this->_session_discussion_notification_array = (array)$value;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {

      // public
      if ( isset($this->_item) ) {
         $creator_item = $this->_item->getCreatorItem();
         $fullname = $creator_item->getFullname();
      } elseif (!empty($this->_form_post['iid']) and $this->_form_post['iid'] != 'NEW') {
         $manager = $this->_environment->getManager(CS_GROUP_TYPE);
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

      if ( !empty($this->_item) ) {
         $this->_has_picture = $this->_item->getPicture();
      } elseif (!empty($this->_form_post['has_picture']) and $this->_form_post['has_picture'] == 'yes') {
         $this->_has_picture = true;
      } else {
         $this->_has_picture = false;
      }

      if (!empty($this->_item)) {
         $this->_headline = $this->_translator->getMessage('GROUP_EDIT');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = $this->_translator->getMessage('GROUP_EDIT');
         } else {
            $this->_headline = $this->_translator->getMessage('GROUP_ENTER_NEW');
            $new='';
            $context_item = $this->_environment->getCurrentContextItem();
            $rubric_array = $context_item->_getRubricArray(CS_GROUP_TYPE);
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
         $this->_headline = $this->_translator->getMessage('GROUP_ENTER_NEW');
         $new='';
         $context_item = $this->_environment->getCurrentContextItem();
         $rubric_array = $context_item->_getRubricArray(CS_GROUP_TYPE);
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

      #############################################
      # FLAG: group rooms
      #############################################
      $context_item = $this->_environment->getCurrentContextItem();
      if ( $context_item->showGrouproomFunctions() ) {
         $this->_with_group_room = true;
         if ( isset($this->_item) ) {
            $this->_exists_group_room = $this->_item->isGroupRoomActivated();
            $this->_exists_group_room_id = $this->_item->getGroupRoomItemID();
         } elseif ( !empty($this->_form_post['group_room_exists']) ) {
            $this->_exists_group_room = $this->_form_post['group_room_exists'];
            $this->_exists_group_room_id = $this->_form_post['group_room_id'];
         }
      }

      // Foren zuordnen:
      $context_item = $this->_environment->getCurrentContextItem();
      $discussion_array = $context_item->getWikiDiscussionArray();

      $discussion_notification_array = array();
      $temp_array['text'] = '*'.$this->_translator->getMessage('PREFERENCES_NO_DISCUSSION_NOTIFICATION');
      $temp_array['value'] = '-1';
      $discussion_notification_array[] = $temp_array;
      $temp_array['text'] = '--------------------';
      $temp_array['value'] = 'disabled';
      $discussion_notification_array[] = $temp_array;

      if ( isset($discussion_array) and !empty($discussion_array) ) {
         foreach ($discussion_array as $discussion) {
            $temp_array['text'] = $discussion;
            $temp_array['value'] = $discussion;
            $discussion_notification_array[] = $temp_array;
         }
      }

      $this->_discussion_notification_array = $discussion_notification_array;

      $discussion_notification_array = array();

      if (!empty($this->_session_discussion_notification_array)) {
         foreach ( $this->_session_discussion_notification_array as $discussion_notification ) {
            $temp_array['text'] = $discussion_notification;
            $temp_array['value'] = $discussion_notification;
            $discussion_notification_array[] = $temp_array;
         }
      } elseif ( isset($this->_item)) {
         $discussion_notification_array = $this->_item->getDiscussionNotificationArray();
         if (isset($discussion_notification_array[0])) {
            foreach ($discussion_notification_array as $discussion_notification) {
               $temp_array['text'] = $discussion_notification;
               $temp_array['value'] = $discussion_notification;
               $discussion_notification_array[] = $temp_array;
            }
         }
      }
      $this->_shown_discussion_notification_array = $discussion_notification_array;

   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      #############################################
      # FLAG: group rooms
      #############################################
      $context_id = '';
      if ( !empty($this->_exists_group_room_id) ) {
         $context_id = $this->_exists_group_room_id;
      }
      #############################################
      # FLAG: group rooms
      #############################################

      // group
      $this->_form->addHidden('iid','');
      if (isset($this->_item) and $this->_item->isSystemLabel()) {
         $this->_form->addTitleField('system_name',$this->_translator->getMessage('COMMON_NAME'),$this->_item->getName(),$this->_translator->getMessage('GROUP_ALL_NAME_DESC'),200,'',true,'','','','left','','',false);
      } else {
         $this->_form->addTitleField('name','',$this->_translator->getMessage('COMMON_NAME'),$this->_translator->getMessage('COMMON_NAME_DESC'),200,45,true);
      }
      $this->_setFormElementsForConnectedRubrics();

      $this->_form->addTextArea('description','',$this->_translator->getMessage('CONFIGURATION_ROOM_DESCRIPTION'),'');

      $this->_form->addEmptyline();      // public radio-buttons
      $this->_form->addHidden('has_picture','');
      $this->_form->addImage('picture_upload','',$this->_translator->getMessage('USER_PICTURE_UPLOADFILE'), $this->_translator->getMessage('GROUP_PICTURE_FILE_DESC'),$context_id);

      //delete picture
      if ( $this->_has_picture ) {
         $this->_form->combine();
         $this->_form->addCheckbox('deletePicture',$this->_translator->getMessage('USER_DEL_PIC'),false,$this->_translator->getMessage('USER_DEL_PIC'),$this->_translator->getMessage('USER_DEL_PIC_BUTTON'),'');
      }
      $this->_form->addHidden('picture_hidden','');

      // Foren zuordnen:

      $context_item = $this->_environment->getCurrentContextItem();
      if($context_item->WikiEnableDiscussionNotificationGroups() == 1){
         if ( !empty ($this->_shown_discussion_notification_array) ) {
            $this->_form->addCheckBoxGroup('discussion_notification_list',$this->_shown_discussion_notification_array,'',$this->_translator->getMessage('PREFERENCES_DISCUSSION_NOTIFICATION'),'',false,false);
            $this->_form->combine();
         }
         $this->_form->addSelect('discussion_notification',$this->_discussion_notification_array,'',$this->_translator->getMessage('PREFERENCES_DISCUSSION_NOTIFICATION'),'', 1, false,true,false,'','','','',16);
         $this->_form->combine('horizontal');
         $this->_form->addButton('option',$this->_translator->getMessage('PREFERENCES_ADD_DISCUSSION_NOTIFICATION_BUTTON'),'','',160);
      }

      #############################################
      # FLAG: group rooms
      #############################################
      if ( !(isset($this->_item) and $this->_item->isSystemLabel()) ) {
         if ( $this->_with_group_room ) {
            $checked = false;
            $dead = false;
            if ( $this->_exists_group_room ) {
               $checked = true;
               $dead = true;
               $this->_form->addHidden('group_room_exists',1);
            }
            if ( !empty($this->_exists_group_room_id) ) {
               $this->_form->addHidden('group_room_id',$this->_exists_group_room_id);
            }
            $this->_form->addCheckbox('group_room_activate','1',$checked,$this->_translator->getMessage('GROUPROOM_FORM_CHECKBOX_TITLE'),$this->_translator->getMessage('GROUPROOM_FORM_CHECKBOX_TEXT'),'','',$dead);
         }
      }
      #############################################
      # FLAG: group rooms
      #############################################

      // public radio-buttons
      if ( !isset($this->_item) ) {
         $this->_form->addRadioGroup('public',$this->_translator->getMessage('RUBRIC_PUBLIC'),$this->_translator->getMessage('RUBRIC_PUBLIC_DESC'),$this->_public_array);
      } else {
         $current_user = $this->_environment->getCurrentUser();
         $creator = $this->_item->getCreatorItem();
         if ( ($current_user->getItemID() == $creator->getItemID() or $current_user->isModerator()) and !$this->_item->isSystemLabel() ) {
            $this->_form->addRadioGroup('public',$this->_translator->getMessage('RUBRIC_PUBLIC'),$this->_translator->getMessage('RUBRIC_PUBLIC_DESC'),$this->_public_array);
         } else {
            $this->_form->addHidden('public','');
         }
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

      if ( $id == 0 or (isset($this->_item) and $this->_item->isSystemLabel()) )  {
         $this->_form->addButtonBar('option',$this->_translator->getMessage('GROUP_SAVE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
      } else {
         $this->_form->addButtonBar('option',$this->_translator->getMessage('GROUP_CHANGE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','');
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();

      if ( isset($this->_form_post['discussion_notification_list']) ) {
         $this->_values['discussion_notification_list'] = $this->_form_post['discussion_notification_list'];
         $this->_shown_discussion_notification_array = $this->_form_post['discussion_notification_list'];
      }

      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
         if ( !isset($this->_values['public']) ) {
            $this->_values['public'] = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'1':'0'; //In projectrooms everybody can edit the item by default, else default is creator only
         }
         if (!isset($this->_values['name'])) { //if group ist group for all members, we set name hier
            $this->_values['name'] = $this->_translator->getMessage('ALL_MEMBERS');
         }
       if ( isset($this->_values['picture_hidden']) and !empty($this->_values['picture_hidden']) ) {
          $this->_values['picture_upload'] = $this->_values['picture_hidden'];
       }
      } elseif (isset($this->_item)) {
         $this->_values['iid'] = $this->_item->getItemID();
         $discussion_notification_array = $this->_item->getDiscussionNotificationArray();
         $this->_values['discussion_notification_list'] = $discussion_notification_array;

         if (!$this->_item->isSystemLabel()) {
            $this->_values['name'] = $this->_item->getName();
         } else {
            $this->_values['name'] = $this->_translator->getMessage('ALL_MEMBERS');
         }
         if ( !isset($this->_exists_group_room) or ! $this->_exists_group_room ) {
            $this->_values['description'] = $this->_item->getDescription();
            $this->_values['picture_upload'] = $this->_item->getPicture();
            $this->_values['picture_hidden'] = $this->_item->getPicture();
            $picture = $this->_item->getPicture();
            if ( !empty($picture) ) {
               $this->_values['has_picture'] = 'yes';
            } else {
               $this->_values['has_picture'] = 'no';
            }

         #############################################
         # FLAG: group rooms
         #############################################
         } else {
            $grouproom = $this->_item->getGroupRoomItem();
            if ( isset($grouproom) and !empty($grouproom) ) {
               $this->_values['description'] = $grouproom->getDescription();
               $picture = $grouproom->getLogoFileName();
               $this->_values['picture_upload'] = $picture;
               $this->_values['picture_hidden'] = $picture;
               if ( !empty($picture) ) {
                  $this->_values['has_picture'] = 'yes';
               } else {
                  $this->_values['has_picture'] = 'no';
               }
            }
         }
         #############################################
         # FLAG: group rooms
         #############################################

         $this->_values['public'] = $this->_item->isPublic();
         $this->_setValuesForRubricConnections();
      } else {
         $this->_values['public'] = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'1':'0'; //In projectrooms everybody can edit the item by default, else default is creator only
      }
   }
}
?>