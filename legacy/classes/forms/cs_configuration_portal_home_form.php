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
class cs_configuration_portal_home_form extends cs_rubric_form {

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
      $this->_headline = $this->_translator->getMessage('CONFIGURATION_PORTAL_HOME');
      $this->setHeadline($this->_headline);
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      // form fields
#	  $this->_form->addHidden('iid','');
         $radio_values = array();
         $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_SHOW_ANNOUNCEMENT');
         $radio_values[0]['value'] = '1';
         $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_SHOW_ANNOUNCEMENT_NOT');
         $radio_values[1]['value'] = '2';
         $this->_form->addRadioGroup('announcement',$this->_translator->getMessage('CONFIGURATION_ANNOUNCEMENT'),'',$radio_values,'',true,false);

         $radio_values = array();
         $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_NO_SELECTION');
         $radio_values[0]['value'] = '1';
         $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_PRESELECT_COMMUNITY_ROOMS');
         $radio_values[1]['value'] = '2';
         $radio_values[2]['text'] = $this->_translator->getMessage('CONFIGURATION_ONLY_COMMUNITY_ROOMS');
         $radio_values[2]['value'] = '3';
         $radio_values[3]['text'] = $this->_translator->getMessage('CONFIGURATION_ONLY_PROJECT_ROOMS');
         $radio_values[3]['value'] = '4';
         $radio_values[4]['text'] = $this->_translator->getMessage('CONFIGURATION_PRESELECT_MY_ROOMS');
         $radio_values[4]['value'] = '5';
         $this->_form->addRadioGroup('preselection',$this->_translator->getMessage('CONFIGURATION_SELECTION'),'',$radio_values,'',true,false);
         
         // templates in room list
         $this->_form->combine();
         $this->_form->addCheckbox('with_templates', 1, '', '', $this->_translator->getMessage('CONFIGURATION_ROOM_LIST_TEMPLATES'));

         $radio_values = array();
         $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_SORT_ROOMS_ACTIVITY');
         $radio_values[0]['value'] = '1';
         $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_SORT_ROOMS_TITLE');
         $radio_values[1]['value'] = '2';
         $this->_form->addRadioGroup('room_sort',$this->_translator->getMessage('CONFIGURATION_SORT_ROOMS'),'',$radio_values,'',true,false);

         $radio_values = array();
         $radio_values[0]['text'] = 10;
         $radio_values[0]['value'] = 10;
         $radio_values[1]['text'] = 20;
         $radio_values[1]['value'] = 20;
         $radio_values[2]['text'] = 50;
         $radio_values[2]['value'] = 50;
         $radio_values[3]['text'] = $this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL');
         $radio_values[3]['value'] = 'all';
         $this->_form->addRadioGroup('number',$this->_translator->getMessage('CONFIGURATION_NUMBER'),'',$radio_values,'',true,false);

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
         $with_announcements = $room->isShowAnnouncementsOnHome();
         if ($with_announcements){
            $this->_values['announcement'] ='1';
         }else{
            $this->_values['announcement'] ='2';
         }
         $sort_rooms_by_title = $room->isSortRoomsByTitleOnHome();
         if ($sort_rooms_by_title){
            $this->_values['room_sort'] ='2';
         }else{
            $this->_values['room_sort'] ='1';
         }
         $show_only_community_rooms = $room->getShowRoomsOnHome();
         if ($show_only_community_rooms =='preselectcommunityrooms'){
            $this->_values['preselection'] ='2';
         }elseif ($show_only_community_rooms =='onlycommunityrooms'){
            $this->_values['preselection'] ='3';
         }elseif ($show_only_community_rooms =='onlyprojectrooms'){
            $this->_values['preselection'] ='4';
         }elseif ($show_only_community_rooms =='preselectmyrooms'){
            $this->_values['preselection'] ='5';
         }else{
            $this->_values['preselection'] ='1';
         }
         $this->_values['number'] = $room->getNumberRoomsOnHome();
         
         if ($room->showTemplatesInRoomList()) {
         	$this->_values['with_templates'] = 1;
         }
      }
   }

}
?>