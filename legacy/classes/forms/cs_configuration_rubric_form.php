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
class cs_configuration_rubric_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_optionArrays = array();

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

   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

     $first = true;
     $second = false;
     $third = false;
     $text = '<table style="border: 0px solid black;"><tr style="border: 0px solid black;"><td style="border: 0px solid black; vertical-align:top;" summary="Layout">'.
     $this->_translator->getMessage('CONFIGURATION_RUBRIC_DESC').'</td><td style="border: 0px solid black;">'.
     '<img src="images/configuration_rubric.jpg" width="400px;" style=" border:1px solid black; vertical-align: middle;"/>'.
     '</td></tr></table>';

     $count = 8;
     if ( $this->_environment->inCommunityRoom()
          or $this->_environment->inGroupRoom()
        ) {
        $count = 7;
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

     $room = $this->_environment->getCurrentContextItem();
     $default_rubrics = $room->getAvailableDefaultRubricArray();
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
                       $select_array[$i]['text'] = $this->_translator->getMessage('DATE_INDEX');
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
                    default:
                       $text = '';
                       if ( $this->_environment->isPlugin($rubric) ) {
                          $text = plugin_hook_output($rubric,'getDisplayName');
                       }
                       if ( !empty($text) ) {
                          $select_array[$i]['text'] = $text;
                       } else {
                          $select_array[$i]['text'] = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_configuration_rubric_form('.__LINE__.') ');
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

     for ($i=0; $i<$count; $i++) {
        if ($i>0 ){
          $this->_form->combine('horizontal');
       }
       $this->_form->addSelect('rubric_'.$i,$select_array,'','','','',false,false);
     }
      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('COMMON_SAVE_BUTTON'),'');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $count =8;
     if ($this->_environment->inCommunityRoom()){
         $count =7;
      }
      $this->_values = array();
      if (isset ($_POST)){
        $this->_values = $_POST;
     }
     if (isset($this->_item)) {
       $home_conf = $this->_item->getHomeConf();
       $home_conf_array = explode(',',$home_conf);
       $i=0;
       foreach ($home_conf_array as $rubric_conf) {
         $rubric_conf_array = explode('_',$rubric_conf);
         if ($rubric_conf_array[1] != 'none') {
            $this->_values['rubric_'.$i] = $rubric_conf_array[0];
            $i++;
         }
       }
        for ($j=$i+1; $j<$count; $j++) {
            $this->_values['rubric_'.$j] = 'none';
       }
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
}

?>