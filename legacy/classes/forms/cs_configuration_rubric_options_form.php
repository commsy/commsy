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
   function __construct($params) {
      cs_rubric_form::__construct($params);
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
     $view_mod_array[0]['text'] = $this->_translator->getMessage('RUBRIC_CONFIG_SHORT');
     $view_mod_array[0]['value'] = 'short';
     $view_mod_array[1]['text'] = $this->_translator->getMessage('RUBRIC_CONFIG_TINY');
     $view_mod_array[1]['value'] = 'tiny';
     $view_mod_array[2]['text'] = $this->_translator->getMessage('RUBRIC_CONFIG_NO');
     $view_mod_array[2]['value'] = 'nodisplay';

     $first = true;
     $second = false;
     $third = false;
     $text = '<table style="border: 0px solid black;"><tr style="border: 0px solid black;"><td style="border: 0px solid black; vertical-align:top;" summary="Layout">'.
     $this->_translator->getMessage('CONFIGURATION_RUBRIC_DESC').'</td><td style="border: 0px solid black;">'.
     '<img src="images/configuration_rubric.jpg" width="400px;" style=" border:1px solid black; vertical-align: middle;"/>'.
     '</td></tr></table>';

     $room = $this->_environment->getCurrentContextItem();
     $default_rubrics = $room->getAvailableDefaultRubricArray();
     $plugin_list = $this->_environment->getRubrikPluginClassList($this->_environment->getCurrentPortalID());
     if ( isset($plugin_list)
          and $plugin_list->isNotEmpty()
        ) {
        $plugin_class = $plugin_list->getFirst();
        while ( $plugin_class ) {
           if ( !in_array($plugin_class->getIdentifier(),$default_rubrics)
                and ( ( $this->_environment->inPrivateRoom()
                        and $plugin_class->inPrivateRoom()
                      )
                      or
                      ( $this->_environment->inProjectRoom()
                        and $plugin_class->inProjectRoom()
                      )
                      or
                      ( $this->_environment->inCommunityRoom()
                        and $plugin_class->inCommunityRoom()
                      )
                      or
                      ( $this->_environment->inGroupRoom()
                        and $plugin_class->inGroupRoom()
                      )
                    )
              ) {
              $default_rubrics[] = mb_strtolower($plugin_class->getIdentifier());
           }
           $plugin_class = $plugin_list->getNext();
        }
     }
     if ( count($default_rubrics) > 8 ) {
        $count = 8;
     } else {
        $count = count($default_rubrics);
     }

     for ( $i = 0; $i < $count; $i++ ) {
        $nameArray[0] = 'module['.$i.']';
        $nameArray[1] = 'view['.$i.']';
        $desc = '';
        if ($first) {
           $first = false;
           $desc = $this->_translator->getMessage('INTERNAL_MODULE_CONF_DESC_SHORT',$this->_translator->getMessage('MODULE_CONFIG_SHORT'));
           $second = true;
        } elseif ($second) {
           $second = false;
           $desc = $this->_translator->getMessage('INTERNAL_MODULE_CONF_DESC_TINY',$this->_translator->getMessage('MODULE_CONFIG_TINY'));
           $third = true;
        } elseif ($third) {
           $third = false;
           $desc = $this->_translator->getMessage('INTERNAL_MODULE_CONF_DESC_NONE',$this->_translator->getMessage('MODULE_CONFIG_NONE'));
        }
     }

     $rubric_array = array();
     $i = 1;
     $select_array[0]['text'] = '----------';
     $select_array[0]['value'] = 'none';
     foreach ($default_rubrics as $rubric){
        if ($this->_environment->inPrivateRoom() and $rubric =='user' ){
           $select_array[$i]['text'] = $this->_translator->getMessage('COMMON_MY_USER_DESCRIPTION');
        } else {
           switch ( mb_strtoupper($rubric, 'UTF-8') ){
              case 'ANNOUNCEMENT':
                 $select_array[$i]['text'] = $this->_translator->getMessage('ANNOUNCEMENT_INDEX');
                 break;
              case 'DATE':
                 $select_array[$i]['text'] = $this->_translator->getMessage('MYCALENDAR_INDEX');
                 break;
              case 'DISCUSSION':
                 $select_array[$i]['text'] = $this->_translator->getMessage('DISCUSSION_INDEX');
                 break;
              case 'GROUP':
                 $select_array[$i]['text'] = $this->_translator->getMessage('GROUP_INDEX');
                 break;
              case 'INSTITUTION':
                 $select_array[$i]['text'] = $this->_translator->getMessage('INSTITUTION_INDEX');
                 break;
              case 'MATERIAL':
                 $select_array[$i]['text'] = $this->_translator->getMessage('MATERIAL_INDEX');
                 break;
              case 'MYROOM':
                 $select_array[$i]['text'] = $this->_translator->getMessage('MYROOM_INDEX');
                 break;
              case 'PROJECT':
                 $select_array[$i]['text'] = $this->_translator->getMessage('PROJECT_INDEX');
                 break;
              case 'TODO':
                 $select_array[$i]['text'] = $this->_translator->getMessage('TODO_INDEX');
                 break;
              case 'TOPIC':
                 $select_array[$i]['text'] = $this->_translator->getMessage('TOPIC_INDEX');
                 break;
              case 'USER':
                 $select_array[$i]['text'] = $this->_translator->getMessage('USER_INDEX');
                 break;
              case 'ENTRY':
                 $select_array[$i]['text'] = $this->_translator->getMessage('ENTRY_INDEX');
                 break;
              default:
                 $text = '';
                 if ( $this->_environment->isPlugin($rubric) ) {
                    $text = plugin_hook_output($rubric,'getDisplayName');
                 }
                 if ( !empty($text) ) {
                    $select_array[$i]['text'] = $text;
                 } elseif ( !$this->_environment->isPlugin($rubric) ) {
                    $select_array[$i]['text'] = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' '.__FILE__.'('.__LINE__.')');
                 }
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
     $value_array = array();
     foreach ($home_conf_array as $rubric_conf) {
        $rubric_conf_array = explode('_',$rubric_conf);
        if ($rubric_conf_array[1] != 'none') {
           $value_array[] = true;
        } else {
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
                                $this->_translator->getMessage('COMMON_CHOOSE_RUBRIC'),
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
      if ( $this->_environment->inProjectRoom()
           or $this->_environment->inGroupRoom()
           or $this->_environment->inCommunityRoom()
         ){
         $this->_form->addTextField('time_spread',
                                    '',
                                    $this->_translator->getMessage('INTERNAL_TIME_SPREAD'),
                                    $this->_translator->getMessage('INTERNAL_TIME_SPREAD_DESC'),
                                    '3',
                                    '3',
                                    true
                                   );
      }
      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      if ( !isset($_POST['rubric_0']) ) {
         $room = $this->_environment->getCurrentContextItem();
         $default_rubrics = $room->getAvailableDefaultRubricArray();
         if ( count($default_rubrics) > 8 ) {
            $count = 8;
         } else {
            $count = count($default_rubrics);
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
      } else {
         $this->_values = $this->_form_post;
      }
      if ( isset($_POST['time_spread']) ) {
        $this->_values['time_spread'] = $_POST['time_spread'];
      } elseif (!$this->_environment->inPrivateRoom()) {
         $this->_values['time_spread'] = $this->_item->getTimeSpread();
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      if ( !empty($this->_form_post['time_spread'])
           and is_numeric($this->_form_post['time_spread'])
           and $this->_form_post['time_spread'] > 365
         ) {
         $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_ERROR_TIME_SPREAD_LONG');
         $this->_form->setFailure('time_spread','');
      }
      if ( !empty($this->_form_post['rubric_0']) ) {
         $room = $this->_environment->getCurrentContextItem();
         $default_rubrics = $room->getAvailableDefaultRubricArray();
         if ( count($default_rubrics) > 8 ) {
            $count = 8;
         } else {
            $count = count($default_rubrics);
         }
         if ( isset($_POST['rubric_0']) ) {
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
         }
         if ( !$value ) {
            $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_RUBRIC_ERROR_DESCRIPTION');
            $this->_form->setFailure('rubric_0','');
         }
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
      return $retour;
   }
}
?>