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
class cs_configuration_room_opening_form extends cs_rubric_form {

var $_with_template_form_element2 = false;
var $_with_template_form_element3 = false;

var $_template_community_array = array();
var $_template_array = array();

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
      $this->_headline = $this->_translator->getMessage('CONFIGURATION_ROOM_OPENING_LINK');
      $this->setHeadline($this->_headline);
      $room_manager = $this->_environment->getProjectManager();
      $room_manager->setContextLimit($this->_environment->getCurrentPortalID());
      $room_manager->setTemplateLimit();
      $room_manager->select();
      $room_list = $room_manager->get();
      if ($room_list->isNotEmpty()) {
         $temp_array = array();
         $temp_array['text'] = '*'.$this->_translator->getMessage('CONFIGURATION_TEMPLATE_NO_CHOICE');
         $temp_array['value'] = -1;
         $this->_template_array[] = $temp_array;
         $temp_array = array();
         $temp_array['text'] = '------------------------';
         $temp_array['value'] = 'disabled';
         $this->_template_array[] = $temp_array;
         $item = $room_list->getFirst();
         $current_user = $this->_environment->getCurrentUser();
         while ($item) {
            $temp_array = array();
            $template_availability = $item->getTemplateAvailability();
            if( ($template_availability == '0') ){
               $this->_with_template_form_element2 = true;
               $temp_array['text'] = $item->getTitle();
               $temp_array['value'] = $item->getItemID();
               $this->_template_array[] = $temp_array;
            }
            $item = $room_list->getNext();
         }
         unset($current_user);
      }
      $room_manager = $this->_environment->getCommunityManager();
      $room_manager->setContextLimit($this->_environment->getCurrentPortalID());
      $room_manager->setTemplateLimit();
      $room_manager->select();
      $room_list = $room_manager->get();
      if ($room_list->isNotEmpty()) {
         $temp_array = array();
         $temp_array['text'] = '*'.$this->_translator->getMessage('CONFIGURATION_TEMPLATE_NO_CHOICE');
         $temp_array['value'] = -1;
         $this->_template_community_array[] = $temp_array;
         $temp_array = array();
         $temp_array['text'] = '------------------------';
         $temp_array['value'] = 'disabled';
         $this->_template_community_array[] = $temp_array;
         $item = $room_list->getFirst();
         $current_user = $this->_environment->getCurrentUser();
         while ($item) {
            $temp_array = array();
            $template_availability = $item->getCommunityTemplateAvailability();
            if( ($template_availability == '0') ){
               $this->_with_template_form_element3 = true;
               $temp_array['text'] = $item->getTitle();
               $temp_array['value'] = $item->getItemID();
               $this->_template_community_array[] = $temp_array;
            }
            $item = $room_list->getNext();
         }
         unset($current_user);
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      // form fields
#	  $this->_form->addHidden('iid','');
         $radio_values = array();
         $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_COMMUNITYROOM_OPENING_ALL');
         $radio_values[0]['value'] = '1';
         $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_COMMUNITYROOM_OPENING_MODERATOR');
         $radio_values[1]['value'] = '2';
         $this->_form->addRadioGroup('community_room_opening',$this->_translator->getMessage('CONFIGURATION_COMMUNITYROOM_OPENING'),'',$radio_values,'',true,false);
         if ( $this->_with_template_form_element3 ) {
            $this->_form->addSelect('template_select_community',
                                    $this->_template_community_array,
                                    '',
                                    $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_DEFAULT_COMMUNITY_TITLE'),
                                    $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_SELECT_DESC'),
                                    0,
                                    false,
                                    false,
                                    '',
                                    '',
                                    '',
                                    '',
                                    '',
                                    '12'
                                   );
           $this->_form->combine('vertical');
           $this->_form->addText('template_select_desc_community','',$this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_SELECT_DESC'));
         }
         $this->_form->addEmptyLine();
         $radio_values = array();
         $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_PROJECTROOM_OPENING_PORTAL');
         $radio_values[0]['value'] = '1';
         $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_PROJECTROOM_OPENING_COMMUNITYROOM');
         $radio_values[1]['value'] = '2';
         $this->_form->addRadioGroup('project_room_opening',$this->_translator->getMessage('CONFIGURATION_PROJECTROOM_OPENING'),'',$radio_values,'',true,false);

         $radio_values = array();
         $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_PROJECTROOM_LINK_OPTIONAL');
         $radio_values[0]['value'] = '1';
         $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_PROJECTROOM_LINK_MANDATORY');
         $radio_values[1]['value'] = '2';
         $this->_form->addRadioGroup('project_room_link',$this->_translator->getMessage('CONFIGURATION_PROJECTROOM_LINK'),'',$radio_values,'',true,false);

        if ( $this->_with_template_form_element2 ) {
            $this->_form->addSelect('template_select',
                                    $this->_template_array,
                                    '',
                                    $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_DEFAULT_TITLE'),
                                    $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_SELECT_DESC'),
                                    0,
                                    false,
                                    false,
                                    '',
                                    '',
                                    '',
                                    '',
                                    '',
                                    '12'
                                   );
           $this->_form->combine('vertical');
           $this->_form->addText('template_select_desc','',$this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_SELECT_DESC'));
        }

         $this->_form->addEmptyLine();
         $this->_form->addCheckbox('room_archiving',1,'',$this->_translator->getMessage('CONFIGURATION_ROOM_ARCHIVING'),strtolower($this->_translator->getMessage('COMMON_ACTIVATE')));
         $this->_form->combine();
         $this->_form->addTextfield('room_archiving_days_unused','','','',4,4,false,'','','','left',$this->_translator->getMessage('CONFIGURATION_ROOM_ARCHIVING_DAYS_UNUSED1'),'',false,$this->_translator->getMessage('CONFIGURATION_ROOM_ARCHIVING_DAYS_UNUSED2'));
         $this->_form->combine();
         $this->_form->addTextfield('room_archiving_days_unused_mail','','','',2,2,false,'','','','left',$this->_translator->getMessage('CONFIGURATION_ROOM_ARCHIVING_DAYS_MAIL_UNUSED1'),'',false,$this->_translator->getMessage('CONFIGURATION_ROOM_ARCHIVING_DAYS_MAIL_UNUSED2'));
         $this->_form->addEmptyLine();
         $this->_form->addCheckbox('room_deleting',1,'',$this->_translator->getMessage('CONFIGURATION_ROOM_DELETING'),strtolower($this->_translator->getMessage('COMMON_ACTIVATE')));
         $this->_form->combine();
         $this->_form->addTextfield('room_deleting_days_unused','','','',4,4,false,'','','','left',$this->_translator->getMessage('CONFIGURATION_ROOM_DELETING_DAYS_UNUSED1'),'',false,$this->_translator->getMessage('CONFIGURATION_ROOM_DELETING_DAYS_UNUSED2'));
         $this->_form->combine();
         $this->_form->addTextfield('room_deleting_days_unused_mail','','','',2,2,false,'','','','left',$this->_translator->getMessage('CONFIGURATION_ROOM_DELETING_DAYS_MAIL_UNUSED1'),'',false,$this->_translator->getMessage('CONFIGURATION_ROOM_DELETING_DAYS_MAIL_UNUSED2'));

         $this->_form->addEmptyLine();
         $this->_form->addText('', $this->_translator->getMessage('CONFIGURATION_ROOM_CATEGORIES'), '<a href="/portal/'.$this->_environment->getCurrentPortalId().'/room/categories">'.$this->_translator->getMessage('CONFIGURATION_ROOM_CATEGORIES_DESC').'</a>');

      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      if (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
      }else{
         $room = $this->_environment->getCurrentContextItem();
         $community_room_opening = $room->getCommunityRoomCreationStatus();
         if ($community_room_opening == 'moderator'){
            $this->_values['community_room_opening'] ='2';
         }else{
            $this->_values['community_room_opening'] ='1';

         }
         $project_room_opening = $room->getProjectRoomCreationStatus();
         if ($project_room_opening == 'communityroom'){
            $this->_values['project_room_opening'] ='2';
         }else{
            $this->_values['project_room_opening'] ='1';

         }
         $project_room_link = $room->getProjectRoomLinkStatus();
         if ($project_room_link == 'mandatory'){
            $this->_values['project_room_link'] ='2';
         }else{
            $this->_values['project_room_link'] ='1';

         }
         $this->_values['template_select'] = $room->getDefaultProjectTemplateID();
         $this->_values['template_select_community'] = $room->getDefaultCommunityTemplateID();
         if ( $room->showAllwaysPrivateRoomLink() ) {
            $this->_values['private_room_link'] = 1;
         } else {
            $this->_values['private_room_link'] = -1;
         }
         
         // archiving
         if ( $room->isActivatedArchivingUnusedRooms() ) {
         	$this->_values['room_archiving'] = 1;
         }
         $this->_values['room_archiving_days_unused'] = $room->getDaysUnusedBeforeArchivingRooms();
         $this->_values['room_archiving_days_unused_mail'] = $room->getDaysSendMailBeforeArchivingRooms();
         
         // deleting
         if ( $room->isActivatedDeletingUnusedRooms() ) {
         	$this->_values['room_deleting'] = 1;
         }
         $this->_values['room_deleting_days_unused'] = $room->getDaysUnusedBeforeDeletingRooms();
         $this->_values['room_deleting_days_unused_mail'] = $room->getDaysSendMailBeforeDeletingRooms();
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
   	
   	// archiving
      if ( !empty($this->_form_post['room_archiving'])
           and $this->_form_post['room_archiving'] == 1
           and empty($this->_form_post['room_archiving_days_unused'])
         ) {
         $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_ROOM_ARCHIVING_DAYS_UNUSED_ERROR_EMPTY');
         $this->_form->setFailure('room_archiving_days_unused','');
      }
      if ( !empty($this->_form_post['room_archiving'])
           and $this->_form_post['room_archiving'] == 1
           and !empty($this->_form_post['room_archiving_days_unused'])
           and !empty($this->_form_post['room_archiving_days_unused_mail'])
           and $this->_form_post['room_archiving_days_unused_mail'] > $this->_form_post['room_archiving_days_unused'] 
         ) {
         $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_ROOM_ARCHIVING_DAYS_UNUSED_ERROR_DAYS');
         $this->_form->setFailure('room_archiving_days_unused','');
      }
      
      // deleting
      if ( !empty($this->_form_post['room_deleting'])
           and $this->_form_post['room_deleting'] == 1
      	  and empty($this->_form_post['room_archiving'])
      	) {
      	$this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_ROOM_ARCHIVING_DELETING_ARCHIVE_NOT_ACTIVATED');
      	$this->_form->setFailure('room_archiving','');
      }
      if ( !empty($this->_form_post['room_archiving'])
           and $this->_form_post['room_archiving'] == 1
      	  and !empty($this->_form_post['room_deleting'])
           and $this->_form_post['room_deleting'] == 1
      	  and empty($this->_form_post['room_deleting_days_unused'])
         ) {
         $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_ROOM_ARCHIVING_DAYS_UNUSED_ERROR_EMPTY');
         $this->_error_array = array_unique($this->_error_array);
         $this->_form->setFailure('room_deleting_days_unused','');
      }
      if ( !empty($this->_form_post['room_archiving'])
           and $this->_form_post['room_archiving'] == 1
      	  and !empty($this->_form_post['room_deleting'])
      	  and $this->_form_post['room_deleting'] == 1
      	  and !empty($this->_form_post['room_deleting_days_unused'])
           and !empty($this->_form_post['room_deleting_days_unused_mail'])
           and $this->_form_post['room_deleting_days_unused_mail'] > $this->_form_post['room_deleting_days_unused'] 
         ) {
         $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_ROOM_ARCHIVING_DAYS_UNUSED_ERROR_DAYS');
         $this->_error_array = array_unique($this->_error_array);
         $this->_form->setFailure('room_archiving_days_unused','');
      }
   }   
}
?>