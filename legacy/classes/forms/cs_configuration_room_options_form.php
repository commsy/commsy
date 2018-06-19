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

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_room_options_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
  var $_headline = NULL;
  var $_array_or_color_arrays = array();
  var $_with_logo = NULL;
  var $_community_array = array();
  var $_community_room_array = array();
  var $_shown_community_room_array = array();
  var $_session_community_room_array = array();
  var $_with_bg_image = false;
  var $_time_array2 = array();
  var $_with_time_array2 = false;

  private $_with_template_form_element = false;

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function cs_configuration_room_options($params) {
      cs_rubric_form::__construct($params);
   }

   function setCurrentColor($color){
      $this->_current_color = $color;
   }

   function setCurrentRubric($rubric){
      $this->_current_rubric = $rubric;
   }

   function setColorArray($color_array){
      $this->_color_array = $color_array;
   }

   function setSessionCommunityRoomArray ($value) {
      $this->_session_community_room_array = (array)$value;
   }


   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {

      $current_context_item = $this->_environment->getCurrentContextItem();

      /********Zuordnung********/
      $community_room_array = array();
      // links to community room
      $current_portal = $this->_environment->getCurrentPortalItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $community_list = $current_portal->getCommunityList();
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

      if ($this->_environment->inProjectRoom()){
         if (!empty($this->_session_community_room_array)) {
            foreach ( $this->_session_community_room_array as $community_room ) {
               $temp_array['text'] = $community_room['name'];
               $temp_array['value'] = $community_room['id'];
               $community_room_array[] = $temp_array;
            }
         } else{
            $community_room_list = $current_context_item->getCommunityList();
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


      /**********Logo**********/
      $this->_with_logo = $current_context_item->getLogoFilename();
      $this->_with_bg_image = $current_context_item->getBGImageFilename();


      /****Zeittakte*****/
      // time pulses
      $current_context = $this->_environment->getCurrentContextItem();
      $current_portal  = $this->_environment->getCurrentPortalItem();
      if (
            ( $current_context->isProjectRoom() and $this->_environment->inProjectRoom() )
            or ( $current_context->isProjectRoom()
                 and $this->_environment->inCommunityRoom()
                 and $current_context->showTime()
               )
            or ( $this->_environment->getCurrentModule() == CS_PROJECT_TYPE
                 and ( $this->_environment->inCommunityRoom() or $this->_environment->inPortal() )
                 and $current_context->showTime()
               )
            or ( $this->_environment->inGroupRoom()
                 and $current_portal->showTime()
               )
         ) {
         if ( $this->_environment->inPortal() ) {
            $portal_item = $current_context;
         } else {
            $portal_item = $current_context->getContextItem();
         }
         if ($portal_item->showTime()) {
                     $current_time_title = $portal_item->getTitleOfCurrentTime();
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
                     $time_list = $portal_item->getTimeList();
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

      /*******Farben********/
      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_DEFAULT');
      $temp_array['value'] = 'COMMON_COLOR_DEFAULT';
      $this->_array_info_text[] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = '-----';
      $temp_array['value'] = '-1';
      $this->_array_info_text[] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_1');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_1';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_1')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_2');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_2';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_2')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_3');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_3';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_3')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_4');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_4';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_4')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_5');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_5';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_5')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_6');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_6';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_6')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_7');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_7';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_7')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_8');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_8';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_8')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_9');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_9';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_9')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_10');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_10';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_10')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_11');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_11';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_11')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_12');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_12';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_12')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_13');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_13';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_13')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_14');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_14';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_14')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_15');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_15';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_15')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_16');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_16';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_16')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_17');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_17';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_17')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_18');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_18';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_18')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_19');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_19';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_19')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_20');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_20';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_20')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_21');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_21';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_21')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_22');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_22';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_22')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_23');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_23';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_23')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_24');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_24';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_24')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_25');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_25';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_25')] = $temp_array;
      
      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_26');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_26';
      $array_info_text_temp[$this->_translator->getMessage('COMMON_COLOR_SCHEMA_26')] = $temp_array;

      ksort($array_info_text_temp);
      foreach($array_info_text_temp as $entry){
         $this->_array_info_text[] = $entry;
      }
      $temp_array = array();
      $temp_array['text']  = '-----';
      $temp_array['value'] = '-1';
      $this->_array_info_text[] = $temp_array;
      $temp_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_COLOR_SCHEMA_OWN');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_OWN';
      $this->_array_info_text[] = $temp_array;
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $this->_form->addTextField('title','',$this->_translator->getMessage('COMMON_ROOM_NAME'),'',60,48,true);
      $this->_form->combine('vertical');
      $this->_form->addCheckbox('show_title','yes','',$this->_translator->getMessage('COMMON_TITLE'),$this->_translator->getMessage('PREFERENCES_SHOW_TITLE_OPTION'),'');

      // specials in private room
      if ( $this->_environment->inPrivateRoom() ) {
         $this->_form->combine();
         $this->_form->addCheckbox('title_reset',
                                   'value',
                                   false,
                                   '',
                                   $this->_translator->getMessage('COMMON_ROOM_NAME_RESET',$this->_translator->getMessage('COMMON_PRIVATEROOM')),
                                   ''
                                  );
      }

      /********Sprache*******/
      $languageArray = array();
      $zaehler = 0;
      $languageArray[$zaehler]['text']  = $this->_translator->getMessage('CONTEXT_LANGUAGE_USER');
      $languageArray[$zaehler]['value'] = 'user';
      $zaehler++;
      $languageArray[$zaehler]['text']  = '-------';
      $languageArray[$zaehler]['value'] = 'disabled';
      $zaehler++;
      $tmpArray = $this->_environment->getAvailableLanguageArray();
      foreach ($tmpArray as $item){
         switch ( mb_strtoupper($item, 'UTF-8') ){
            case 'DE':
               $languageArray[$zaehler]['text']= $this->_translator->getMessage('DE');
               break;
            case 'EN':
               $languageArray[$zaehler]['text']= $this->_translator->getMessage('EN');
               break;
            default:
               break;
         }
         $languageArray[$zaehler]['value']= $item;
         $zaehler++;
      }
      $zaehler++;
      $message = $this->_translator->getMessage('CONTEXT_LANGUAGE_DESC2');
      $this->_form->addSelect('language',
                              $languageArray,
                              '',
                              $this->_translator->getMessage('CONTEXT_LANGUAGE'),
                              $message,
                              0,
                              false,
                              true,
                              false,
                              '',
                              '',
                              '',
                              '',
                              '16',
                              true
                             );

      /********Logo*******/
      $this->_form->addRoomLogo('logo',
                             '',
                             $this->_translator->getMessage('LOGO_UPLOAD'),
                             $this->_translator->getMessage('LOGO_UPLOAD_DESC'),
                             '',
                             false,
                             '4em'
                             );
      $this->_form->addHidden('logo_hidden','');
      $this->_form->addHidden('with_logo',$this->_with_logo);

      /**********Zeittakte**************/
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




      /**********Zuordnung**************/
      if ($this->_environment->inProjectRoom()){
         if ( !empty($this->_community_room_array) ) {
            $portal_item = $this->_environment->getCurrentPortalItem();
            $project_room_link_status = $portal_item->getProjectRoomLinkStatus();
            if ($project_room_link_status =='optional'){
               if ( !empty ($this->_shown_community_room_array) ) {
                  $this->_form->addCheckBoxGroup('communityroomlist',$this->_shown_community_room_array,'',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),'',false,false);
                  $this->_form->combine();
               }
               if(count($this->_community_room_array) > 2){
                  $this->_form->addSelect('communityrooms',$this->_community_room_array,'',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),'', 1, false,false,false,'','','','',16);
                  $this->_form->combine('horizontal');
                  $this->_form->addButton('option',$this->_translator->getMessage('PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON'),'','',100);
               }
            }else{
               if ( !empty ($this->_shown_community_room_array) ) {
                  $this->_form->addCheckBoxGroup('communityroomlist',$this->_shown_community_room_array,'',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),'',false,false);
                  $this->_form->combine();
               }
               if(count($this->_community_room_array) > 2){
                  $this->_form->addSelect('communityrooms',$this->_community_room_array,'',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),'', 1, false,true,false,'','','','',16);
                  $this->_form->combine('horizontal');
                  $this->_form->addButton('option',$this->_translator->getMessage('PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON'),'','',100);
               }
            }
         }
      }elseif($this->_environment->inCommunityRoom()){
         $radio_values = array();
         $radio_values[0]['text'] = $this->_translator->getMessage('COMMON_ASSIGMENT_ON');
         $radio_values[0]['value'] = 'open';
         $radio_values[1]['text'] = $this->_translator->getMessage('COMMON_ASSIGMENT_OFF');
         $radio_values[1]['value'] = 'closed';
         $this->_form->addRadioGroup('room_assignment',
                                     $this->_translator->getMessage('PREFERENCES_ROOM_ASSIGMENT'),
                                     $this->_translator->getMessage('PREFERENCES_ASSIGMENT_OPEN_FOR_GUESTS_DESC'),
                                     $radio_values,
                                     '',
                                     true,
                                     false
                                    );
         unset($radio_values);
      }

      $languageArray = array();
      $tmpArray = $this->_environment->getAvailableLanguageArray();
      $zaehler = 0;
      foreach ($tmpArray as $item){
         switch ( mb_strtoupper($item, 'UTF-8') ){
            case 'DE':
               $languageArray[$zaehler]['text']= $this->_translator->getMessage('DE');
               break;
            case 'EN':
               $languageArray[$zaehler]['text']= $this->_translator->getMessage('EN');
               break;
            default:
               break;
         }
         $languageArray[$zaehler]['value']= $item;
         $zaehler++;
      }
      $context_item = $this->_environment->getCurrentContextItem();
      $html_status = $context_item->getHtmlTextAreaStatus();
      if ($html_status =='1'){
          $html_status ='2';
      }
      $this->_form->addTextArea('description','',$this->_translator->getMessage('CONFIGURATION_ROOM_DESCRIPTION'),'','','5','virtual',false,false,true,$html_status);

      $radio_values = array();
      $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_RSS_YES');
      $radio_values[0]['value'] = 'yes';
      $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_RSS_NO');
      $radio_values[1]['value'] = 'no';
      $this->_form->addRadioGroup('rss',$this->_translator->getMessage('CONFIGURATION_RSS'),'',$radio_values,'',true,false);

      // specials in private room - E-Mail to CommSy
      global $c_email_upload;
      if ($c_email_upload && $this->_environment->inPrivateRoom()) {
      	global $c_email_upload_email_account;
         $this->_form->addCheckbox('email_to_commsy',
                                   'value',
                                   false,
                                   $this->_translator->getMessage('PRIVATE_ROOM_EMAIL_TO_COMMSY'),
                                   $this->_translator->getMessage('PRIVATE_ROOM_EMAIL_TO_COMMSY_CHECKBOX', $c_email_upload_email_account)
                                   );
         $this->_form->combine();
         $this->_form->addTextField('email_to_commsy_secret','',$this->_translator->getMessage('PRIVATE_ROOM_EMAIL_TO_COMMSY_SECRET'),'',60,48);
         $this->_form->combine();
         $this->_form->addText('email_to_commsy_text','',$this->_translator->getMessage('PRIVATE_ROOM_EMAIL_TO_COMMSY_TEXT', $this->_translator->getMessage('EMAIL_TO_COMMSY_PASSWORD'), $this->_translator->getMessage('EMAIL_TO_COMMSY_ACCOUNT')));
      }
      
      /******** buttons***********/
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'',$this->_translator->getMessage('COMMON_DELETE_ROOM'));

   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $context_item = $this->_environment->getCurrentContextItem();

      $this->_values = array();
      $color = $context_item->getColorArray();
      $temp_array = array();
      $temp_array['color_1'] = $color['tabs_background'];
      $temp_array['color_2'] = $color['tabs_focus'];
      $temp_array['color_3'] = $color['tabs_title'];
      $temp_array['color_31'] = $color['tabs_separators'];
      $temp_array['color_32'] = $color['tabs_dash'];
      $temp_array['color_4'] = $color['content_background'];
      $temp_array['color_5'] = $color['boxes_background'];
      $temp_array['color_6'] = $color['hyperlink'];
      $temp_array['color_7'] = $color['list_entry_even'];
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
         if (empty($this->_values['color_choice'])){
            $this->_values['color_choice'] = 'COMMON_COLOR_'.mb_strtoupper($color['schema'], 'UTF-8');
         }
         if ($this->_values['color_choice']=='COMMON_COLOR_SCHEMA_OWN'){
            for ($i=1; $i<8; $i++){
               if ( !empty($this->_form_post['color_'.$i]) ){
                  $this->_values['color_'.$i] = $this->_form_post['color_'.$i];
               }else{
                  $this->_values['color_'.$i] = $temp_array['color_'.$i];
               }
            }
            if(!empty($this->_form_post['color_31'])) {
               $this->_values['color_31'] = $ths->_form_post['color_31'];
            } else {
               $this->_values['color_31'] = $temp_array['color_31'];
            }
            if(!empty($this->_form_post['color_32'])) {
               $this->_values['color_32'] = $ths->_form_post['color_32'];
            } else {
               $this->_values['color_32'] = $temp_array['color_32'];
            }
         }
      } else {
         $color_array = $context_item->getColorArray();
         $this->_values['color_choice'] = 'COMMON_COLOR_'.mb_strtoupper($color['schema'], 'UTF-8');
         $this->_values['color_1'] = $color['tabs_background'];
         $this->_values['color_2'] = $color['tabs_focus'];
         $this->_values['color_3'] = $color['tabs_title'];
         $this->_values['color_31'] = $color['tabs_separators'];
         $this->_values['color_32'] = $color['tabs_dash'];
         $this->_values['color_5'] = $color['boxes_background'];
         $this->_values['color_7'] = $color['list_entry_even'];
         $this->_values['color_6'] = $color['hyperlink'];
         $this->_values['color_4'] = $color['content_background'];
         $this->_values['title'] = $context_item->getTitle();
         $this->_values['show_title'] = $context_item->showTitle();
         if ( $context_item->isPrivateRoom() ) {
            if ( $context_item->getTitle() == 'PRIVATEROOM' ) {
               $this->_values['title'] = $this->_translator->getMessage('COMMON_PRIVATEROOM');
            } elseif ( $context_item->isTemplate() ) {
               $this->_values['title'] = $context_item->getTitlePure();
            }
         }
         if ($context_item->isAssignmentOnlyOpenForRoomMembers()) {
            $this->_values['room_assignment'] = 'closed';
         } else {
            $this->_values['room_assignment'] = 'open';
         }
      }
      if ($context_item->getLogoFilename()){
         $this->_values['logo'] = $context_item->getLogoFilename();
      }
      if ($context_item->isRSSOn()) {
         $this->_values['rss'] = 'yes';
      } else {
         $this->_values['rss'] = 'no';
      }
      if ($context_item->getBGImageFilename()){
         $this->_values['bgimage'] = $context_item->getBGImageFilename();
      }
      if ($context_item->issetBGImageRepeat()){
         $this->_values['bg_image_repeat'] = '1';
      }
      $this->_values['language'] = $context_item->getLanguage();

      if (
            ( $context_item->isA(CS_PROJECT_TYPE) and $this->_environment->inProjectRoom() )
            or ( $context_item->isA(CS_PROJECT_TYPE) and $this->_environment->inCommunityRoom() )
            or ( $context_item->isA(CS_GROUPROOM_TYPE) and $this->_environment->inGroupRoom() )
         ) {
         $portal_item = $this->_environment->getCurrentPortalItem();
         if ( $portal_item->showTime() ) {
            $time_list = $context_item->getTimeList();
            $mark_array = array();
            if ( $time_list->isNotEmpty() ) {
               $time_item = $time_list->getFirst();
               while ($time_item) {
                  $mark_array[] = $time_item->getItemID();
                  $time_item = $time_list->getNext();
               }
               if ($context_item->isContinuous()) {
                  $mark_array[] = 'cont';
               }
               $this->_values['time2'] = $mark_array;
               unset($mark_array);
            }
         }
      }

      if ($this->_environment->inProjectRoom()){
         $community_room_array = array();
         if (!empty($this->_session_community_room_array)) {
            foreach ( $this->_session_community_room_array as $community_room ) {
               $community_room_array[] = $community_room['id'];
            }
         }
         $community_room_list = $context_item->getCommunityList();
         if ($community_room_list->getCount() > 0) {
            $community_room_item = $community_room_list->getFirst();
            while ($community_room_item) {
               $community_room_array[] = $community_room_item->getItemID();
               $community_room_item = $community_room_list->getNext();
            }
         }
         if ( isset($this->_form_post['communityroomlist']) ) {
            $this->_values['communityroomlist'] = $this->_form_post['communityroomlist'];
         } else {
            $this->_values['communityroomlist'] = $community_room_array;
         }
      }

      $this->_values['description'] = $context_item->getDescription();
      
      global $c_email_upload;
      if ($c_email_upload && $this->_environment->inPrivateRoom()) {
         if ( isset($this->_form_post['email_to_commsy']) ) {
            $this->_values['email_to_commsy'] = $this->_form_post['email_to_commsy'];
         } else {
            $this->_values['email_to_commsy'] = $context_item->getEmailToCommSy();
         }
         
         if ( isset($this->_form_post['email_to_commsy_secret']) ) {
            $this->_values['email_to_commsy_secret'] = $this->_form_post['email_to_commsy_secret'];
         } else {
            $this->_values['email_to_commsy_secret'] = $context_item->getEmailToCommSySecret();
         }
      }
   }

   function _checkValues () {
      $portal_item = $this->_environment->getCurrentPortalItem();
      if (isset($portal_item) ) {
         $project_room_link_status = $portal_item->getProjectRoomLinkStatus();
         if ( isset($this->_form_post['communityrooms']) and $project_room_link_status !='optional'){
            if ( ($this->_form_post['communityrooms'] == -1 or $this->_form_post['communityrooms'] == 'disabled' or $this->_form_post['communityrooms']=='--------------------') and !isset($this->_form_post['communityroomlist']) ){
               $this->_form->setFailure('communityrooms','mandatory');
               $this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_COMMUNITY_ROOM_ENTRY',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'));
            }
         }
      }
      
      if ($this->_environment->inPrivateRoom()) {
      	if (isset($this->_form_post['email_to_commsy']) and empty($this->_form_post['email_to_commsy_secret'])){
	         $this->_form->setFailure('email_to_commsy_secret','');
            $this->_error_array[] = $this->_translator->getMessage('PRIVATE_ROOM_EMAIL_TO_COMMSY_NO_SECRET',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'));
         }
      }
      
   }

   function getInfoForHeaderAsHTML () {
      $retour  = '';
      if ( $this->_environment->inPrivateRoom()
           and $this->_with_template_form_element
         ) {
         #$retour .= '      <!--'.LF;
         $retour .= '   var template_array = new Array();'.LF;
         $retour .= '   initToggleTemplate('.$this->_environment->getCurrentContextItem()->getItemID().');'.LF;
         $retour .= '   template_array['.'-1'.'] = "'.$this->_translator->getMessage('CONFIGURATION_TEMPLATE_FORM_SELECT_DESC_PRIVATEROOM').'"'.LF;
         foreach($this->_javascript_array as $key => $value){
            if ( empty($value) ) {
               $retour .= '   template_array['.$key.'] = "<img id=\"toggle'.$this->_environment->getCurrentContextItem()->getItemID().'\" src=\"images\/more.gif\"\/>&nbsp;<span class=\"template_description\">'.$this->_translator->getMessage('COMMON_TEMPLATE_DESCRIPTION').'<\/span><div id=\"template_information_box\">'.$this->_translator->getMessage('COMMON_NO_DESCRIPTION').'<\/div>"'.LF;
            } else {
               if ( $key > 0 ) {
                  $room_manager = $this->_environment->getPrivateRoomManager();
                  $room_item = $room_manager->getItem($key);
                  if ( !empty($room_item) ) {
                     if ( $room_item->withHTMLTextArea() ) {
                        $value = str_replace(LF,'',$value);
                     } else {
                        $value = str_replace(LF,BR,$value);
                     }
                  }
               }
               $value = str_replace('/','\/',$value);
               $value = str_replace('"','\"',$value);
               $retour .= '   template_array['.$key.'] = "<img id=\"toggle'.$this->_environment->getCurrentContextItem()->getItemID().'\" src=\"images\/more.gif\"\/>&nbsp;<span class=\"template_description\">'.$this->_translator->getMessage('COMMON_TEMPLATE_DESCRIPTION').'<\/span><div id=\"template_information_box\">'.$value.'<\/div>"'.LF;
            }
         }
         #$retour .= '      -->'.LF;
      }
      return $retour;
   }
}
?>