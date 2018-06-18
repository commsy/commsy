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
include_once('functions/text_functions.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_home_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_optionArray = array();

   var $_rubricArray = array();
   var $_awareness_array = array();

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

      // headline
      $this->setHeadline($this->_translator->getMessage('CONFIGURATION_ROOM_HOME'));

      if (!$this->_environment->inPrivateRoom() ){
        $room = $this->_environment->getCurrentContextItem();

          $default_rubrics = $room->getAvailableRubrics();
          $rubric_array = array();
          foreach ($default_rubrics as $rubric) {
                 if ($rubric != 'chat'){
                    $temp_array = array();
                    $temp_array['text']  = $rubric;
                $temp_array['value'] = $rubric;
                    $rubric_array[] = $temp_array;
                 unset($temp_array);
                 }
          }
         $this->_rubricArray = $rubric_array;

          $view_mod_array[0]['text'] = $this->_translator->getMessage('MODULE_CONFIG_SHORT');
          $view_mod_array[0]['value'] = 'short';
          $view_mod_array[1]['text'] = $this->_translator->getMessage('MODULE_CONFIG_TINY');
          $view_mod_array[1]['value'] = 'tiny';
          $view_mod_array[2]['text'] = $this->_translator->getMessage('MODULE_CONFIG_NO');
          $view_mod_array[2]['value'] = 'nodisplay';
          $this->_optionArray = $view_mod_array;

          $view_mod_array2[0]['text'] = $this->_translator->getMessage('MODULE_CONFIG_SHORT');
          $view_mod_array2[0]['value'] = 'short';
          $view_mod_array2[1]['text'] = $this->_translator->getMessage('MODULE_CONFIG_TINY');
          $view_mod_array2[1]['value'] = 'tiny';
          $this->_optionArray2 = $view_mod_array2;

        }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

           if ( !$this->_environment->inPrivateRoom() ){
             for ( $i = 0; $i < count($this->_rubricArray); $i++ ) {
                $tempMessage = '';
                switch (mb_strtoupper($this->_rubricArray[$i]['text'], 'UTF-8')){
                   case 'ANNOUNCEMENT':
                      $tempMessage = $this->_translator->getMessage('ANNOUNCEMENT_INDEX');
                      break;
                   case 'DATE':
                      $tempMessage = $this->_translator->getMessage('DATE_INDEX');
                      break;
                   case 'DISCUSSION':
                      $tempMessage = $this->_translator->getMessage('DISCUSSION_INDEX');
                      break;
                   case 'GROUP':
                      $tempMessage = $this->_translator->getMessage('GROUP_INDEX');
                      break;
                   case 'INSTITUTION':
                      $tempMessage = $this->_translator->getMessage('INSTITUTION_INDEX');
                      break;
                   case 'MATERIAL':
                      $tempMessage = $this->_translator->getMessage('MATERIAL_INDEX');
                      break;
                   case 'PROJECT':
                      $tempMessage = $this->_translator->getMessage('PROJECT_INDEX');
                      break;
                   case 'TODO':
                      $tempMessage = $this->_translator->getMessage('TODO_INDEX');
                      break;
                   case 'TOPIC':
                      $tempMessage = $this->_translator->getMessage('TOPIC_INDEX');
                      break;
                   case 'USER':
                      $tempMessage = $this->_translator->getMessage('USER_INDEX');
                      break;
                   default:
                      $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR' . ' cs_configuration_home_form(139)');
                      break;
                }
               $this->_form->addSelect($this->_rubricArray[$i]['text'],
                                            $this->_optionArray,
                                            '',
                                            $tempMessage,
                                            ''
                               );
             }
             if ($this->_environment->inProjectRoom() or $this->_environment->inGroupRoom()){
                 $this->_form->addTextField('time_spread',
                                    '',
                                    $this->_translator->getMessage('INTERNAL_TIME_SPREAD'),
                                    $this->_translator->getMessage('INTERNAL_TIME_SPREAD_DESC'),
                                    '2',
                                    '3',
                                    true
                                   );
             }
             $this->_form->addSelect('activity',
                                            $this->_optionArray,
                                            '',
                                            $this->_translator->getMessage('COMMON_ACTIVITY'),
                                            ''
                               );
             $this->_form->addSelect('actions',
                                            $this->_optionArray,
                                            '',
                                            $this->_translator->getMessage('COMMON_ACTIONS'),
                                            ''
                               );
              $this->_form->addSelect('search',
                                            $this->_optionArray,
                                            '',
                                            $this->_translator->getMessage('COMMON_SEARCH'),
                                            ''
                               );
              $current_context = $this->_environment->getCurrentContextItem();
              if ( $current_context->withBuzzwords() ){
                 $this->_form->addSelect('buzzwords',
                                            $this->_optionArray,
                                            '',
                                            $this->_translator->getMessage('COMMON_BUZZWORDS'),
                                            ''
                               );
              }
              if ( $current_context->withTags() ){
                 $this->_form->addSelect('tags',
                                            $this->_optionArray,
                                            '',
                                            $this->_translator->getMessage('COMMON_TAGS'),
                                            ''
                               );
              }
              $this->_form->addSelect('usageinfos',
                                            $this->_optionArray,
                                            '',
                                            $this->_translator->getMessage('PREFERENCES_USAGE_INFOS'),
                                            ''
                               );
              $this->_form->addSelect('homeextratools',
                                            $this->_optionArray,
                                            '',
                                            $this->_translator->getMessage('HOME_EXTRA_TOOLS'),
                                            ''
                               );

              $this->_form->addSelect('preferences',
                                            $this->_optionArray2,
                                            '',
                                            $this->_translator->getMessage('ADMIN_INDEX'),
                                            ''
                               );

           }
      if ( $this->_environment->inPrivateRoom() ){
              $radio_values = array();
              $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_PRIVATEROOM_HOME_DESC_1').
              '<br/><img src="images/private_room_normal_home.gif" alt="home_normal" style=" width:290px; border:1px solid black; vertical-align: middle;"/>'.BRLF.BRLF;
              $radio_values[0]['value'] = '1';
              $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_PRIVATEROOM_HOME_DESC_2').
              '<br/><img src="images/private_room_detailed_home.gif" alt="home_detailed" style=" width:290px; border:1px solid black; vertical-align: middle;"/>'.$this->_translator->getMessage('CONFIGURATION_PRIVATEROOM_HOME_DESC_3').BRLF.BRLF;
              $radio_values[1]['value'] = '2';

              $this->_form->addRadioGroup('home_status',$this->_translator->getMessage('CONFIGURATION_HOME_STATUS'),'',$radio_values,'',true,false);
      }
      if ($this->_environment->inPrivateRoom() ){
        $radio_values = array();
        $radio_values[0]['text'] = '1 '.$this->_translator->getMessage('CONFIGURATION_PRIVATEROOM_HOME_DAILY');
        $radio_values[0]['value'] = '1';
         $radio_values[1]['text'] = '7 '.$this->_translator->getMessage('CONFIGURATION_PRIVATEROOM_HOME_WEEKLY');
        $radio_values[1]['value'] = '2';
         $radio_values[2]['text'] = '30 '.$this->_translator->getMessage('CONFIGURATION_PRIVATEROOM_HOME_WEEKLY');
        $radio_values[2]['value'] = '3';
        $this->_form->addRadioGroup('time_spread',$this->_translator->getMessage('INTERNAL_TIME_SPREAD'),'',$radio_values,'',true,false);
      }



      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
     if (isset($this->_form_post)) {
          $this->_values = $this->_form_post;
      } elseif (!$this->_environment->inPrivateRoom()){
             $room = $this->_environment->getCurrentContextItem();
             $home_conf = $room->getHomeConf();
             $home_conf_array = explode(',',$home_conf);
             $i = 0;
             foreach ($home_conf_array as $rubric_conf) {
                $rubric_conf_array = explode('_',$rubric_conf);
                if ($rubric_conf_array[1] != 'none') {
                   $this->_values[$rubric_conf_array[0]]= $rubric_conf_array[1];
                }
             }
             $home_right_conf = $room->getHomeRightConf();
             if (!empty($home_right_conf)){
                $home_right_conf_array = explode(',',$home_right_conf);
                foreach ($home_right_conf_array as $box_conf) {
                   $box_conf_array = explode('_',$box_conf);
                   $this->_values[$box_conf_array[0]]= $box_conf_array[1];
                }
             }else{
                  $this->_values['activity'] = 'short';
                  $this->_values['search'] = 'short';
                  $this->_values['buzzwords'] = 'tiny';
                  $this->_values['tags'] = 'tiny';
                  $this->_values['actions'] = 'tiny';
                  $this->_values['preferences'] = 'tiny';
                  $this->_values['usageinfos'] = 'tiny';
                  $this->_values['homeextratools'] = 'tiny';
             }
             if ($room->isProjectRoom() or $room->isPrivateRoom() or $room->isGroupRoom()) {
                $this->_values['time_spread'] = $room->getTimeSpread();
        }
        if($room->isPrivateRoom()){
           $status = $room->getHomeStatus();
           if ($status == 'detailed'){
             $this->_values['home_status'] ='2';
           }else{
             $this->_values['home_status'] ='1';
           }
           $time = $room->getTimeSpread();
           if ($time == '7'){
              $value = '2';
           }elseif ($time == '30'){
              $value = '3';
           }elseif ($time == '1'){
              $value = '1';
           } else{
              $value = '2';
           }
          $this->_values['time_spread'] = $value;
             }
      }else{
             $room = $this->_environment->getCurrentContextItem();
              if ($room->isPrivateRoom() ){
                 $time = $room->getTimeSpread();
                 if ($time == '7'){
                    $value = '2';
                 }elseif ($time == '30'){
                    $value = '3';
                 }elseif ($time == '1'){
                    $value = '1';
                 } else{
                    $value = '2';
                 }
                $this->_values['time_spread'] = $value;
        }elseif ($room->isProjectRoom() or $room->isGroupRoom()) {
                $this->_values['time_spread'] = $room->getTimeSpread();
             }
        if($room->isPrivateRoom()){
           $status = $room->getHomeStatus();
           if ($status == 'detailed'){
                   $this->_values['home_status'] ='2';
           }else{
                   $this->_values['home_status'] ='1';

           }
             }
      }
   }

 }

?>