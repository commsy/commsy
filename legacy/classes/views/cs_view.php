<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez, Johannes Schultze
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

/** upper class for views of commsy
 * this class implements an upper class of views for commsy
 */
class cs_view {

   /**
    * string - a name that can uniquely identify this view if
    *          multiple views are displayed on one page
    */
   var $_name = NULL;


   var $_view_name = NULL;

   /**
    * integer - containing the id of the project room
    */
   var $_room_id = NULL;

   /**
    * string - containing the name of the module of commsy
    */
   var $_module = NULL;

   /**
    * string - containing the function (index,edit,...) of the module of commsy
    */
   var $_function = NULL;

   /**
    * object - holding the CommSy environment
    */
   var $_environment = NULL;

   /**
    * object - holding the translation object
    */
   var $_translator = NULL;

   /**
    * boolean - true, if display modifying actions - false, if not
    */
   var $_with_modifying_actions = true;

   /**
    * string - containing the anchor of the detail view
    */
   var $_anchor = NULL;

   var $_shown_as_printable = false;

   var $_parameter = NULL;

   var $_view_title = 'title unknown';

   var $_item_file_list = NULL;

   var $_with_slimbox = false;

   public $_class_factory = NULL;

   public $_text_converter;


   /** constructor: cs_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      if ( !empty($params['environment']) ) {
         $this->_environment = $params['environment'];
      } else {
         include_once('functions/error_functions.php');
         trigger_error('no environment defined '.__FILE__.' '.__LINE__,E_USER_ERROR);
      }
      $this->_with_modifying_actions = true;
      if ( !empty($params['with_modifying_actions']) ) {
         $this->_with_modifying_actions = $params['with_modifying_actions'];
      }
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      if ( $current_context->isClosed()
           or $current_user->isOnlyReadUser()
         ) {
         $this->_with_modifying_actions = false;
      }
      $this->_class_factory = $this->_environment->getClassFactory();
      $this->_room_id = $this->_environment->getCurrentContextID();
      $this->_module = $this->_environment->getCurrentModule();
      $this->_function = $this->_environment->getCurrentFunction();
      $this->_translator = $this->_environment->getTranslationObject();
      $this->_text_converter = $this->_environment->getTextConverter();
   }

   function getEnvironment () {
      return $this->_environment;
   }

   function getViewName(){
     return $this->_view_name;
   }

   function setViewName($value){
     $this->_view_name = (string)$value;
   }

   function getPortletJavascriptAsHTML(){
   	return '';
   }

   /** set anchor of view
    * this method sets the anchor of the view
    *
    * @param string value anchor of the view
    *
    * @author CommSy Development Group
    */
   function setAnchor ($value) {
      $this->_anchor = (string)$value;
   }

   /** set flag: display without modifying methods
    * this method sets the flag to display without modifying methods
    *
    * @author CommSy Development Group
    */
   function withoutModifyingActions () {
      $this->_with_modifying_actions = false;
   }

   /** get information for header as HTML
    * this method returns information in HTML-Code needs for the header of the HTML-Page (e.g. javascript)
    *
    * @return string nothing - needs to be overwritten
    *
    * @version $Revision$
    * @author CommSy Development Group
    */
   function getInfoForHeaderAsHTML () {
      $html = '';
      return $html;
   }

   function getStylesForHeaderAsHTML () {
      $html = '';
      return $html;
   }

   function getJavaScriptInfoArrayForHeaderAsHTML($array){
      if ($this->_with_slimbox){
         $array['slimbox'] = true;
         $array['mootools'] = true;
      }
      return $array;
   }

   /** get information for body as HTML
    * this method returns information in HTML-Code needs for the body of the HTML-Page
    *
    * @return string nothing - needs to be overwritten
    *
    * @version $Revision$
    * @author CommSy Development Group
    */
   function getInfoForBodyAsHTML () {
      $html = '';
      return $html;
   }

   /** get information about the fuction
    * this method returns information about the function of the view
    *
    * @return string function
    *
    * @version $Revision$
    * @author CommSy Development Group
    */
   function getFunction () {
      return $this->_function;
   }

   function setViewTitle($text){
      $this->_view_title = $text;
   }

   function getViewTitle(){
      return $this->_view_title;
   }
   /** set information about the module
    * this method sets information about the module of the view
    *
    * @return string function
    */
   function setModule ($value) {
      $this->_module = (string)$value;
   }

   function setPrintableView() {
      $this->_shown_as_printable = true;
   }
   function isPrintableView() {
      return $this->_shown_as_printable;
   }

