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
class cs_configuration_rubric_options_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_optionArrays = array();

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function cs_configuration_rubric_options_form ($params) {
      $this->cs_rubric_form($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {

   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
     $view_mod_array = array();
     $view_mod_array[0]['text'] = getMessage('RUBRIC_CONFIG_SHORT');
     $view_mod_array[0]['value'] = 'short';
     $view_mod_array[1]['text'] = getMessage('RUBRIC_CONFIG_TINY');
     $view_mod_array[1]['value'] = 'tiny';
     $view_mod_array[2]['text'] = getMessage('RUBRIC_CONFIG_NO');
     $view_mod_array[2]['value'] = 'nodisplay';

     $first = true;
     $second = false;
     $third = false;
     $text = '<table style="border: 0px solid black;"><tr style="border: 0px solid black;"><td style="border: 0px solid black; vertical-align:top;" summary="Layout">'.
     getMessage('CONFIGURATION_RUBRIC_DESC').'</td><td style="border: 0px solid black;">'.
     '<img src="images/configuration_rubric.jpg" width="400px;" style=" border:1px solid black; vertical-align: middle;"/>'.
     '</td></tr></table>';

        $count =8;
     if ( $this->_environment->inCommunityRoom()
          or $this->_environment->inGroupRoom()
        ) {
         $count =7;
     }elseif($this->_environment->inPrivateRoom()){
         $count =4;
     }
     for ( $i = 0; $i < $count; $i++ ) {
        $nameArray[0] = 'module['.$i.']';
        $nameArray[1] = 'view['.$i.']';
        $desc = '';
        if ($first) {
           $first = false;
           $desc = getMessage('INTERNAL_MODULE_CONF_DESC_SHORT',getMessage('MODULE_CONFIG_SHORT'));
           $second = true;
        } elseif ($second) {
           $second = false;
           $desc = getMessage('INTERNAL_MODULE_CONF_DESC_TINY',getMessage('MODULE_CONFIG_TINY'));
           $third = true;
        } elseif ($third) {
           $third = false;
           $desc = getMessage('INTERNAL_MODULE_CONF_DESC_NONE',getMessage('MODULE_CONFIG_NONE'));
        }
     }

     $room = $this->_environment->getCurrentContextItem();
     $default_rubrics = $room->getAvailableDefaultRubricArray();
     $rubric_array = array();
     $i = 1;
     $select_array[0]['text'] = '----------';
     $select_array[0]['value'] = 'none';
     foreach ($default_rubrics as $rubric){
              if ($this->_environment->inPrivateRoom() and $rubric =='user' ){
                 $select_array[$i]['text'] = getMessage('COMMON_MY_USER_DESCRIPTION');
              } else {
                 switch ( strtoupper($rubric) ){
                    case 'ANNOUNCEMENT':
                       $select_array[$i]['text'] = getMessage('ANNOUNCEMENT_INDEX');
                       break;
                    case 'DATE':
                       $select_array[$i]['text'] = getMessage('DATE_INDEX');
                       break;
                    case 'DISCUSSION':
                       $select_array[$i]['text'] = getMessage('DISCUSSION_INDEX');
                       break;
                    case 'GROUP':
                       $select_array[$i]['text'] = getMessage('GROUP_INDEX');
                       break;
                    case 'INSTITUTION':
                       $select_array[$i]['text'] = getMessage('INSTITUTION_INDEX');
                       break;
                    case 'MATERIAL':
                       $select_array[$i]['text'] = getMessage('MATERIAL_INDEX');
                       break;
                    case 'MYROOM':
                       $select_array[$i]['text'] = getMessage('MYROOM_INDEX');
                       break;
                    case 'PROJECT':
                       $select_array[$i]['text'] = getMessage('PROJECT_INDEX');
                       break;
                    case 'TODO':
                       $select_array[$i]['text'] = getMessage('TODO_INDEX');
                       break;
                    case 'TOPIC':
                       $select_array[$i]['text'] = getMessage('TOPIC_INDEX');
                       break;
                    case 'USER':
                       $select_array[$i]['text'] = getMessage('USER_INDEX');
                       break;
                    default:
                       $select_array[$i]['text'] = getMessage('COMMON_MESSAGETAG_ERROR'.' cs_configuration_rubric_form(144) ');
                       break;
                 }
              }
        $select_array[$i]['value'] = $rubric;
       $i++;
     }

     // sorting
     $sort_by = 'text';
     usort($select_array,create_function('$a,$b','return strnatcasecmp($a[\''.$sort_by.'\'],$b[\''.$sort_by.'\']);'));

     $home_conf = $this->_environment->getCurrentContextItem()->getHomeConf();
     $home_conf_array = explode(',',$home_conf);
     $i=0;
     $count =8;
     if($this->_environment->inPrivateRoom()){
        $count = 4;
     }
     $value_array = array();
     foreach ($home_conf_array as $rubric_conf) {
        $rubric_conf_array = explode('_',$rubric_conf);
        if ($rubric_conf_array[1] != 'none') {
           $value_array[] = true;
        }else{
           $value_array[] = false;
        }
     }
     for ($j=$i+1; $j<$count; $j++) {
        $value_array[] = false;
     }

     for ($i=0; $i<$count; $i++) {
        if ($i>0 ){
          $this->_form->combine();
       }
       $this->_form->addSelect('rubric_'.$i,$select_array,
                                '',
                                getMessage('COMMON_CHOOSE_RUBRIC'),
                                '',
                                '',
                                '',
                                '',
                                '',
                                true,
                                '',
                                '',
                                '',
                                '',
                                '',
                                false,
                                '',
                                'id="id'.$i.'" onChange="javascript:cs_toggleSelect('.$i.')"');
       if(!$this->_environment->inPrivateRoom()){
          $this->_form->combine('horizontal');
          if ($value_array[$i]) {
             $disabled = false;
          } else {
             $disabled = true;
          }

          $this->_form->addSelect('show_'.$i,$view_mod_array,
                                '',
                                '',
                                '',
                                '',
                                '',
                                '',
                                '',
                                true,
                                '',
                                '',
                                '',
                                '',
                                '',
                                $disabled,
                                '',
                                'id="nr'.$i.'" ');
       }
      }
      if ($this->_environment->inProjectRoom() or $this->_environment->inGroupRoom()){
         $this->_form->addTextField('time_spread',
                                    '',
                                    getMessage('INTERNAL_TIME_SPREAD'),
                                    getMessage('INTERNAL_TIME_SPREAD_DESC'),
                                    '2',
                                    '3',
                                    true
                                   );
      }
      // buttons
      $this->_form->addButtonBar('option',getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $count =8;
      if ($this->_environment->inCommunityRoom()){
         $count =7;
      }
      if ($this->_environment->inPrivateRoom()){
         $count =4;
      }
      $this->_values = array();
      $home_conf = $this->_environment->getCurrentContextItem()->getHomeConf();
      $home_conf_array = explode(',',$home_conf);
      $i=0;
      foreach ($home_conf_array as $rubric_conf) {
         $rubric_conf_array = explode('_',$rubric_conf);
         if ($rubric_conf_array[1] != 'none') {
            $this->_values['rubric_'.$i] = $rubric_conf_array[0];
            $this->_values['show_'.$i] = $rubric_conf_array[1];
            $i++;
         }
      }
      for ($j=$i+1; $j<$count; $j++) {
         $this->_values['rubric_'.$j] = 'none';
         $this->_values['show_'.$j] = 'none';
      }
      if (isset ($_POST['time_spread'])){
        $this->_values['time_spread'] = $_POST['time_spread'];
      }else{
         $this->_values['time_spread'] = $this->_item->getTimeSpread();
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function check () {
      $count =8;
      if ( $this->_environment->inCommunityRoom()
           or $this->_environment->inGroupRoom()
         ) {
         $count =7;
      } elseif ($this->_environment->inPrivateRoom()){
         $count =4;
      }
    if (isset ($_POST['rubric_0'])){
         $post_array=array();
        for ($j=0; $j<$count; $j++) {
            $post_array[] = $_POST['rubric_'.$j];
       }
       $value = true;
        for ($k=0; $k<$count; $k++) {
           for ($l=0; $l<$count; $l++) {
            if ($k!=$l){
               if ($post_array[$l]==$post_array[$k] and $post_array[$l]!='none'){
                  $value= false;
               }
            }
           }
        }
        return $value;
     }else{
        return true;
     }
   }

   function getInfoForHeaderAsHTML () {
      $retour  = '';
      $retour .= '        window.addEvent(\'domready\', function(){'.LF;
      $retour .= '         });'.LF;
      $retour .= '         function cs_toggleSelect(nummer) {'.LF;
      $retour .= '            var value = document.getElementById("id"+nummer).selectedIndex'.LF;
      $retour .= '            if ( value == "0" ){'.LF;
      $retour .= '               var element = document.getElementById("nr"+nummer);'.LF;
      $retour .= '               element.disabled = true;'.LF;
      $retour .= '            }else{'.LF;
      $retour .= '               var element = document.getElementById("nr"+nummer);'.LF;
      $retour .= '               element.disabled = false;'.LF;
      $retour .= '            }'.LF;
      $retour .= '         }'.LF;
/*      $retour .= '         function cs_toggleTags() {'.LF;
      $retour .= '            if (document.f.tags.checked) {'.LF;
      $retour .= '               cs_enable_tags();'.LF;
      $retour .= '            } else {'.LF;
      $retour .= '               cs_disable_tags();'.LF;
      $retour .= '            }'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_disable_buzzword() {'.LF;
      $retour .= '            document.f.buzzword_mandatory.disabled = true;'.LF;
      $retour .= '            document.f.buzzword_show.disabled = true;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_enable_buzzword() {'.LF;
      $retour .= '            document.f.buzzword_mandatory.disabled = false;'.LF;
      $retour .= '            document.f.buzzword_show.disabled = false;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_enable_tags() {'.LF;
      $retour .= '            document.f.tags_mandatory.disabled = false;'.LF;
      $retour .= '            document.f.tags_show.disabled = false;'.LF;
      $retour .= '            document.f.tags_edit.disabled = false;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_disable_tags() {'.LF;
      $retour .= '            document.f.tags_mandatory.disabled = true;'.LF;
      $retour .= '            document.f.tags_show.disabled = true;'.LF;
      $retour .= '            document.f.tags_edit.disabled = true;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_enable_netnavigation() {'.LF;
      $retour .= '            document.f.netnavigation_show.disabled = false;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_disable_netnavigation() {'.LF;
      $retour .= '            document.f.netnavigation_show.disabled = true;'.LF;
      $retour .= '         }'.LF;*/
      return $retour;
   }


}

?>