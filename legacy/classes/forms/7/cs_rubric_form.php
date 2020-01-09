<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

$this->includeClass(FORM);
include_once('functions/text_functions.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_rubric_form {

  /**
   * object - containing the environment object, set at constructor
   */
   var $_environment = NULL;

   var $_translator = NULL;

  /**
   * object - containing the item to edit
   */
   var $_item = NULL;

  /**
   * array - containing the variables of HTTP_POST_VARS
   */
   var $_form_post = NULL;

   /**
   * array - containing the values for the form
   */
   var $_values = array();

  /**
   * object - containing the form object
   */
   var $_form = NULL;

   /**
   * string - containing the module of this form
   */
   var $_module = NULL;

   /**
   * string - containing the function of this form
   */
   var $_function = NULL;

   /**
   * array - containing strings of error messages
   */
   var $_error_array = array();

   var $_rubric_connection_array = array();

   var $_headline_form = '';

  /**
   * integer - containing the file_id of the added file to check the checkbox at form
   */
   var $_select_file_id = NULL;

  /**
   * array - containing the files of an existing material
   */
   var $_file_array = array();

  /**
   * array - containing the files out of the session
   */
   var $_session_file_array = array();

   var $_with_multi_upload = false;



   /** constructor: cs_rubric_form
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function __construct($params) {
      if ( is_array($params) ) {
         $environment = $params['environment'];
      } else {
         $environment = $params;
      }
      $this->_environment = $environment;
      $this->_translator = $environment->getTranslationObject();
      $this->_form = new cs_form();
      $context_item = $this->_environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
         $first = array();
         $secon = array();
         foreach ( $room_modules as $module ) {
            $link_name = explode('_', $module);
            if ( $link_name[1] != 'none'
                 and $context_item->withRubric($link_name[0])
                 and $link_name[0] != CS_USER_TYPE
                 and $link_name[0] != CS_MYROOM_TYPE
               ) {
               $rubric_connections[] = $link_name[0];
            }
         }
         $this->_rubric_connection_array = $rubric_connections;
      }
   }

   /** set an item
    * set an item to edit it
    *
    * @param object item a cs_item
    *
    * @author CommSy Development Group
    */
   function setItem ($item) {
      $this->_item = (object)$item;
   }

   /** set form post data
    * set an array with the form post data
    *
    * @param array array an array: HTTP_POST_VARS
    */
   function setFormPost ($array) {
      $this->_form_post = $array;
   }

   /**
    * Internal methods for printing out connected rubrics.
    * Generally, these methods need not be overridden.
    */
   function _is_perspective ($rubric) {
      $in_array = in_array($rubric, array(CS_GROUP_TYPE,CS_TOPIC_TYPE)) ;
      return $in_array;
   }

   function setRubricConnections ($array) {
      $this->_rubric_connection_array = $array;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups, headline
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      include_once('functions/error_functions.php');trigger_error('must be overwritten',E_USER_ERROR);
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {
      include_once('functions/error_functions.php');trigger_error('must be overwritten',E_USER_ERROR);
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      include_once('functions/error_functions.php');trigger_error('must be overwritten',E_USER_ERROR);
   }

   /** prepare values and load it into form
    * this methods prepare the values in an arry and load it into the form
    */
   function loadValues () {
      $this->_prepareValues();
      $this->_form->loadValues($this->_values);
   }

   /** prepare form, init and create it
    * this methods prepare the form: init data and define the form
    *
    * @author CommSy Development Group
    */
   function prepareForm () {
      $this->_initForm();
      $this->_createForm();
   }

   /** get from elements
    * this methods returns the form elements to be show in the form_view
    *
    * return object list of form elements
    *
    * @author CommSy Development Group
    */
   function getFormElements () { //weg TBD
      return $this->_form->getFormElements();
   }

   /** check mandatory fields
    * this methods check mandatory fields
    *
    * return boolean is mandatory ?
    */
   function check () {
      $this->_form->checkMandatory();
      $this->_form->checkValues();
      $this->_error_array = $this->_form->getErrorArray();
      $this->_checkValues();
      if (count($this->_error_array) == 0) {
         $retour = true;
      } else {
         $retour = false;
      }
      return $retour;
   }

   /** specific check the values of the form
    * this methods check the entered values
    *
    * @author CommSy Development Group
    */
   function _checkValues () {
      // for children class:
      // you can fill an error_array with error messages here:
      // $this->_form->setFailure('label','error_type');
      // $this->_error_array[] = $this->_translator->getMessage('ERROR_TEXT');
   }

   /** set mark a field with "failure"
    * mark an element with failure and failure type
    *
    * @param string name name of the field of the form
    * @param string type type of the failure
    */
   function setFailure($name, $type='', $text='') {
      if (!empty($text)) {
         $this->_error_array[] = $text;
      }
      $this->_form->setFailure($name,$type);
   }

   /** get error array from form
    * this method returns the error array with error messages
    *
    * @return array an array of error messages
    */
   function getErrorArray () {
      return $this->_error_array;
   }


/***buzzwords and tags ***/

   /** set buzzwords from session
    * set an array with the buzzwords from the session
    *
    * @param array array of buzzwords out of session
    */
   function setSessionBuzzwordArray ($value) {
      $this->_session_buzzword_array = (array)$value;
   }

   /** set tags from session
    * set an array with the tags from the session
    *
    * @param array array of tags out of session
    */
   function setSessionTagArray ($value) {
      $this->_session_tag_array = (array)$value;
   }

   function _initTagArray($item = NULL, $ebene = 0) {
      if ( isset($item) ) {
         $list = $item->getChildrenList();
         if ( isset($list) and !$list->isEmpty() ) {
            $current_item = $list->getFirst();
            while ( $current_item ) {
               $temp_array = array();
               $text = '';
               $i = 0;
               while($i < $ebene){
                  $text .='>  ';
                  $i++;
               }
               $text .= $current_item->getTitle();
               $temp_array['text']  = $text;
               $temp_array['value'] = $current_item->getItemID();
               $this->_tag_array[] = $temp_array;
               $this->_initTagArray($current_item, $ebene+1);
               $current_item = $list->getNext();
            }
         }
      }
   }



   function _setFormElementsForConnectedRubrics () {
      foreach ( $this->_rubric_connection_array as $rubric ) {
         $this->_setFormElementForConnectedRubric($rubric);
      }
   }

   function _setFormElementForConnectedRubric ($type) {
      switch ( $type ) {
         case 'entry':
         case 'project':
         case 'todo':
         case 'material':
         case 'announcement':
         case 'news':
         case 'date':
         case 'discussion':
         case 'user':
         case 'contact':
            return $this->_setLinkListForConnectedRubric($type);
         case 'topic':
         case 'institution':
         case 'community':
         case 'group':
            return $this->_setCheckboxesForConnectedRubric($type);
         default:
            if ( !$this->_environment->isPlugin($type) ) {
               include_once('functions/error_functions.php');
               trigger_error('Unknown item type: "'.$type.'"',E_USER_ERROR);
            }
            break;
      }
   }


   function _setLinkListForConnectedRubric ($type) {
      $item = $this->_item;
      if (empty($item) and !empty($this->_form_post['iid']) and $this->_form_post['iid'] !='NEW') {
         $item_manager = $this->_environment->getItemManager();
         $item = $item_manager->getItem($this->_form_post['iid']);
         $item_type = $item->getItemType();
         if ($item_type == 'item' and !empty($this->_form_post['type'])) {
            $item_type = $this->_form_post['type'];
         }
         $manager = $this->_environment->getManager($item_type);
         $item = $manager->getItem($this->_form_post['iid']);
      }
      $user = $this->_environment->getCurrentUserItem();
      $item_array = array();
       // Get linked items from post vars
      if ( isset($this->_form_post) ) {
         if ( !empty($this->_form_post[$type]) ) {
            $linked_item_manager = $this->_environment->getManager($type);
            $linked_item_list = $linked_item_manager->getItemList($this->_form_post[$type]);
            if ( !$linked_item_list->isEmpty() ) {
               $link_manager = $this->_environment->getLinkItemManager();
               $linked_item = $linked_item_list->getFirst();
               while ( $linked_item ) {
                  $temp_array = array();
                  $link_manager->resetLimits();
                  $link_manager->setLinkedItemLimit($item);
                  $link_manager->setSecondLinkedItemLimit($linked_item);
                  $link_manager->select();
                  $link_item_list = $link_manager->get();
                  if ( !empty($link_item_list) and !$link_item_list->isEmpty() ) {
                     $link_item = $link_item_list->getFirst();
                  } else {
                     $link_item = NULL;
                  }
                  if ( $linked_item->getItemType() == CS_GROUP_TYPE
                       and $linked_item->isSystemLabel()
                       and isset($item)
                       and ($item->isA(CS_USER_TYPE) )
                     ) {
                     $temp_array['is_disabled'] = true;
                  }
                  $temp_array['value'] = $linked_item->getItemID();
                  if ($type==CS_USER_TYPE ){
                     $temp_array['text'] = chunkText($linked_item->getFullname(),50);
                  } else {
                     $temp_array['text'] = chunkText($linked_item->getTitle(),50);
                  }
                  if ( isset($link_item) and
                     $user->getItemID() != $link_item->getCreatorID() ) {
                     $link_creator = $link_item->getCreatorItem();
                     $temp_array['text'] .= '<BR><SPACE><SPACE><SPACE><SPACE><SPACE><DISABLED>('.
                                            $this->_translator->getMessage('COMMON_LINK_CREATOR').': '.
                                            $link_creator->getFullname().')</DISABLED>';
                  }
                  $item_array[] = $temp_array;
                  unset($temp_array);
                  $linked_item = $linked_item_list->getNext();
               }
            }
         }
      }

      // Get linked items from the item
      // (these are the initial / unmodified linked items)
      elseif ( isset($item) ) {
         $link_item_list = $item->getLinkItemList($type);
         if ( !$link_item_list->isEmpty() ) {
            $link_item = $link_item_list->getFirst();
            while ( $link_item ) {
            $temp_array = array();
               $linked_item = $link_item->getLinkedItem($item);
            if ( $linked_item->getItemType() == CS_GROUP_TYPE
                 and $linked_item->isSystemLabel()
                and isset($item)
                and ($item->isA(CS_USER_TYPE) )
               ) {
                  $temp_array['is_disabled'] = true;
            }
               $temp_array['value'] = $linked_item->getItemID();
               if ($type==CS_USER_TYPE ){
                  $temp_array['text'] = chunkText($linked_item->getFullname(),50);
               } else {
                  $temp_array['text'] = chunkText($linked_item->getTitle(),50);
               }
               if ( $user->getItemID() != $link_item->getCreatorID() ) {
                  $link_creator = $link_item->getCreatorItem();
                  $temp_array['text'] .= '<BR><SPACE><SPACE><SPACE><SPACE><SPACE><DISABLED>('.
                                         $this->_translator->getMessage('COMMON_LINK_CREATOR').': '.
                                         $link_creator->getFullname().')</DISABLED>';
               }
               $item_array[] = $temp_array;
            unset($temp_array);
               $link_item = $link_item_list->getNext();
            }
         }
      }

      // Sort by item title
      $field = 'text';
      usort($item_array,create_function('$a,$b','return strnatcasecmp($a[\''.$field.'\'],$b[\''.$field.'\']);'));

      // Create the form fields
      $this->_form->addNetNavigationContent($type, $item_array, '', '', false, false, true,$type);
   }

   function _setCheckboxesForConnectedRubric ($type) {
      $user = $this->_environment->getCurrentUserItem();
      $item = $this->_item;
      if (empty($item) and !empty($this->_form_post['iid']) and $this->_form_post['iid'] != 'NEW') {
         $item_manager = $this->_environment->getItemManager();
         $item = $item_manager->getItem($this->_form_post['iid']);
         $item_type = $item->getItemType();
         if ($item_type == 'item' and !empty($this->_form_post['type'])) {
            $item_type = $this->_form_post['type'];
         }
         $manager = $this->_environment->getManager($item_type);
         $item = $manager->getItem($this->_form_post['iid']);
      }
      $item_array = array();

      // Get all possibly linked items in this room
      // (Only works with label managers)
      $perspective_manager = $this->_environment->getManager($type);
      $perspective_manager->resetLimits();
      $perspective_manager->setContextLimit($this->_environment->getCurrentContextID());
      $perspective_manager->setTypeLimit($type);
      $perspective_manager->select();
      $perspective_item_list = $perspective_manager->get();

      // If count of perspective items is less than some limit
      // show all with checkboxes, else show buttonlist as with
      // non-perspectives
      if ( $perspective_item_list->getCount() < 10 ) {
         $link_manager = $this->_environment->getLinkItemManager();
         $linked_item = $perspective_item_list->getFirst();
         while ( $linked_item ) {
         $temp_array = array();
            $link_manager->resetLimits();
            $link_manager->setLinkedItemLimit($item);
            $link_manager->setSecondLinkedItemLimit($linked_item);
            $link_manager->select();
            $link_item_list = $link_manager->get();
            if ( !empty($link_item_list) and !$link_item_list->isEmpty() ){
               $link_item = $link_item_list->getFirst();
            } else {
               $link_item = NULL;
            }
         if ( $linked_item->getItemType() == CS_GROUP_TYPE
              and $linked_item->isSystemLabel()
             and isset($item)
             and ($item->isA(CS_USER_TYPE) )
            ) {
               $temp_array['is_disabled'] = true;
         }
         $temp_array['value'] = $linked_item->getItemID();
            if ($type == CS_USER_TYPE){
               $temp_array['text'] = chunkText($linked_item->getFullname(),50);
            } else {
               $temp_array['text'] = chunkText($linked_item->getTitle(),50);
            }
            if ( isset($link_item)
                 and $user->getItemID() != $link_item->getCreatorID() ) {
               $link_creator = $link_item->getCreatorItem();
               //uses tags only used by "_text_as_html_short_coding_format" function...
               $temp_array['text'] .= '<BR><SPACE><SPACE><SPACE><SPACE><SPACE><DISABLED>('.
                                      $this->_translator->getMessage('COMMON_LINK_CREATOR').': '.
                                      $link_creator->getFullname().')</DISABLED>';
            }
            $item_array[] = $temp_array;
         unset($temp_array);
            $linked_item = $perspective_item_list->getNext();
         }
         $this->_form->addNetNavigationContent($type, $item_array, '', '', false, false, false,$type);

      } else {
         $this->_setLinkListForConnectedRubric($type);
      }
   }

   function _setValuesForRubricConnections () {
      $item = $this->_item;
      foreach ($this->_rubric_connection_array as $rubric) {
         if ( isset($this->_form_post) ) {
            if ( !empty($this->_form_post[$rubric]) ) {
               $this->_values[$rubric] = $this->_form_post[$rubric];
            }
         } elseif ( isset($this->_item) ) {
            $mark_array = array();
            $linked_item_list = $item->getLinkedItemList($rubric);
            if ( !$linked_item_list->isEmpty() ) {
               $linked_item = $linked_item_list->getFirst();
               while ( $linked_item ) {
                  $mark_array[] = $linked_item->getItemID();
                  $linked_item = $linked_item_list->getNext();
               }
            }
            $this->_values[$rubric] = $mark_array;
         }
      }
   }

   /** reset rubric form
    *  reset this rubric form (item, values, postvars and the form [elements])
    */
   function reset () {
      unset($this->_form_post);
      unset($this->_values);
      unset($this->_item);
      $this->_form->reset();
   }

   /** get information for header as HTML
    * this method returns information in HTML-Code needs for the header of the HTML-Page
    *
    * @return string javascipt needed for the form
    */
   function getInfoForHeaderAsHTML () {
      // must be overwritten in subclasses
   }

   function getInfoForBodyAsHTML() {
      // must be overwritten in subclasses
   }

   function setHeadline ($value) {
      $this->_headline_form = $value;
   }

   function getHeadline () {
      $retour = $this->_headline_form;
      return $retour;
   }

   function translatorChangeToPortal () {
     $current_portal = $this->_environment->getCurrentPortalItem();
     if (isset($current_portal)) {
       $this->_translator->setContext(CS_PORTAL_TYPE);
       $this->_translator->setRubricTranslationArray($current_portal->getRubricTranslationArray());
       $this->_translator->setEmailTextArray($current_portal->getEmailTextArray());
     }
   }

   function translatorChangeToCurrentContext () {
     $current_context = $this->_environment->getCurrentContextItem();
     if (isset($current_context)) {
         if ($current_context->isCommunityRoom()) {
          $this->_translator->setContext(CS_COMMUNITY_TYPE);
         } elseif ($current_context->isProjectRoom()) {
          $this->_translator->setContext(CS_PROJECT_TYPE);
         } elseif ($current_context->isPortal()) {
          $this->_translator->setContext(CS_PORTAL_TYPE);
       } else {
          $this->_translator->setContext(CS_SERVER_TYPE);
       }
       $this->_translator->setRubricTranslationArray($current_context->getRubricTranslationArray());
       $this->_translator->setEmailTextArray($current_context->getEmailTextArray());
     }
   }

   ########## file upload ########################

   /** set a file id
    * set a file id to select the file, checkbox will be marked
    *
    * @param int value a file id
    */
   function setSelectFileID ($value) {
      $this->_select_file_id = (string)$value;
   }

   /** set files from session
    * set an array with the files from the session
    *
    * @param array array of files out of session
    */
   function setSessionFileArray ($value) {
      $this->_session_file_array = (array)$value;
   }

   function setWithMultiUpload () {
      $this->_with_multi_upload = true;
   }

   function setWithoutMultiUpload () {
      $this->_with_multi_upload = false;
   }
}
?>