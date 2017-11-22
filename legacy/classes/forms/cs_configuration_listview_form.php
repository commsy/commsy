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
class cs_configuration_listview_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */

   var $_boxArray = array();
   var $_detailBoxArray = array();
   var $_optionArray = array();
   var $_optionArray2 = array();
   var $_lengthArray = array();

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
      $this->setHeadline($this->_translator->getMessage('CONFIGURATION_LISTVIEW_HOME'));

      $room = $this->_environment->getCurrentContextItem();
      $default_boxes = $room->getAvailableListBoxes();
      $box_array = array();
      foreach ($default_boxes as $box) {
         if (!empty($box)){
            $temp_array = array();
            $temp_array['text']  = $box;
            $temp_array['value'] = $box;
            $box_array[] = $temp_array;
            unset($temp_array);
         }
      }
      $this->_boxArray = $box_array;

      $default_boxes = $room->getAvailableDetailBoxes();
      $box_array = array();
      foreach ($default_boxes as $box) {
         if (!empty($box)){
            $temp_array = array();
            $temp_array['text']  = $box;
            $temp_array['value'] = $box;
            $box_array[] = $temp_array;
            unset($temp_array);
         }
      }
      $this->_detailBoxArray = $box_array;

      $view_mod_array[0]['text'] = $this->_translator->getMessage('MODULE_CONFIG_SHORT');
      $view_mod_array[0]['value'] = 'short';
      $view_mod_array[1]['text'] = $this->_translator->getMessage('MODULE_CONFIG_TINY');
      $view_mod_array[1]['value'] = 'tiny';
      $view_mod_array[2]['text'] = $this->_translator->getMessage('MODULE_CONFIG_NO');
      $view_mod_array[2]['value'] = 'nodisplay';
      $this->_optionArray = $view_mod_array;

      $view_mod_array = array();
      $view_mod_array[0]['text'] = $this->_translator->getMessage('MODULE_CONFIG_SHORT');
      $view_mod_array[0]['value'] = 'short';
      $view_mod_array[1]['text'] = $this->_translator->getMessage('MODULE_CONFIG_TINY');
      $view_mod_array[1]['value'] = 'tiny';
      $this->_optionArray2 = $view_mod_array;

      $length[1]['text'] = '20';
      $length[1]['value'] = '2';
      $length[2]['text'] = '50';
      $length[2]['value'] = '3';
      $length[3]['text'] = $this->_translator->getMessage('CONFIGURATION_LISTVIEW_LENGTH_ALL');
      $length[3]['value'] = '4';
      $this->_lengthArray = $length;
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
             $with_usage_infos = false;
             $this->_form->addSubHeadline('header_list',$this->_translator->getMessage('CONFIGURATION_LISTVIEWS_FORM_SUB_TITLE_LIST'));
             for ( $i = 0; $i < count($this->_boxArray); $i++ ) {
                $tempMessage = '';
                switch (mb_strtoupper($this->_boxArray[$i]['text'], 'UTF-8')){
                   case 'ACTIONS':
                      $tempMessage = $this->_translator->getMessage('COMMON_ACTIONS');
                      break;
                   case 'SEARCH':
                      $tempMessage = $this->_translator->getMessage('COMMON_SEARCH');
                      break;
                   case 'BUZZWORDS':
                      $tempMessage = $this->_translator->getMessage('COMMON_BUZZWORD_BOX');
                      break;
                   case 'TAGS':
                      $tempMessage = $this->_translator->getMessage('COMMON_TAG_BOX');
                      break;
                   case 'USAGE':
                      $tempMessage = $this->_translator->getMessage('USAGE_INFO_HEADER');
                      $with_usage_infos = true;
                      break;
                   default:
                      $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR' . ' cs_configuration_listview_form(122)');
                      break;
                }
                if ( mb_strtoupper($this->_boxArray[$i]['text'], 'UTF-8') == 'ACTIONS' ){
                   $this->_form->addSelect($this->_boxArray[$i]['text'],
                                            $this->_optionArray2,
                                            '',
                                            $tempMessage,
                                            ''
                               );
                }else{
                   $this->_form->addSelect($this->_boxArray[$i]['text'],
                                            $this->_optionArray,
                                            '',
                                            $tempMessage,
                                            ''
                               );
                }
             }
             if ( !$with_usage_infos ){
                $this->_form->addSelect('usage',
                                            $this->_optionArray,
                                            '',
                                            $this->_translator->getMessage('USAGE_INFO_HEADER'),
                                            ''
                               );
             }
             $this->_form->addSelect('length',
                                            $this->_lengthArray,
                                            '',
                                            $this->_translator->getMessage('CONFIGURATION_LISTVIEW_LENGTH'),
                                            ''
                               );
             $this->_form->addEmptyLine();
             $this->_form->addSubHeadline('header_detail',$this->_translator->getMessage('CONFIGURATION_LISTVIEWS_FORM_SUB_TITLE_DETAIL'));
             for ( $i = 0; $i < count($this->_detailBoxArray); $i++ ) {
                $tempMessage = '';
                switch (mb_strtoupper($this->_detailBoxArray[$i]['text'], 'UTF-8')){
                   case 'DETAILACTIONS':
                      $tempMessage = $this->_translator->getMessage('COMMON_ACTIONS');
                      break;
                  case 'DETAILBUZZWORDS':
                      $tempMessage = $this->_translator->getMessage('COMMON_BUZZWORD_BOX');
                      break;
                   case 'DETAILTAGS':
                      $tempMessage = $this->_translator->getMessage('COMMON_TAG_BOX');
                      break;
                   case 'DETAILNETNAVIGATION':
                      $tempMessage = $this->_translator->getMessage('COMMON_NETNAVIGATION');
                      break;
                    default:
                      $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR' . ' cs_configuration_listview_form(122)');
                      break;
                }
                if ( mb_strtoupper($this->_detailBoxArray[$i]['text'], 'UTF-8') == 'DETAILACTIONS' ){
                   $this->_form->addSelect($this->_detailBoxArray[$i]['text'],
                                            $this->_optionArray2,
                                            '',
                                            $tempMessage,
                                            ''
                               );
                }else{
                   $this->_form->addSelect($this->_detailBoxArray[$i]['text'],
                                            $this->_optionArray,
                                            '',
                                            $tempMessage,
                                            ''
                               );
                }
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
      } else {
         $with_usage_infos = false;
         $room = $this->_environment->getCurrentContextItem();
         $boxes_right_conf = $room->getListBoxConf();
         if (!empty($boxes_right_conf)){
            $boxes_right_conf_array = explode(',',$boxes_right_conf);
            foreach ($boxes_right_conf_array as $box_conf) {
               $box_conf_array = explode('_',$box_conf);
               $this->_values[$box_conf_array[0]]= $box_conf_array[1];
               if( $box_conf_array[0] == 'usage' ){
                  $with_usage_infos = true;
               }
            }
            if ( !$with_usage_infos ){
               $this->_values['usage']= 'tiny';
            }
         }
         $detail_boxes_right_conf = $room->getDetailBoxConf();
         if (!empty($detail_boxes_right_conf)){
            $boxes_right_conf_array = explode(',',$detail_boxes_right_conf);
            foreach ($boxes_right_conf_array as $box_conf) {
               $box_conf_array = explode('_',$box_conf);
               $this->_values[$box_conf_array[0]]= $box_conf_array[1];
            }
         }
         $this->_values['length'] = $room->getListLength();
         if ( $this->_values['length'] == 'all' ) {
            $this->_values['length'] = 4;
         }
      }
   }

}
?>