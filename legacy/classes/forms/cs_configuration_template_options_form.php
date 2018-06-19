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

class cs_configuration_template_options_form extends cs_rubric_form {

   var $_with_template_form_element = false;

   var $_with_template_form_element2 = false;

   var $_with_template_form_element3 = false;

   function __construct($environment) {
      cs_rubric_form::__construct($environment);
   }


   function _initForm () {
      // room templates
      $current_context = $this->_environment->getCurrentContextItem();
      $this->_with_template_form_element = true;
      $this->_with_template_form_element_select = false;

      // disable template checkbox
      $this->_disable_template_form_element = $current_context->isOpen();

      // room templates 2 - select
      $current_portal = $this->_environment->getCurrentPortalItem();
      if ( ( empty($this->_type) or empty($_GET['iid']) )
             and isset($current_portal)
             and ( ( $this->_environment->inCommunityRoom()
                     and $this->_environment->getCurrentModule() == CS_PROJECT_TYPE
                   )
                   or ( $this->_environment->inPortal()
                        and $this->_environment->getCurrentModule() == CS_PROJECT_TYPE
                      )
                 )
         ) {
         $room_manager = $this->_environment->getProjectManager();
         $room_manager->setContextLimit($current_portal->getItemID());
         $room_manager->setTemplateLimit();
         if ( $this->_environment->inCommunityRoom() ) {
            global $c_cache_cr_pr;
            if ( !isset($c_cache_cr_pr) or !$c_cache_cr_pr ) {
               $room_manager->setCommunityRoomLimit($this->_environment->getCurrentContextID());
            } else {
               /**
                * use redundant infos in community room
                */
               $current_community_item = $this->_environment->getCurrentContextItem();
               $room_manager->setIDArrayLimit($current_community_item->getInternalProjectIDArray());
               unset($current_community_item);
            }
         }
         $room_manager->select();
         $room_list = $room_manager->get();
         $default_id = $this->_environment->getCurrentPortalItem()->getDefaultProjectTemplateID();
         if ($room_list->isNotEmpty() or $default_id != '-1' ) {
            $temp_array = array();
            $temp_array['text'] = '*'.$this->_translator->getMessage('CONFIGURATION_TEMPLATE_NO_CHOICE');
            $temp_array['value'] = -1;
            $this->_template_array[] = $temp_array;
            $temp_array = array();
            $temp_array['text'] = '------------------------';
            $temp_array['value'] = 'disabled';
            $this->_template_array[] = $temp_array;
            $current_user = $this->_environment->getCurrentUser();
            if ( $default_id != '-1' ){
               $default_item = $room_manager->getItem($default_id);
               $template_availability = $default_item->getTemplateAvailability();
               if( ($template_availability == '0') ){
                  $temp_array['text'] = '*'.$default_item->getTitle();
                  $temp_array['value'] = $default_item->getItemID();
                  $this->_template_array[] = $temp_array;
                  $temp_array = array();
                  $temp_array['text'] = '------------------------';
                  $temp_array['value'] = 'disabled';
                  $this->_with_template_form_element2 = true;
                  $this->_template_array[] = $temp_array;
               }
            }
            $item = $room_list->getFirst();
            while ($item) {
               $temp_array = array();
               $template_availability = $item->getTemplateAvailability();
               if( ($template_availability == '0') OR
                   ($template_availability == '1' and $item->mayEnter($current_user)) OR
                   ($template_availability == '2' and $item->mayEnter($current_user) and $item->isModeratorByUserID($current_user->getUserID(),$current_user->isModerator())))
               {
                  if ($item->getItemID() != $default_id){
                     $this->_with_template_form_element2 = true;
                     $temp_array['text'] = $item->getTitle();
                     $temp_array['value'] = $item->getItemID();
                     $this->_template_array[] = $temp_array;
                  }

               }
               $item = $room_list->getNext();
            }
            unset($current_user);
         }
      }


      // room templates 3 - select
      $current_portal = $this->_environment->getCurrentPortalItem();
      if ( ( empty($this->_type) or empty($_GET['iid']) )
             and isset($current_portal)
               and ( $this->_environment->inPortal()
                           and $this->_environment->getCurrentModule() == CS_COMMUNITY_TYPE
                   )
         ) {
         $room_manager = $this->_environment->getCommunityManager();
         $room_manager->setContextLimit($current_portal->getItemID());
         $room_manager->setTemplateLimit();
         $room_manager->select();
         $room_list = $room_manager->get();
         $default_id = $this->_environment->getCurrentPortalItem()->getDefaultProjectTemplateID();
         if ($room_list->isNotEmpty() or $default_id != '-1' ) {
            $temp_array = array();
            $temp_array['text'] = '*'.$this->_translator->getMessage('CONFIGURATION_TEMPLATE_NO_CHOICE');
            $temp_array['value'] = -1;
            $this->_community_template_array[] = $temp_array;
            $temp_array = array();
            $temp_array['text'] = '------------------------';
            $temp_array['value'] = 'disabled';
            $this->_community_template_array[] = $temp_array;
            $current_user = $this->_environment->getCurrentUser();
            if ( $default_id != '-1' ){
               $default_item = $room_manager->getItem($default_id);
               $template_availability = $default_item->getCommunityTemplateAvailability();
               if( ($template_availability == '0') ){
                  $temp_array['text'] = '*'.$default_item->getTitle();
                  $temp_array['value'] = $default_item->getItemID();
                  $this->_community_template_array[] = $temp_array;
                  $temp_array = array();
                  $temp_array['text'] = '------------------------';
                  $temp_array['value'] = 'disabled';
                  $this->_with_template_form_element2 = true;
                  $this->_community_template_array[] = $temp_array;
               }
            }
            $item = $room_list->getFirst();
            while ($item) {
               $temp_array = array();
               $template_availability = $item->getCommunityTemplateAvailability();
               if( ($template_availability == '0') OR
                   ($template_availability == '1' and $item->mayEnter($current_user)) OR
                   ($template_availability == '2' and $item->mayEnter($current_user) and $item->isModeratorByUserID($current_user->getUserID(),$current_user->isModerator())))
               {
                  if ($item->getItemID() != $default_id){
                     $this->_with_template_form_element3 = true;
                     $temp_array['text'] = $item->getTitle();
                     $temp_array['value'] = $item->getItemID();
                     $this->_community_template_array[] = $temp_array;
                  }

               }
               $item = $room_list->getNext();
            }
            unset($current_user);
         }
      }

      // template in private rooms
      if ( $this->_environment->inPrivateRoom()
           and !$current_context->isTemplate()
         ) {
         $room_manager = $this->_environment->getPrivateRoomManager();
         $room_manager->setContextLimit($current_portal->getItemID());
         $room_manager->setTemplateLimit();
         $room_manager->select();
         $room_list = $room_manager->get();
         #$default_id = $this->_environment->getCurrentPortalItem()->getDefaultPrivateRoomTemplateID();
         $default_id = -1;
         if ($room_list->isNotEmpty() or $default_id != '-1' ) {
            $temp_array = array();
            $temp_array['text'] = '*'.$this->_translator->getMessage('CONFIGURATION_TEMPLATE_NO_CHOICE_PRIVATEROOM');
            $temp_array['value'] = -1;
            $this->_template_array[] = $temp_array;
            $temp_array = array();
            $temp_array['text'] = '------------------------';
            $temp_array['value'] = 'disabled';
            $this->_template_array[] = $temp_array;
            $current_user = $this->_environment->getCurrentUser();
            if ( $default_id != '-1' ) {
               $default_item = $room_manager->getItem($default_id);
               if ( isset($default_item) ) {
                  $template_availability = $default_item->getTemplateAvailability();
                  if( ($template_availability == '0') and $default_item->isClosed() ){
                     $temp_array['text'] = '*'.$default_item->getTitle();
                     $temp_array['value'] = $default_item->getItemID();
                     $this->_template_array[] = $temp_array;
                     $temp_array = array();
                     $temp_array['text'] = '------------------------';
                     $temp_array['value'] = 'disabled';
                     $this->_with_template_form_element_select = true;
                     $this->_template_array[] = $temp_array;
                     $this->_javascript_array[$default_item->getItemID()] = $default_item->getTemplateDescription();
                  }
               }
            }
            $item = $room_list->getFirst();
            while ($item) {
               $temp_array = array();
               $template_availability = $item->getTemplateAvailability();

               if ( $template_availability == '0' ) {
                  if ( $item->getItemID() != $default_id
                       and $item->getItemID() != $current_context->getItemID()
                     ) {
                     $temp_array['text'] = $item->getTitle();
                     if ( $item->getTitle() == $this->_translator->getMessage('COMMON_PRIVATEROOM') ) {
                        $template_title = $item->getTemplateTitle();
                        if ( !empty($template_title) ) {
                           $temp_array['text'] = $template_title;
                        }
                     }
                     $temp_array['value'] = $item->getItemID();
                     $this->_with_template_form_element_select = true;
                     $this->_template_array[] = $temp_array;
                     $this->_javascript_array[$item->getItemID()] = $item->getTemplateDescription();
                  }
               }
               $item = $room_list->getNext();
            }
         }
         unset($room_manager);
      }
   }

