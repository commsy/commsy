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
class cs_tag_form extends cs_rubric_form {

  /**
   * array - containing the materials of a news
   */
   var $_root_tag = array();
   var $_first_sort_tree = array();
   var $_second_sort_tree = array();
   var $_sort_actions = array();

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   private function _initFormChildren ( $item, $depth ) {
      $retour = array();
      if ( isset($item) ) {
         $children_list = $item->getChildrenList();
         if ( isset($children_list) and $children_list->isNotEmpty() ) {
            $child = $children_list->getFirst();
            $arrows = '';
            $depth_temp = $depth;
            while ( $depth_temp > 0 ) {
               $arrows .= '> ';
               $depth_temp = $depth_temp-1;
            }
            while ( $child ) {
               $temp_array = array();
               $temp_array['value'] = $child->getItemID();
               $temp_array['text']  = $arrows.$child->getTitle();
               $retour[] = $temp_array;
               $retour = array_merge($retour,$this->_initFormChildren($child,$depth+1));
               unset($child);
               $child = $children_list->getNext();
            }

         }
         unset($children_list);
      }
      $this->_first_sort_tree = $retour;
      return $retour;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      // get root tag
      $tag_manager = $this->_environment->getTagManager();
      $this->_root_tag = $tag_manager->getRootTagItem();
      unset($tag_manager);

      $this->_values_tree = array();
      if ( isset($this->_root_tag) ) {
         $temp_array = array();
         $temp_array['value'] = $this->_root_tag->getItemID();
         $temp_array['text'] = '*'.$this->_translator->getMessage('TAG_FORM_ROOT_LEVEL');
         $this->_values_tree[] = $temp_array;
         unset($temp_array);
         $temp_array = array();
         $temp_array['value'] = 'disabled';
         $temp_array['text'] = '--------------------';
         $this->_values_tree[] = $temp_array;
         unset($temp_array);
         $this->_values_tree = array_merge($this->_values_tree,$this->_initFormChildren($this->_root_tag,0));
         $this->_second_sort_tree = $this->_values_tree;
      }
      $sort_actions = array();
      $temp_array['text']  = $this->_translator->getMessage('TAG_ACTIONS_UNDER');
      $temp_array['value'] = 3;
      $sort_actions[] = $temp_array;
      $temp_array['text']  = $this->_translator->getMessage('TAG_ACTIONS_BEFORE');
      $temp_array['value'] = 1;
      $sort_actions[] = $temp_array;
      $temp_array['text']  = $this->_translator->getMessage('TAG_ACTIONS_AFTER');
      $temp_array['value'] = 2;
      $sort_actions[] = $temp_array;
      $this->_sort_actions = $sort_actions;
   }

   private function _createFormForChildren ( $item, $depth ) {
      if ( isset($item) ) {
         $children_list = $item->getChildrenList();
         if ( isset($children_list) and $children_list->isNotEmpty() ) {
            $arrows = '';
            $depth_temp = $depth;
            while ( $depth_temp > 0 ) {
               $arrows .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ';
               $depth_temp = $depth_temp-1;
            }
            $len_text_field = 30-($depth*4);
            if ( $depth > 0 ) {
               $len_text_field = $len_text_field - 1;
            }
            $child = $children_list->getFirst();
            while ( $child ) {
               $this->_form->addTextField('tag'.'#'.$child->getItemID(),$child->getTitle(),'','','',$len_text_field,false,$this->_translator->getMessage('BUZZWORDS_CHANGE_BUTTON'),'option'.'#'.$child->getItemID(),'','',$arrows);
               $this->_form->combine('horizontal');
               $this->_form->addButton('right_box_option'.'#'.$child->getItemID(),$this->_translator->getMessage('COMMON_ITEM_NEW_ATTACH'),'','',(mb_strlen($this->_translator->getMessage('COMMON_ITEM_NEW_ATTACH'))*7));
               $this->_form->combine('horizontal');
               $this->_form->addButton(	'option'.'#'.$child->getItemID(),
               							$this->_translator->getMessage('COMMON_DELETE_BUTTON'),
              							'',
               							'',
               							'',
               							'',
               							'',
               							'',
               							'',
               							'',
               							'delete_confirm_option#' . $child->getItemID());

               $this->_createFormForChildren($child,$depth+1);
               unset($child);
               $child = $children_list->getNext();
            }
         }
         unset($children_list);
      }
      unset($item);
   }
    