   /** get view as HTML
    * this method returns the view in HTML-Code, needs to be overwritten
    */
   function asHTML () {
      echo('as HTML needs to be overwritten !!!<br />'."\n");
   }

   /** get prinatble view as HTML
    * this method returns the view in HTML-Code, needs to be overwritten
    */
   function asPrintableHTML () {
      echo('asPrintableHTML needs to be overwritten !!!<br />'."\n");
   }

   #######################################################################
   # text functions - BEGIN
   #######################################################################
   function _cleanDataFromTextArea ( $text ) {
      return $this->_text_converter->cleanDataFromTextArea($text);
   }

   function _text_as_html_long ($text,$htmlTextArea=true) {
      $this->_text_converter->setFileArray($this->_getItemFileListForView());
      return $this->_text_converter->text_as_html_long($text,$htmlTextArea);
   }

   function _text_as_html_short ($text) {
      return $this->_text_converter->text_as_html_short($text);
   }

   function _text_as_html_short_coding_format($text) {
      return $this->_text_converter->text_as_html_short_coding_format($text);
   }

   function _text_as_form ($text) {
      return $this->_text_converter->text_as_form($text);
   }

   function _text_as_form1 ($text) {
      return $this->_text_converter->text_as_form1($text);
   }

   function _text_as_form2 ($text) {
      return $this->_text_converter->text_as_form2($text);
   }

   function _text_as_form_for_html_editor ($text) {
      return $this->_text_converter->text_as_form_for_html_editor($text);
   }

   function _show_images($description,$item,$with_links = true) {
      return $this->_text_converter->showImages($description,$item,$with_links);
   }
   #######################################################################
   # text functions - END
   #######################################################################

   function _getItemFileListForView () {
      if ( !isset($this->_item_file_list) ) {
          if ( isset($this->_item) ) {
            if ( $this->_item->isA(CS_MATERIAL_TYPE) ) {
               $file_list = $this->_item->getFileListWithFilesFromSections();
            } elseif ( $this->_item->isA(CS_DISCUSSION_TYPE) ) {
               $file_list = $this->_item->getFileListWithFilesFromArticles();
            } elseif ( $this->_item->isA(CS_TODO_TYPE) ) {
               $file_list = $this->_item->getFileListWithFilesFromSteps();
            } else {
               $file_list = $this->_item->getFileList();
            }
          } else {
            if ($this->_environment->getCurrentModule() == 'home') {
               $current_context_item = $this->_environment->getCurrentContextItem();
               if ($current_context_item->withInformationBox()){
                  $id = $current_context_item->getInformationBoxEntryID();
                  $manager = $this->_environment->getItemManager();
                  $item = $manager->getItem($id);
                  $entry_manager = $this->_environment->getManager($item->getItemType());
                  $entry = $entry_manager->getItem($id);
                  $file_list = $entry->getFileList();
               }
            } else {
               $file_list = $this->_environment->getCurrentContextItem()->getFileList();
            }
         }
         if ( isset($this->_item) and $this->_item->isA(CS_SECTION_TYPE) ) {
            $material_item = $this->_item->getLinkedItem();
            $file_list2 = $material_item->getFileList();
            if ( isset($file_list2) and !empty($file_list2) and $file_list2->getCount() > 0 ) {
               $file_list->addList($file_list2);
            }
            unset($file_list2);
            unset($material_item);
         }
         if ( !empty($file_list) ) {
            $file_array = $file_list->to_Array();
            unset($file_list);
            $file_name_array = array();
            foreach ($file_array as $file) {
               $file_name_array[htmlentities($file->getDisplayName(), ENT_NOQUOTES, 'UTF-8')] = $file;
            }
            unset($file_array);
            $this->_item_file_list = $file_name_array;
            unset($file_name_array);
         }
      }
      return $this->_item_file_list;
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

  /** return a text indicating the modification state of an item
   * this method returns a string like [new] or [modified] depending
   * on the read state of the current user.
   *
   * @param  object item       a CommSy item (cs_item)
   *
   * @return string value
   */
   function _getItemChangeStatus($item) {
      $current_user = $this->_environment->getCurrentUserItem();
      if ($current_user->isUser()) {
         $noticed_manager = $this->_environment->getNoticedManager();
         $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
         if ( empty($noticed) ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_NEW').']</span>';
         } elseif ( $noticed['read_date'] < $item->getModificationDate() ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_CHANGED').']</span>';
         } else {
            $info_text = '';
         }
         // Add change info for annotations (TBD)
      } else {
         $info_text = '';
      }
      return $info_text;
   }
   
   function getPreferencesAsHTML(){
      return '';
   }
}
?>