   function _createForm () {
      // specials in private room
      if ( $this->_environment->inPrivateRoom() ) {
         // select a template
         if ( $this->_with_template_form_element_select ) {
            $this->_form->addSubHeadline('header_template_select',$this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_TITLE_SELECT'));
            $this->_form->addSelect('template_select',
                                    $this->_template_array,
                                    '',
                                    $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_SHORT_TITLE'),
                                    '',
                                    0,
                                    false,
                                    false,
                                    '',
                                    '',
                                    '',
                                    '',
                                    '',
                                    '27',
                                    false,
                                    false,
                                    '10',
                                    'onChange="cs_toggle_template()"'
                                   );
            $this->_form->combine('vertical');
            $this->_form->addText('template_select_text','',$this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_SELECT_DESC_PRIVATEROOM'),'',false,'','','left','','id="template_extention"');
            $this->_form->addEmptyline();
            $this->_form->addSubHeadline('header_template_make',$this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_TITLE_MAKE'));
         }
      }

      // template functions
      if ($this->_with_template_form_element) {
         $this->_form->addCheckbox('template',
                                   1,
                                   '',
                                   $this->_translator->getMessage('ROOM_STATUS'),
                                   $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_VALUE'),
                                   $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_DESC'),
                                   '',
                                   ''
                                  );
         $user_array = array();
         $user_array['0']['text'] = $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_AVAILABILITY_ALL_USERS');
         $user_array['0']['value'] = '0';
         $current_context = $this->_environment->getCurrentContextItem();
         if ( !$current_context->isPrivateRoom() ) {
            if ($current_context->isProjectRoom()){
               $community_list = $current_context->getCommunityList();
               if ( $community_list->isNotEmpty() ) {
                  $user_array['1']['text'] = $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_AVAILABILITY_COMMUNITY_ROOM_USERS');
                  $user_array['1']['value'] = '3';
               }
            }
            $user_array['2']['text'] = $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_AVAILABILITY_ROOM_USERS');
            $user_array['2']['value'] = '1';
            $user_array['3']['text'] = $this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_AVAILABILITY_ROOM_MODERATORS');
            $user_array['3']['value'] = '2';
         }
         $this->_form->addSelect('template_availability',
                                 $user_array,
                                 '',
                                 $this->_translator->getMessage('CONFIGURATION_TEMPLATE_GROUP'),
                                 '',0,'','','','','','','','','','');

      }
      $this->_form->addTextfield('template_title','',$this->_translator->getMessage('CONFIGURATION_TEMPLATE_NAME'),'',100,50,false);
      $this->_form->addTextArea('description','',$this->_translator->getMessage('COMMON_TEMPLATE_DESCRIPTION'),'','','10','virtual',false,'');
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   function _prepareValues () {
      $current_context= $this->_environment->getCurrentContextItem();
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
      } else {
         if ($current_context->isTemplate()) {
            $this->_values['template'] = 1;
         }
         if ( $current_context->isCommunityRoom() ){
            $this->_values['template_availability'] = $current_context->getCommunityTemplateAvailability();
         }else{
            $this->_values['template_availability'] = $current_context->getTemplateAvailability();
         }
         $description = $current_context->getTemplateDescription();
         if ( empty($description) ){
#            $this->_values['description'] = $this->_translator->getMessage('COMMON_DEFAULT_TEMPLATE_DESCRIPTION');
         }else{
            $this->_values['description'] = $description;
         }

         // templates in private rooms
         if ( $current_context->isPrivateRoom() ) {
            $this->_values['template_select'] = $current_context->getTemplateID();
            $this->_values['template_title'] = $current_context->getTemplateTitle();
         }
      }
   }
}
?>