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

/** class for CommSy forms
 * this class implements an interface for the creation of forms in the CommSy style
 */
class cs_configuration_common_form extends cs_rubric_form {

   var $_languages = NULL;

   var $_with_logo = NULL;

   var $_image_context_id = NULL;

   var $_mod_array = array();

   var $_iid = NULL;

   var $_type = NULL;

   var $_community_array = array();
   var $_community_room_array = array();

   var $_shown_community_room_array = array();

   var $_with_html_textarea = false;
   var $_with_html_textarea_status = 1;

   var $_time_array2 = array();


   /**
   * array - containing the 2 choices of the public field
   */
   var $_public_array = array();


   /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   function setSessionCommunityRoomArray ($value) {
      $this->_session_community_room_array = (array)$value;
   }
   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {

      if ( isset($this->_item) ) {
         $this->_iid = $this->_item->getItemID();
      } elseif (isset($this->_form_post['iid'])) {
             $this->_iid = $this->_form_post['iid'];
      } else {
             $this->_iid = 'NEW';
      }

      if ( !empty($this->_iid)
           and $this->_iid != 'NEW'
           and $this->_iid != $this->_environment->getCurrentContextID()
         ) {
         $this->_image_context_id = $this->_iid;
      } else {
         $this->_image_context_id = '';
      }

      $this->_headline = $this->_translator->getMessage('INTERNAL_META_TITLE');
      $this->setHeadline($this->_headline);

      if ( isset($this->_item) ) {
         $this->_type = $this->_item->getItemType();
      } elseif (isset($this->_form_post['type'])) {
         $this->_type = $this->_form_post['type'];
      }

      if ( isset($this->_item) ) {
         $this->_with_html_textarea = $this->_item->withHtmlTextArea();
         $this->_with_html_textarea_status = $this->_item->getHtmlTextAreaStatus();
      } elseif (isset($this->_form_post['with_html_textarea'])) {
             $this->_with_html_textarea = $this->_form_post['with_html_textarea'];
             $this->_with_html_textarea_status = $this->_form_post['with_html_textarea_status'];
      }

      if ( isset($this->_item) ) {
         $this->_with_logo = $this->_item->getLogoFilename();
      } elseif (isset($this->_form_post['with_logo'])) {
             $this->_with_logo = $this->_form_post['with_logo'];
      }

      if ($this->_type == CS_PROJECT_TYPE) {
         $community_room_array = array();

         // links to community room
         $current_portal = $this->_environment->getCurrentPortalItem();
         $community_list = $current_portal->getCommunityList();
         $current_user = $this->_environment->getCurrentUserItem();
         $community_room_array = array();
         $temp_array['text'] = '*'.$this->_translator->getMessage('PREFERENCES_NO_COMMUNITY_ROOM');
         $temp_array['value'] = '-1';
         $community_room_array[] = $temp_array;
         $temp_array['text'] = '--------------------';
         $temp_array['value'] = 'disabled';
         $community_room_array[] = $temp_array;
         unset($temp_array);
         if ($community_list->isNotEmpty()) {
            $community_item = $community_list->getFirst();
            while ($community_item) {
               $temp_array = array();
               if ($community_item->isAssignmentOnlyOpenForRoomMembers() ){
                  if ( !$community_item->isUser($current_user)) {
                     $temp_array['text'] = $community_item->getTitle();
                     $temp_array['value'] = 'disabled';
                  }else{
                     $temp_array['text'] = $community_item->getTitle();
                     $temp_array['value'] = $community_item->getItemID();
                  }
               }else{
                  $temp_array['text'] = $community_item->getTitle();
                  $temp_array['value'] = $community_item->getItemID();
               }
               $community_room_array[] = $temp_array;
               unset($temp_array);
               $community_item = $community_list->getNext();
            }
         }

      $this->_community_room_array = $community_room_array;
      $community_room_array = array();

      if (!empty($this->_session_community_room_array)) {

         foreach ( $this->_session_community_room_array as $community_room ) {
            $temp_array['text'] = $community_room['name'];
            $temp_array['value'] = $community_room['id'];
            $community_room_array[] = $temp_array;
         }

         } elseif (isset($this->_item)) {
            $community_room_list = $this->_item->getCommunityList();

            if ($community_room_list->getCount() > 0) {
               $community_room_item = $community_room_list->getFirst();

               while ($community_room_item) {
                  $temp_array['text'] = $community_room_item->getTitle();
                  $temp_array['value'] = $community_room_item->getItemID();
                  $community_room_array[] = $temp_array;
                  $community_room_item = $community_room_list->getNext();
               }
            }
         }

         $this->_shown_community_room_array = $community_room_array;

      }

      if ( $this->_type == CS_PROJECT_TYPE and $this->_environment->inPortal() ) {
         $current_context = $this->_environment->getCurrentContextItem();

         if ($current_context->showTime()) {
            $current_time_title = $current_context->getTitleOfCurrentTime();

            if (isset($this->_item)) {
               $time_list = $this->_item->getTimeList();
               if ($time_list->isNotEmpty()) {
                  $time_item = $time_list->getFirst();
                  $linked_time_title = $time_item->getTitle();
               }
            }

            if ( !empty($linked_time_title)
                 and $linked_time_title < $current_time_title
               ) {
               $start_time_title = $linked_time_title;
            } else {
               $start_time_title = $current_time_title;
            }

            $time_list = $current_context->getTimeList();

            if ($time_list->isNotEmpty()) {
               $time_item = $time_list->getFirst();

               while ($time_item) {
                  if ($time_item->getTitle() >= $start_time_title) {
                     $temp_array = array();
                     $temp_array['text'] = $this->_translator->getTimeMessage($time_item->getTitle());
                     $temp_array['value'] = $time_item->getItemID();
                     $this->_time_array2[] = $temp_array;
                  }
                  $time_item = $time_list->getNext();
               }
            }

            // continuous
            $temp_array = array();
            $temp_array['text'] = $this->_translator->getMessage('COMMON_CONTINUOUS');
            $temp_array['value'] = 'cont';
            $this->_time_array2[] = $temp_array;
            $this->_with_time_array2 = true;
         }
      }

      // languages
      $this->_languages = $this->_environment->getAvailableLanguageArray();

  }  // End of function _initForm (ca. line 80)

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      // form fields
      $this->_form->addHidden('iid',$this->_iid);
      $this->_form->addHidden('type',$this->_type);
      $this->_form->addHidden('with_html_textarea',$this->_with_html_textarea);
      $this->_form->addHidden('with_html_textarea_status',$this->_with_html_textarea_status);