   /** get information for header as HTML
    * this method returns information in HTML-Code needs for the header of the HTML-Page
    *
    * @return string javascipt needed for the form
    */
   function getInfoForHeaderAsHTML() {
      $text2 = '';
      if($this->_environment->getCurrentUserItem()->isModerator()) {
         $text2 = $this->_translator->getMessage("COMMON_DELETE_BOX_DESCRIPTION_MODERATOR");
      }
      
      $return = "
          var headline = '" . $this->_translator->getMessage("COMMON_DELETE_BOX_TITLE") . "';
          var text1 = '" . $this->_translator->getMessage("COMMON_DELETE_BOX_DESCRIPTION") . "';
          var text2 = '" . $text2 . "';
          var button_delete = '" . $this->_translator->getMessage("COMMON_DELETE_BUTTON") . "';
          var button_cancel = '" . $this->_translator->getMessage("COMMON_CANCEL_BUTTON") . "';
      ";

      return $return;
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      include_once('functions/text_functions.php');
      $this->_form->addSubHeadline('headline1',cs_ucfirst($this->_translator->getMessage('COMMON_ADD_BUTTON')),'','',3);
      $this->_form->addTextField('new_tag','','','','',30,false,'','','','','','',false,$this->_translator->getMessage('TAG_WORD_TO'));
      $this->_form->combine('horizontal');
      $this->_form->addSelect('father_id',$this->_values_tree,'','','', 1, false,false,false,'','','','',12);
      $this->_form->combine('horizontal');
      $this->_form->addButton('option',$this->_translator->getMessage('COMMON_ADD_BUTTON'),'','',80);
      $this->_form->addSubHeadline('headline2',cs_ucfirst($this->_translator->getMessage('COMMON_SORT_BUTTON')),'','',3);
      $this->_form->addSelect('sort1',$this->_first_sort_tree,'','','', 1, false,false,false,'','','','',11);
      $this->_form->combine('horizontal');
      $this->_form->addSelect('sort_action',$this->_sort_actions,'','','', 1, false,false,false,'','','','',7);
      $this->_form->combine('horizontal');
      $this->_form->addSelect('sort2',$this->_second_sort_tree,'','','', 1, false,false,false,'','','','',11);
      $this->_form->combine('horizontal');
      $this->_form->addButton('option',$this->_translator->getMessage('TAG_SORT_BUTTON'),'','',80);
      $this->_form->addButton('option',$this->_translator->getMessage('TAG_SORT_ABC'));
      $this->_form->addSubHeadline('headline3',cs_ucfirst($this->_translator->getMessage('TAG_COMBINE_BUTTON')),'','',3);
      
      $session_item = $this->_environment->getSessionItem();
      if(   $session_item->issetValue('tag_cannot_combine') &&
            $session_item->getValue('tag_cannot_combine') == 'true') {
         $this->_form->addText('tag_cannot_combine', '', $this->_translator->getMessage('TAG_CANNOT_COMBINE'), '', true, '', '', '', '', 'style="color: #FF0000;"');
         $session_item->unsetValue('tag_cannot_combine');
      }
      unset($session_item);
      
      $this->_form->addSelect('sel1',$this->_first_sort_tree,'','','', 1, false,false,false,'','','','',13.2);
      $this->_form->combine('horizontal');
      $this->_form->addSelect('sel2',$this->_first_sort_tree,'','','', 1, false,false,false,'','','','',13.2);
      $this->_form->combine('horizontal');
      $this->_form->addSelect('combine_father_id', $this->_values_tree, '', '', '', 1, false, false, false, '', '', '', $this->_translator->getMessage('TAG_WORD_TO'), 12);
      $this->_form->combine('horizontal');
      $this->_form->addButton('option',$this->_translator->getMessage('TAG_COMBINE_BUTTON'));
      $this->_form->addEmptyline();

      if ( isset($this->_root_tag) ) {
         $this->_form->addSubHeadline('headline4',cs_ucfirst($this->_translator->getMessage('COMMON_EDIT')),'','',3);
         $this->_createFormForChildren($this->_root_tag,0);
      }

      $session = $this->_environment->getSession();
      if ( !empty($_GET['module']) ) {
         $linked_rubric = $_GET['module'];
         $this->_form->addHidden('module',$linked_rubric);
         $session->setValue($this->_environment->getCurrentModule().'_linked_rubric',$linked_rubric);
      }
      unset($session);
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
      }
   }
}
?>