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

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function cs_configuration_room_options($params) {
      $this->cs_rubric_form($params);
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
/*      $community_room_array = array();
      // links to community room
      $current_portal = $this->_environment->getCurrentPortalItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $community_list = $current_portal->getCommunityList();
      $community_room_array = array();
      $temp_array['text'] = '*'.getMessage('PREFERENCES_NO_COMMUNITY_ROOM');
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
      } else{
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
*/

      /**********Logo**********/
      $this->_with_logo = $current_context_item->getLogoFilename();

      /****Beschreibung*****/
      $this->_languages = $this->_environment->getAvailableLanguageArray();
      if (isset($this->_form_post['description_text'])) {
         $this->_description_text = $this->_form_post['description_text'];
      } else{
         $this->_description_text = $current_context_item->getLanguage();
         if ( $this->_description_text == 'user' ) {
            $this->_description_text = 'de';
         }
      }

      /*******Farben********/
      $temp_array = array();
      $temp_array['text']  = getMessage('COMMON_COLOR_DEFAULT');
	   $temp_array['value'] = 'COMMON_COLOR_DEFAULT';
	   $this->_array_info_text[] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = '-----';
	   $temp_array['value'] = '-1';
	   $this->_array_info_text[] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = getMessage('COMMON_COLOR_SCHEMA_1');
	   $temp_array['value'] = 'COMMON_COLOR_SCHEMA_1';
	   $array_info_text_temp[getMessage('COMMON_COLOR_SCHEMA_1')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = getMessage('COMMON_COLOR_SCHEMA_2');
	   $temp_array['value'] = 'COMMON_COLOR_SCHEMA_2';
	   $array_info_text_temp[getMessage('COMMON_COLOR_SCHEMA_2')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = getMessage('COMMON_COLOR_SCHEMA_3');
	   $temp_array['value'] = 'COMMON_COLOR_SCHEMA_3';
	   $array_info_text_temp[getMessage('COMMON_COLOR_SCHEMA_3')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = getMessage('COMMON_COLOR_SCHEMA_4');
	   $temp_array['value'] = 'COMMON_COLOR_SCHEMA_4';
	   $array_info_text_temp[getMessage('COMMON_COLOR_SCHEMA_4')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = getMessage('COMMON_COLOR_SCHEMA_5');
	   $temp_array['value'] = 'COMMON_COLOR_SCHEMA_5';
	   $array_info_text_temp[getMessage('COMMON_COLOR_SCHEMA_5')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = getMessage('COMMON_COLOR_SCHEMA_6');
	   $temp_array['value'] = 'COMMON_COLOR_SCHEMA_6';
	   $array_info_text_temp[getMessage('COMMON_COLOR_SCHEMA_6')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = getMessage('COMMON_COLOR_SCHEMA_7');
	   $temp_array['value'] = 'COMMON_COLOR_SCHEMA_7';
	   $array_info_text_temp[getMessage('COMMON_COLOR_SCHEMA_7')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = getMessage('COMMON_COLOR_SCHEMA_8');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_8';
      $array_info_text_temp[getMessage('COMMON_COLOR_SCHEMA_8')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = getMessage('COMMON_COLOR_SCHEMA_9');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_9';
      $array_info_text_temp[getMessage('COMMON_COLOR_SCHEMA_9')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = getMessage('COMMON_COLOR_SCHEMA_10');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_10';
      $array_info_text_temp[getMessage('COMMON_COLOR_SCHEMA_10')] = $temp_array;

      $temp_array = array();
      $temp_array['text']  = getMessage('COMMON_COLOR_SCHEMA_11');
      $temp_array['value'] = 'COMMON_COLOR_SCHEMA_11';
      $array_info_text_temp[getMessage('COMMON_COLOR_SCHEMA_11')] = $temp_array;

      ksort($array_info_text_temp);
      foreach($array_info_text_temp as $entry){
         $this->_array_info_text[] = $entry;
      }
      $temp_array = array();
      $temp_array['text']  = '-----';
	   $temp_array['value'] = '-1';
	   $this->_array_info_text[] = $temp_array;
      $temp_array = array();
      $temp_array['text']  = getMessage('COMMON_COLOR_SCHEMA_OWN');
	   $temp_array['value'] = 'COMMON_COLOR_SCHEMA_OWN';
	   $this->_array_info_text[] = $temp_array;

   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {
      $this->_form->addTextField('title','',$this->_translator->getMessage('COMMON_ROOM_NAME'),'',60,48,true);

      /********Sprache*******/
      $languageArray = array();
      $zaehler = 0;
      $languageArray[$zaehler]['text']  = getMessage('CONTEXT_LANGUAGE_USER');
      $languageArray[$zaehler]['value'] = 'user';
      $zaehler++;
      $languageArray[$zaehler]['text']  = '-------';
      $languageArray[$zaehler]['value'] = 'disabled';
      $zaehler++;
      $tmpArray = $this->_environment->getAvailableLanguageArray();
      foreach ($tmpArray as $item){
         switch ( strtoupper($item) ){
            case 'DE':
               $languageArray[$zaehler]['text']= getMessage('DE');
               break;
            case 'EN':
               $languageArray[$zaehler]['text']= getMessage('EN');
               break;
            default:
               break;
         }
         $zaehler++;
      }
      $languageArray[$zaehler]['value']= $item;
      $zaehler++;
      $message = getMessage('CONTEXT_LANGUAGE_DESC2');
      $this->_form->addSelect('language',
                              $languageArray,
                              '',
                              getMessage('CONTEXT_LANGUAGE'),
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

      /**********Zuordnung**************/
/*      if ( !empty($this->_community_room_array) ) {
         $portal_item = $this->_environment->getCurrentPortalItem();
         $project_room_link_status = $portal_item->getProjectRoomLinkStatus();
         if ($project_room_link_status =='optional'){
            if ( !empty ($this->_shown_community_room_array) ) {
               $this->_form->addCheckBoxGroup('communityroomlist',$this->_shown_community_room_array,'',getMessage('PREFERENCES_COMMUNITY_ROOMS'),'',false,false);
               $this->_form->combine();
            }
            $this->_form->addSelect('communityrooms',$this->_community_room_array,'',getMessage('PREFERENCES_COMMUNITY_ROOMS'),'', 1, false,false,false,'','','','',16);
            $this->_form->combine('horizontal');
            $this->_form->addButton('option',getMessage('PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON'),'','',100);
         }else{
            if ( !empty ($this->_shown_community_room_array) ) {
               $this->_form->addCheckBoxGroup('communityroomlist',$this->_shown_community_room_array,'',getMessage('PREFERENCES_COMMUNITY_ROOMS'),'',false,false);
               $this->_form->combine();
            }
            $this->_form->addSelect('communityrooms',$this->_community_room_array,'',getMessage('PREFERENCES_COMMUNITY_ROOMS'),'', 1, false,true,false,'','','','',16);
            $this->_form->combine('horizontal');
            $this->_form->addButton('option',getMessage('PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON'),'','',100);
         }
      }*/

      /***************Farben************/
      if ( !empty($this->_form_post['color_choice']) and $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_OWN' ) {
          $this->_form->addEmptyLine();
      }
      $this->_form->addSelect( 'color_choice',
                               $this->_array_info_text,
                               '',
                               getMessage('CONFIGURATION_COLOR_FORM_CHOOSE_TEXT'),
                               '',
                               '',
                               '',
                               '',
                               true,
                               getMessage('COMMON_CHOOSE_BUTTON'),
                               'option',
			                      '',
			                      '',
			                      '16',
                               true);
      if ( !empty($this->_form_post['color_choice']) ) {
         if ( $this->_form_post['color_choice']== 'COMMON_COLOR_DEFAULT' ) {
            $this->_form->combine();
            $desc = '<img src="images/color_schema_default.gif" alt="'.getMessage('COMMON_COLOR_DEFAULT').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']== 'COMMON_COLOR_SCHEMA_1' ) {
            $this->_form->combine();
            $desc = '<img src="images/color_schema_1.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_1').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_3' ) {
            $this->_form->combine();
            $desc = '<img src="images/color_schema_3.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_3').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_2' ) {
            $this->_form->combine();
            $desc = '<img src="images/color_schema_2.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_2').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_4' ) {
            $this->_form->combine();
            $desc = '<img src="images/color_schema_4.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_4').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_5' ) {
            $this->_form->combine();
            $desc = '<img src="images/color_schema_5.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_5').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_6' ) {
            $this->_form->combine();
            $desc = '<img src="images/color_schema_6.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_6').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_7' ) {
            $this->_form->combine();
            $desc = '<img src="images/color_schema_7.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_7').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_8' ) {
            $this->_form->combine();
            $desc = '<img src="images/color_schema_8.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_8').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_9' ) {
            $this->_form->combine();
            $desc = '<img src="images/color_schema_9.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_9').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_10' ) {
            $this->_form->combine();
            $desc = '<img src="images/color_schema_10.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_10').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_11' ) {
            $this->_form->combine();
            $desc = '<img src="images/color_schema_11.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_11').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_OWN' ) {
            $this->_form->addTextField('color_1','',getMessage('COMMON_COLOR_1'),'','',10);
            $this->_form->addTextField('color_2','',getMessage('COMMON_COLOR_2'),'','',10);
	         $this->_form->addHidden('color_3','');
	         $this->_form->addHidden('color_4','');
            $this->_form->addTextField('color_5','',getMessage('COMMON_COLOR_4'),'','',10);
            $this->_form->addTextField('color_9','',getMessage('COMMON_COLOR_9'),'','',10);
            $this->_form->addTextField('color_8','',getMessage('COMMON_COLOR_8'),'','',10);
            $this->_form->addTextField('color_6','',getMessage('COMMON_COLOR_6'),'','',10);
	         $this->_form->addHidden('color_7','');
            $this->_form->addTextField('color_10','',getMessage('COMMON_COLOR_10'),'','',10);
            $this->_form->addTextField('color_11','',getMessage('COMMON_COLOR_11'),'','',10);
	         $this->_form->addHidden('color_12','');
            $this->_form->addTextField('color_13','',getMessage('COMMON_COLOR_13'),'','',10);
            $this->_form->addTextField('color_14','',getMessage('COMMON_COLOR_14'),'','',10);
            $this->_form->addTextField('color_15','',getMessage('COMMON_COLOR_15'),'','',10);
            $this->_form->addTextField('color_16','',getMessage('COMMON_COLOR_16'),'','',10);
            $this->_form->addEmptyLine();
         }
      } else{
         $this->_form->combine();
         $context_item = $this->_environment->getCurrentContextItem();
         $color = $context_item->getColorArray();
         if ( $color['schema']== 'DEFAULT' ) {
            $desc = '<img src="images/color_schema_default.gif" alt="'.getMessage('COMMON_COLOR_DEFAULT').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ($color['schema']== 'SCHEMA_1' ) {
            $desc = '<img src="images/color_schema_1.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_1').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_3' ) {
            $desc = '<img src="images/color_schema_3.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_3').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_2' ) {
            $desc = '<img src="images/color_schema_2.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_2').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_4' ) {
            $desc = '<img src="images/color_schema_4.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_4').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_5' ) {
            $desc = '<img src="images/color_schema_5.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_5').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_6' ) {
            $desc = '<img src="images/color_schema_6.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_6').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_7' ) {
            $desc = '<img src="images/color_schema_7.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_7').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_8' ) {
            $desc = '<img src="images/color_schema_8.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_8').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_9' ) {
            $desc = '<img src="images/color_schema_9.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_9').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_10' ) {
            $desc = '<img src="images/color_schema_10.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_10').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_11' ) {
            $desc = '<img src="images/color_schema_11.gif" alt="'.getMessage('COMMON_COLOR_SCHEMA_11').'" style=" width:200px; border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_OWN' ) {
            $this->_form->addEmptyLine();
            $this->_form->addTextField('color_1','',getMessage('COMMON_COLOR_1'),'','',10);
            $this->_form->addTextField('color_2','',getMessage('COMMON_COLOR_2'),'','',10);
	         $this->_form->addHidden('color_3','');
	         $this->_form->addHidden('color_4','');
            $this->_form->addTextField('color_5','',getMessage('COMMON_COLOR_5'),'','',10);
            $this->_form->addEmptyLine();
            $this->_form->addTextField('color_9','',getMessage('COMMON_COLOR_9'),'','',10);
            $this->_form->addTextField('color_8','',getMessage('COMMON_COLOR_8'),'','',10);
            $this->_form->addTextField('color_6','',getMessage('COMMON_COLOR_6'),'','',10);
	         $this->_form->addHidden('color_7','');
            $this->_form->addEmptyLine();
            $this->_form->addTextField('color_10','',getMessage('COMMON_COLOR_10'),'','',10);
            $this->_form->addTextField('color_11','',getMessage('COMMON_COLOR_11'),'','',10);
	         $this->_form->addHidden('color_12','');
            $this->_form->addEmptyLine();
            $this->_form->addTextField('color_13','',getMessage('COMMON_COLOR_13'),'','',10);
            $this->_form->addTextField('color_14','',getMessage('COMMON_COLOR_14'),'','',10);
            $this->_form->addTextField('color_15','',getMessage('COMMON_COLOR_15'),'','',10);
            $this->_form->addTextField('color_16','',getMessage('COMMON_COLOR_16'),'','',10);
         }

      }

      /*****Beschreibung****/
      $languageArray = array();
      $tmpArray = $this->_environment->getAvailableLanguageArray();
      $zaehler = 0;
      foreach ($tmpArray as $item){
         switch ( strtoupper($item) ){
            case 'DE':
               $languageArray[$zaehler]['text']= getMessage('DE');
               break;
            case 'EN':
               $languageArray[$zaehler]['text']= getMessage('EN');
               break;
            default:
               break;
         }
         $languageArray[$zaehler]['value']= $item;
         $zaehler++;
      }
      $this->_form->addSelect( 'description_text',
                               $languageArray,
                               '',
                               getMessage('CONFIGURATION_CHOOSE_LANGUAGE'),
                               '',
                               '',
                               '',
                               '',
                               true,
                               getMessage('COMMON_LANGUAGE_CHOOSE_BUTTON'),
                               'option','','','16',true);

      $this->_form->combine();
      $context_item = $this->_environment->getCurrentContextItem();
      foreach ($this->_languages as $language) {
         if ($language == $this->_description_text){
            $html_status = $context_item->getHtmlTextAreaStatus();
            if ($html_status =='1'){
               $html_status ='2';
            }
            $this->_form->addTextArea('description_'.$language,'','','','','5','virtual',false,false,true,$html_status);
         } else {
            $this->_form->addHidden('description_'.$language,'');
         }
      }

      /******** buttons***********/
      $this->_form->addButtonBar('option',getMessage('PREFERENCES_SAVE_BUTTON'),'');

   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $context_item = $this->_environment->getCurrentContextItem();

      $color = $context_item->getColorArray();
      $this->_values = array();
      $temp_array = array();
      $temp_array['color_1'] = $color['tabs_background'];
      $temp_array['color_2'] = $color['tabs_focus'];
      $temp_array['color_3'] = $color['table_background'];
      $temp_array['color_4'] = $color['tabs_title'];
      $temp_array['color_5'] = $color['headline_text'];
      $temp_array['color_6'] = $color['hyperlink'];
      $temp_array['color_7'] = $color['help_background'];
      $temp_array['color_8'] = $color['boxes_background'];
      $temp_array['color_9'] = $color['content_background'];
      $temp_array['color_10'] = $color['list_entry_odd'];
      $temp_array['color_11'] = $color['list_entry_even'];
      $temp_array['color_12'] = $color['index_td_head_title'];
      $temp_array['color_13'] = $color['date_title'];
      $temp_array['color_14'] = $color['info_color'];
      $temp_array['color_15'] = $color['disabled'];
      $temp_array['color_16'] = $color['warning'];
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
         if (empty($this->_values['color_choice'])){
            $this->_values['color_choice'] = 'COMMON_COLOR_'.strtoupper($color['schema']);
         }
         if ($this->_values['color_choice']=='COMMON_COLOR_SCHEMA_OWN'){
            for ($i=1; $i<17; $i++){
               if ( !empty($this->_form_post['color_'.$i]) ){
                  $this->_values['color_'.$i] = $this->_form_post['color_'.$i];
               }else{
                  $this->_values['color_'.$i] = $temp_array['color_'.$i];
               }
            }
         }
       } else {
         $this->_values['color_choice'] = 'COMMON_COLOR_'.strtoupper($color['schema']);
         if ($this->_values['color_choice']=='COMMON_COLOR_SCHEMA_OWN'){
            $this->_values['color_1'] = $color['tabs_background'];
            $this->_values['color_2'] = $color['tabs_focus'];
            $this->_values['color_3'] = $color['table_background'];
            $this->_values['color_4'] = $color['tabs_title'];
            $this->_values['color_5'] = $color['headline_text'];
            $this->_values['color_6'] = $color['hyperlink'];
            $this->_values['color_7'] = $color['help_background'];
            $this->_values['color_8'] = $color['boxes_background'];
            $this->_values['color_9'] = $color['content_background'];
            $this->_values['color_10'] = $color['list_entry_odd'];
            $this->_values['color_11'] = $color['list_entry_even'];
            $this->_values['color_12'] = $color['index_td_head_title'];
            $this->_values['color_13'] = $color['date_title'];
            $this->_values['color_14'] = $color['info_color'];
            $this->_values['color_15'] = $color['disabled'];
            $this->_values['color_16'] = $color['warning'];
         }
         $this->_values['title'] = $context_item->getTitle();
      }
      if ($context_item->getLogoFilename()){
         $this->_values['logo'] = $context_item->getLogoFilename();
      }

      $this->_values['language'] = $context_item->getLanguage();
/*      $community_room_array = array();
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
      }*/

      $description_array = $context_item->getDescriptionArray();
      $languages = $this->_environment->getAvailableLanguageArray();
      foreach ($languages as $language) {
         if (!empty($description_array[cs_strtoupper($language)])) {
            $this->_values['description_'.$language] = $description_array[cs_strtoupper($language)];
         } else {
            $this->_values['description_'.$language] = '';
         }
      }

   }


}
?>