      $this->_form->addTitleField('title','',$this->_translator->getMessage('COMMON_TITLE'),'',50,46,true);

      $this->_form->addImage('logo',
                             '',
                             $this->_translator->getMessage('LOGO_UPLOAD'),
                             $this->_translator->getMessage('LOGO_UPLOAD_DESC'),
                             $this->_image_context_id
                            );

      if ( !empty($this->_with_logo) ) {
         $this->_form->combine();
         $this->_form->addCheckbox('delete_logo',
                                   '',
                                   false,
                                   '',
                                   $this->_translator->getMessage('LOGO_DELETE_OPTION'),
                                   ''
                                  );
      }

      $this->_form->addHidden('with_logo',$this->_with_logo);


      // time
      if (isset($this->_with_time_array2) and $this->_with_time_array2) {
         $this->translatorChangeToPortal();
         $form_element_title = $this->_translator->getMessage('COMMON_TIME_NAME');
         $this->_form->addCheckboxGroup('time2',
                                        $this->_time_array2,
                                        '',
                                        $form_element_title,
                                        '',
                                        '',
                                        true,
                                        2
                                       );
         $this->translatorChangeToCurrentContext();
      }

      // community room in project room
      if ( $this->_type == CS_PROJECT_TYPE and !empty($this->_community_room_array) ) {
         $portal_item = $this->_environment->getCurrentPortalItem();
         $project_room_link_status = $portal_item->getProjectRoomLinkStatus();

         if ($project_room_link_status =='optional'){

            if ( !empty ($this->_shown_community_room_array) ) {
               $this->_form->addCheckBoxGroup('communityroomlist',
                                              $this->_shown_community_room_array,
                                              '',
                                              $this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),
                                              '',
                                              false,
                                              false
                                             );
               $this->_form->combine();
            }
            if(count($this->_community_room_array) > 2){
               $this->_form->addSelect('communityrooms',
                                       $this->_community_room_array,
                                       '',
                                       $this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),
                                       '',
                                       1,
                                       false,
                                       false,
                                       false,
                                       '',
                                       '',
                                       '',
                                       '',
                                       13
                                      );
               $this->_form->combine('horizontal');
               $this->_form->addButton('option',$this->_translator->getMessage('PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON'),'','',180);
            }
         }else{

            if ( !empty ($this->_shown_community_room_array) ) {
               $this->_form->addCheckBoxGroup('communityroomlist',
                                              $this->_shown_community_room_array,
                                              '',
                                              $this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),
                                              '',
                                              false,
                                              false
                                             );
               $this->_form->combine();
            }
            if(count($this->_community_room_array) > 2){
               $this->_form->addSelect('communityrooms',
                                       $this->_community_room_array,
                                       '',
                                       $this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),
                                       '',
                                       1,
                                       false,
                                       true,
                                       false,
                                       '',
                                       '',
                                       '',
                                       '',
                                       13
                                      );
               $this->_form->combine('horizontal');
               $this->_form->addButton('option',
                                       $this->_translator->getMessage('PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON'),
                                       '',
                                       '',
                                       180
                                      );
            }
         }
      }

            if (isset ($this->_item) ){
               $html_status = $this->_item->getHtmlTextAreaStatus();
            }elseif ( $this->_environment->inCommunityRoom() ){
               $context = $this->_environment->getCurrentContextItem();
               $html_status = $context->getHtmlTextAreaStatus();
            }else{
               $portal = $this->_environment->getCurrentPortalItem();
               $html_status = $portal->getHtmlTextAreaStatus();
            }

            if ($html_status =='1'){
               $html_status ='2';
            }

            $this->_form->addTextArea('description',
                                      '',
                                      $this->_translator->getMessage('CONFIGURATION_ROOM_DESCRIPTION'),
                                      '',
                                      '44',
                                      '15',
                                      'virtual',
                                      false,
                                      false,
                                      true,
                                      $html_status
                                     );

      // buttons
      $this->_form->addButtonBar('option',
                                 $this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),
                                 $this->_translator->getMessage('COMMON_CANCEL_BUTTON'),
                                 ''
                                );
   } // End of function _createForm (ca. line 272)

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();

      if (isset($this->_item)) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['title'] = $this->_item->getTitle();
         $this->_values['show_title'] = $this->_item->showTitle();
         $this->_values['logo'] = $this->_item->getLogoFilename();

         $description = $this->_item->getDescription();
         $this->_values['description'] = $description;

         if ($this->_type == 'project') {
            $community_room_array = array();
            $community_room_list = $this->_item->getCommunityList();

            if ($community_room_list->getCount() > 0) {
               $community_room_item = $community_room_list->getFirst();

               while ($community_room_item) {
                  $community_room_array[] = $community_room_item->getItemID();
                  $community_room_item = $community_room_list->getNext();
               }
            }

            if(isset($this->_form_post['communityroomlist'])){
               $this->_values['communityroomlist'] = $this->_form_post['communityroomlist'];
            }else{
               $this->_values['communityroomlist'] = $community_room_array;
            }
         }

         if ( $this->_type == 'project' and $this->_environment->inPortal() ){
            $current_context = $this->_environment->getCurrentContextItem();

            if ($current_context->showTime()) {
               $time_list = $this->_item->getTimeList();
               $mark_array = array();

               if ($time_list->isNotEmpty()) {
                  $time_item = $time_list->getFirst();
                  while ($time_item) {
                     $mark_array[] = $time_item->getItemID();
                     $time_item = $time_list->getNext();
                  }
               }

               if ($this->_item->isContinuous()) {
                  $mark_array[] = 'cont';
               }

               $this->_values['time2'] = $mark_array;
               unset($mark_array);
                }
             }

      } elseif ( isset($this->_form_post) ) {
         $this->_values = $this->_form_post;
      }
   } // End of function _prepareValues (ca. line 569)

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      $portal_item = $this->_environment->getCurrentPortalItem();
      $project_room_link_status = $portal_item->getProjectRoomLinkStatus();

      if ( isset($this->_form_post['communityrooms']) and $project_room_link_status !='optional'){

         if ( ($this->_form_post['communityrooms'] == -1
               or $this->_form_post['communityrooms'] == 'disabled')
               and !isset($this->_form_post['communityroomlist'])
            ){

            $this->_form->setFailure('communityrooms','mandatory');
            $this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_COMMUNITY_ROOM_ENTRY',
                                               $this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS')
                                              );
         }
      }
   }

}
?>