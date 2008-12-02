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

/** class for commsy form: topic
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_topic_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing the materials of a topic
   */
   var $_material_array = array();

  /**
   * array - containing an array of materials form the session
   */
   var $_session_material_array = array();

  /**
   * array - containing an array of existing institution in the context
   */
   var $_institution_array = array();

  /**
   * boolean - true  -> institutions will be displayed
   *           false -> institutions will NOT be displayed
   */
   var $_institution_with = true;

   /**
   * array - containing the values for the edit status for the item (everybody or creator)
   */
   var $_public_array = array();

   var $_path_activated = false;

   var $_link_item_array = array();

   var $_link_item_place_array = array();

   var $_link_item_check_array = array();

   var $_path_button_disable = true;

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function cs_topic_form($environment) {
      $this->cs_rubric_form($environment);
   }

   /** set materials from session
    * set an array with the materials from the session
    *
    * @param array array of materials out of session
    *
    * @author CommSy Development Group
    */
   function setSessionMaterialArray ($value) {
      $this->_session_material_array = (array)$value;
   }

   function activatePath () {
     $this->_path_activated = true;
   }

   function deactivatePath () {
     $this->_path_activated = false;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example topics
    *
    * @author CommSy Development Group
    */
   function _initForm () {

      // public
      if ( isset($this->_item) ) {
         $creator_item = $this->_item->getCreatorItem();
         $fullname = $creator_item->getFullname();
      } elseif (!empty($this->_form_post['iid'])) {
         $manager = $this->_environment->getManager(CS_TOPIC_TYPE);
         $item = $manager->getItem($this->_form_post['iid']);
         $creator_item = $item->getCreatorItem();
         $fullname = $creator_item->getFullname();
      } else {
         $current_user = $this->_environment->getCurrentUser();
         $fullname = $current_user->getFullname();
      }
      $public_array = array();
      $temp_array['text']  = getMessage('RUBRIC_PUBLIC_YES');
      $temp_array['value'] = 1;
      $public_array[] = $temp_array;
      $temp_array['text']  = getMessage('RUBRIC_PUBLIC_NO', $fullname);
      $temp_array['value'] = 0;
      $public_array[] = $temp_array;
      $this->_public_array = $public_array;

      if (!empty($this->_item)) {
         $this->_headline = getMessage('TOPIC_EDIT');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = getMessage('TOPIC_EDIT');
         } else {
            $this->_headline = getMessage('TOPIC_ENTER_NEW');
            $new='';
            $context_item = $this->_environment->getCurrentContextItem();
            $rubric_array = $context_item->_getRubricArray(CS_TOPIC_TYPE);
            if (isset($rubric_array[strtoupper($this->_environment->getSelectedLanguage())]['GENUS']) ){
              $genus = $rubric_array[strtoupper($this->_environment->getSelectedLanguage())]['GENUS'];
            } else {
               $genus = $rubric_array['EN']['GENUS'];
            }
            if ($genus =='M'){
               $new = getMessage('COMMON_NEW_M_BIG').' ';
            }
            elseif ($genus =='F'){
               $new =  getMessage('COMMON_NEW_F_BIG').' ';
            }
            else {
               $new = getMessage('COMMON_NEW_N_BIG').' ';
            }

            $this->_headline = $new.$this->_headline;
         }
      } else {
         $this->_headline = getMessage('TOPIC_ENTER_NEW');
         $new='';
         $context_item = $this->_environment->getCurrentContextItem();
         $rubric_array = $context_item->_getRubricArray(CS_TOPIC_TYPE);
         if (isset($rubric_array[strtoupper($this->_environment->getSelectedLanguage())]['GENUS']) ){
           $genus = $rubric_array[strtoupper($this->_environment->getSelectedLanguage())]['GENUS'];
         } else {
            $genus = $rubric_array['EN']['GENUS'];
         }
         if ($genus =='M'){
            $new = getMessage('COMMON_NEW_M_BIG').' ';
         }
         elseif ($genus =='F'){
            $new =  getMessage('COMMON_NEW_F_BIG').' ';
         }
         else {
            $new = getMessage('COMMON_NEW_N_BIG').' ';
         }
         $this->_headline = $new.$this->_headline;
      }
      $this->setHeadline($this->_headline);

      // PATH
      if ( isset($this->_item) or isset($item) ) {

         $link_manager = $this->_environment->getLinkItemManager();
         if ( isset($this->_item) ) {
            $link_manager->setLinkedItemLimit($this->_item);
            $topic_item = $this->_item;
         } elseif ( isset($item) ) {
            $link_manager->setLinkedItemLimit($item);
            $topic_item = $item;
         }
         $link_manager->sortbySortingPlace();
         $link_manager->select();
         $link_item_list = $link_manager->get();


         if ( !$link_item_list->isEmpty() ) {
            $counter = 1;
            $link_item = $link_item_list->getFirst();
            while ($link_item) {
               $this->_link_item_place_array[$counter] = $link_item->getItemID();
               if ($link_item->getSortingPlace()) {
                  $this->_link_item_check_array[] = $link_item->getItemID();
               }
               $linked_item = $link_item->getLinkedItem($topic_item);
               $temp_array = array();
               $item_type = $linked_item->getItemType();
               if ($item_type == 'date') {
                  $item_type .= 's';
               }

               $temp_item_type = strtoupper($item_type);
               switch ( $temp_item_type )
               {
                  case 'ANNOUNCEMENT':
                     $temp_array['text'] = $this->_translator->getMessage('COMMON_ANNOUNCEMENT');
                     break;
                  case 'DATES':
                     $temp_array['text'] = $this->_translator->getMessage('COMMON_DATES');
                     break;
                  case 'INSTITUTION':
                     $temp_array['text'] = $this->_translator->getMessage('COMMON_INSTITUTION');
                     break;
                  case 'DISCUSSION':
                     $temp_array['text'] = $this->_translator->getMessage('COMMON_DISCUSSION');
                     break;
                  case 'USER':
                     $temp_array['text'] = $this->_translator->getMessage('COMMON_USER');
                     break;
                  case 'GROUP':
                     $temp_array['text'] = $this->_translator->getMessage('COMMON_GROUP');
                     break;
                  case 'MATERIAL':
                     $temp_array['text'] = $this->_translator->getMessage('COMMON_MATERIAL');
                     break;
                  case 'PROJECT':
                     $temp_array['text'] = $this->_translator->getMessage('COMMON_PROJECT');
                     break;
                  case 'TODO':
                     $temp_array['text'] = $this->_translator->getMessage('COMMON_TODO');
                     break;
                  case 'TOPIC':
                     $temp_array['text'] = $this->_translator->getMessage('COMMON_TOPIC');
                     break;
                  default:
                     $temp_array['text'] = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_topc_form('.__LINE__.') ');
                     break;
               }
               $temp_array['text'] .= ': '.$linked_item->getTitle();

               $link_item_sort_array[] = $link_item->getItemID();
               $temp_array['value'] = $link_item->getItemID();
               $this->_link_item_array[] = $temp_array;
               $link_item = $link_item_list->getNext();
               $counter++;
            }
         }
         if ( isset($this->_form_post['place_array'])
              and !empty($this->_form_post['place_array']) ) {
            $temp_array = array();
            $place_array_inv = array_flip($this->_form_post['place_array']);
            foreach ($this->_link_item_array as $item) {
               $temp_array[$place_array_inv[$item['value']]-1] = $item;
            }
            ksort($temp_array);
            $this->_link_item_array = $temp_array;
         }
         if ( isset($this->_link_item_array) and !empty($this->_link_item_array) ) {
            $this->_path_button_disable = false;
         }
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      // topic
      $this->_form->addHidden('iid','');
      $this->_form->addTitleField('name','',getMessage('COMMON_NAME'),getMessage('COMMON_NAME_DESC'),200,45,true);
      $format_help_link = ahref_curl($this->_environment->getCurrentContextID(), 'help', 'context',
                  array('module'=>$this->_environment->getCurrentModule(),'function'=>$this->_environment->getCurrentFunction(),'context'=>'HELP_COMMON_FORMAT'),
                  getMessage('HELP_COMMON_FORMAT_TITLE'), '', '_help', '', '',
                  'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"');
      $this->_form->addTextArea('description','',getMessage('COMMON_CONTENT'),getMessage('COMMON_CONTENT_DESC',$format_help_link),60);

      $current_context = $this->_environment->getCurrentContextItem();
      if ($current_context->withPath()){
         // PATH - BEGIN
         $this->_form->addEmptyline();
         if ( !$this->_path_activated ) {
            $this->_form->addHidden('path_active',-1);
            $this->_form->addButton('option',$this->_translator->getMessage('TOPIC_ACTIVATE_PATH'),'','','',$this->_path_button_disable);
            $this->_form->combine('vertical');
            $this->_form->addText('activate_path','',getMessage('TOPIC_ACTIVATE_PATH_DESCRIPTION'));
         } else {
            $this->_form->addHidden('path_active',1);
            $this->_form->addHidden('place_array',$this->_link_item_place_array);
            $this->_form->addButton('option',$this->_translator->getMessage('TOPIC_DEACTIVATE_PATH'),'','','',$this->_path_button_disable);
            $this->_form->combine('vertical');
            $this->_form->addText('activate_path','',getMessage('TOPIC_ACTIVATE_PATH_SELECT_DESCRIPTION'));
            $this->_form->addCheckboxGroup('sorting',$this->_link_item_array,$this->_link_item_check_array,$this->_translator->getMessage('TOPIC_PATH'),'','','','','','','',50,true,false,true);
         }
         // PATH - END
      }

      // public radio-buttons
      if ( !$this->_environment->inPrivateRoom() ){
         $this->_form->addEmptyline();
         if ( !isset($this->_item) ) {
            $this->_form->addRadioGroup('public',getMessage('RUBRIC_PUBLIC'),getMessage('RUBRIC_PUBLIC_DESC'),$this->_public_array);
         } else {
            $current_user = $this->_environment->getCurrentUser();
            $creator = $this->_item->getCreatorItem();
            if ($current_user->getItemID() == $creator->getItemID() or $current_user->isModerator()) {
               $this->_form->addRadioGroup('public',getMessage('RUBRIC_PUBLIC'),getMessage('RUBRIC_PUBLIC_DESC'),$this->_public_array);
            } else {
               $this->_form->addHidden('public','');
            }
         }
      } else {
         $this->_form->addHidden('public','');
      }

      // buttons
      $id = 0;
      if (isset($this->_item)) {
         $id = $this->_item->getItemID();
      } elseif (isset($this->_form_post)) {
         if (isset($this->_form_post['iid'])) {
            $id = $this->_form_post['iid'];
         }
      }
      // rubric connections
      $this->_setFormElementsForConnectedRubrics();

      if ( $id == 0 )  {
         $this->_form->addButtonBar('option',getMessage('TOPIC_SAVE_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'),'','','','','','','','','');
      } else {
         $this->_form->addButtonBar('option',getMessage('TOPIC_CHANGE_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'),'','','','','','','','',' onclick="saveData()"');
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
          $this->_values = $this->_form_post;
         if ( !isset($this->_values['public']) ) {
            $this->_values['public'] = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'1':'0'; //In projectrooms everybody can edit the item by default, else default is creator only
         }
      } elseif (isset($this->_item)) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['name'] = $this->_item->getName();
         $this->_values['description'] = $this->_item->getDescription();
         $this->_values['public'] = $this->_item->isPublic();
         $this->_setValuesForRubricConnections();
      } else {
         $this->_values['public'] = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'1':'0'; //In projectrooms everybody can edit the item by default, else default is creator only
      }
   }
}
?>