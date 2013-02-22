<?php
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

$this->includeClass(VIEW);
include_once('classes/cs_list.php');
include_once('functions/curl_functions.php');

/**
 *  generic upper class for CommSy detail views
 */
class cs_detail_view extends cs_view {

   /**
    * array - an array of item ids to browse
    */
   var $_browse_ids = array();
   var $_rubric_connections = array();
   var $_sub_rubric_connections = array();

   var $_annotation_list = null;
   
   var $_assessment = null;

   var $_openCreatorInfo = null;

   /**
    * int - position in browsing list
    */
   var $_position = -1;

   var $_horizontal_line_number = 2;
   /**
    * item - containing the item to display
    */
   var $_item = NULL;

   /**
    * subitems - cs_list containing the item to display below the actual item (e.g. sections)
    */
   var $_subitems = NULL;

   var $_display_title = true;

   var $_with_slimbox = false;

   var $_right_box_config = array();

   /**
    * string - with search_text as keys
    */
   var $_search_text = NULL;

   /*
    * array containing all search expressions to be highlighted
    */
   var $_search_array = array();

   var $_show_content_without_window = false;


   var $_dropdown_image_array = array();

   var $_dropdown_message_array = array();

   var $_dropdown_rubrics_new = array();

   var $_tagBoxInitialized = false;

   /** constructor: cs_detail_view
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param string  viewname               a name for this view (e.g. news, dates)
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_detail_view ($params) {
      $creatorInfoStatus = array();
      if ( isset($params['creator_info_status']) ) {
         $creatorInfoStatus = $params['creator_info_status'];
      }
      $this->cs_view($params);
      $this->_openCreatorInfo = $creatorInfoStatus;
      $context_item = $this->_environment->getCurrentContextItem();
      $this->_right_box_config['title_string']  = '';
      $this->_right_box_config['desc_string']   = '';
      $this->_right_box_config['config_string'] = '';
      $this->_right_box_config['size_string']   = '';

      $this->_dropdown_image_array[CS_ANNOUNCEMENT_TYPE] = 'announcement';
      $this->_dropdown_image_array[CS_DATE_TYPE] = 'date';
      $this->_dropdown_image_array[CS_MATERIAL_TYPE] = 'material';
      $this->_dropdown_image_array[CS_DISCUSSION_TYPE] = 'discussion';
      $this->_dropdown_image_array[CS_GROUP_TYPE] = 'group';
      $this->_dropdown_image_array[CS_TODO_TYPE] = 'todo';
      $this->_dropdown_image_array[CS_TOPIC_TYPE] = 'topic';
      $this->_dropdown_image_array[CS_INSTITUTION_TYPE] = 'institution';

      $this->_dropdown_message_array[CS_ANNOUNCEMENT_TYPE] = 'DROPDOWN_NEW_ANNOUNCEMENT';
      $this->_dropdown_message_array[CS_DATE_TYPE] = 'DROPDOWN_NEW_DATE';
      $this->_dropdown_message_array[CS_MATERIAL_TYPE] = 'DROPDOWN_NEW_MATERIAL';
      $this->_dropdown_message_array[CS_DISCUSSION_TYPE] = 'DROPDOWN_NEW_DISCUSSION';
      $this->_dropdown_message_array[CS_GROUP_TYPE] = 'DROPDOWN_NEW_GROUP';
      $this->_dropdown_message_array[CS_TODO_TYPE] = 'DROPDOWN_NEW_TODO';
      $this->_dropdown_message_array[CS_TOPIC_TYPE] = 'DROPDOWN_NEW_TOPIC';
      $this->_dropdown_message_array[CS_INSTITUTION_TYPE] = 'DROPDOWN_NEW_INSTITUTION';

      $home_conf = $context_item->getHomeConf();
      $home_conf_array = explode(',',$home_conf);

      if(isset($_GET['mod'])){
         $dropdown_mod = $_GET['mod'];
      } elseif(isset($_POST['mod'])){
         $dropdown_mod = $_POST['mod'];
      } else {
         $dropdown_mod = '';
      }

      foreach($home_conf_array as $rubric){
         $temp_rubric_array = explode('_',$rubric);
         $temp_rubric = $temp_rubric_array[0];
         if($temp_rubric == 'announcement'){ #and $dropdown_mod != 'announcement'){
            $this->_dropdown_rubrics_new[] = CS_ANNOUNCEMENT_TYPE;
         } elseif($temp_rubric == 'date'){ # and $dropdown_mod != 'date'){
            $this->_dropdown_rubrics_new[] = CS_DATE_TYPE;
         }  elseif($temp_rubric == 'material'){ # and $dropdown_mod != 'material'){
            $this->_dropdown_rubrics_new[] = CS_MATERIAL_TYPE;
         }  elseif($temp_rubric == 'discussion'){ # and $dropdown_mod != 'discussion'){
            $this->_dropdown_rubrics_new[] = CS_DISCUSSION_TYPE;
         }  elseif($temp_rubric == 'group'){ # and $dropdown_mod != 'group'){
            $this->_dropdown_rubrics_new[] = CS_GROUP_TYPE;
         }  elseif($temp_rubric == 'todo'){ # and $dropdown_mod != 'todo'){
            $this->_dropdown_rubrics_new[] = CS_TODO_TYPE;
         }  elseif($temp_rubric == 'topic'){ # and $dropdown_mod != 'topic'){
            $this->_dropdown_rubrics_new[] = CS_TOPIC_TYPE;
         }  elseif($temp_rubric == 'institution'){ # and $dropdown_mod != 'topic'){
            $this->_dropdown_rubrics_new[] = CS_INSTITUTION_TYPE;
         }
      }
   }


   function setAnnotationList($annotation_list) {
      $this->_annotation_list = $annotation_list;
   }
   
   function setAssessment($assessment, $num_votes, $has_current_user_already_voted, $own_vote = -1, $detail) {
   	  $this->_assessment = array(	'assessment'	=> $assessment,
   	  								'num_votes'		=> $num_votes,
   	  								'already_voted'	=> $has_current_user_already_voted,
									'own_vote'		=> $own_vote,
   	  								'detail'		=> $detail);
   }

   function setExtraHorizontalLineNumbers($count) {
      $this->_horizontal_line_number = 2+$count;
   }

   /**
    * Set an array with the ids of all items shown in the last list view the
    * user saw to enable browsing within the detail views of those items.
    */
   function setBrowseIDs ($browse_ids) {
      $this->_browse_ids = array_values((array)$browse_ids);  // Re-Index array, starting at 0
   }

   function getBrowseIDs () {
      return $this->_browse_ids;
   }

   /**
    * Set the position of the current item in the browsing array.
    * Cannot be determined automatically, if the same item appears
    * multiple time, e.g. if ordered by group.
    */
   function setPosition ($pos) {
      $this->_position = (int)$pos;
   }

   function getPosition () {
      return $this->_position;
   }

   /**
    * Set an array of connected rubrics to be shown in the network
    * navigation area on the right side. Set for the main item and
    * subitems seperately.
    */
   function setRubricConnections ($item) {
      $user_manager = $this->_environment->getUserManager();
      $context_id = $this->_environment->getCurrentContextID();
      if ( !$this->_environment->inPortal()
           and !$this->_environment->inServer()
           and $this->_environment->getCurrentModule() != 'account'
         ) {
         $user_manager->getRoomUserByIDsForCache($context_id);
      }
      $context_item = $this->_environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      }
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
      /*
      // translation of entry to rubrics for new private room
      if ( $this->_environment->inPrivateRoom()
           and in_array(CS_ENTRY_TYPE,$rubric_connections)
         ) {
         $temp_array = array();
         $temp_array2 = array();
         $rubric_array2 = array();
         $temp_array[] = CS_ANNOUNCEMENT_TYPE;
         $temp_array[] = CS_TODO_TYPE;
         $temp_array[] = CS_DISCUSSION_TYPE;
         $temp_array[] = CS_MATERIAL_TYPE;
         $temp_array[] = CS_DATE_TYPE;
         foreach ( $temp_array as $temp_rubric ) {
            if ( !in_array($temp_rubric,$rubric_connections) ) {
               $temp_array2[] = $temp_rubric;
            }
         }
         foreach ( $rubric_connections as $temp_rubric ) {
            if ( $temp_rubric != CS_ENTRY_TYPE ) {
               $rubric_array2[] = $temp_rubric;
            } else {
               $rubric_array2 = array_merge($rubric_array2,$temp_array2);
            }
         }
         $rubric_connections = $rubric_array2;
         unset($rubric_array2);
      }
      */
      $this->_rubric_connections = $rubric_connections;
   }

   function getRubricConnections () {
      return $this->_rubric_connections;
   }

   function setSubItemRubricConnections ($rc) {
      $this->_sub_rubric_connections = $rc;
   }

   function getSubItemRubricConnections () {
      return $this->_sub_rubric_connections;
   }

   /**
    * Set the cs_item and optionally a list of subitems (also
    * of type cs_item) to display.
    */
   function setItem ($item){
      $this->_item = $item;
   }

   function getItem () {
      return $this->_item;
   }

   function setSubItemList ($subitems) {
      $this->_subitems = $subitems;
   }

   function getSubItemList () {
      return $this->_subitems;
   }

   function getAnnotationActionsAsHTML($item= NULL){
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $annotated_item = $this->getItem();
      $annotated_item_type = $annotated_item->getItemType();
        $item_manager = $this->_environment->getItemManager();
      $html  = '';
      if ( (
            $item->mayEdit($current_user) or
            $item_manager->getExternalViewerForItem($annotated_item->getItemID(),$current_user->getUserID())
         )

         and $this->_with_modifying_actions ) {
         $params = array();
         $params['iid'] = $item->getItemID();
         $params['mode'] = 'annotate';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/edit.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/edit.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                          'annotation',
                                          'edit',
                                          $params,
                                          $image,
                                          $this->_translator->getMessage('COMMON_EDIT_ITEM')).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/edit_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/edit_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_EDIT_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
      }
      if ( (
         $item->mayEdit($current_user)
         or
         $item_manager->getExternalViewerForItem($annotated_item->getItemID(),$current_user->getUserId())
         )
         and $this->_with_modifying_actions ) {
         $params = $this->_environment->getCurrentParameterArray();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         }
         $params = $this->_environment->getCurrentParameterArray();
         $params['action'] = 'delete';
         $params['annotation_iid'] = $item->getItemID();
         $params['iid'] = $annotated_item->getItemID();
         $params['annotation_action'] = 'delete';
         if ( !($this->_environment->getCurrentBrowser() =='MSIE'
                and $this->_environment->getCurrentBrowserVersion() != '7.0')
            ){
               $anchor = 'anchor'.$item->getItemID();
         }else{
            $anchor = '';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                          $this->_environment->getCurrentModule(),
                                          'detail',
                                          $params,
                                          $image,
                                          $this->_translator->getMessage('COMMON_DELETE_ITEM'),
                                          '',
                                          $anchor).BRLF;
           unset($params);
       } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_DELETE_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
      }
      return $html;
   }

   function _getDetailItemActionsAsHTML($item){
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';

      $html .= $this->_getEditAction($item,$current_user);
      $html .= $this->_getDeleteAction($item,$current_user);

      $html .='&nbsp;&nbsp;&nbsp;';
      return $html;

   }

   function _getDetailActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= $this->_getDetailItemActionsAsHTML($item);
      $html .= $this->_getAdditionalActionsAsHTML($item);
      $html .= $this->_getPrintAction($item,$current_user);
      if ( !$this->_environment->inPrivateRoom() ) {
         $html .= $this->_getMailAction($item,$current_user);
      }
      $html .= $this->_getDownloadAction($item,$current_user);
      $html .= $this->_getCopyAction($item,$current_user);

      // actions from rubric plugins
      $html .= plugin_hook_output_all('getDetailActionAsHTML',NULL,LF);

      $html .= $this->_getNewAction($item,$current_user);
      $html .= $this->_initDropDownMenus();
      return $html;
   }

   function _initDropDownMenus(){
      $action_array = array();
      $html = '';
      $current_context = $this->_environment->getCurrentContextItem();
      $current_portal = $this->_environment->getCurrentPortalItem();

      if ( $current_context->isOpen() ) {
         $image_new  = '';
         $href_new = '';
         $params = array();
         $params['iid'] = 'NEW';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image_new = 'images/commsyicons_msie6/22x22/new.gif';
         } else {
            $image_new = 'images/commsyicons/22x22/new.png';
         }
         $href_new = curl($this->_environment->getCurrentContextID(),
                          $this->_environment->getCurrentModule(),
                          'edit',
                          $params);
         unset($params);

         if(isset($_GET['mod'])){
            $dropdown_mod = $_GET['mod'];
         } elseif(isset($_POST['mod'])){
            $dropdown_mod = $_POST['mod'];
         } else {
            $dropdown_mod = '';
         }

         if($dropdown_mod == 'announcement'){
            $text_new = $this->_translator->getMessage('COMMON_ENTER_NEW_ANNOUNCEMENT');
         } elseif($dropdown_mod == 'date'){
            $text_new = $this->_translator->getMessage('COMMON_ENTER_NEW_DATE');
         } elseif($dropdown_mod == 'material'){
            $text_new = $this->_translator->getMessage('COMMON_ENTER_NEW_MATERIAL');
         } elseif($dropdown_mod == 'discussion'){
            $text_new = $this->_translator->getMessage('COMMON_ENTER_NEW_DISCUSSION');
         } elseif($dropdown_mod == 'group'){
            $text_new = $this->_translator->getMessage('COMMON_ENTER_NEW_GROUP');
         } elseif($dropdown_mod == 'todo'){
            $text_new = $this->_translator->getMessage('COMMON_ENTER_NEW_TODO');
         } elseif($dropdown_mod == 'topic'){
            $text_new = $this->_translator->getMessage('COMMON_ENTER_NEW_TOPIC');
         } elseif($dropdown_mod == 'institution'){
            $text_new = $this->_translator->getMessage('COMMON_ENTER_NEW_INSTITUTION');
         }

         if ( !empty($text_new)
              and !empty($image_new)
              and !empty($href_new)
            ) {
            $temp_array = array();
            $temp_array['dropdown_image']  = "new_icon";
            $temp_array['text']  = $text_new;
            $temp_array['image'] = $image_new;
            $temp_array['href']  = $href_new;
            $action_array[] = $temp_array;
            unset($temp_array);
         }
      }

      unset($current_context);

      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = '';
      $temp_array['image'] = 'seperator';
      $temp_array['href']  = '';
      $action_array[] = $temp_array;

      $action_array = array_merge($action_array, $this->_getAdditionalDropDownEntries());

      // init drop down menu
      if ( !empty($action_array)
           and count($action_array) > 1
         ) {
         $html .= '<script type="text/javascript">'.LF;
         $html .= '<!--'.LF;
         #$html .= 'var dropDownMenus = new Array(new Array("new_icon",new Array(';
         $html .= 'var dropDownMenus = new Array(';
         $first = true;
         foreach ($action_array as $action) {
            if ( $first ) {
               $first = false;
            } else {
               $html .= ',';
            }
            $html .= 'new Array("'.$action['dropdown_image'].'","'.$action['image'].'","'.$action['text'].'","'.$action['href'].'")';
         }
         $html .= ');'.LF;
         $html .= '-->'.LF;
         $html .= '</script>'.LF;
      }
      return $html;
   }

   #function _getAdditionalDropDownEntries() {
   #   return array();
   #}

   function _getAdditionalDropDownEntries() {
      $action_array = array();
      $current_context = $this->_environment->getCurrentContextItem();

      foreach($this->_dropdown_rubrics_new as $rubric){
         if ( $current_context->isOpen()) {
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image_import = 'images/commsyicons_msie6/22x22/'.$this->_dropdown_image_array[$rubric].'.gif';
            } else {
               $image_import = 'images/commsyicons/22x22/'.$this->_dropdown_image_array[$rubric].'.png';
            }
            $params = array();
            $params['iid'] = 'NEW';
            $params['linked_item'] = $this->_item->getItemID();
            $href_import = curl($this->_environment->getCurrentContextID(),
                               $rubric,
                               'edit',
                               $params);
            $text_import = $this->_translator->getMessage($this->_dropdown_message_array[$rubric]);
            if ( !empty($text_import)
                 and !empty($image_import)
                 and !empty($href_import)
               ) {
               $temp_array = array();
               $temp_array['dropdown_image']  = "new_icon";
               $temp_array['text']  = $text_import;
               $temp_array['image'] = $image_import;
               $temp_array['href']  = $href_import;
               $action_array[] = $temp_array;
               unset($temp_array);
            }
         }
      }

      unset($current_context);
      return $action_array;
   }

   function getScrollableContent($text,$item,$width,$width_link = true){
      $html = '';
      if (empty($width)){
         $session = $this->_environment->getSession();
         $left_menue_status = $session->getValue('left_menue_status');
         if ($left_menue_status != 'disapear') {
            if ($this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE){
               $width = '520';
            }else{
               $width = '640';
            }
         }else{
            $width = '640';
         }
      }
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
          $width = '600';
      }
      $params = $this->_environment->getCurrentParameterArray();
      if (!isset($params['mode']) or $params['mode'] != 'print'){
      $params['mode']='print';
      $anchor = '';
      if ($item->getType()=='section' or $item->getType()=='annotation' or $item->getType()=='discarticle'){
         $anchor = 'anchor'.$item->getItemID();
      }
      $link = '&gt; '.ahref_curl($this->_environment->getCurrentContextID(),
                                 $this->_environment->getCurrentModule(),
                                 'detail',
                                 $params,
                                 $this->_translator->getMessage('COMMON_LIST_WHOLE_CONTENT'),
                                 '',
                                 'help',
                                 $anchor,
                                 '',
                                 'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=800, height=500\');"',
                                 '',
                                 ''
                                 );
      $link = addslashes($link);
      $link = str_replace('</','COMMSYDHTMLTAG',$link);
      $html .= '<div style="margin:0px padding:0px;" id="handle_width_'.$item->getItemID().'"><div style="margin:0px padding:0px;" id="inner_handle_width_'.$item->getItemID().'" class="handle_width">'.$this->_show_images($text,$this->_item,$width_link).'</div></div>'.LF;
      $html .= '<script type="text/javascript"> handleWidth("handle_width_'.$item->getItemID().'","'.$width.'","'.$link.'");</script>';

      }else{
         $html .= $text;
      }
      return $html;
   }

   function _getAdditionalActionsAsHTML($item){
      $html = '';
      return $html;
   }


   function C ($item) {
      $html  = '';
      return $html;
   }


   function _getForwardBoxAsHTML () {
      $html = '';

      $html .= '<div style="margin-bottom:1px;">'.LF;
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title">'.LF;
      $ids = array();
      $params = array();
      if (isset($_GET['path']) and !empty($_GET['path'])){
         $topic_manager = $this->_environment->getManager(CS_TOPIC_TYPE);
         $topic_item = $topic_manager->getItem($_GET['path']);
         $path_item_list = $topic_item->getPathItemList();
         $path_item = $path_item_list->getFirst();
         $ids = array();
         while ($path_item){
            $ids[] = $path_item->getItemID();
            $path_item = $path_item_list->getNext();
         }
         $params['path'] = $_GET['path'];
         $html .= $this->_getForwardLinkAsHTML($ids,'path');
      }elseif(isset($_GET['search_path']) and !empty($_GET['search_path'])){
         $session = $this->_environment->getSessionItem();
         $ids = $session->getValue('cid'.$this->_environment->getCurrentContextID().'_campus_search_index_ids');
         $html .= $this->_getForwardLinkAsHTML($ids,'search');
         $params['search_path'] = $_GET['search_path'];
      }elseif(isset($_GET['link_item_path']) and !empty($_GET['link_item_path'])){
         $manager = $this->_environment->getItemManager();
         $item = $manager->getItem($_GET['link_item_path']);
         $ids = $item->getAllLinkedItemIDArray();
         $html .= $this->_getForwardLinkAsHTML($ids,'link_item');
         $params['link_item_path'] = $_GET['link_item_path'];
      }else{
         $ids = $this->getBrowseIDs();
         $html .= $this->_getForwardLinkAsHTML($ids);
      }
      if (empty($ids)){
         $ids = array();
         $ids[] = $this->_item->getItemID();
      }
      $html .='</div>'.LF;
      $html .= '<div class="right_box_main" style="padding:5px 0px 0px 0px;" >'.LF;

      $count = 0;
      $pos = 0;
      foreach($ids as $id){
         if ($id == $this->_item->getItemID()){
            $pos = $count;
         }else{
            $count++;
         }
      }
      $start = $pos-4;
      $end = $pos+4;
      if($start < 0){
          $end = $end - $start;
      }
      if($end > count($ids)){
        $end = count($ids);
        $start = $end-9;
        if ($start <0){
           $start = 0;
        }
      }
      $listed_ids = array();
      $count_items = 0;
      $html .='<ul style="list-style-type: none; list-style-position:inside; font-size:8pt; padding-left:0px; margin-left:0px; margin-top:0px; margin-bottom:2px; padding-bottom:0px;">  '.LF;
      $i = 1;
      foreach($ids as $id){
         if ($count_items >= $start and $count_items <= $end){
            $item_manager = $this->_environment->getItemManager();
            $tmp_item = $item_manager->getItem($id);
            $text = '';
            if ( isset($tmp_item) ) {
               $manager = $this->_environment->getManager($tmp_item->getItemType());
               $item = $manager->getItem($ids[$count_items]);
               $type = $tmp_item->getItemType();
               if ($type == 'label'){
                  $label_manager = $this->_environment->getLabelManager();
                  $label_item = $label_manager->getItem($tmp_item->getItemID());
                  $type = $label_item->getLabelType();
               }
               switch ( mb_strtoupper($type, 'UTF-8') ){
                  case 'ANNOUNCEMENT':
                     $text .= $this->_translator->getMessage('COMMON_ONE_ANNOUNCEMENT');
                     break;
                  case 'DATE':
                     $text .= $this->_translator->getMessage('COMMON_ONE_DATE');
                     break;
                  case 'DISCUSSION':
                     $text .= $this->_translator->getMessage('COMMON_ONE_DISCUSSION');
                     break;
                  case 'GROUP':
                     $text .= $this->_translator->getMessage('COMMON_ONE_GROUP');
                     break;
                  case 'INSTITUTION':
                     $text .= $this->_translator->getMessage('COMMON_ONE_INSTITUTION');
                     break;
                  case 'MATERIAL':
                     $text .= $this->_translator->getMessage('COMMON_ONE_MATERIAL');
                     break;
                  case 'PROJECT':
                     $text .= $this->_translator->getMessage('COMMON_ONE_PROJECT');
                     break;
                  case 'TODO':
                     $text .= $this->_translator->getMessage('COMMON_ONE_TODO');
                     break;
                  case 'TOPIC':
                     $text .= $this->_translator->getMessage('COMMON_ONE_TOPIC');
                     break;
                  case 'USER':
                     $text .= $this->_translator->getMessage('COMMON_ONE_USER');
                     break;
                  case 'ACCOUNT':
                     $text .= $this->_translator->getMessage('COMMON_ACCOUNTS');
                     break;
                  default:
                     $text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_detail_view('.__LINE__.') ';
                     break;
               }
            }


            if ( isset($item)
                 and is_object($item)
                 and $item->isA(CS_USER_TYPE)
               ) {
               $link_title = $item->getFullName();
            } elseif ( isset($item)
                       and is_object($item)
                     ) {
               $link_title = $item->getTitle();
            } else {
               $link_title = '';
            }
            $link_title = $this->_text_as_html_short($link_title);
            if ($this->_environment->getCurrentModule() == 'account'){
               $type = 'account';
            } elseif ( $this->_environment->getCurrentModule() == type2module(CS_MYROOM_TYPE) ) {
               $type = CS_MYROOM_TYPE;
            }
            if ($count_items < 9){
               $style='padding:0px 5px 0px 10px;';
            }else{
                $style='padding:0px 5px 0px 5px;';
            }
            $current_user_item = $this->_environment->getCurrentUserItem();
            if ( isset($item) and $item->getItemID()== $this->_item->getItemID()){
               $html .='<li class="detail_list_entry" style="'.$style.'">';
               $html .= '<span>'.($count_items+1).'. '.chunkText($link_title,35).'</span>';
               $html .='</li>';
            } elseif ( isset($item) and $item->isNotActivated() and !($item->getCreatorID() == $current_user_item->getItemID()) and !($current_user_item->isModerator())){
              $activating_date = $item->getActivatingDate();
               if (strstr($activating_date,'9999-00-00')){
                  $activating_text = $this->_translator->getMessage('COMMON_NOT_ACTIVATED');
               }else{
                  $activating_text = $this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($item->getActivatingDate());
               }
               $html .='<li class="disabled" style="'.$style.'">';
               $params['iid'] =   $item->getItemID();
               $html .= ($count_items+1).'. '.ahref_curl( $this->_environment->getCurrentContextID(),
                                 $type,
                                 $this->_environment->getCurrentFunction(),
                                 $params,
                                 chunkText($link_title,35),
                                 $text.' - '.$link_title . '&nbsp;(' . $activating_text . ')',
                                 '',
                                 '',
                                 '',
                                 '',
                                 '',
                                 'class="disabled"',
                                 '',
                                 '',
                                 true);
               $html .='</li>';
            } elseif ( isset($item) ) {
               $html .='<li style="'.$style.'">';
               $params['iid'] =   $item->getItemID();
               $html .= ($count_items+1).'. '.ahref_curl( $this->_environment->getCurrentContextID(),
                                 $type,
                                 $this->_environment->getCurrentFunction(),
                                 $params,
                                 chunkText($link_title,35),
                                 $text.' - '.$link_title,
                                 '',
                                 '',
                                 '',
                                 '',
                                 '',
                                 'class="detail_list"');
               $html .='</li>';
            }
            unset($item);
         }
         $count_items++;
      }
      $html .='</ul>';
      unset($params);
      $html .= '<div style="float:right; font-size:8pt; padding: 5px 3px 3px 0px;">'.LF;
      if (isset($_GET['path']) and !empty($_GET['path'])){
         $topic_manager = $this->_environment->getTopicManager();
         $topic_item = $topic_manager->getItem($_GET['path']);
         $params = array();
         $params['iid'] = $_GET['path'];
         $html .= $this->_translator->getMessage('COMMON_BACK_TO_PATH').': '.ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_TOPIC_TYPE,
                           'detail',
                           $params,
                           chunkText($topic_item->getTitle(),30)
                           );
      }elseif (isset($_GET['search_path']) and !empty($_GET['search_path'])){
         $params = array();
         $params['back_to_search'] = 'true';
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                           'campus_search',
                           'index',
                           $params,
                           $this->_translator->getMessage('COMMON_BACK_TO_SEARCH')
                           );
      }elseif(isset($_GET['link_item_path']) and !empty($_GET['link_item_path'])){
         $params = array();
         $params['iid'] = $_GET['link_item_path'];
         $item_manager = $this->_environment->getItemManager();
         $tmp_item = $item_manager->getItem($_GET['link_item_path']);
         $manager = $this->_environment->getManager($tmp_item->getItemType());
         $item = $manager->getItem($_GET['link_item_path']);
         $type = $tmp_item->getItemType();
         if ($type == 'label'){
            $label_manager = $this->_environment->getLabelManager();
            $label_item = $label_manager->getItem($tmp_item->getItemID());
            $type = $label_item->getLabelType();
         }
         $manager = $this->_environment->getManager($type);
         $item = $manager->getItem($_GET['link_item_path']);
         if($type == CS_USER_TYPE){
             $link_title = $this->_text_as_html_short($item->getFullName());
         } else {
             $link_title = $this->_text_as_html_short($item->getTitle());
         }
         $html .= $this->_translator->getMessage('COMMON_BACK_TO_ITEM').': '.ahref_curl( $this->_environment->getCurrentContextID(),
                           $type,
                           'detail',
                           $params,
                           chunkText($link_title,20),
                           $link_title
                           );
      }else{
         $display_mod = $this->_environment->getValueOfParameter('seldisplay_mode');
         if ( empty($display_mod) ) {
            $session = $this->_environment->getSessionItem();
            if ( $session->issetValue($this->_environment->getCurrentContextID().'_dates_seldisplay_mode') ) {
               $display_mod = $session->getValue($this->_environment->getCurrentContextID().'_dates_seldisplay_mode');
            }
         }
         $params = array();
         $params['back_to_index'] = 'true';
         $link_text = $this->_translator->getMessage('COMMON_BACK_TO_LIST');
         $link_module = $this->_environment->getCurrentModule();
         if ( module2type($this->_environment->getCurrentModule()) == CS_DATE_TYPE
              and !empty($display_mod)
              and $display_mod == 'calendar'
            ) {
            $link_text = $this->_translator->getMessage('DATE_BACK_TO_CALENDAR');
         }
         if ( module2type($this->_environment->getCurrentModule()) == CS_DATE_TYPE
              and $this->_environment->inPrivateRoom()
              and $this->_environment->getConfiguration('c_use_new_private_room')
            ) {
            $link_text = $this->_translator->getMessage('COMMON_BACK_TO_INDEX');
         }
         if ( module2type($this->_environment->getCurrentModule()) == CS_TODO_TYPE
              and $this->_environment->inPrivateRoom()
              and $this->_environment->getConfiguration('c_use_new_private_room')
            ) {
            $link_text = $this->_translator->getMessage('COMMON_BACK_TO_INDEX');
            $link_module = type2module(CS_DATE_TYPE);
         }
         if ( $this->_environment->inPrivateRoom()
              and $this->_environment->getConfiguration('c_use_new_private_room')
              and ( module2type($this->_environment->getCurrentModule()) == CS_MATERIAL_TYPE
                    or module2type($this->_environment->getCurrentModule()) == CS_DISCUSSION_TYPE
                    or module2type($this->_environment->getCurrentModule()) == CS_ANNOUNCEMENT_TYPE
                    or module2type($this->_environment->getCurrentModule()) == CS_TOPIC_TYPE
                  )
            ) {
            $link_text = $this->_translator->getMessage('COMMON_BACK_TO_INDEX');
            $link_module = type2module(CS_ENTRY_TYPE);
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                           $link_module,
                           'index',
                           $params,
                           $link_text
                           );
      }
      $html .= '</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .= '</div>'.LF;
      $html .='</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }



   function getBuzzwordSizeLogarithmic( $count, $mincount=0, $maxcount=30, $minsize=10, $maxsize=20, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
   }

   function getBuzzwordColorLogarithmic( $count, $mincount=0, $maxcount=30, $minsize=30, $maxsize=70, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
   }



   function _getBuzzwordBoxAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      if(!empty($this->_right_box_config['title_string'])){
         $separator = ',';
      }else{
         $separator = '';
      }
      $item = $this->getItem();
      $buzzword_list = $item->getBuzzwordList();
      $count_link_item = $buzzword_list->getCount();
      $this->_right_box_config['title_string'] .= $separator.'"'.$this->_translator->getMessage('COMMON_ATTACHED_BUZZWORDS').' ('.$count_link_item.')"';
      $this->_right_box_config['desc_string'] .= $separator.'""';
      $this->_right_box_config['size_string'] .= $separator.'"10"';
      if($current_context->isBuzzwordShowExpanded()){
         $this->_right_box_config['config_string'] .= $separator.'true';
      } else {
         $this->_right_box_config['config_string'] .= $separator.'false';
      }
      $current_user = $this->_environment->getCurrentUserItem();
      $params = $this->_environment->getCurrentParameterArray();
      $buzzword_entry = $buzzword_list->getFirst();
      $item_id_array = array();
      while($buzzword_entry){
         $item_id_array[] = $buzzword_entry->getItemID();
         $buzzword_entry = $buzzword_list->getNext();
      }
      if ( isset($item_id_array[0]) ){
         $links_manager = $this->_environment->getLinkManager();
         $count_array = $links_manager->getCountLinksFromItemIDArray($item_id_array,'buzzword');
      }
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_ATTACHED_BUZZWORDS').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main">'.LF;
      $html .= '<div>'.LF;
      if ($buzzword_list ->isEmpty()) {
         $html .= '   <div style="padding:0px 5px; font-size:8pt;" class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</div>'.LF;
      }else{
         $buzzword_entry = $buzzword_list->getFirst();
         while($buzzword_entry){
            $count = 0;
            if ( isset($count_array[$buzzword_entry->getItemID()]) ){
                $count = $count_array[$buzzword_entry->getItemID()];
            }
            $font_size = $this->getBuzzwordSizeLogarithmic($count);
            $font_color = 100 - $this->getBuzzwordColorLogarithmic($count);
            $params['selbuzzword'] = $buzzword_entry->getItemID();
            $temp_text = '';
            $style_text  = 'style="margin-left:2px; margin-right:2px;';
            $style_text .= ' color: rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
            $style_text .= 'font-size:'.$font_size.'px;"';
            $title  = '<span  '.$style_text.'>'.LF;
            $title .= $this->_text_as_html_short($buzzword_entry->getName()).LF;
            $title .= '</span> ';
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                $this->_environment->getCurrentModule(),
                                'index',
                                $params,
                                $title,$title).LF;
           $buzzword_entry = $buzzword_list->getNext();
         }
      }
      $html .= '<div style="width:235px; font-size:8pt; text-align:right; padding-top:5px;">';
      if ($current_user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         $params = $this->_environment->getCurrentParameterArray();
         $params['attach_view'] = 'yes';
         $params['attach_type'] = 'buzzword';
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
                             $this->_environment->getCurrentModule(),
                             $this->_environment->getCurrentFunction(),
                             $params,
                             $this->_translator->getMessage('COMMON_BUZZWORD_ATTACH')
                             ).LF;
         unset($params);
      } else {
         $html .= '<span class="disabled">'.$this->_translator->getMessage('COMMON_BUZZWORD_ATTACH').'</span>'.LF;
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      unset($current_user);
      unset($current_context);
      return $html;
   }

   function _getWorkflowBoxAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      if(!empty($this->_right_box_config['title_string'])){
         $separator = ',';
      }else{
         $separator = '';
      }
      $item = $this->getItem();

      $this->_right_box_config['title_string'] .= $separator.'"'.$this->_translator->getMessage('COMMON_WORKFLOW').'"';
      $this->_right_box_config['desc_string'] .= $separator.'""';
      $this->_right_box_config['size_string'] .= $separator.'"10"';
      #if($current_context->isWorkflowShowExpanded()){
         $this->_right_box_config['config_string'] .= $separator.'true';
      #} else {
      #   $this->_right_box_config['config_string'] .= $separator.'false';
      #}
      
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_ATTACHED_BUZZWORDS').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main">'.LF;
      $html .= '<div>'.LF;
      
      $traffic_light = '';
      if($item->getWorkflowTrafficLight() == 'none'){
         $traffic_light = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_NONE');
      }else if($item->getWorkflowTrafficLight() == 'green'){
         $alt_title = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_GREEN_DEFAULT');
         if($current_context->getWorkflowTrafficLightTextGreen() != ''){
            $alt_title = $current_context->getWorkflowTrafficLightTextGreen();
         }
         $traffic_light = '<img src="images/commsyicons/workflow_traffic_light_green.png" alt="'.$alt_title.'" title="'.$alt_title.'">';
      }else if($item->getWorkflowTrafficLight() == 'yellow'){
         $alt_title = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_YELLOW_DEFAULT');
         if($current_context->getWorkflowTrafficLightTextYellow() != ''){
            $alt_title = $current_context->getWorkflowTrafficLightTextYellow();
         }
         $traffic_light = '<img src="images/commsyicons/workflow_traffic_light_yellow.png" alt="'.$alt_title.'" title="'.$alt_title.'">';
      }else if($item->getWorkflowTrafficLight() == 'red'){
         $alt_title = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_RED_DEFAULT');
         if($current_context->getWorkflowTrafficLightTextRed() != ''){
            $alt_title = $current_context->getWorkflowTrafficLightTextRed();
         }
         $traffic_light = '<img src="images/commsyicons/workflow_traffic_light_red.png" alt="'.$alt_title.'" title="'.$alt_title.'">';
      }
      $html .= $traffic_light;
      
      #$html .= '<div style="width:235px; font-size:8pt; text-align:right; padding-top:5px;">';
      #$html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      unset($current_context);
      return $html;
   }
   
   function getTagColorLogarithmic( $count, $mincount=0, $maxcount=5, $minsize=0, $maxsize=40, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
   }

   function _getTagBoxAsHTML($item){
      $current_user = $this->_environment->getCurrentUserItem();
      $current_context = $this->_environment->getCurrentContextItem();
      if(!empty($this->_right_box_config['title_string'])){
         $separator = ',';
      }else{
         $separator = '';
      }
      $tag_list = $item->getTagList();
      $count_link_item = $tag_list->getCount();
      $this->_right_box_config['title_string'] .= $separator.'"'.$this->_translator->getMessage('COMMON_ATTACHED_TAGS').' ('.$count_link_item.')"';
      $this->_right_box_config['desc_string'] .= $separator.'""';
      $this->_right_box_config['size_string'] .= $separator.'"10"';
      if($current_context->isTagsShowExpanded()){
         $this->_right_box_config['config_string'] .= $separator.'true';
      } else {
         $this->_right_box_config['config_string'] .= $separator.'false';
      }
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_TAGS').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" >'.LF;

      $text = '';
      $tag2tag_manager = $this->_environment->getTag2TagManager();
      $tag_manager = $this->_environment->getTagManager();
      $tag_item = $tag_list->getFirst();
      if ( isset ($tag_item) ){
         $params = $this->_environment->getCurrentParameterArray();
         while( $tag_item ){
            $text .= '<div style="margin-bottom:5px;">';
            $count_all = 1;
            $shown_tag_array = $tag2tag_manager->getFatherItemIDArray($tag_item->getItemID());
            $i = 1;
            if( !empty($shown_tag_array) ) {
               $count_all = count($shown_tag_array);
               $shown_tag_array = array_reverse($shown_tag_array);
               foreach( $shown_tag_array as $shown_tag ){
                  $father_tag_item = $tag_manager->getItem($shown_tag);
                  $count = $count_all - $i + 1;
                  $ebene = $i-1;
                  $font_size = round(13 - (($count*0.2)+$count));
                  $font_weight = 'normal';
                  $font_style = 'normal';
                  if ($font_size < 8){
                     $font_size = 8;
                  }
                  $font_color = 20 + $this->getTagColorLogarithmic($count);
                  $color = 'rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
                  if (($ebene*15) <= 30){
                     $text .= '<div style="padding-left:'.($ebene*15).'px; color:'.$color.'; font-style:'.$font_style.'; font-size:'.$font_size.'px; font-weight:'.$font_weight.';">';
                  }else{
                     $text .= '<div style="padding-left:40px; color:'.$color.'; font-size:'.$font_size.'px; font-style:'.$font_style.'; font-weight:'.$font_weight.';">';
                  }
                  $params['seltag'] = 'yes';
                  if ( isset($father_tag_item) ) {
                     $params['seltag_'.($count_all-$i)] = $father_tag_item->getItemID();
                  }
                  $title_link = ahref_curl($this->_environment->getCurrentContextID(),
                                $this->_environment->getCurrentModule(),
                                'index',
                                $params,
                                $this->_text_as_html_short($father_tag_item->getTitle()),
                                $this->_text_as_html_short($father_tag_item->getTitle()),
                                '',
                                '',
                                '',
                                '',
                                '',
                                'style="color:'.$color.'"').LF;
                  $text .= '- '.$title_link;
                  $text .= '</div>';
                  $i++;
               }
            }
            $params['seltag'] = 'yes';
            $params['seltag_'.($count_all-1)] = $tag_item->getItemID();
            $count = $count_all - $i + 1;
            $ebene = $i-1;
            $font_size = 13;
            $font_weight = 'normal';
            $font_style = 'normal';
            $font_color = 20 + $this->getTagColorLogarithmic($count);
            $color = 'rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
            $title_link = ahref_curl($this->_environment->getCurrentContextID(),
                             $this->_environment->getCurrentModule(),
                             'index',
                             $params,
                             $this->_text_as_html_short($tag_item->getTitle()),
                             $this->_text_as_html_short($tag_item->getTitle()),
                             '',
                             '',
                             '',
                             '',
                             '',
                             'style="color:'.$color.'"').LF;
            $text .= '<div style="padding-left:'.($ebene*15).'px; color:'.$color.'; font-style:'.$font_style.'; font-size:'.$font_size.'px; font-weight:'.$font_weight.';">';
            $text .= '- '.$title_link;
            $text .= '</div>';
            $text .= '</div>';
            $tag_item = $tag_list->getNext();
         }

      }
      if ( empty($text) ){
         $html .= '   <div style="padding:0px 5px; font-size:8pt;" class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</div>'.LF;
      }else{
         $html .= $text;
      }
      $html .= '<div style="width:235px; font-size:8pt; text-align:right; padding-top:5px;">';
      if ($current_user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         $params = $this->_environment->getCurrentParameterArray();
         $params['attach_view'] = 'yes';
         $params['attach_type'] = 'tag';
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
                             $this->_environment->getCurrentModule(),
                             $this->_environment->getCurrentFunction(),
                             $params,
                             $this->_translator->getMessage('COMMON_TAG_ATTACH')
                             ).LF;
         unset($params);
      } else {
         $html .= '<span class="disabled">'.$this->_translator->getMessage('COMMON_TAG_ATTACH').'</span>'.LF;
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      unset($current_user);
      return $html;
   }

   function _getTagBoxAsHTMLWithJavascript($item, $distance = 0, $ebene = 0, $with_div = false, $tagged_item){
      // MUSEUM
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      if ( isset($item) ) {
         $list = $item->getChildrenList();
         if ( isset($list) and !$list->isEmpty() ) {
            $this->_tagBoxInitialized = true;
            $tag_list = $tagged_item->getTagList();
            if($with_div){
               $current_user = $this->_environment->getCurrentUserItem();
               $current_context = $this->_environment->getCurrentContextItem();
               if(!empty($this->_right_box_config['title_string'])){
                  $separator = ',';
               }else{
                  $separator = '';
               }
               $count_link_item = $tag_list->getCount();
               $this->_right_box_config['title_string'] .= $separator.'"'.$this->_translator->getMessage('COMMON_ATTACHED_TAGS').' ('.$count_link_item.')"';
               $this->_right_box_config['desc_string'] .= $separator.'""';
               $this->_right_box_config['size_string'] .= $separator.'"10"';
               if($current_context->isTagsShowExpanded()){
                  $this->_right_box_config['config_string'] .= $separator.'true';
               } else {
                  $this->_right_box_config['config_string'] .= $separator.'false';
               }
               $html .= '<div class="right_box">'.LF;
               $html .= '         <noscript>';
               $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_TAGS').'</div>';
               $html .= '         </noscript>';
               $html .= '<div class="right_box_main" >'.LF;
               $html .= '<div id="tag_tree" name="tag_tree_detail">';
            }
            $html .= '<ul>'.LF; // oberstes <ul>
            $current_item = $list->getFirst();
            $distance = $distance +1;
            $font_weight ='normal';
            $link_name = '';
            $font_color = 30;
            $font_style = 'normal';
            $i = 1;
            while ( $current_item ) {
               $font_weight = 'normal';
               $link_name = '';
               $tag_item = $tag_list->getFirst();
               if ( isset ($tag_item) ){
                  while( $tag_item ){
                     if($tag_item->getItemID() == $current_item->getItemID()){
                        $font_weight = 'bold';
                        $link_name = 'selected';
                     }
                     $tag_item = $tag_list->getNext();
                  }
               }
               $id = $current_item->getItemID();
               $tag2tag_manager = $this->_environment->getTag2TagManager();
               $count = count($tag2tag_manager->getFatherItemIDArray($id));
               $font_size = round(13 - (($count*0.2)+$count));
               if ($font_size < 8){
                  $font_size = 8;
               }
               $font_color = 20 + $this->getTagColorLogarithmic($count);
               $color = 'rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
               $title = $this->_text_as_html_short($current_item->getTitle());
               $params['seltag_'.$ebene] = $current_item->getItemID();
               $params['name'] = $link_name;
               if( isset($params['seltag']) ){
                  $i = $ebene+1;
                  while( isset($params['seltag_'.$i]) ){
                     unset($params['seltag_'.$i]);
                     $i++;
                  }
               }
               $params['seltag'] = 'yes';
               $link = curl($this->_environment->getCurrentContextID(),
                             'campus_search',
                             'index',
                             $params);
               #$html .= '<li id="' . $current_item->getItemID() . '" data="url: \'' . $link . '\'" style="color:'.$color.'; font-style:'.$font_style.'; font-size:'.$font_size.'px; font-weight:'.$font_weight.';">'.LF;
               $html .= '<li id="' . $current_item->getItemID() . '" data="url: \'' . $link . '\'" style="color:#545454; font-style:normal; font-size:10pt; font-weight:normal;">'.LF;
               #$html .= ahref_curl($this->_environment->getCurrentContextID(),
               #              'campus_search',
               #              'index',
               #              $params,
               #              $title,$title,'','','','','','style="color:'.$color.'"').LF;
               $html .= ahref_curl($this->_environment->getCurrentContextID(),
                             'campus_search',
                             'index',
                             $params,
                             $title,$title,'','','','','','style="color:#545454; font-size:10pt; font-weight:' . $font_weight . ';"').LF;
               $html .= $this->_getTagBoxAsHTMLWithJavascript($current_item, 0, 0, false, $tagged_item);
               $current_item = $list->getNext();
               $i++;
               $html.='</li>'.LF;
            }
            $html.='</ul>'.LF;
            if($with_div){
               $html .= '</div>'.LF;
               $html .= '<div style="width:235px; font-size:8pt; text-align:right; padding-top:5px;">';
               if ($current_user->isUser() and $this->_with_modifying_actions ) {
                  $params = array();
                  $params = $this->_environment->getCurrentParameterArray();
                  $params['attach_view'] = 'yes';
                  $params['attach_type'] = 'tag';
                  $html .= ahref_curl($this->_environment->getCurrentContextID(),
                             $this->_environment->getCurrentModule(),
                             $this->_environment->getCurrentFunction(),
                             $params,
                             $this->_translator->getMessage('COMMON_TAG_ATTACH')
                             ).LF;
                  unset($params);
               } else {
                  $html .= '<span class="disabled">'.$this->_translator->getMessage('COMMON_TAG_ATTACH').'</span>'.LF;
               }
               $html .= '</div>'.LF;
               $html .= '</div>'.LF;
               $html .= '</div>'.LF;
            }
         } elseif (!$this->_tagBoxInitialized) {
            if(!empty($this->_right_box_config['title_string'])){
               $separator = ',';
            }else{
               $separator = '';
            }
            #$count_link_item = $tag_list->getCount();
            $this->_right_box_config['title_string'] .= $separator.'"'.$this->_translator->getMessage('COMMON_ATTACHED_TAGS').' (0)"';
            $this->_right_box_config['desc_string'] .= $separator.'""';
            $this->_right_box_config['size_string'] .= $separator.'"10"';
            $current_context = $this->_environment->getCurrentContextItem();
            if($current_context->isTagsShowExpanded()){
               $this->_right_box_config['config_string'] .= $separator.'true';
            } else {
               $this->_right_box_config['config_string'] .= $separator.'false';
            }
            $html .= '<div class="right_box">'.LF;
            $html .= '         <noscript>';
            $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_TAGS').'</div>';
            $html .= '         </noscript>';
            $html .= '<div class="right_box_main" >'.LF;
            $html .= '<div style="padding:0px 5px; font-size:8pt;" class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</div>'.LF;
            $html .= '<div style="width:235px; font-size:8pt; text-align:right; padding-top:5px;">';
            $current_user = $this->_environment->getCurrentUserItem();
            if ($current_user->isUser() and $this->_with_modifying_actions ) {
               $params = array();
               $params = $this->_environment->getCurrentParameterArray();
               $params['attach_view'] = 'yes';
               $params['attach_type'] = 'tag';
               $html .= ahref_curl($this->_environment->getCurrentContextID(),
                          $this->_environment->getCurrentModule(),
                          $this->_environment->getCurrentFunction(),
                          $params,
                          $this->_translator->getMessage('COMMON_TAG_ATTACH')
                          ).LF;
               unset($params);
            } else {
               $html .= '<span class="disabled">'.$this->_translator->getMessage('COMMON_TAG_ATTACH').'</span>'.LF;
            }
            $html .= '</div>'.LF;
            $html .= '</div>'.LF;
            $html .= '</div>'.LF;
         }
      }
      return $html;
   }

   function showBuzzwords(){
      $retour = false;
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->withBuzzwords()
          and ( $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                or $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
                or $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
                or $this->_environment->getCurrentModule() == CS_TODO_TYPE
                or $this->_environment->getCurrentModule() == CS_DATE_TYPE
                or $this->_environment->getCurrentModule() == 'campus_search')
      ){
         $retour = true;
      }
      return $retour;
   }

   function showTags(){
      $retour = false;
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->withTags()
          and ( $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                or $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
                or $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
                or $this->_environment->getCurrentModule() == CS_TODO_TYPE
                or $this->_environment->getCurrentModule() == CS_DATE_TYPE
                or $this->_environment->getCurrentModule() == 'campus_search')
      ){
         $retour = true;
      }
      return $retour;
   }

   function showNetnavigation(){
      $retour = false;
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->withNetnavigation()
          and ( $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                or $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
                or $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
                or $this->_environment->getCurrentModule() == CS_TODO_TYPE
                or $this->_environment->getCurrentModule() == CS_DATE_TYPE
                or $this->_environment->getCurrentModule() == CS_GROUP_TYPE
                or $this->_environment->getCurrentModule() == CS_TOPIC_TYPE
                or $this->_environment->getCurrentModule() == CS_INSTITUTION_TYPE
                or ($this->_environment->getCurrentModule() == CS_USER_TYPE and ($context_item->withRubric(CS_GROUP_TYPE) or($context_item->withRubric(CS_INSTITUTION_TYPE))))
                or $this->_environment->getCurrentModule() == 'campus_search')
      ){
         $retour = true;
      }
      return $retour;
   }

   function showWorkflow(){
      $retour = false;
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->withWorkflow()
          and ( $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                #or $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE
                #or $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE
                #or $this->_environment->getCurrentModule() == CS_TODO_TYPE
                #or $this->_environment->getCurrentModule() == CS_DATE_TYPE
              )
      ){
         $retour = true;
      }
      return $retour;
   }
   
    function getSearchText (){
       if (empty($this->_search_text)){
        $this->_search_text = $this->_translator->getMessage('COMMON_SEARCH_IN_RUBRIC');
       }
       return $this->_search_text;
    }


  function _getSearchAsHTML () {
     $html  = '';
     $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), 'campus_search', 'index','').'" method="get" name="indexform">'.LF;
     $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
     $html .= '   <input type="hidden" name="mod" value="campus_search"/>'.LF;
     $html .= '   <input type="hidden" name="SID" value="'.$this->_environment->getSessionItem()->getSessionID().'"/>'.LF;
     $html .= '   <input type="hidden" name="fct" value="index"/>'.LF;
     $html .= '   <input type="hidden" name="selrubric" value="'.$this->_environment->getCurrentModule().'"/>'.LF;
     $html .= '<input id="searchtext" onclick="javascript:resetSearchText(\'searchtext\');" style="width:220px; font-size:10pt; margin-bottom:0px;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/>'.LF;
     if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
        $html .= '<input type="image" src="images/commsyicons_msie6/22x22/search.gif" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
     } else {
        $html .= '<input type="image" src="images/commsyicons/22x22/search.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
     }
     $html .= '</form>';
     return $html;
  }


   function _getDetailPageHeaderAsHTML(){
      $item = $this->getItem();
      $current_user_item = $this->_environment->getCurrentUserItem();
      $current_context = $this->_environment->getCurrentContextItem();
      $html = '';
      $html .='<div style="width:100%;">'.LF;
      $html .='<div style="height:30px;">'.LF;
      if ($item->maySee($current_user_item) and ((!$current_context->isPrivateRoom()) or $current_user_item->getContextID() == $current_context->getItemID())){
         $html .= '<div id="search_box" style="float:right; width:28%; white-space:nowrap; text-align-left; padding-top:5px; margin:0px;">'.LF;
         $html .= $this->_getSearchAsHTML();
         $html .= '</div>'.LF;
      }
      $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
      $current_browser_version = $this->_environment->getCurrentBrowserVersion();
      if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
         $html .='<div style="width: 99%;">'.LF;
      }else{
         $html .='<div style="width: 70%;">'.LF;

      }
      $html .='<div id="action_box">';
      $html .= $this->_getDetailActionsAsHTML($this->_item);
      $html .='</div>';
      $html .='<div style="vertical-align:bottom;">'.LF;
      $tempMessage = '';
      switch ( mb_strtoupper($this->_environment->getCurrentModule(), 'UTF-8') ) {
         case 'ANNOUNCEMENT':
            $tempMessage = $this->_translator->getMessage('ANNOUNCEMENT_DETAIL');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/announcement.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/announcement.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'DATE':
            $tempMessage = $this->_translator->getMessage('DATE_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/date.gif" style="vertical-align:bottom;"/>'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/date.png" style="vertical-align:bottom;"/>'.$tempMessage;
            }
            break;
         case 'DISCUSSION':
            $tempMessage = $this->_translator->getMessage('DISCUSSION_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/discussion.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/discussion.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'INSTITUTION':
            $tempMessage = $this->_translator->getMessage('INSTITUTION_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/group.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/group.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'GROUP':
            $tempMessage = $this->_translator->getMessage('GROUP_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/group.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/group.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'MATERIAL_ADMIN':
            $tempMessage = $this->_translator->getMessage('MATERIAL_ADMIN_INDEX').' ('.$this->_translator->getMessage('MATERIAL_INDEX').')';
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/config/material_admin.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/config/material_admin.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'MATERIAL':
            $tempMessage = $this->_translator->getMessage('MATERIAL_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/material.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/material.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'MYROOM':
            $tempMessage = $this->_translator->getMessage('MYROOM_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/room.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/room.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'PROJECT':
            $tempMessage = $this->_translator->getMessage('PROJECT_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/room.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/room.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         case 'TODO':
            $tempMessage = $this->_translator->getMessage('TODO_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/todo.gif" style="vertical-align:bottom;"/>'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/todo.png" style="vertical-align:bottom;"/>'.$tempMessage;
            }
            break;
         case 'TOPIC':
            $tempMessage = $this->_translator->getMessage('TOPIC_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/topic.gif" style="vertical-align:bottom;"/>'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/topic.png" style="vertical-align:bottom;"/>'.$tempMessage;
            }
            break;
         case 'USER':
            $tempMessage = $this->_translator->getMessage('USER_INDEX');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/user.gif" style="vertical-align:bottom;"/>'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/user.png" style="vertical-align:bottom;"/>'.$tempMessage;
            }
            break;
         case 'ACCOUNT':
            $tempMessage = $this->_translator->getMessage('COMMON_ACCOUNTS');
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $tempMessage = '<img src="images/commsyicons_msie6/32x32/config/account.gif" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            } else {
               $tempMessage = '<img src="images/commsyicons/32x32/config/account.png" style="vertical-align:bottom;"/>&nbsp;'.$tempMessage;
            }
            break;
         default:
            $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_detail_view(1187) ');
            break;
      }
      $html .= '<h2 class="pagetitle">'.$tempMessage;

      $html .= '</h2>'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="font-size:0pt; width:100%; clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      return $html;
   }

   function _getAdditionalCalendarAsHTML(){
      $params = array();
      $additional_calendar_href = curl($this->_environment->getCurrentContextID(),
                                       CS_DATE_TYPE,
                                       'index',
                                       $params);
      $additional_calendar_href = str_replace('&amp;', '&', $additional_calendar_href);
      $additional_calendar_href .= '&presentation_mode=' . '2&month=';//$this->_presentation_mode;
      /*
       * if($this->_presentation_mode == 1){
         $additional_calendar_href .= '&week=';
      } elseif ($this->_presentation_mode == 2) {
         $additional_calendar_href .= '&month=';
      }
      */
      #$date_explode = explode('-', $this->_item->getStartingDay());
      #$additional_calendar_href .= $date_explode[0] . $date_explode[1] . $date_explode[2];
      $html = '<div id="additional_calendar" class="additional_calendar" style="width:100%; margin:auto; padding:3px 0px 3px 0px;"></div>';
      $html .= '<script type="text/javascript">'.LF;
      $html .= '<!--'.LF;
      $html .= 'var additional_calendar_href = "' . $additional_calendar_href . '"'.LF;
      $html .= 'var presentation_mode = "' . '2'/*$this->_presentation_mode*/ . '"'.LF;
      $html .= '-->'.LF;
      $html .= '</script>'.LF;

      return $html;
   }

   /** get detail view as HTML
    * this method returns the detail view in HTML-Code
    *
    * @returns string detail view as HMTL
    */
   function asHTML () {
      $item = $this->getItem();
      $current_user_item = $this->_environment->getCurrentUserItem();
      $html  = LF.'<!-- BEGIN OF DETAIL VIEW -->'.LF;
      
      $html .= $this->addJavaScriptForSearch();
      
      $html .='<div style="width:100%;">'.LF;
      $rubric = $this->_environment->getCurrentModule();
      $current_context = $this->_environment->getCurrentContextItem();
      $detail_box_conf = $current_context->getDetailBoxConf();

      $html .= $this->_getDetailPageHeaderAsHTML();
      if(!(isset($_GET['mode']) and $_GET['mode']=='print') and

           $item->maySee($current_user_item) and ((!$current_context->isPrivateRoom()) or $current_user_item->getContextID() == $current_context->getItemID())

      ){
         $this->_right_box_config['size_string'] = '';
         $current_context = $this->_environment->getCurrentContextItem();
         $html .='<div style="float:right; font-size:10pt; width:28%; margin-top:5px; vertical-align:top; text-align:left;">'.LF;
         $html .='<div>'.LF;
         $html .='<div style="width:250px;">'.LF;
         $html .='<div id="commsy_panels">'.LF;

         if(!isset($this->_browse_ids) or count($this->_browse_ids) ==0){
             $this->_browse_ids[] = $this->_item->getItemID();
         }
         $html .= '<div class="commsy_no_panel" style="margin-bottom:1px;">'.LF;

         $from_display_mode = "";
         $session = $this->_environment->getSessionItem();
         if($session->issetValue($this->_environment->getCurrentContextID() . '_dates_seldisplay_mode')) {
            $from_display_mode = $session->getValue($this->_environment->getCurrentContextID() . '_dates_seldisplay_mode');
         } else {
            $from_display_mode = $current_context->getDatesPresentationStatus();
         }
         unset($session);

         // display calendar in date detail view, when request came from calendar
         if($rubric == CS_DATE_TYPE && $from_display_mode != 'normal') {
            $html .= '<div class="column" style="width: 100%;">'.LF;
            $html .= '<div class="right_box">'.LF;
            $html .= '<div class="right_box_title">'.LF;

            $date = $this->_item->getStartingDay();
            $date_array = explode('-',$date);
            $month = $date_array[1];
            $year = $date_array[0];
            $first_char = mb_substr($month,0,1);
            if ($first_char == '0'){
               $month = mb_substr($month,1,2);
            }
            $month_array = array($this->_translator->getMessage('DATES_JANUARY_LONG'),
                                 $this->_translator->getMessage('DATES_FEBRUARY_LONG'),
                                 $this->_translator->getMessage('DATES_MARCH_LONG'),
                                 $this->_translator->getMessage('DATES_APRIL_LONG'),
                                 $this->_translator->getMessage('DATES_MAY_LONG'),
                                 $this->_translator->getMessage('DATES_JUNE_LONG'),
                                 $this->_translator->getMessage('DATES_JULY_LONG'),
                                 $this->_translator->getMessage('DATES_AUGUST_LONG'),
                                 $this->_translator->getMessage('DATES_SEPTEMBER_LONG'),
                                 $this->_translator->getMessage('DATES_OCTOBER_LONG'),
                                 $this->_translator->getMessage('DATES_NOVEMBER_LONG'),
                                 $this->_translator->getMessage('DATES_DECEMBER_LONG'));
            $tempMessage = $month_array[$month-1].' '.$year;

            $html .= '<div style="white-space:nowrap;">'.$tempMessage.'</div>'.LF;
            $html .='</div>'.LF;
            $html .= '<div class="right_box_main">'.LF;
            $html .= '<div style="margin-left: 20px;">'.LF;
            $html .= $this->_getAdditionalCalendarAsHTML();
            $html .= '</div>'.LF;


            $html .= '<div style="float:right; font-size:8pt; padding: 5px 3px 3px 0px;">'.LF;
            $params = array();
            $params['back_to_index'] = 'true';
            $link_module = $this->_environment->getCurrentModule();
            $link_text = $this->_translator->getMessage('DATE_BACK_TO_CALENDAR');
            $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                              $link_module,
                              'index',
                              $params,
                              $link_text
                              );
            $html .= '</div>'.LF;
            $html .='<div style="clear:both;"></div>'.LF;

            $html .='</div>'.LF;
            $html .='</div>'.LF;
            $html .='</div>'.LF;

            $html .='</div>'.LF; // end div commsy_no_panel
            $html .='<div style="clear:both;"></div>'.LF;
         }

         // display forward box
         else {
            $html .= $this->_getForwardBoxAsHTML($item);
            $html .='</div>'.LF;
         }

         /***********Buzzwords*************/
         if ( $this->showBuzzwords() ) {
            $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
            $html .= $this->_getBuzzwordBoxAsHTML($item);
            $html .='</div>'.LF;
         }

         if ( $this->_environment->getCurrentModule() == 'account'){
            $html .=  $this->_getConfigurationOverviewAsHTML();
         }


         /***********Tags*************/
         if ( $this->showTags() ) {
            $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
            $session_item = $this->_environment->getSessionItem();
            if($session_item->issetValue('javascript')){
               if($session_item->getValue('javascript') == "1"){
                  $with_javascript = true;
               }else{
                  $with_javascript = false;
               }
            } else {
               $with_javascript = true;
            }
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
              $with_javascript = false;
           }
            // UMSTELLUNG MUSEUM
            if($with_javascript and true){
               #$html .= $this->_getTagBoxAsHTMLWithJavaScript($item);
               $tag_manager = $this->_environment->getTagManager();
               $html .= $this->_getTagBoxAsHTMLWithJavaScript($tag_manager->getRootTagItem(), 0, 0, true, $item);
            } else {
               $html .= $this->_getTagBoxAsHTML($item);
            }
            $html .='</div>'.LF;
         }

          /**********Netnaviation*********/
         if ( $this->showNetnavigation() ){
            $html .= $this->_getAllLinkedItemsAsHTML($item);
         }

         
         /***********Workflow*************/
         if ( $this->showWorkflow() ) {
            #$html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
            #$html .= $this->_getWorkflowBoxAsHTML($item);
            #$html .='</div>'.LF;
         }   
         
         $html .='<div>&nbsp;'.LF;
         $html .='</div>'.LF;
         $html .='</div>'.LF;
         $html .='</div>'.LF;
         $html .='</div>'.LF;
         $html .='</div>'.LF;
      }
      $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
      $current_browser_version = $this->_environment->getCurrentBrowserVersion();
      if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
         $width= ' width:100%; padding-right:10px; ';
      }else{
         $width= '';
      }

      if ( (isset($_GET['mode']) and $_GET['mode']=='print') ){
         $html .='<div class="infoborder" style="width:100%; margin-top:5px; vertical-align:bottom;">'.LF;
      }else{
         $html .='<div class="infoborder_display_content"  style="'.$width.'margin-top:5px; vertical-align:bottom;">'.LF;
      }
      if ( !$this->_show_content_without_window ) {
         $html .='<div id="detail_headline">'.LF;
         $html .= '<div style="padding:3px 5px 4px 5px;">'.LF;
         if($rubric == CS_DISCUSSION_TYPE){
            $html .= '<h2 class="contenttitle">'.$this->_getTitleAsHTML();
         }elseif ($rubric != CS_USER_TYPE and $rubric != 'account'){
            $html .= '<h2 class="contenttitle">'.$this->_text_as_html_short($this->_compareWithSearchText($item->getTitle(),false));
         }elseif ($rubric == 'account' ){
            $html .= '<h2 class="contenttitle">'.$this->_text_as_html_short($this->_compareWithSearchText($item->getFullName(),false));
         }else{
           $html .= '<h2 class="contenttitle">'.$this->_text_as_html_short($this->_compareWithSearchText($item->getFullName(),false));
         }
         $html .= '</h2>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         
         $html .='<div id="detail_content" style="font-size:10pt; '.$width.'">'.LF;
		 
		 if($rubric == CS_MATERIAL_TYPE ||
	  		$rubric == CS_DISCUSSION_TYPE ||
			$rubric == CS_ANNOUNCEMENT_TYPE) {
		  	 // check if assessment is active for this room
	         $current_context = $this->_environment->getCurrentContextItem();
	         if($current_context->isAssessmentActive()) {
	         	$html .= '<script type="text/javascript">'.LF;
				$html .= '<!--'.LF;
				$html .= 'var ajax_cid = "' . $current_context->getItemID() . '"'.LF;
				$html .= '-->'.LF;
	         	$html .= '</script>'.LF;
	         	//$html .= '<div class="detail_content" style="border: none;">'.LF;
			   	$html .= $this->_getAssessmentsAsHTML();
			   	//$html .= '</div>'.LF;
			 }
		  }
		  
        if($rubric == CS_MATERIAL_TYPE) {
	         $current_context = $this->_environment->getCurrentContextItem();
	         if($current_context->withWorkflow()) {
			   	$html .= $this->_getWorkflowAsHTML();
			 }
		  }
      }

      $formal_data1 = array();
      if ($item->isNotActivated()){
         $temp_array = array();
         $temp_array[]  = $this->_translator->getMessage('COMMON_RIGHTS');

         $activating_date = $item->getActivatingDate();
         if (strstr($activating_date,'9999-00-00')){
            $title = $this->_translator->getMessage('COMMON_NOT_ACTIVATED');
         }else{
            $title = $this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($item->getActivatingDate());
         }
         $temp_array[] = $title;
         $formal_data1[] = $temp_array;
      }
      if ($this->_environment->getCurrentModule() == CS_DATE_TYPE and $item->issetPrivatDate()){
         $temp_array = array();
         $temp_array[]  = $this->_translator->getMessage('COMMON_PRIVATE_DATE');
         $title = $this->_translator->getMessage('COMMON_NOT_ACCESSIBLE');
         $temp_array[] = $title;
         $formal_data1[] = $temp_array;
      }
      if (!empty($formal_data1)){
         $html .= $this->_getFormalDataAsHTML($formal_data1);
      }
      $formal_data1 = array();
      if ($this->_item->issetExternalViewerStatus()){
         $temp_array = array();
         $temp_array[]  = $this->_translator->getMessage('COMMON_EXTERNAL_VIEWER');
         $external_viewer_array = $this->_item->getExternalViewerArray();
         $user_manager = $this->_environment->getUserManager();
         $tmp_html = '';
         foreach($external_viewer_array as $external_viewer){
             $user_manager->setUserIDLimit($external_viewer);
             $user_manager->setContextLimit($this->_environment->getCurrentPortalID());
             $user_manager->select();
             $user_list = $user_manager->get();
             $user_item = $user_list->getFirst();
             if (isset($user_item) and is_object($user_item)){
                $tmp_html .= $user_item->getFullname().', ';
             }
         }
         if (!empty($tmp_html)){
             $tmp_html = substr($tmp_html, 0, -2);
         }
         $temp_array[] = $tmp_html;
         $formal_data1[] = $temp_array;
      }
      if (!empty($formal_data1)){
         $html .= $this->_getFormalDataAsHTML($formal_data1);
      }
	  
      $html .= $this->_getContentAsHTML();
      if ( !$this->_show_content_without_window ) {
         if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
            $html .='<div class="infoborder" style="margin-top:5px; padding-top:10px; vertical-align:top;">';
            $mode = 'short';
            if (in_array($item->getItemID(),$this->_openCreatorInfo)) {
               $mode = 'long';
            }
            $html .= $this->_getCreatorInformationAsHTML($item, 3,$mode).LF;
         }
      }
      if ( ( $this->_environment->getCurrentModule() != 'user'
             or !$this->_environment->inPrivateRoom()
           )
           and !( $rubric == CS_TODO_TYPE
                  and !$current_context->withTodoManagement()
                )
         ) {

############SQL-Statements reduzieren

         $html .= $this->_getSubItemsAsHTML($item);
         $current_user = $this->_environment->getCurrentUser();
         if ( $rubric == CS_DISCUSSION_TYPE
              and !$item->isClosed()
              and $this->_with_modifying_actions
              and ($current_user->isUser())
            ) {
            $html .= $this->_getDiscussionFormAsHTML();
            $html .= '</div>'.LF;
         }
         if ( $rubric == CS_TODO_TYPE
              and $this->_with_modifying_actions
            ) {
            if ( $current_context->withTodoManagement() ){
               $html .= $this->_getTodoFormAsHTML();
            }
            $html .= '</div>'.LF;
         }
      }
      if ( $rubric != CS_GROUP_TYPE
           and $rubric != CS_TOPIC_TYPE
           and $rubric != CS_INSTITUTION_TYPE
           and $rubric != CS_USER_TYPE
           and $rubric != CS_DISCUSSION_TYPE
           and $this->_environment->getCurrentModule() !='account'
         ) {
           $html .= $this->_getAnnotationsAsHTML();
           $html .= $this->_getAnnotationFormAsHTML();
      }
      if($rubric == CS_DISCUSSION_TYPE and $current_context->isPrivateRoom()){
         $html .= $this->_getAnnotationsAsHTML();
         $html .= $this->_getAnnotationFormAsHTML();
      }

      if($rubric == CS_TOPIC_TYPE){
         $anno_list = $item->getAnnotationList();
         $anno_item = $anno_list->getFirst();
         if (isset($anno_item) and !empty($anno_item)){
            $html .= $this->_getAnnotationsAsHTML();
         }
      }
      if($rubric == CS_INSTITUTION_TYPE){
         $anno_list = $item->getAnnotationList();
         $anno_item = $anno_list->getFirst();
         if (isset($anno_item) and !empty($anno_item)){
            $html .= $this->_getAnnotationsAsHTML();
         }
      }

      $html .='</div>'.LF;
      if ( !$this->_show_content_without_window
           or $this->_environment->getCurrentModule() == type2module(CS_PROJECT_TYPE)
         ) {
         $html .='</div>'.LF;
      }
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      if ( !$this->_show_content_without_window
           or $this->_environment->getCurrentModule() == type2module(CS_PROJECT_TYPE)
         ) {
         $html .='</div>'.LF;
      }
      $html .= '<!-- END OF DETAIL VIEW -->'.LF.LF;
      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .= '<script type="text/javascript">'.LF;
         $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
         $current_browser_version = $this->_environment->getCurrentBrowserVersion();
         if ( $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE and $current_browser == 'msie' and !strstr($current_browser_version,'7.')){
            $html .= 'preInitCommSyPanels(Array('.$this->_right_box_config['title_string'].'),Array('.$this->_right_box_config['desc_string'].'),Array('.$this->_right_box_config['config_string'].'), Array(),Array('.$this->_right_box_config['size_string'].'));'.LF;
         }else{
            $html .= 'initCommSyPanels(Array('.$this->_right_box_config['title_string'].'),Array('.$this->_right_box_config['desc_string'].'),Array('.$this->_right_box_config['config_string'].'), Array(),Array('.$this->_right_box_config['size_string'].'),Array(),null,null);'.LF;
         }
         $html .= '</script>'.LF;
      }
      return $html;
   }

   function _getSubItemsAsHTML($item){
      $html ='';
      $html = '<!-- BEGIN OF SUB ITEM DETAIL VIEW -->'.LF.LF;
      $subitems = $this->getSubItemList();
      $count = 0;
      if ( isset($subitems) and !$subitems->isEmpty() ) {
         $count=$subitems->getCount();
         $current_item = $subitems->getFirst();
         $pos_number = 1;
         while ( $current_item ) {
            if ( !isset($this->_sub_item_pos_number) ){
               $this->_sub_item_pos_number = 1;
            }else{
               $this->_sub_item_pos_number =  $this->_sub_item_pos_number+1;
            }
            $html .= '<div style="width:100%; margin-top:50px;">'.LF;
            $html .= '<a id="anchor'.$current_item->getItemID().'" name="anchor'.$current_item->getItemID().'"></a>'.LF;
            $html .='<a id="anchor'.$this->_sub_item_pos_number.'" name="anchor'.$this->_sub_item_pos_number.'"></a>';
            $html .= '<div>';
            $html .= '<div style="float:right; text-align:right; vertical-align:bottom;">';
            if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
               $html .= $this->_getSubItemDetailActionsAsHTML($current_item);
            }
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<h3 class="subitemtitle">'.$this->_getSubItemTitleAsHTML($current_item, $pos_number);
            $html .= '</h3>'.LF;
            $html .= '</div>';
            $html .= '</div>';

            $html .='<div style="width: 100%; margin-bottom:10px; margin-top:5px; padding-top:5px; padding-bottom: 0px; border-top:1px solid #B0B0B0;">'.LF;
            if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
               $html .='<div style="float:right; height:6px; font-size:2pt;">'.LF;
               $html .= $this->_getBrowsingIconsAsHTML($current_item, $this->_sub_item_pos_number,$count);
               $html .='</div>'.LF;
            }
            $html .= $this->_getSubItemAsHTML($current_item, $pos_number).LF;
            $html .='</div>'.LF;
            $html .='<div style="vertical-align:top;">';


            if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
               $mode = 'short';
               if (!$item->isA(CS_USER_TYPE)) {
                  $mode = 'short';
                  if (in_array($current_item->getItemId(),$this->_openCreatorInfo)) {
                     $mode = 'long';
                  }

                  $html .='<div style="padding-bottom:0px; margin:0px;vertical-align:top;">';
                  $html .= $this->_getCreatorInformationAsHTML($current_item, 6,'long').LF;
                  $html .='</div>'.LF;
               }
               $html .='</div>'.LF;
               $html .='</div>'.LF;
            }
            $current_item = $subitems->getNext();
            $pos_number++;
         }
      }
      $html .= '<!-- END OF SUB ITEM DETAIL VIEW -->'.LF.LF;
      return $html;
   }

   function _getItemPicture($item){
      if (isset($item)){
         $picture = $item->getPicture();
         $linktext = '';
         if ( !empty($picture) ) {
            $disc_manager = $this->_environment->getDiscManager();
            if ($disc_manager->existsFile($picture)){
               $image_array = getimagesize($disc_manager->getFilePath().$picture);
               $pict_height = $image_array[1];
               if ($pict_height > 60){
                  $height = 60;
               }else{
                  $height = $pict_height;
               }
            }else{
                $height = 60;
            }
            $params = array();
            $params['picture'] = $picture;
            $curl = curl($this->_environment->getCurrentContextID(),
                      'picture', 'getfile', $params,'');
            unset($params);
            $html = '<img alt="'.$this->_translator->getMessage('USER_PICTURE_UPLOADFILE').'" src="'.$curl.'" style="vertical-align:middle; width: '.$height.'px;"/>'.LF;
            if ($item->isA(CS_USER_TYPE)) {
               $linktext = str_replace('"','&quot;',encode(AS_HTML_SHORT,$item->getFullName()));
            } else {
               $linktext = $this->_translator->getMessage('USER_PICTURE_UPLOADFILE');
            }
         }else{
            $html = '<img alt="'.$this->_translator->getMessage('USER_PICTURE_UPLOADFILE').'" src="images/commsyicons/common/user_unknown.gif" style="vertical-align:middle;  width: 60px;"/>'.LF;
            if ($item->isA(CS_USER_TYPE)) {
               $linktext = $this->_translator->getMessage('USER_PICTURE_NO_PICTURE',str_replace('"','&quot;',encode(AS_HTML_SHORT,$item->getFullName())));
            } else {
               $linktext = $this->_translator->getMessage('USER_PICTURE_UPLOADFILE');
            }
         }
         $params = array();
         $params['iid'] = $item->getItemID();
         $html = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_USER_TYPE,
                           'detail',
                           $params,
                           $html,
                           $linktext,'', '', '', '', '', '', '',
                           '');
         return $html;
      }else{
           return '';
      }
   }


   function _getAnnotationBrowsingIconsAsHTML($current_item, $pos_number, $count){
      $html ='';
      $i =0;
      if ( $pos_number == 1 ) {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/16x16/browse_left2.gif" alt="&lt;" style="vertical-align:bottom;"/>';
         } else {
            $image = '<img src="images/commsyicons/16x16/browse_left2.png" alt="&lt;" style="vertical-align:bottom;"/>';
         }
         $html .= '<a href="#top">'.$image.'</a>'.LF;
      }elseif ( $pos_number > 1 ) {
         $i = $pos_number-1;
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/16x16/browse_left2.gif" alt="&lt;" style="vertical-align:bottom;"/>';
         } else {
            $image = '<img src="images/commsyicons/16x16/browse_left2.png" alt="&lt;" style="vertical-align:bottom;"/>';
         }
         $html .= '<a href="#annotation_'.$i.'">'.$image.'</a>'.LF;
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $html .= '         <span class="disabled"><img src="images/commsyicons_msie6/16x16/browse_left_grey2.gif" alt="&lt;" style="vertical-align:bottom;"/></span>'.LF;
         } else {
            $html .= '         <span class="disabled"><img src="images/commsyicons/16x16/browse_left_grey2.png" alt="&lt;" style="vertical-align:bottom;"/></span>'.LF;
         }
      }
      $html .= '';
      if ( $pos_number < $count) {
         $i = $pos_number+1;
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/16x16/browse_right2.gif" alt="&gt;" style="vertical-align:bottom;"/>';
         } else {
            $image = '<img src="images/commsyicons/16x16/browse_right2.png" alt="&gt;" style="vertical-align:bottom;"/>';
         }
         $html .= '<a href="#annotation_'.$i.'">'.$image.'</a>'.LF;
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $html .= '         <span class="disabled"><img src="images/commsyicons_msie6/16x16/browse_right_grey2.gif" alt="&gt;" style="vertical-align:bottom;"/></span>'.LF;
         } else {
            $html .= '         <span class="disabled"><img src="images/commsyicons/16x16/browse_right_grey2.png" alt="&gt;" style="vertical-align:bottom;"/></span>'.LF;
         }
      }
      return $html;
   }

   function _getAssessmentsAsHTML() {
   	$html = '';
	
	$html .= '<div id="assessment">'.LF;
	
	// always display actual voting width stars
	$assessment = 0;
	if($this->_assessment !== null) {
		$assessment = sprintf('%1.1f', (float) $this->_assessment['assessment']);
	}
	
	$span_pre = '';
	$tooltip = '';
	if($this->_assessment['already_voted'] === false || $this->_assessment === null) {
		$span_pre = 'assessment_vote_star_';
	} else {
		$span_pre = 'assessment_overlay_star_';
		$tooltip = 'assessment_overlay_tooltip';
		
		// create tooltip
		$html .= '<div id="assessment_tooltip" class="stickytooltip" style="width: 250px; border-width: 1px;">'.LF;
		$html .= '	<div style="padding: 5px;">'.LF;
		$html .= '		<div id="assessment_overlay_tooltip">'.LF;
		$html .= $this->_translator->getMessage('COMMON_ASSESSMENT_OVERLAY_DESCRIPTION');
		
		// show information about all votes
		for($i = 1; $i <= 5; $i++) {
			$html .= '		<div style="margin-top: 5px;">'.LF;
			for($j = 0; $j < $i; $j++) {
				$html .= '		<span style="width: 200px;"><img src="images/commsyicons/32x32/star_filled.png" style="width:18px; height:18px"/></span>'.LF;
			}
			for($j = $i; $j < 5; $j++) {
				$html .= '		<span style="width: 200px;"><img src="images/commsyicons/32x32/star_unfilled.png" style="width:18px; height:18px"/></span>'.LF;
			}
			
			$votes = 0;
			if(isset($this->_assessment['detail'][$i])) {
				$votes = $this->_assessment['detail'][$i];
			}
			
			$assessment_text = '';
			if($votes == 1) {
				$assessment_text = $this->_translator->getMessage('COMMON_ASSESSMENT_INDEX');
			} else {
				$assessment_text = $this->_translator->getMessage('COMMON_ASSESSMENT');
			}
			$html .= '			<span>' . $votes . ' ' . $assessment_text . '</span>'.LF;
			$html .= '		</div>'.LF;
		}
		
		$html .= '		</div>'.LF;
		$html .= '	</div>'.LF;
		//$html .= '	<div class="stickystatus">'.LF;
		//$html .= '	</div>'.LF;
		$html .= '</div>'.LF;
	}
	
	// display stars
	$php_version = explode('.', phpversion());
	if($php_version[0] >= 5 && $php_version[1] >= 3) {
		// if php version is equal to or above 5.3
		$stars_full = round($assessment, 0, PHP_ROUND_HALF_UP);
	} else {
		// if php version is below 5.3
		$stars_full = round($assessment);
	}
	
	for($i = 0; $i < $stars_full; $i++) {
		$html .= '<span id="' . $span_pre . $i . '"><img src="images/commsyicons/32x32/star_filled.png" data-tooltip="' . $tooltip . '" style="width:18px; height:18px"/></span>'.LF;
	}
	for($i = $stars_full; $i < 5; $i++) {
		$html .= '<span id="' . $span_pre . $i . '"><img src="images/commsyicons/32x32/star_unfilled.png" data-tooltip="' . $tooltip . '" style="width:18px; height:18px"/></span>'.LF;
	}
	
	if($this->_assessment['already_voted'] === false || $this->_assessment === null) {
		// display not voted now
		$html .= '(' . $this->_translator->getMessage('COMMON_ASSESSMENT_OWN_NO') . ')';
	} else {
		// display own voting
		$html .= '(' . $this->_translator->getMessage('COMMON_ASSESSMENT_OWN') . ' ' . sprintf('%1.1f', (float) $this->_assessment['own_vote']) . ')'.LF;
		$html .= '<a href="#" id="assessment_delete_own" alt="' . $this->_translator->getMessage('COMMON_DELETE_BUTTON') . '"><img src="images/commsyicons/22x22/delete.png" style="width:18px; height:18px; vertical-align: bottom;"/></img></a>'.LF;
	}
	
	$html .= '</div>';
	  
	return $html;
   }

   function _getWorkflowAsHTML(){
      $current_context = $this->_environment->getCurrentContextItem();
      $traffic_light = '';
      if($this->_item->getWorkflowTrafficLight() == '3_none'){
         $traffic_light = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_NONE');
      }else if($this->_item->getWorkflowTrafficLight() == '0_green'){
         $alt_title = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_GREEN_DEFAULT');
         if($current_context->getWorkflowTrafficLightTextGreen() != ''){
            $alt_title = $current_context->getWorkflowTrafficLightTextGreen();
         }
         $traffic_light = '<img src="images/commsyicons/workflow_traffic_light_green.png" alt="'.$alt_title.'" title="'.$alt_title.'">';
      }else if($this->_item->getWorkflowTrafficLight() == '1_yellow'){
         $alt_title = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_YELLOW_DEFAULT');
         if($current_context->getWorkflowTrafficLightTextYellow() != ''){
            $alt_title = $current_context->getWorkflowTrafficLightTextYellow();
         }
         $traffic_light = '<img src="images/commsyicons/workflow_traffic_light_yellow.png" alt="'.$alt_title.'" title="'.$alt_title.'">';
      }else if($this->_item->getWorkflowTrafficLight() == '2_red'){
         $alt_title = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_RED_DEFAULT');
         if($current_context->getWorkflowTrafficLightTextRed() != ''){
            $alt_title = $current_context->getWorkflowTrafficLightTextRed();
         }
         $traffic_light = '<img src="images/commsyicons/workflow_traffic_light_red.png" alt="'.$alt_title.'" title="'.$alt_title.'">';
      }
      $html = '<div style="margin-top:5px;">'.$traffic_light.'</div>';
      return $html;
   }
   
   function _getAnnotationsAsHTML () {
      $item = $this->_item;
      $html = '';
      $count = 0;
      if ( !empty($this->_annotation_list) ) {
         $count = $this->_annotation_list->getCount();
      }
      if ( !(isset($_GET['mode']) and $_GET['mode']=='print') or $count > 0){
         if ( !$this->_show_content_without_window ) {
            $html .= '</div>'.LF.LF;
            $html .= '</div>'.LF.LF;
         }
         $html .= '<!-- BEGIN OF ANNOTATION VIEW -->'.LF.LF;
         $html .= '<div id="detail_annotations">'.LF;
         if ( !empty($this->_annotation_list) ){
            $count = $this->_annotation_list->getCount();
            if ($count == 1){
               $desc = ' ('.$this->_translator->getMessage('COMMON_ONE_ANNOTATION');
            }else{
               $desc = ' ('.$this->_translator->getMessage('COMMON_X_ANNOTATIONS',$count);
            }
         }else{
            $desc = ' ('.$this->_translator->getMessage('COMMON_NO_ANNOTATIONS');
         }
         $desc .= ')'.LF;
         $html .='<div id="detail_annotation_headline">'.LF;
         $html .= '<h3>'.$this->_translator->getMessage('COMMON_ANNOTATIONS').$desc;
         $html .= '</h3>'.LF;
         $html .='</div>'.LF;
         if ( !(isset($_GET['mode']) and $_GET['mode']=='print') ){
            $html .='<div class="sub_item_main">'.LF;
         }else{
            $html .='<div class="sub_item_main" style="background-color:#FFFFFF;">'.LF;
         }
         $html .= '<a name="annotations"></a>'.LF;
         $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
         $current_browser_version = $this->_environment->getCurrentBrowserVersion();
         if ($current_browser == 'msie' and !(strstr($current_browser_version,'7.') or strstr($current_browser_version,'8.'))){
            $html .='<div style="width:100%; padding:5px 10px 5px 5px; background-color:#FFFFFF;">'.LF;
         }else{
            $html .='<div style="background-color:#FFFFFF; padding:5px;">'.LF;
         }
         if ( !empty($this->_annotation_list) ){
            $annotation_item = $this->_annotation_list->getFirst();
         }
         if ( !empty($annotation_item) ){
            $pos_number = 1;
            if ($current_browser == 'msie' and !strstr($current_browser_version,'7.')){
               $html .='<table summary="layout" class="detail_annotation_table">'.LF;
            }else{
               $html .='<table summary="layout" class="detail_annotation_table">'.LF;
            }
            $current_item = $this->_annotation_list->getFirst();
            while( $current_item ){
                  $image = $this->_getItemPicture($current_item->getModificatorItem());
                  $html .='<tr>'.LF;
                  $html .= '<td rowspan="3" style="width:60px; vertical-align:top; padding:20px 5px 5px 5px;">'.$image.'</td>'.LF;
                  $html .='<td style="width:70%; padding-top:5px; vertical-align:bottom;">'.LF;
                  $html .= '<a id="annotation_'.$pos_number.'" name="annotation_'.$pos_number.'"></a>'.LF;
                  $html .='<div style="padding-top:10px;">'.LF;
                  $html .= '<a id="anchor'.$current_item->getItemID().'" name="anchor'.$current_item->getItemID().'"></a>'.LF;
                  $html .= '<h3 class="subitemtitle">'.$pos_number.'. '.$this->_getSubItemTitleAsHTML($current_item, $pos_number);
                  $html .= '</h3>'.LF;
                  $html .='</div>'.LF;
                  $html .='</td>'.LF;
                  if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
                     $html .='<td style="width:28%; padding-top:5px; padding-left:0px; padding-right:3px; vertical-align:bottom; text-align:right;">'.LF;
                     $html .= $this->getAnnotationActionsAsHTML($current_item);
                     $html .='</td>'.LF;
                  }else{
                     $html .='<td style="width:28%; padding-top:5px; padding-left:0px; padding-right:3px; vertical-align:bottom; text-align:right;">'.LF;
                     $html .= '&nbsp';
                     $html .='</td>'.LF;
                  }
                  $html .='</tr>'.LF;
                  $html .='<tr>'.LF;
                  $html .='<td colspan="2" class="infoborder" style="padding-top:5px; vertical-align:top; ">'.LF;
                  if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
                     $html .='<div style="float:right; height:6px; font-size:2pt;">'.LF;
                     $html .= $this->_getAnnotationBrowsingIconsAsHTML($current_item, $pos_number,$count);
                     $html .='</div>'.LF;
                  }
                  $html .= $this->_getAnnotationContentAsHTML($current_item).LF;
                  $html .='</td>'.LF;
                  $html .='</tr>'.LF;
                  if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
                     $html .='<tr>'.LF;
                     $html .='<td class="annotation_creator_information" style="padding-top:5px; padding-bottom:30px; vertical-align:top; ">'.LF;
                     $mode = 'short';
                     if (!$item->isA(CS_USER_TYPE)) {
                        $mode = 'short';
                        if (in_array($current_item->getItemId(),$this->_openCreatorInfo)) {
                           $mode = 'long';
                        }
                        $html .= $this->_getCreatorInformationAsHTML($current_item, 6,$mode).LF;
                     }
                     $html .='</td>'.LF;
                     $html .='</tr>'.LF;
                  }else{
                     $html .='<tr>'.LF;
                     $html .='<td style="padding-top:5px; padding-bottom:40px; vertical-align:top; ">'.LF;
                     $html .='</td>'.LF;
                     $html .='</tr>'.LF;
                  }
               $pos_number++;
               $current_item = $this->_annotation_list->getNext();
            }
            $html .='</table>'.LF;
         }
         $html .= '<!-- END OF ANNOTATION VIEW -->'.LF.LF;
      }
      return $html;
}



   function _getContentAsHTML() {
      $item = $this->getItem();
      $html ='';
      if ( isset($item) ) {
         $html .= $this->_getItemAsHTML($item);
      } else {
         $html .= '<!-- No item set! -->'.LF;
      }
      $html .= '<!-- END OF DETAIL VIEW -->'.LF.LF;
      return $html;
   }


   function _getAllPathsAsHTML(){
      $html = '';
      $current_context = $this->_environment->getCurrentContextItem();
      if(!empty($this->_right_box_config['title_string'])){
         $separator = ',';
      }else{
         $separator = '';
      }
      $this->_right_box_config['title_string'] .= $separator.'"'.$this->_translator->getMessage('COMMON_PATHS').'"';
      $this->_right_box_config['desc_string'] .= $separator.'""';
      $this->_right_box_config['size_string'] .= $separator.'"10"';
      if (isset($_GET['path']) and !empty($_GET['path'])){
         $this->_right_box_config['config_string'] .= $separator.'true';
      }else{
         $this->_right_box_config['config_string'] .= $separator.'false';
      }
      $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_PATHS').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" style="margin:0px; padding:5px 0px;">'.LF;
      $connections = $this->getRubricConnections();
      $item = $this->getItem();
      $path_counter = 0;
      $path_entry_counter = 0;
      $tmp_html = '';
      $counter = 1;
      if ($current_context->withPath()){
         $topic_link_items = $item->getLinkItemList(CS_TOPIC_TYPE);
         $path_counter = $topic_link_items->getCount();
         $link_item = $topic_link_items->getFirst();
         while($link_item){
            if (isset($link_item) and !empty($link_item)){
               $topic_item = $link_item->getLinkedItem($item);
               if ($topic_item->isPathActive()){
                  $path_item_list = $topic_item->getPathItemList();
                  $temp_path_counter = $path_item_list->getCount();
                  if ($temp_path_counter > $path_entry_counter){
                     $path_entry_counter = $temp_path_counter;
                  }
               }
            }
            $link_item = $topic_link_items->getNext();
         }
      }
      $current_context = $this->_environment->getCurrentContextItem();
      if ($current_context->withPath()){
         $topic_link_items = $item->getLinkItemList(CS_TOPIC_TYPE);
         $link_item = $topic_link_items->getFirst();
         while($link_item){
            if (isset($link_item) and !empty($link_item)){
               $topic_item = $link_item->getLinkedItem($item);
               if ($topic_item->isPathActive()){
                  $path_item_list = $topic_item->getPathItemList();
                  $in_list = $path_item_list->inList($item);
                  if ($in_list){
                     $title = $topic_item->getTitle();
                     $length = mb_strlen($title);
                     if ( $length > 22 ) {
                        $title = mb_substr($this->_text_as_html_short($title),0,22).'...';
                     }
                     $params['iid'] = $topic_item->getItemID();
                     $noscript_title = ahref_curl($this->_environment->getCurrentContextID(),CS_TOPIC_TYPE,'detail',$params,$title);
                     $title = ahref_curl($this->_environment->getCurrentContextID(),CS_TOPIC_TYPE,'detail',$params,$title);
                     if ($counter >1){
                        $tmp_html .= '<div style="padding-top:10px; padding-left:5px;">'.LF;
                     }else{
                        $tmp_html .= '<div style="padding:0px 5px;">'.LF;
                     }
                     $counter++;
                     $tmp_html .= $this->_translator->getMessage('TOPIC_PATH').': '.$title;
                     $tmp_html .= '</div>'.LF;
                     $tmp_html .= $this->_getPathItemsAsHTML($topic_item,$item->getItemID(),$path_item_list);
                     $parameter_array = $this->_environment->getCurrentParameterArray();
                  }
               }
            }
            $link_item = $topic_link_items->getNext();
         }
         $item = $this->getItem();
         $type = $item->getItemType();
         if ($type == CS_TOPIC_TYPE and $item->isPathActive()){
            $show_entry = '-1';
         }
      }
      if (!empty($tmp_html)){
         $html .= $tmp_html;
      }else{
         $html .= '   <div style="padding:0px 7px; font-size:8pt;" class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'&nbsp;</div>'.LF;
      }
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      return $html;
   }

   function _getAllLinkedItemsAsHTML ($spaces=0) {
      $html = '';
      $current_context = $this->_environment->getCurrentContextItem();
      if(!empty($this->_right_box_config['title_string'])){
         $separator = ',';
      }else{
         $separator = '';
      }
      $item = $this->getItem();
      $link_items = $item->getAllLinkItemList();
      // Löschen der gesperrten Kennungen für die RightBox
      $countItem = $link_items->getFirst();
      while($countItem) {
         $linked_item = $countItem->getLinkedItem($item);
         if ( isset($linked_item) ) {
            $fragment = '';    // there is no anchor defined by default
            $type = $linked_item->getType();
         }
         $module = Type2Module($type);
         $user = $this->_environment->getCurrentUser();
         if ($module == CS_USER_TYPE and (!$linked_item->isUser() or !$linked_item->maySee($user))){
            $link_items->removeElement($countItem);
         }
         $countItem = $link_items->getNext();
      }
      $count_link_item = $link_items->getCount();
      $this->_right_box_config['title_string'] .= $separator.'"'.$this->_translator->getMessage('COMMON_NETNAVIGATION_ENTRIES').' ('.$count_link_item.')"';
      $this->_right_box_config['desc_string'] .= $separator.'""';
      $this->_right_box_config['size_string'] .= $separator.'"10"';
      if($current_context->isNetnavigationShowExpanded()){
         $this->_right_box_config['config_string'] .= $separator.'true';
      } else {
         $this->_right_box_config['config_string'] .= $separator.'false';
      }
      $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
      $html .= '<div class="right_box">'.LF;
      $connections = $this->getRubricConnections();
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_ATTACHED_ENTRIES').'</div>';
      $html .= '         </noscript>';
      $html .='      <div class="right_box_main">     '.LF;
      if ($link_items->isEmpty()) {
         $html .= '  <div style="padding:0px 5px; font-size:8pt;" class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'&nbsp;</div>'.LF;
      } else {
         $html .='     <ul style="list-style-type: circle; font-size:8pt; list-style-position:inside; margin:0px; padding:0px;">'.LF;
         $link_item = $link_items->getFirst();
         while($link_item){
            $link_creator = $link_item->getCreatorItem();
            if ( isset($link_creator) and !$link_creator->isDeleted()) {
               $fullname = $this->_text_as_html_short($link_creator->getFullname());
            } else {
               $fullname = $this->_translator->getMessage('COMMON_DELETED_USER');
            }
          // Create the list entry
            $linked_item = $link_item->getLinkedItem($item);  // Get the linked item
            if ( isset($linked_item) ) {
               $fragment = '';    // there is no anchor defined by default
               $type = $linked_item->getType();
               if ($type =='label'){
                  $type = $linked_item->getLabelType();
               }
               $link_created = $this->_translator->getDateInLang($link_item->getCreationDate());
               $text = '';
               switch ( mb_strtoupper($type, 'UTF-8') )
               {
                  case 'ANNOUNCEMENT':
                     $text .= $this->_translator->getMessage('COMMON_ONE_ANNOUNCEMENT');
                     $img = 'images/commsyicons/netnavigation/announcement.png';
                     break;
                  case 'DATE':
                     $text .= $this->_translator->getMessage('COMMON_ONE_DATE');
                     $img = 'images/commsyicons/netnavigation/date.png';
                     break;
                  case 'DISCUSSION':
                     $text .= $this->_translator->getMessage('COMMON_ONE_DISCUSSION');
                     $img = 'images/commsyicons/netnavigation/discussion.png';
                     break;
                  case 'GROUP':
                     $text .= $this->_translator->getMessage('COMMON_ONE_GROUP');
                     $img = 'images/commsyicons/netnavigation/group.png';
                     break;
                  case 'INSTITUTION':
                     $text .= $this->_translator->getMessage('COMMON_ONE_INSTITUTION');
                     $img = '';
                     break;
                  case 'MATERIAL':
                     $text .= $this->_translator->getMessage('COMMON_ONE_MATERIAL');
                     $img = 'images/commsyicons/netnavigation/material.png';
                     break;
                  case 'PROJECT':
                     $text .= $this->_translator->getMessage('COMMON_ONE_PROJECT');
                     $img = '';
                     break;
                  case 'TODO':
                     $text .= $this->_translator->getMessage('COMMON_ONE_TODO');
                     $img = 'images/commsyicons/netnavigation/todo.png';
                     break;
                  case 'TOPIC':
                     $text .= $this->_translator->getMessage('COMMON_ONE_TOPIC');
                     $img = 'images/commsyicons/netnavigation/topic.png';
                     break;
                  case 'USER':
                     $text .= $this->_translator->getMessage('COMMON_USER');
                     $img = 'images/commsyicons/netnavigation/user.png';
                     break;
                  default:
                     $text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_detail_view('.__LINE__.') ';
                     $img = '';
                     break;
               }
               $link_creator_text = $text.' - '.$this->_translator->getMessage('COMMON_LINK_CREATOR').' '.
                                    $fullname.', '.
                                    $link_created;
               switch ( $type ) {
                  case CS_DISCARTICLE_TYPE:
                     $linked_iid = $linked_item->getDiscussionID();
                     $fragment = $linked_item->getItemID();
                     $discussion_manager = $this->_environment->getDiscussionManager();
                     $linked_item = $discussion_manager->getItem($linked_iid);
                     break;
                  case CS_SECTION_TYPE:
                     $linked_iid = $linked_item->getLinkedItemID();
                     $fragment = $linked_item->getItemID();
                     $material_manager = $this->_environment->getMaterialManager();
                     $linked_item = $material_manager->getItem($linked_iid);
                     break;
                  default:
                     $linked_iid = $linked_item->getItemID();
               }
               $html .= '   <li  style="padding-left:5px; list-style-type:none;">';
               $params = array();
               $params['iid'] = $linked_iid;
               $params['link_item_path'] = $this->getItem()->getItemID();
               $module = Type2Module($type);
               $user = $this->_environment->getCurrentUser();
               if ($module == CS_USER_TYPE and (!$linked_item->isUser() or !$linked_item->maySee($user))){
                 /*$link_title = chunkText($this->_text_as_html_short($linked_item->getFullName()),35);

                   $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       '<img src="' . $img . '" style="padding-right:3px;" title="' . $link_creator_text . '"/>',
                                       $this->_translator->getMessage('USER_STATUS_REJECTED'),
                                       '_self',
                                       $fragment,
                                       '',
                                       '',
                                       '',
                                       'class="disabled"',
                                       '',
                                       '',
                                       true);
                   $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       $link_title,
                                       $this->_translator->getMessage('USER_STATUS_REJECTED'),
                                       '_self',
                                       $fragment,
                                       '',
                                       '',
                                       '',
                                       'class="disabled"',
                                       '',
                                       '',
                                       true); */
               }else{
                  if ($linked_item->isNotActivated() and !($linked_item->getCreatorID() == $user->getItemID() or $user->isModerator()) ){
                      $activating_date = $linked_item->getActivatingDate();
                      if (strstr($activating_date,'9999-00-00')){
                         $link_creator_text .= ' ('.$this->_translator->getMessage('COMMON_NOT_ACTIVATED').')';
                      }else{
                         $link_creator_text .= ' ('.$this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($linked_item->getActivatingDate()).')';
                      }
                      if ($module == CS_USER_TYPE){
                          $link_title = chunkText($this->_text_as_html_short($linked_item->getFullName()),35);
                      }else{
                          $link_title = chunkText($this->_text_as_html_short($linked_item->getTitle()),35);
                      }
                      $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       '<img src="' . $img . '" style="padding-right:3px;" title="' . $link_creator_text . '"/>',
                                       $link_creator_text,
                                       '_self',
                                       $fragment,
                                       '',
                                       '',
                                       '',
                                       'class="disabled"',
                                       '',
                                       '',
                                       true);
                      $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       $link_title,
                                       $link_creator_text,
                                       '_self',
                                       $fragment,
                                       '',
                                       '',
                                       '',
                                       'class="disabled"',
                                       '',
                                       '',
                                       true);
                     unset($params);
                  }else{
                      if ($module == CS_USER_TYPE){
                          $link_title = chunkText($this->_text_as_html_short($linked_item->getFullName()),35);
                      }else{
                          $link_title = chunkText($this->_text_as_html_short($linked_item->getTitle()),35);
                      }
                      $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       '<img src="' . $img . '" style="padding-right:3px;" title="' . $link_creator_text . '"/>',
                                       $link_creator_text,
                                       '_self',
                                       $fragment,
                                       '',
                                       '',
                                       '',
                                       'style=""');
                      $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       $link_title,
                                       $link_creator_text,
                                       '_self',
                                       $fragment,
                                       '',
                                       '',
                                       '',
                                       'style=""');
                     unset($params);
                  }
               }
               $html .= '</li>'.LF;
            }
            $link_item = $link_items->getNext();
         }
         $html .= '</ul>'.LF;
      }
      $html .= '<div style="width:235px; font-size:8pt; text-align:right; padding-top:5px;">';
      $current_user = $this->_environment->getCurrentUserItem();
      if ($current_user->isUser() and $this->_with_modifying_actions ) {
          $params = array();
         $params = $this->_environment->getCurrentParameterArray();
         $group_manager = $this->_environment->getGroupManager();
         $group_manager->setContextLimit($this->_environment->getCurrentContextID());
           $params['attach_view'] = 'yes';
           $params['attach_type'] = 'item';
           $html .= ahref_curl($this->_environment->getCurrentContextID(),
                               $this->_environment->getCurrentModule(),
                               $this->_environment->getCurrentFunction(),
                               $params,
                               $this->_translator->getMessage('COMMON_ITEM_ATTACH')
                               ).LF;
           unset($params);
      } else {
         $html .= '<span class="disabled">'.$this->_translator->getMessage('COMMON_ITEM_ATTACH').'</span>'.LF;
      }
      $html .= '</div>'.LF;
      $html .='      </div>';
      $html .='      </div>';
      $html .='      </div>';
      return $html;
   }

   function _getPathItemsAsHTML($topic_item,$item_id,$path_item_list){
      $html  ='<div>'.LF;
      $html .='<ol style="list-style-type: decimal; list-style-position:inside; font-size:8pt; margin:0px; padding:0px;">  '.LF;
      $path_item_list = $topic_item->getPathItemList();
      $path_item = $path_item_list->getFirst();
      while($path_item){
         $path_item_id = $path_item->getItemID();
         $path_item_type = $path_item->getItemType();
         if ($path_item_id == $item_id){
            $html .='<li style="padding-left:7px;"  class="detail_list_entry">'.LF;
            $html .= '<a title="'.$path_item->getTitle().'">'.chunkText($path_item->getTitle(),35).'</a>';
         }else{
            $html .='<li style="padding-left:7px;">'.LF;
            $params = array();
            $params['iid'] = $path_item_id;
            $params['path'] = $topic_item->getItemID();

            $user = $this->_environment->getCurrentUser();
            if ($path_item->isNotActivated() and !($path_item->getCreatorID() == $user->getItemID() or $user->isModerator()) ){
                $activating_date = $path_item->getActivatingDate();
                if (strstr($activating_date,'9999-00-00')){
                   $link_creator_text = $path_item->getTitle().' ('.$this->_translator->getMessage('COMMON_NOT_ACTIVATED').')';
                }else{
                   $link_creator_text = $path_item->getTitle().' ('.$this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($path_item->getActivatingDate()).')';
                }
                $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                     type2Module($path_item_type),
                                     'detail',
                                     $params,
                                     chunkText($path_item->getTitle(),35),
                                     $link_creator_text);
                unset($params);
            }else{
               $html .= ahref_curl($this->_environment->getCurrentContextID(),type2Module($path_item_type),'detail',$params,chunkText($path_item->getTitle(),35),$path_item->getTitle());
               unset($params);
            }
         }
         $html .='</li>'.LF;
         $path_item = $path_item_list->getNext();
      }
      $html .='</ol>'.LF;
      $html .=' </div>'.LF;
      return $html;
   }


   function _getBrowsingIconsAsHTML($current_item, $pos_number, $count){
     return '';
   }


   function _getItemAsHTML ($spaces=0) {
      include_once('functions/error_functions.php');
      trigger_error('cs_detail_view->_getItemAsHTML must be overwritten in subclass', E_USER_ERROR);
   }

   function _getSubItemTitleAsHTML ($subitem, $pos_number) {
      $html = '';
      if ( isset($subitem) ) {
         if ($subitem->isA(CS_USER_TYPE)) {
            $html .= $this->_translator->getMessage('USER_PREFERENCES').LF;
            if ( !empty($this->_sub_item_title_description) ) {
               $html .= ' <span style="font-weight: normal; font-size: small;">('.$this->_text_as_html_short($this->_compareWithSearchText($this->_sub_item_title_description)).')</span>'.LF;
            }
         } else {
            $html .= $this->_text_as_html_short($this->_compareWithSearchText($subitem->getTitle()));
         }
      } else {
         $html .= 'NO ITEM';
      }
      return $html;
   }

   function _getSubItemAsHTML ($subitem, $pos_number, $spaces=0) {
      include_once('functions/error_functions.php');
      trigger_error('cs_detail_view->_getSubItemAsHTML must be overwritten in subclass', E_USER_ERROR);
   }


   /**
    * Internal methods for printing out connected rubrics.
    * Generally, these methods need not be overridden.
    */
   function _is_perspective ($rubric) {
      $in_array = in_array($rubric, array(CS_GROUP_TYPE, CS_TOPIC_TYPE, CS_INSTITUTION_TYPE)) ;
      if ($rubric == CS_INSTITUTION_TYPE) {
         $context = $this->_environment->getCurrentContextItem();
         $in_array = $context->withRubric(CS_INSTITUTION_TYPE);
      }
      return $in_array;
   }

   function _has_attach_link ($rubric) {
      return $this->_is_perspective($rubric) or $rubric ==CS_COMMUNITY_TYPE;
   }

   function _is_always_visible ($rubric) {
      return $this->_is_perspective($rubric) or $rubric ==CS_COMMUNITY_TYPE;
   }

   function _getPluginInfosForNetNavigationAsHTML () {
      $html = '';
      global $c_plugin_array;
      if (isset($c_plugin_array) and !empty($c_plugin_array)) {
         foreach ($c_plugin_array as $plugin) {
            $plugin_class = $this->_environment->getPluginClass($plugin);
            if (method_exists($plugin_class,'getUnderNetNavigationAsHTML')) {
               $retour = $plugin_class->getUnderNetNavigationAsHTML();
               if (isset($retour)) {
                  $html .= $retour;
               }
            }
         }
      }
      return $html;
   }

  function _getRubricInfoAsHTML($act_rubric){
      $html='';
      $room = $this->_environment->getCurrentContextItem();
      $info_text = $room->getUsageInfoTextForRubric($act_rubric);
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title">'.$this->_text_as_html_short($room->getUsageInfoHeaderForRubric($act_rubric)).'</div>';
      $html .= '<div class="right_box_main" style="font-size:8pt;">'.LF;
      $html .= $this->_text_as_html_long($this->_cleanDataFromTextArea($info_text)).BRLF;
      $act_user = $this->_environment->getCurrentUserItem();
      if ($act_user->isModerator()){
         $array = $this->_environment->getCurrentParameterArray();
         $array['back_mod']=$this->_environment->getCurrentModule();
         $array['back_fct']=$this->_environment->getCurrentFunction();
         $html .= '<div style="width:100%; text-align:center;">'
               .'<span class="desc_usage">'.ahref_curl($this->_environment->getCurrentContextID(), 'context', 'info_text_edit', $array,$this->_translator->getMessage('COMMON_EDIT'), '', '', '', '', '')
               .'</span></div>';
      }else{
         $html .= '<div style="width:100%; text-align:center;">'
               .'<span class="disabled" style="font-size: 8pt;">'.$this->_translator->getMessage('COMMON_EDIT').'</span>'
               .'</div>';
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }



   function _getForwardLinkAsHTML ($ids,$forward_type='') {
      $pos       = $this->getPosition();  // zero-based!
      $item_manager = $this->_environment->getItemManager();
      $ids_not_activated = array();
      #foreach($ids as $index => $id){
      #   $item = $item_manager->getItem($id);
      #   if($item->isNotActivated()){
      #      $ids_not_activated[] = $id;
      #   }
      #}
      $item_manager->resetLimits();
      $item_manager->setContextLimit($this->_environment->getCurrentContextID());
      $item_manager->setIDArrayLimit($ids);
      $item_manager->select();
      $item_list = $item_manager->get();
      $temp_item = $item_list->getFirst();
      while($temp_item){
         if($temp_item->isNotActivated()){
            $ids_not_activated[] = $temp_item->getItemID();
         }
         $temp_item = $item_list->getNext();
      }
      $item_manager->resetLimits();

      $count_all = count($ids);
      // Determine the position if it is not (correctly) given
      if ( $pos < 0 || $pos >= $count_all ) {
         if ( empty($ids) ) {
            $pos = -1;
         } else {
            $item = $this->getItem();
            if ( isset($item) ) {
               $pos = array_search($item->getItemID(), $ids);
               if ( $pos === NULL or $pos === false ) {
                  $pos = -1;
               }
            } else {
               $pos = -1;
            }
         }
         $this->setPosition($pos);
      }

      $pos_index_start = 0;
      $pos_index_left = $pos-1;
      $pos_index_right = $pos+1;
      $pos_index_end = $count_all-1;
      // prepare browsing
      if ( $pos > 0 ) { // can I browse to the left / start?
         for ($index = $pos-1, $max_count = 0; $index >= $max_count; $index--) {
            if(in_array($ids[$index], $ids_not_activated)){
               $pos_index_left--;
            } else {
               break;
            }
       }
       if($pos_index_left >= 0){
            $browse_left = $ids[$pos_index_left];
       } else {
          $browse_left = 0;      // 0 means: do not browse
       }
       for ($index = 0, $max_count = $pos-1; $index <= $max_count; $index++) {
            if(in_array($ids[$index], $ids_not_activated)){
               $pos_index_start++;
            } else {
               break;
            }
       }
       if($pos_index_left >= 0){
            $browse_start = $ids[$pos_index_start];
       } else {
            $browse_start = 0;     // 0 means: do not browse
       }
      } else {
         $browse_left = 0;      // 0 means: do not browse
         $browse_start = 0;     // 0 means: do not browse
      }
      if ( $pos >= 0 and $pos < $count_all-1 ) { // can I browse to the right / end?
        for ($index = $pos+1, $max_count = $count_all-1; $index <= $max_count; $index++) {
            if(in_array($ids[$index], $ids_not_activated)){
               $pos_index_right++;
            } else {
               break;
            }
       }
       if($pos_index_right < sizeof($ids)){
            $browse_right = $ids[$pos_index_right];
       } else {
          $browse_right = 0;   // 0 means: do not browse
       }
       for ($index = $count_all-1, $max_count = $pos+1; $index >= $max_count; $index--) {
            if(in_array($ids[$index], $ids_not_activated)){
               $pos_index_end--;
            } else {
               break;
            }
       }
       if($pos_index_right < sizeof($ids)){
            $browse_end = $ids[$pos_index_end];
       } else {
            $browse_end = 0;     // 0 means: do not browse
       }
      } else {
         $browse_right = 0;     // 0 means: do not browse
         $browse_end = 0;       // 0 means: do not browse
      }

      // create HTML for browsing arrows to left
      $html = '<div style="float:right;">';
      if ( $browse_start > 0 ) {
         $image = '<span class="bold">&lt;&lt;</span>';
         $params = array();
         $params = $this->_environment->getCurrentParameterArray();
         unset($params[$this->_module.'_option']);
         unset($params['add_to_'.$this->_module.'_clipboard']);
         $params['iid'] = $browse_start;
         $params['pos'] = $pos_index_start;
         if (!empty($forward_type) and ($forward_type =='path' or $forward_type =='search')){
            $item = $item_manager->getItem($browse_start);
            $module = $item->getItemType();
            if ($module == 'label'){
               $label_manager = $this->_environment->getLabelManager();
               $label_item = $label_manager->getItem($item->getItemID());
               $module = $label_item->getLabelType();
            }
         }else{
            $module = $this->_module;
         }
         $html .= ahref_curl($this->_environment->getCurrentContextID(),$module, $this->_function,
                                   $params,
                                   $image, $this->_translator->getMessage('COMMON_BROWSE_START_DESC'),
                                   '','','','','','class="detail_system_link"').LF;
         unset($params);
      } else {
         $html .= '         <span>&lt;&lt;</span>'.LF;
      }
      $html .= '|';
      if ( $browse_left > 0 ) {
         $image = '<span class="bold">&lt;</span>';
         $params = array();
         $params = $this->_environment->getCurrentParameterArray(); // $this->_parameter ???
         unset($params[$this->_module.'_option']);
         unset($params['add_to_'.$this->_module.'_clipboard']);
         $params['iid'] = $browse_left;
         if (!empty($forward_type) and ($forward_type =='path' or $forward_type =='search')){
            $item = $item_manager->getItem($browse_left);
            $module = $item->getItemType();
            if ($module == 'label'){
               $label_manager = $this->_environment->getLabelManager();
               $label_item = $label_manager->getItem($item->getItemID());
               $module = $label_item->getLabelType();
            }
         }else{
            $module = $this->_module;
         }
         //$params['pos'] = $pos-1;
         $params['pos'] = $pos_index_left;
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $module, $this->_function,
                                   $params,
                                   $image, $this->_translator->getMessage('COMMON_BROWSE_LEFT_DESC'),
                                   '','','','','','class="detail_system_link"').LF;
         unset($params);
      } else {
         $html .= '         <span>&lt;</span>'.LF;
      }
      $html .= '|';
      // Show position

      // create HTML for browsing arrows to left
      if ( $browse_right > 0 ) {
         $image = '<span class="bold">&gt;</span>';
         $params = array();
         $params = $this->_environment->getCurrentParameterArray(); // $this->_parameter ???
         unset($params[$this->_module.'_option']);
         unset($params['add_to_'.$this->_module.'_clipboard']);
         $params['iid'] = $browse_right;
         if (!empty($forward_type) and ($forward_type =='path' or $forward_type =='search' or $forward_type =='link_item')){
            $item = $item_manager->getItem($browse_right);
            $module = $item->getItemType();
            if ($module == 'label'){
               $label_manager = $this->_environment->getLabelManager();
               $label_item = $label_manager->getItem($item->getItemID());
               $module = $label_item->getLabelType();
            }
         }else{
            $module = $this->_module;
         }

         //$params['pos'] = $pos+1;
         $params['pos'] = $pos_index_right;
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $module, $this->_function,
                                   $params, $image, $this->_translator->getMessage('COMMON_BROWSE_RIGHT_DESC'),'','','','','','class="detail_system_link"').LF;
         unset($params);
      } else {
         $html .= '         <span>&gt;</span>'.LF;
      }
      $html .= '|';
      if ( $browse_end > 0 ) {
         $image = '<span class="bold">&gt;&gt;</span>';
         $params = array();
         $params = $this->_environment->getCurrentParameterArray(); // $this->_parameter ???
         unset($params[$this->_module.'_option']);
         unset($params['add_to_'.$this->_module.'_clipboard']);
         $params['iid'] = $browse_end;
         if (!empty($forward_type) and ($forward_type =='path' or $forward_type =='search')){
            $item = $item_manager->getItem($browse_end);
            $module = $item->getItemType();
            if ($module == 'label'){
               $label_manager = $this->_environment->getLabelManager();
               $label_item = $label_manager->getItem($item->getItemID());
               $module = $label_item->getLabelType();
            }
         }else{
            $module = $this->_module;
         }
         $params['pos'] = $pos_index_end;
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                   $params,
                                   $image, $this->_translator->getMessage('COMMON_BROWSE_END_DESC'),'','','','','','class="detail_system_link"').LF;
         unset($params);
      } else {
         $html .= '         <span>&gt;&gt;</span>'.LF;
      }
      $html .= '</div>';
      $html .= '<div id="right_box_page_numbers">';
      if (!empty($forward_type) and $forward_type =='path'){
         if ( empty($ids) ) {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_PATH_ENTRIES').' 1 / 1</span>'.LF;
         } else {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_PATH_ENTRIES').' '.($pos+1).' / '.$count_all.'</span>'.LF;
         }
      }elseif(!empty($forward_type) and $forward_type =='search'){
         if ( empty($ids) ) {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_SEARCH_ENTRIES').' 1 / 1</span>'.LF;
         } else {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_SEARCH_ENTRIES').' '.($pos+1).' / '.$count_all.'</span>'.LF;
         }
      }elseif(!empty($forward_type) and $forward_type =='link_item'){
         if ( empty($ids) ) {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_REFERENCED_ENTRIES').' 1 / 1</span>'.LF;
         } else {
            $html .= '<span class="bold">'.$this->_translator->getMessage('COMMON_REFERENCED_ENTRIES').' '.($pos+1).' / '.$count_all.'</span>'.LF;
         }
       }else{
         switch ( mb_strtoupper($this->_environment->getCurrentModule(), 'UTF-8') ){
            case 'ANNOUNCEMENT':
               $text = $this->_translator->getMessage('COMMON_ANNOUNCEMENT');
               break;
            case 'DATE':
               $text = $this->_translator->getMessage('COMMON_DATE');
               break;
            case 'DISCUSSION':
               $text = $this->_translator->getMessage('COMMON_DISCUSSION');
               break;
            case 'GROUP':
               $text = $this->_translator->getMessage('COMMON_GROUP');
               break;
            case 'INSTITUTION':
               $text = $this->_translator->getMessage('COMMON_INSTITUTION');
               break;
            case 'MATERIAL':
               $text = $this->_translator->getMessage('COMMON_MATERIAL');
               break;
            case 'MATERIAL_ADMIN':
               $text = $this->_translator->getMessage('COMMON_MATERIAL');
               break;
            case 'PROJECT':
               $text = $this->_translator->getMessage('COMMON_PROJECT');
               break;
            case 'TODO':
               $text = $this->_translator->getMessage('COMMON_TODO');
               break;
            case 'TOPIC':
               $text = $this->_translator->getMessage('COMMON_TOPIC');
               break;
            case 'USER':
               $text = $this->_translator->getMessage('COMMON_USER');
               break;
            case 'MYROOM':
               $text = $this->_translator->getMessage('COMMON_ROOM');
               break;
            case 'ACCOUNT':
               $text = $this->_translator->getMessage('COMMON_ACCOUNTS');
            break;            default:
               $text = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' '.__FILE__.'('.__LINE__.') ' );
               break;
         }
         if ( empty($ids) ) {
            $html .= '<span class="bold">'.$text.' 1 / 1</span>'.LF;
         } else {
            $html .= '<span class="bold">'.$text.' '.($pos+1).' / '.$count_all.'</span>'.LF;
         }
      }
      $html .= '';
      $html .= '</div>';

      return /*$this->_text_as_html_short(*/$html/*)*/;
   }



   /**
    * Internal method for showing the creator or modificator
    * of an item or subitem.
    */
   function _getCreatorInformationAsHTML ($item, $spacecount=0, $mode = 'short') {
      $html  = '';
      $environment = $this->_environment;
      $context = $environment->getCurrentContextItem();
      $user = $environment->getCurrentUserItem();
      $formal_data = array();
      // Modificator
      $modificator = $item->getModificatorItem();
      // Calculate number / percentage of users who read this item
      if ( ( $context->isProjectRoom()
             or $context->isGroupRoom()
           )
           and !in_array($item->getType(), array(CS_SECTION_TYPE,
                                                 CS_DISCARTICLE_TYPE,
                                                 CS_STEP_TYPE,
                                                 CS_ANNOTATION_TYPE)) ) {
         $reader_manager = $environment->getReaderManager();
         $user_manager = $environment->getUserManager();
         $user_list = $user_manager->getAllRoomUsersFromCache($environment->getCurrentContextID());
         $user_count = $user_list->getCount();
         $read_count = 0;
         $read_since_modification_count = 0;
         $current_user = $user_list->getFirst();
         $id_array = array();
         while ( $current_user ) {
            $id_array[] = $current_user->getItemID();
            $current_user = $user_list->getNext();
         }
         $reader_manager->getLatestReaderByUserIDArray($id_array,$item->getItemID());
         $current_user = $user_list->getFirst();
         while ( $current_user ) {
            $current_reader = $reader_manager->getLatestReaderForUserByID($item->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
               if ( $current_reader['read_date'] >= $item->getModificationDate() ) {
                  $read_count++;
                  $read_since_modification_count++;
               } else {
                  $read_count++;
               }
            }
            $current_user = $user_list->getNext();
         }
         $read_percentage = round(($read_count/$user_count) * 100);
         $read_since_modification_percentage = round(($read_since_modification_count/$user_count) * 100);
      }

      if ( isset($modificator)
           and $modificator->isRoot()
         ) {
         $temp_html = $this->_text_as_html_short($this->_compareWithSearchText($modificator->getFullname()));
      } elseif ( $environment->inProjectRoom()
                 or $environment->inGroupRoom()
               ) {
         if ( isset($modificator)
              and $modificator->isUser()
              and !$modificator->isDeleted()
              and $modificator->maySee($user)
            ) {
            $params = array();
            $params['iid'] = $modificator->getItemID();
            $temp_html = ahref_curl($this->_environment->getCurrentContextID(),
                                    CS_USER_TYPE,
                                    'detail',
                                    $params,
                                    // $this->_compareWithSearchText($modificator->getFullname()),
                                    $this->_text_as_html_short($this->_compareWithSearchText($modificator->getFullname())),
                                     '',
                                     '',
                                     '',
                                     '',
                                     '',
                                     '',
                                     'style="font-size:10pt;"');
         } elseif ( isset($modificator) and !$modificator->isDeleted() ) {
            $temp_html = '<span class="disabled">'.$modificator->getFullname().'</span>';

         } elseif ( $item->isA(CS_USER_TYPE)
                    and $item->getUserID() == $modificator->getUserID()
                    and $item->getAuthSource() == $modificator->getAuthSource()
                  ) {
            $temp_html = $this->_compareWithSearchText($modificator->getFullname());
         } else {
            $temp_html = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
         }
         unset($params);
      } elseif ( ($user->isUser() and isset($modificator) and  $modificator->isVisibleForLoggedIn())
                 || (!$user->isUser() and isset($modificator) and $modificator->isVisibleForAll())
                 || ( isset($modificator) and $environment->getCurrentUserID() == $modificator->getItemID()) ) {
         $params = array();
         $params['iid'] = $modificator->getItemID();
         if( !$modificator->isDeleted() and $modificator->maySee($user) ){
            if ( !$this->_environment->inPortal() ){
               $temp_html = ahref_curl($this->_environment->getCurrentContextID(),
                                     'user',
                                     'detail',
                                     $params,
                                     $this->_text_as_html_short($this->_compareWithSearchText($modificator->getFullname())),
                                     '',
                                     '',
                                     '',
                                     '',
                                     '',
                                     '',
                                     'style="font-size:10pt;"');
            }else{
               $temp_html = '<span class="disabled">'.$this->_compareWithSearchText($modificator->getFullname()).'</span>';
            }
         } elseif ( $item->isA(CS_USER_TYPE)
                    and $item->getUserID() == $modificator->getUserID()
                    and $item->getAuthSource() == $modificator->getAuthSource()
                  ) {
            $temp_html = $this->_compareWithSearchText($modificator->getFullname());
         }  else {
            $temp_html = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
         }
         unset($params);
      }elseif ( $item->mayExternalSee($this->_environment->getCurrentUserItem())) {
            $temp_html = $this->_compareWithSearchText($modificator->getFullname());
      }else {
         if(isset($modificator) and !$modificator->isDeleted()){
            $current_user_item = $this->_environment->getCurrentUserItem();
            if ( $current_user_item->isGuest() or  !$modificator->maySee($user) ) {
               $temp_html = '<span class="disabled">'.$this->_translator->getMessage('COMMON_USER_NOT_VISIBLE').'</span>';
            } else {
               $temp_html = '<span class="disabled">'.$this->_compareWithSearchText($modificator->getFullname()).'</span>';
            }
            unset($current_user_item);
         }else{
            $temp_html = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
         }
      }
      if ($item->isNotActivated()){
         $title = '&nbsp;<span class="creator_information_key">'.$this->_translator->getMessage('COMMON_CREATED_BY').':</span> '.$temp_html.', '.$this->_translator->getDateTimeInLangWithoutOClock($item->getCreationDate());
      }else{
         $title = '&nbsp;<span class="creator_information_key">'.$this->_translator->getMessage('COMMON_LAST_MODIFIED_BY').':</span> '.$temp_html.', '.$this->_translator->getDateTimeInLangWithoutOClock($item->getModificationDate());
      }
      $html .='&nbsp;<img id="toggle'.$item->getItemID().'" src="images/more.gif"/>';
      $html .= $title;
      $html .= '<div id="creator_information'.$item->getItemID().'">'.LF;
      $html .= '<div class="creator_information_panel">     '.LF;
      $html .= '<div>'.LF;
      $html .= '<table class="creator_info" summary="Layout">'.LF;


      // Read count (for improved awareness)
      if ( ( $context->isProjectRoom()
             or $context->isGroupRoom()
           )
              and !in_array($item->getType(), array(CS_SECTION_TYPE,
                                                    CS_DISCARTICLE_TYPE,
                                                    CS_STEP_TYPE,
                                                    CS_ANNOTATION_TYPE))
         ) {
         
         
         $user_allowed_detailed_awareness = false;
         if($user->isModerator()){
            $user_allowed_detailed_awareness = true;
         } else {
            if($context->getWorkflowReaderShowTo() == 'all'){
               $user_allowed_detailed_awareness = true;
            }
         }
         
         $is_workflow_type = false;
         if(in_array($item->getType(), array(CS_SECTION_TYPE,CS_MATERIAL_TYPE))){
            $is_workflow_type = true;
         }
         
         if(!$context->withWorkflowReader() or ($context->withWorkflowReader() and ($context->getWorkflowReaderGroup() == '0') and ($context->getWorkflowReaderPerson() == '0')) or !$user_allowed_detailed_awareness or !$is_workflow_type){
            $html .= '   <tr>'.LF;
            $html .= '      <td></td>'.LF;
            $html .= '      <td class="key" style="padding-left:8px; vertical-align:top;">'.LF;
            $html .= '         '.$this->_translator->getMessage('COMMON_READ_SINCE_MODIFICATION').':&nbsp;'.LF;
            $html .= '      </td>'.LF;
            $html .= '      <td class="value">'.LF;
            if ( $read_since_modification_count == 1 ) {
               $html .= ' '.$read_since_modification_count.'&nbsp;'.$this->_translator->getMessage('COMMON_NUMBER_OF_MEMBERS_SINGULAR').''.LF;
            } else {
               $html .= '       '.$read_since_modification_count.'&nbsp;'.$this->_translator->getMessage('COMMON_NUMBER_OF_MEMBERS').''.LF;
            }
         } else if($context->withWorkflowReader()){
            $html .= '   <tr>'.LF;
            $html .= '      <td></td>'.LF;
            $html .= '      <td class="key" style="padding-left:8px; vertical-align:top;">'.LF;
            $html .= '         '.$this->_translator->getMessage('COMMON_WORKFLOW_READ_SINCE_MODIFICATION').':&nbsp;'.LF;
            $html .= '      </td>'.LF;
            $html .= '      <td class="value" style="padding-top:10px; padding-bottom:10px;">'.LF;
            $item_manager = $environment->getItemManager();
            $user_manager = $environment->getUserManager();
            $user_list = $user_manager->getAllRoomUsersFromCache($environment->getCurrentContextID());
            $current_user = $user_list->getFirst();
            $id_array = array();
            while ( $current_user ) {
               $id_array[] = $current_user->getItemID();
               $current_user = $user_list->getNext();
            }
            
            $users_read_array = $item_manager->getUsersMarkedAsWorkflowReadForItem($item->getItemID());
            $persons_array = array();
            foreach($users_read_array as $user_read){
               $persons_array[] = $user_manager->getItem($user_read['user_id']);
            }
            
            if($context->getWorkflowReaderGroup() == '1'){
               $html .= $this->_translator->getMessage('COMMON_GROUPS').': ';
               $group_manager = $environment->getGroupManager();
               $group_manager->setContextLimit($environment->getCurrentContextID());
               $group_manager->setTypeLimit('group');
               $group_manager->select();
               $group_list = $group_manager->get();
               $group_item = $group_list->getFirst();
               $first = true;
               while($group_item){
                  $link_user_list = $group_item->getLinkItemList(CS_USER_TYPE);
                  $user_count_complete = $link_user_list->getCount();
                  
                  $user_count = 0;
                  foreach($persons_array as $person){
                     $temp_link_list = $person->getLinkItemList(CS_GROUP_TYPE);
                     $temp_link_item = $temp_link_list->getFirst();
                     while($temp_link_item){
                        $temp_group_item = $temp_link_item->getLinkedItem($person);
                        if($group_item->getItemID() == $temp_group_item->getItemID()){
                           $user_count++;
                        }
                        $temp_link_item = $temp_link_list->getNext();
                     }
                  }
                  
                  $params = array();
                  $params['iid'] = $group_item->getItemID();
                  if(!$first){
                     $html .= ', ';
                  } else {
                     $first = false;
                  }
                  $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                        'group',
                                        'detail',
                                        $params,
                                        $this->_text_as_html_short($this->_compareWithSearchText($group_item->getTitle()).' ('.$user_count.' '.$this->_translator->getMessage('COMMON_OF').' '.$user_count_complete.')'));
                  $group_item = $group_list->getNext();
               }
               $html .= '<br/>';
            }
            if($context->getWorkflowReaderPerson() == '1'){
               $html .= $this->_translator->getMessage('COMMON_USERS').': ';
               $first = true;
               foreach($persons_array as $person){
                  $params = array();
                  $params['iid'] = $person->getItemID();
                  if(!$first){
                     $html .= ', ';
                  } else {
                     $first = false;
                  }
                  $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                        'user',
                                        'detail',
                                        $params,
                                        $this->_text_as_html_short($this->_compareWithSearchText($person->getFullname())));
               }
            }
         }
         $html .= '      </td>'.LF;
         $html .= '   </tr>'.LF;
      }
      
      // Creator
      $creator = $item->getCreatorItem();
      if ( isset($creator) and $creator->isRoot() ) {
         $temp_html = $this->_text_as_html_short($this->_compareWithSearchText($creator->getFullname()));
      } elseif ( $environment->inProjectRoom() ) {
         if ( isset($creator) and $creator->isUser() and !$creator->isDeleted()  and $creator->maySee($user)){
            $params = array();
            $params['iid'] = $creator->getItemID();
            $temp_html = ahref_curl($this->_environment->getCurrentContextID(),
                                     'user',
                                     'detail',
                                     $params,
                                     $this->_text_as_html_short($this->_compareWithSearchText($creator->getFullname())));
         } elseif ( isset($creator) and !$creator->isDeleted()){
            $temp_html = '<span class="disabled">'.$this->_compareWithSearchText($creator->getFullname()).'</span>';
         } else {
            $temp_html = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
         }
      } elseif ( $user->isUser() and isset($creator)  and $creator->maySee($user) and ($creator->isVisibleForLoggedIn())
                    || (!$user->isUser() and $creator->isVisibleForAll()) ) {
         $params = array();
         $params['iid'] = $creator->getItemID();
         if( !$creator->isDeleted() ){
            if ( !$this->_environment->inPortal() ){
               $temp_html = ahref_curl($this->_environment->getCurrentContextID(),
                                     'user',
                                     'detail',
                                     $params,
                                     $this->_text_as_html_short($this->_compareWithSearchText($creator->getFullname())));
            }else{
               $temp_html = '<span class="disabled">'.$this->_compareWithSearchText($creator->getFullname()).'</span>';
            }
         }else{
            $temp_html = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
         }
         unset($params);
      } elseif ( $item->mayExternalSee($this->_environment->getCurrentUserItem())) {
            $temp_html = $this->_compareWithSearchText($modificator->getFullname());
      } else {
         if(isset($creator) and !$creator->isDeleted()){
            $current_user_item = $this->_environment->getCurrentUserItem();
            if ( $current_user_item->isGuest() ) {
               $temp_html = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
            } else {
               $temp_html = $this->_compareWithSearchText($creator->getFullname());
            }
            unset($current_user_item);
         }else{
            $temp_html = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
         }
      }
      $html .= '   <tr>'.LF;
      $html .= '      <td></td>'.LF;
      $html .= '      <td class="key"  style="padding-left:8px;">'.LF;
      $html .= '         '.$this->_translator->getMessage('COMMON_CREATED_BY').':&nbsp;'.LF;
      $html .= '      </td>'.LF;
      $html .= '      <td class="value">'.LF;
      $html .= '         '.$temp_html.', '.$this->_translator->getDateTimeInLang($item->getCreationDate()).LF;
      $html .= '      </td>'.LF;
      $html .= '   </tr>'.LF;

      // All users who ever edited this item
      $link_modifier_item_manager = $environment->getLinkModifierItemManager();
      $user_manager = $environment->getUserManager();
      $modifiers = $link_modifier_item_manager->getModifiersOfItem($item->getItemID());
      $modifier_array = array();
      foreach($modifiers as $modifier_id) {
         $modificator = $user_manager->getItem($modifier_id);
         //Links only at accessible contact pages
         if ( isset($modificator) and $modificator->isRoot() ) {
            $temp_text = $this->_compareWithSearchText($modificator->getFullname());
            $modifier_array[] = $temp_text;
         } elseif ( $modificator->getContextID() == $item->getContextID() ) {
            if ( $environment->inProjectRoom() ) {
               $params = array();
               if (isset($modificator) and !empty($modificator) and $modificator->isUser() and !$modificator->isDeleted() and $modificator->maySee($user)){
                  $params['iid'] = $modificator->getItemID();
                  $temp_text = ahref_curl($this->_environment->getCurrentContextID(),
                                     'user',
                                     'detail',
                                     $params,
                                     $this->_compareWithSearchText($modificator->getFullname()));
               }elseif(isset($modificator) and  !$modificator->isDeleted()){
                   $temp_text = '<span class="disabled">'.$this->_compareWithSearchText($modificator->getFullname()).'</span>';
               }else{
                   $temp_text = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
               }
               $modifier_array[] = $temp_text;
            } elseif ( ($user->isUser() and isset($modificator) and  $modificator->isVisibleForLoggedIn())
                          || (!$user->isUser() and isset($modificator) and $modificator->isVisibleForAll())
                          || (isset($modificator) and $environment->getCurrentUserID() == $modificator->getItemID()) ) {
               $params = array();
               $params['iid'] = $modificator->getItemID();
               if(!$modificator->isDeleted() and $modificator->maySee($user)){
                  if ( !$this->_environment->inPortal() ){
                     $modifier_array[] = ahref_curl($this->_environment->getCurrentContextID(),
                                        'user',
                                        'detail',
                                        $params,
                                        $this->_text_as_html_short($this->_compareWithSearchText($modificator->getFullname())));
                  }else{
                     $modifier_array[] = '<span class="disabled">'.$this->_compareWithSearchText($modificator->getFullname()).'</span>';
                  }
               }else{
                  $modifier_array[] = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
               }
               unset($params);
            } elseif ( $item->mayExternalSee($this->_environment->getCurrentUserItem())) {
               $modifier_array[] = $this->_compareWithSearchText($modificator->getFullname());
            } else {
               if(isset($modificator) and !$modificator->isDeleted()){
                  $current_user_item = $this->_environment->getCurrentUserItem();
                  if ( $current_user_item->isGuest() ) {
                     $modifier_array[] = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
                  } else {
                     $modifier_array[] = $this->_compareWithSearchText($modificator->getFullname());
                  }
                  unset($current_user_item);
               }else{
                  $modifier_array[] = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
               }
            }
         }
      }
      $modifier_array = array_unique($modifier_array);

      $html .= '   <tr>'.LF;
      $html .= '      <td></td>'.LF;
      $html .= '      <td class="key"  style="padding-left:8px;">'.LF;
      $html .= '         '.$this->_translator->getMessage('COMMON_ALL_MODIFIERS').':&nbsp;'.LF;
      $html .= '      </td>'.LF;
      $html .= '      <td class="value">'.LF;
      $html .= '         '.implode(', ',$modifier_array);
      $html .= '      </td>'.LF;
      $html .= '   </tr>'.LF;

      // Reference number
      $html .= '   <tr>'.LF;
      $html .= '      <td></td>'.LF;
      $html .= '      <td class="key"  style="padding-left:8px;">'.LF;
      $html .= '         '.$this->_translator->getMessage('COMMON_REFNUMBER').':&nbsp;'.LF;
      $html .= '      </td>'.LF;
      $html .= '      <td class="value">'.LF;
      $html .= '         '.$item->getItemID();
      $html .= '      </td>'.LF;
      $html .= '   </tr>'.LF;
      $html .= '</table>'.LF;

      $html .= '</div>'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='<script type="text/javascript">document.onload = initCreatorInformations("'.$item->getItemID().'",false);</script>';

      //Read percentage gauge (for improved awareness)
      if ( ( $context->isProjectRoom()
             or $context->isGroupRoom()
           )
           and !in_array($item->getType(), array(CS_SECTION_TYPE,
                                                 CS_DISCARTICLE_TYPE,
                                                 CS_STEP_TYPE,
                                                 CS_ANNOTATION_TYPE))
         ) {
         $html .= '<table class="gauge-wrapper" summary="Layout"><tr>'.LF;
         $html .= '   <td id="creator_information_read_text" width="50%">'.$this->_translator->getMessage('COMMON_READ').':</td>'.LF;
         $html .= '   <td width="50%">'.LF;
         $html .= '      <div class="gauge">'.LF;
         if ( $read_percentage >= 5 ) {
            $html .= '         <div class="gauge-bar" style="width:'.$read_percentage.'%;">'.$read_count.'</div>'.LF;
         } else {
            $html .= '         <div class="gauge-bar" style="width:'.$read_percentage.'%">&nbsp;</div>'.LF;
         }
         $html .= '      </div>'.LF;
         $html .= '   </td>'.LF;
         $html .= '</tr></table>'.LF;
      }
      return $html;
   }


   /**
    * Internal method used for formatting tabular (formal) data.
    */
   function _getFormalDataAsHTML($data, $spacecount=0, $clear=false) {
      $prefix = str_repeat(' ', $spacecount);
      $html  = $prefix.'<table class="detail" summary="Layout"';
      if ( $clear ) {
         $html .= 'style="clear:both;padding-bottom:10px;"';
      }else{
         $html .= 'style="padding-bottom:10px;"';
      }
      $html .= '>'.LF;
      foreach ($data as $value) {
         $html .= $prefix.'   <tr>'.LF;
         $html .= $prefix.'      <td class="key">'.LF;
         if ( !empty($value[0]) ) {
            $html .= $prefix.'         '.$value[0].':&nbsp;'.LF;
         } else {
            $html .= $prefix.'         &nbsp;';
         }
         $html .= $prefix.'      </td><td class="value">'.LF;
         if ( !empty($value[1]) ) {
            if ( !empty($value[0])) {
               $html .= $prefix.'         '.$value[1].LF;
            }
         }
         $html .= $prefix.'      </td>'.LF;
         $html .= $prefix.'   </tr>'.LF;
      }
      $html .= $prefix.'</table>'.LF;
      return $html;
   }




   function _withAttachedUsers($item){
      return true;
   }

   function getTitle () {
     $retour  = '';
     $retour .= $this->_getTitleAsHTML();
     $this->_display_title = false;
     return $retour;
   }

   function _getAnnotationContentAsHTML($item) {
      $user = $this->_environment->getCurrentUser();
      $annotated_item = $this->getItem();


      $html  = LF.'<!-- BEGIN OF ANNOTATION ITEM -->'.LF;
      $html .= '   <div class="item" style="margin-left:3px;">'.LF;
      $desc = $item->getDescription();
      if ( !empty($desc) ) {
         $desc = $this->_text_as_html_long($this->_cleanDataFromTextArea($desc));
         $html .= $this->getScrollableContent($desc,$item,'',true);
      }
      // Show info about the version the annotation refers to
      $current_version = $annotated_item->getVersionID();
      $annotated_version = $item->getAnnotatedVersionID();
      if ( $current_version > $annotated_version ) {
         $text = '('.$this->_translator->getMessage('ANNOTATION_FOR_OLDER_VERSION').')';
      } elseif ( $current_version < $annotated_version ) {
         $text = '('.$this->_translator->getMessage('ANNOTATION_FOR_NEWER_VERSION').')';
      } else {
         $text = '';
      }
      if ( !empty ($text) ) {
         $html .= '<p class="disabled" style="margin-left:3px;">'.$text.'</p>'.LF;
      }
      $html .= '   </div>'.LF;

      // Files
      $formal_data = array();
      $files = $this->_getFilesForFormalData($item);
      if ( !empty($files) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('MATERIAL_FILES');
         $temp_array[] = implode(BRLF, $files);
         $formal_data[] = $temp_array;
      }
      if ( !empty($formal_data) ) {
         $html .= '<div style="margin: 0px; padding: 0px;">'.$this->_getFormalDataAsHTML($formal_data).'</div>';
         $html .= BRLF;
      }

      $html .= '<!-- END OF ANNOTATION ITEM -->'.LF.LF;
      return $html;
   }

   public function _getFilesForFormalData ($item) {
      $files = array();

      $file_list = $item->getFileList();
      if ( !$file_list->isEmpty() ) {
         $files = array();
         $file = $file_list->getFirst();
         while( $file ) {
            if ( !(isset($_GET['mode']) and $_GET['mode']=='print')
                 or ( isset($_GET['download'])
                      and $_GET['download'] == 'zip'
                    )
               ) {
               if ( ( !isset($_GET['download'])
                      or $_GET['download'] != 'zip'
                    )
                    and
                       in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))
                  ) {
                  $this->_with_slimbox = true;
                  // jQuery
                  //$file_string = '<a href="'.$file->getUrl().'" rel="lightbox[gallery'.$item->getItemID().']">'.
                  //$file_string = '<a href="'.$file->getUrl().'" rel="lightbox-gallery_'.$item->getItemID().'">'.
                  $displayname = $file->getDisplayName();
                  $filesize = $file->getFileSize();
                  $fileicon = $file->getFileIcon();
                  $file_string = '<a href="'.$file->getUrl().'" rel="lightbox-gallery'.$item->getItemID().'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)">'.

                  // jQuery
                  $file->getFileIcon().' '.($this->_text_as_html_short($this->_compareWithSearchText($file->getDisplayName()))).'</a> ('.$file->getFileSize().' KB)';
               } else {
                  $file_string = '<a href="'.$file->getUrl().'" target="blank">';
                  $file_string .= $file->getFileIcon().' '.($this->_text_as_html_short($this->_compareWithSearchText($file->getDisplayName()))).'</a> ('.$file->getFileSize().' KB)';
               }
            }else{
               $file_string = $file->getFileIcon().' '.($this->_text_as_html_short($this->_compareWithSearchText($file->getDisplayName())));
            }
            $files[] = $file_string;
            $file = $file_list->getNext();
         }
      }
      return $files;
   }

   function _getDiscussionFormAsHTML(){
        if(!(isset($_GET['mode']) and $_GET['mode'] == 'print')) {
         $html = '<!-- BEGIN OF ANNOTATION FORM VIEW -->'.LF.LF;
         $item = $this->getItem();
            $count = 1;
            $subitems = $item->getAnnotationList();
            if ( isset($subitems) and !empty($subitems) ){
               $count = $subitems->getCount();
               $count++;
            }
            $html .='</div>'.LF;
            $html .='</div>'.LF;
            if ($count != 1){
               $html .='<div class="sub_item_main" style="border-top: 1px solid #B0B0B0; margin:20px 5px 0px 5px; padding-top:20px; background-color:white;">'.LF;
            }else{
               $html .='<div class="sub_item_main" style="margin-top:0px; padding:5px; background-color:white;">'.LF;
            }
            $html .='<div style="width:100%;" >'.LF;
            $html .= '<a name="form"></a>'.LF;
            $params['ref_iid'] = $item->getItemID();
            $params['mode'] = 'annotate';
            $params['iid'] = 'NEW';

            $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(),'annotation', 'edit',$params).'" method="post" enctype="multipart/form-data" name="f">'.LF;
            $html .= '   <input type="hidden" name="version" value="'.$item->getVersionID().'"/>'.LF;
            $html .= '   <input type="hidden" name="ref_iid" value="'.$item->getItemID().'"/>'.LF;
            $html .= '<table style="width:100%; border-collapse:collapse; margin-bottom:0px; padding-bottom:0px;" summary="Layout">'.LF;
            $html .= '<tr>'.LF;
            $html .= '<td style="width:70px; vertical-align:middle;">'.LF;
            $html .= '<h3 class="subitemtitle">'.$this->_translator->getMessage('COMMON_SUBJECT').': </h3>';
            $html .= '</td>'.LF;
            $html .= '<td style="width:1%; vertical-align:middle;">'.LF;
            $html .= '<h3 class="subitemtitle">'.$count.'.&nbsp;</h3>';
            $html .= '</td>'.LF;
            $html .= '<td style="padding-top:5px; padding-bottom:5px; vertical-align:top; text-align:left;">'.LF;
            $html .= '<input name="title" style="width:98%; font-size:12pt; font-weight:bold; font-family: \'Trebuchet MS\',\'lucida grande\',tahoma,\'ms sans serif\',verdana,arial,sans-serif;" value="" maxlength="200" tabindex="8" type="text"/>';
            $html .= '</td>'.LF;
            $html .= '</tr>'.LF;
            $html .= '<tr>'.LF;
            $html .= '<td style="width:70px; padding-top:5px; vertical-align:top;">'.LF;
            $html .= $this->_translator->getMessage('COMMON_TEXT').': ';
            $html .= '</td>'.LF;
            $html .= '<td colspan="2">'.LF;
            $html .= '<div style=" margin:0px;padding:0px;">'.LF;
            $normal = '<textarea style="font-size:10pt; width:98%;" name="description" rows="10" tabindex="8"></textarea>';
            $text = '';
            global $c_html_textarea;
            $current_context = $this->_environment->getCurrentContextItem();
            $with_htmltextarea = $current_context->withHtmlTextArea();
            $html_status = $current_context->getHtmlTextAreaStatus();
            $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
            $current_browser_version = $this->_environment->getCurrentBrowserVersion();
            if ( !isset($c_html_textarea)
                 or !$c_html_textarea
                 or !$with_htmltextarea
               ) {
               $html .= $normal;
               $title = '&nbsp;'.$this->_translator->getMessage('COMMON_TEXT_FORMATING_HELP_FULL');
               $html .= '<div style="padding-top:5px;">';
               $text .= '<div class="bold" style="padding-bottom:5px;">'.$this->_translator->getMessage('HELP_COMMON_FORMAT_TITLE').':</div>';
               $text .= $this->_translator->getMessage('COMMON_TEXT_FORMATING_FORMAT_TEXT');
               $text .= '<div class="bold" style="padding-bottom:5px;">'.$this->_translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA').':</div>';
               $text .= $this->_translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA_TEXT');
               $html .='<img id="toggle'.$current_context->getItemID().'" src="images/more.gif"/>';
               $html .= $title;
               $html .= '<div id="creator_information'.$current_context->getItemID().'">'.LF;
               $html .= '<div style="padding:2px;">'.LF;
               $html .= '<div id="form_formatting_box" style="width:98%">'.LF;
               $html .= $text;
               $html .= '</div>'.LF;
               $html .= '</div>'.LF;
               $html .= '</div>'.LF;
               $html .= '</div>'.LF;
            } elseif ( ($current_browser != 'msie'
                    and $current_browser != 'firefox'
                    and $current_browser != 'netscape'
                    and $current_browser != 'mozilla'
                    and $current_browser != 'camino'
                    and $current_browser != 'opera'
                    and $current_browser != 'safari')
               ) {
               $html .= $normal;
               $title = '&nbsp;'.$this->_translator->getMessage('COMMON_TEXT_FORMATING_HELP_FULL');
               $html .= '<div style="padding-top:5px;">';
               $text .= '<div class="bold" style="padding-bottom:5px;">'.$this->_translator->getMessage('HELP_COMMON_FORMAT_TITLE').':</div>';
               $text .= $this->_translator->getMessage('COMMON_TEXT_FORMATING_FORMAT_TEXT');
               $text .= '<div class="bold" style="padding-bottom:5px;">'.$this->_translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA').':</div>';
               $text .= $this->_translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA_TEXT');
               $html .='<img id="toggle'.$current_context->getItemID().'" src="images/more.gif"/>';
               $html .= $title;
               $html .= '<div id="creator_information'.$current_context->getItemID().'">'.LF;
               $html .= '<div style="padding:2px;">'.LF;
               $html .= '<div id="form_formatting_box" style="width:98%">'.LF;
               $html .= $text;
               $html .= '</div>'.LF;
               $html .= '</div>'.LF;
               $html .= '</div>'.LF;
               $html .= '</div>'.LF;
            } else {
               $session = $this->_environment->getSessionItem();
                if ($session->issetValue('javascript')) {
                  $javascript = $session->getValue('javascript');
                  if ($javascript == 1) {
                     include_once('classes/cs_html_textarea.php');
                     $html_area = new cs_html_textarea();
                     $html .= $html_area->getAsHTML( 'description',
                                              '',
                                              20,
                                              $html_status,
                                              '',
                                              '',
                                              false
                                            );
                     $title = '&nbsp;'.$this->_translator->getMessage('COMMON_TEXT_FORMATING_HELP_SHORT');
                     $html .= '<div style="padding-top:0px;">';
                     $text .= '<div class="bold" style="padding-bottom:5px;">'.$this->_translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA').':</div>';
                     $text .= $this->_translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA_TEXT');
                     $html .='<img id="toggle'.$current_context->getItemID().'" src="images/more.gif"/>';
                     $html .= $title;
                     $html .= '<div id="creator_information'.$current_context->getItemID().'">'.LF;
                     $html .= '<div style="padding:2px;">'.LF;
                     $html .= '<div id="form_formatting_box" style="width:98%">'.LF;
                     $html .= $text;
                     $html .= '</div>'.LF;
                     $html .= '</div>'.LF;
                     $html .= '</div>'.LF;
                     $html .= '</div>'.BRLF;
                  } else {
                     $html .= $normal;
                     $title = '&nbsp;'.$this->_translator->getMessage('COMMON_TEXT_FORMATING_HELP_FULL');
                     $html .= '<div style="padding-top:5px;">';
                     $text .= '<div class="bold" style="padding-bottom:5px;">'.$this->_translator->getMessage('HELP_COMMON_FORMAT_TITLE').':</div>';
                     $text .= $this->_translator->getMessage('COMMON_TEXT_FORMATING_FORMAT_TEXT');
                     $text .= '<div class="bold" style="padding-bottom:5px;">'.$this->_translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA').':</div>';
                     $text .= $this->_translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA_TEXT');
                     $html .='<img id="toggle'.$current_context->getItemID().'" src="images/more.gif"/>';
                     $html .= $title;
                     $html .= '<div id="creator_information'.$current_context->getItemID().'">'.LF;
                     $html .= '<div style="padding:2px;">'.LF;
                     $html .= '<div id="form_formatting_box" style="width:98%">'.LF;
                     $html .= $text;
                     $html .= '</div>'.LF;
                     $html .= '</div>'.LF;
                     $html .= '</div>'.LF;
                     $html .= '</div>'.LF;
                  }
               } else {
                  $html .= $normal;
                  $title = '&nbsp;'.$this->_translator->getMessage('COMMON_TEXT_FORMATING_HELP_FULL');
                  $html .= '<div style="padding-top:5px;">';
                  $text .= '<div class="bold" style="padding-bottom:5px;">'.$this->_translator->getMessage('HELP_COMMON_FORMAT_TITLE').':</div>';
                  $text .= $this->_translator->getMessage('COMMON_TEXT_FORMATING_FORMAT_TEXT');
                  $text .= '<div class="bold" style="padding-bottom:5px;">'.$this->_translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA').':</div>';
                  $text .= $this->_translator->getMessage('COMMON_TEXT_INCLUDING_MEDIA_TEXT');
                  $html .='<img id="toggle'.$current_context->getItemID().'" src="images/more.gif"/>';
                  $html .= $title;
                  $html .= '<div id="creator_information'.$current_context->getItemID().'">'.LF;
                  $html .= '<div style="padding:2px;">'.LF;
                  $html .= '<div id="form_formatting_box" style="width:98%">'.LF;
                  $html .= $text;
                  $html .= '</div>'.LF;
                  $html .= '</div>'.LF;
                  $html .= '</div>'.LF;
                  $html .= '</div>'.LF;
               }
            }
            $html .= '</div>';
            $html .= '</td>'.LF;
            $html .= '</tr>'.LF;
            $html .= '<tr>'.LF;
            $html .= '<td>&nbsp;'.LF;
            $html .= '</td>'.LF;
            $html .= '<td colspan="2" style="padding-top:10px; vertical-align:top; white-space:nowrap;">'.LF;
            $html .= '<input name="option" value="'.$this->_translator->getMessage('ANNOTATION_ADD_NEW_BUTTON').'" tabindex="8" type="submit"/>';
            $current_user = $this->_environment->getCurrentUser();
            if ( $current_user->isAutoSaveOn() ) {
               $html .= '<span class="formcounter">'.LF;
               global $c_autosave_mode;
               if ( $c_autosave_mode == 1 ) {
                  $currTime = time();
                  global $c_autosave_limit;
                  $sessEnds = $currTime + ($c_autosave_limit * 60);
                  $sessEnds = date("H:i", $sessEnds);
                  $html .= '&nbsp;'.$this->_translator->getMessage('COMMON_SAVE_AT_TIME').' '.$sessEnds.LF;
               } elseif ( $c_autosave_mode == 2 ) {
                  $html .= '&nbsp;'.$this->_translator->getMessage('COMMON_SAVE_AT_TIME').' <input type="text" size="5" name="timerField" value="..." class="formcounterfield" />'.LF;
               }
               $html .= '</span>'.LF;
            }
            $html .= '</td>'.LF;
            $html .= '</tr>'.LF;
            $html .= '</table>'.BRLF;
            $html .= '</form>';

            $html .='<script type="text/javascript">initTextFormatingInformation("'.$current_context->getItemID().'",false)</script>';
            if ( $current_user->isAutoSaveOn() ) {
               $html .= '   <script type="text/javascript">'.LF;
               $html .= '      <!--'.LF;
               $html .= '         var breakCrit = "'.$this->_translator->getMessage('ANNOTATION_ADD_NEW_BUTTON').'"'.';'.LF;
               $html .= '         startclock();'.LF;
               $html .= '      -->'.LF;
               $html .= '   </script>'.LF;
            }
         $html .='</div>'.LF;

         $html .= '<!-- END OF ANNOTATION FORM VIEW -->'.LF.LF;
         return $html;
        }
   }

   function _getAnnotationFormAsHTML(){
        if(!(isset($_GET['mode']) and $_GET['mode'] == 'print')) {
         $html = '<!-- BEGIN OF ANNOTATION FORM VIEW -->'.LF.LF;
         $item = $this->getItem();
         $count = 1;
         $subitems = $item->getAnnotationList();
         if ( isset($subitems) and !empty($subitems) ){
            $count = $subitems->getCount();
            $count++;
         }
         $html .='</div>'.LF;
         $html .='</div>'.LF;
         if ($count != 1){
            $html .='<div class="sub_item_main" style="border-top: 1px solid #B0B0B0; margin:20px 5px 0px 5px; padding-top:20px; background-color:white;">'.LF;
         }else{
            $html .='<div class="sub_item_main" style="margin-top:0px; padding:5px; background-color:white;">'.LF;
         }
         $html .='<div style="width:100%;" >'.LF;
         $html .= '<a name="form"></a>'.LF;
         $params['ref_iid'] = $item->getItemID();
         $params['mode'] = 'annotate';
         $params['iid'] = 'NEW';

         $class_factory = $this->_environment->getClassFactory();
         $class_params = array();
         $class_params['environment'] = $this->_environment;
         $form = $class_factory->getClass(ANNOTATION_FORM,$class_params);
         $form->setDetailMode($count);
         $form->setRefId($item->getItemID());
         unset($class_params);
         $form->prepareForm();
         $form->loadValues();
         $class_params = array();
         $class_params['environment'] = $this->_environment;
         $class_params['with_modifying_actions'] = true;
         $form_view = $class_factory->getClass(FORM_DETAIL_VIEW,$class_params);
         unset($class_params);
         $form_view->setAction(curl($this->_environment->getCurrentContextID(),'annotation','edit',$params));
         $form_view->setForm($form);
         $html .= $form_view->asHTML();

         $html .= '</div>'.LF;

         $html .= '<!-- END OF ANNOTATION FORM VIEW -->'.LF.LF;
         return $html;
        }
   }

    /** set the value of the search box
    * this method sets the search value of the list
    *
    * @param string  $this->_search_text
    */
    function setSearchText ($search_text){
       $this->_search_text = $search_text;
       $literal_array = array();
       $search_array = array();

       //find all occurances of quoted text and store them in an array
       preg_match_all('~("(.+?)")~u',$search_text,$literal_array);
       //delete this occurances from the original string
       $search_text = preg_replace('~("(.+?)")~u','',$search_text);

       $search_text = preg_replace('~-(\w+)~u','',$search_text);

       //clean up the resulting array from quots
       $literal_array = str_replace('"','',$literal_array[2]);
       //clean up rest of $limit and get an array with entrys
       $search_text = str_replace('  ',' ',$search_text);
       $search_text = trim($search_text);
       $split_array = explode(' ',$search_text);

       //check which array contains search limits and act accordingly
       if ($split_array[0] != '' AND count($literal_array) > 0) {
          $search_array = array_merge($split_array,$literal_array);
       } else {
          if ($split_array[0] != '') {
             $search_array = $split_array;
          } else {
             $search_array = $literal_array;
          }
       }

       $this->_search_array = $search_array;

       // now handle äöüÄÖÜß
       if ( !empty($search_array) ) {
          foreach ($search_array as $search_word) {
             if ( $search_word != htmlentities($search_word,ENT_COMPAT,'UTF-8') ) {
                $this->_search_array[] = htmlentities($search_word,ENT_COMPAT,'UTF-8');
             }
          }
       }
    }

   /** compare the item text and the search criteria
    * this method returns the item text bold if it fits to the search criteria
    *
    * @return string value
    */
   function _compareWithSearchText($value, $bold = true) {
      if ( !empty($this->_search_array) ) {
         foreach ($this->_search_array as $search_text) {
            if ( mb_stristr($value,$search_text) ) {
               // CSS Klasse erstellen für Farbmarkierung
               include_once('functions/misc_functions.php');
               if ( getMarkerColor() == 'green') {
                  $replace = '(:mainsearch_text_green:)$0(:mainsearch_text_green_end:)';
               }
               else if (getMarkerColor() == 'yellow') {
                  $replace = '(:mainsearch_text_yellow:)$0(:mainsearch_text_yellow_end:)';
               }
               // $replace = '(:mainsearch_text:)$0(:mainsearch_text_end:)';
               // $replace = '*$0*';
               if ( !$bold ) {
                  if ( getMarkerColor() == 'green') {
                    $replace = '(:mainsearch_text_green:)$0(:mainsearch_text_green_end:)';
                }
                else if (getMarkerColor() == 'yellow') {
                    $replace = '(:mainsearch_text_yellow:)$0(:mainsearch_text_yellow_end:)';
                }

                  // $replace = '(:search:)$0(:search_end:)';
               }
               if ( stristr($value,'<!-- KFC TEXT') ) {
                   if(getMarkerColor() == 'green'){
                      $replace = '<span class="searched_text_green">$0</span>';
                   }
                   else if(getMarkerColor() == 'yellow'){
                      $replace = '<span class="searched_text_yellow">$0</span>';
                   }

                  // $replace = '<span class="bold">$0</span>';
                  if ( !$bold ) {
                    $replace = '<span class="italic" style="font-style: italic;">$0</span>';
                  }
               }
               $value = preg_replace('~'.preg_quote($search_text,'/').'~iu',$replace,$value);
            }
         }
      }
      return $value;
   }

   function _getEditAction ( $item, $user, $module = '' ) {
      $html  = '';
      if ( $item->mayEdit($user) and $this->_with_modifying_actions ) {
         if ( empty($module) ) {
            $module = $this->_environment->getCurrentModule();
         }
         $params = array();
         $params['iid'] = $item->getItemID();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/edit.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/edit.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                          $module,
                                          'edit',
                                          $params,
                                          $image,
                                          $this->_translator->getMessage('COMMON_EDIT_ITEM')).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/edit_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/edit_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_EDIT_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
      }
      return $html;
   }

   function _getDeleteAction ( $item, $user ) {
      $html  = '';
      if ( $item->mayEdit($user)
           and $this->_with_modifying_actions
           and ( !$item->isA(CS_LABEL_TYPE)
                 or !$item->isSystemLabel()
               )
         ) {
         $params = $this->_environment->getCurrentParameterArray();
         $params['action'] = 'delete';
         if($item->getItemType() == CS_DATE_TYPE){
            if($item->getRecurrenceId() != '' and $item->getRecurrenceId() != 0){
               $params['recurrence_id'] = $item->getRecurrenceId();
            }
         }
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                          $this->_environment->getCurrentModule(),
                              'detail',
                          $params,
                          $image,
                          $this->_translator->getMessage('COMMON_DELETE_ITEM').LF,
                              '',
                              '',
                              '',
                              '',
                              '',
                              '',
                              '',
                              'delete_confirm_entry');
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_DELETE_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
      }
      return $html;
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
          <script type='text/javascript'>
          <!--
              var headline = '" . $this->_translator->getMessage("COMMON_DELETE_BOX_TITLE") . "';
              var text1 = '" . $this->_translator->getMessage("COMMON_DELETE_BOX_DESCRIPTION") . "';
              var text2 = '" . $text2 . "';
              var button_delete = '" . $this->_translator->getMessage("COMMON_DELETE_BUTTON") . "';
              var button_cancel = '" . $this->_translator->getMessage("COMMON_CANCEL_BUTTON") . "';
          -->
          </script>
      ";

      return $return;
   }

   function _getWikiAction ( $item, $user, $context ) {
      $html  = '';
      if ( $item->mayEdit($user)
           and $context->isWikiActive()
           and $this->_with_modifying_actions
           and ( !$item->isA(CS_DISCUSSION_TYPE)
                 or $item->getDiscussionType() == 'simple'
               )
         ) {
         $params = array();
         $params['iid'] = $item->getItemID();
         $params['export_to_wiki'] = 'true';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/export_wiki.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_EXPORT_TO_WIKI').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/export_wiki.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_EXPORT_TO_WIKI').'"/>';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                   $this->_environment->getCurrentModule(),
                                   'detail',
                                   $params,
                                   $image,
                                   $this->_translator->getMessage('ITEM_EXPORT_TO_WIKI')).LF;
         unset($params);
      } elseif($context->isWikiActive()) {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/export_wiki_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ITEM_EXPORT_TO_WIKI').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/export_wiki_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ITEM_EXPORT_TO_WIKI').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('ITEM_EXPORT_TO_WIKI')).' "class="disabled">'.$image.'</a>'.LF;
      }
      return $html;
   }


   function _getWordpressAction ( $item, $user, $context ) {
      $html  = '';
      $wordpress_manager = $this->_environment->getWordPressManager();
      if ( $item->mayEdit($user)
           and $context->isWordpressActive()
           and $this->_with_modifying_actions
           and ( !$item->isA(CS_DISCUSSION_TYPE)
                 or $item->getDiscussionType() == 'simple'
               )
           and $wordpress_manager->isUserAllowedToExportItem($context->getWordpressId(),$user->getUserID())
         ) {
         $params = array();
         $params['iid'] = $item->getItemID();
         $params['export_to_wordpress'] = 'true';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/export_wordpress.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_EXPORT_TO_WORDPRESS').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/export_wordpress.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_EXPORT_TO_WORDPRESS').'"/>';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                              $this->_environment->getCurrentModule(),
                              'detail',
                              $params,
                              $image,
                              $this->_translator->getMessage('ITEM_EXPORT_TO_WORDPRESS')).LF;
         unset($params);
      } elseif($context->isWordpressActive()) {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/export_wordpress_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ITEM_EXPORT_TO_WORDPRESS').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/export_wordpress_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ITEM_EXPORT_TO_WORDPRESS').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('ITEM_EXPORT_TO_WORDPRESS')).' "class="disabled">'.$image.'</a>'.LF;
      }
      unset($wordpress_manager);
      return $html;
   }

   function _getWorkflowReadAction($item, $user, $context){
      $html = '';
      if($context->withWorkflow() and $context->withWorkflowReader() == '1' and $user->getUserID() != 'root'){
         $params = array();
         $params['iid'] = $item->getItemID();
         $workflow_link_text = '';
         if(!$item->isReadByUser($user)){
            $params['workflow_read'] = 'true';
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/workflow_read.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ITEM_WORKFLOW_MARK_READ').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/workflow_read.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ITEM_WORKFLOW_MARK_READ').'"/>';
            }
            $workflow_link_text = $this->_translator->getMessage('ITEM_WORKFLOW_MARK_READ');
         } else {
            $params['workflow_not_read'] = 'true';
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/workflow_not_read.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ITEM_WORKFLOW_MARK_NOT_READ').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/workflow_not_read.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ITEM_WORKFLOW_MARK_NOT_READ').'"/>';
            }
            $workflow_link_text = $this->_translator->getMessage('ITEM_WORKFLOW_MARK_NOT_READ');
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                              $this->_environment->getCurrentModule(),
                              'detail',
                              $params,
                              $image,
                              $workflow_link_text).LF;
         unset($params);
      }
      return $html;
   }
   
   function _getPrintAction ( $item, $user ) {
      $html  = '';
      $params = $this->_environment->getCurrentParameterArray();
      $params['mode']='print';
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/print.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_LIST_PRINTVIEW').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/print.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_LIST_PRINTVIEW').'"/>';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'detail',
                                    $params,
                                    $image,
                                    $this->_translator->getMessage('COMMON_LIST_PRINTVIEW'),
                                    '_blank'
                         ).LF;
      unset($params['mode']);
      return $html;
   }

   function _getMailAction ( $item, $user, $module = '' ) {
      $html  = '';
      if ( empty($module) ) {
         $module = 'rubric';
         $text = $this->_translator->getMessage('COMMON_EMAIL_TO');
      } else {
         $text = $this->_translator->getMessage('COMMON_EMAIL_TO');
      }
      if ( $user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         $params['iid'] = $item->getItemID();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/mail.gif" style="vertical-align:bottom;" alt="'.$text.'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/mail.png" style="vertical-align:bottom;" alt="'.$text.'"/>';
         }
         $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                               $module,
                               'mail',
                               $params,
                               $image,
                               $text).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/mail_grey.gif" style="vertical-align:bottom;" alt="'.$text.'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/mail_grey.png" style="vertical-align:bottom;" alt="'.$text.'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$text).' "class="disabled">'.$image.'</a>'.LF;
      }
      return $html;
   }

   function _getDownloadAction ( $item, $user ) {
      $html  = '';

      $params = $this->_environment->getCurrentParameterArray();
      $params['download']='zip';
      $params['mode']='print';
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/save.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DOWNLOAD').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/save.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DOWNLOAD').'"/>';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'detail',
                                    $params,
                                    $image,
                                    $this->_translator->getMessage('COMMON_DOWNLOAD')).LF;
      unset($params['download']);
      unset($params['mode']);

      return $html;
   }

   function _getCopyAction ( $item, $user ) {
      $html  = '';
      $params = $this->_environment->getCurrentParameterArray();
      if ( $user->isUser() and !in_array($item->getItemID(), $this->_getClipboardIdArray()) ) {
         $params = array();
         $params['iid'] = $item->getItemID();
         $params['add_to_'.$this->_environment->getCurrentModule().'_clipboard'] = $item->getItemID();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/copy.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_ITEM_COPY_TO_CLIPBOARD').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/copy.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_ITEM_COPY_TO_CLIPBOARD').'"/>';
         }
         $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'detail',
                                    $params,
                                    $image,
                                    $this->_translator->getMessage('COMMON_ITEM_COPY_TO_CLIPBOARD')).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/copy_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_ITEM_COPY_TO_CLIPBOARD').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/copy_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_ITEM_COPY_TO_CLIPBOARD').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_ITEM_COPY_TO_CLIPBOARD')).' "class="disabled">'.$image.'</a>'.LF;
      }
      return $html;
   }

   function _getNewAction ( $item, $user ) {
      $html  = '';
      if ( $user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         $params['iid'] = 'NEW';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_NEW_ITEM').'" id="new_icon"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_NEW_ITEM').'" id="new_icon"/>';
         }
         $html .= '&nbsp;&nbsp;&nbsp;'.ahref_curl(  $this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'edit',
                                    $params,
                                    $image,
                                    $this->_translator->getMessage('COMMON_NEW_ITEM')).LF;
         unset($params);
      } else {
         $html .= $this->_getNewActionDisabled();
      }
      return $html;
   }

   function _getNewActionDisabled () {
      return $this->_getActionDisabled('new',$this->_translator->getMessage('COMMON_NEW_ITEM'));
   }

   function _getActionDisabled ( $file_name, $text ) {
      $html = '';
      if ( $this->_environment->getCurrentBrowser() == 'MSIE'
           and mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6'
         ) {
         $image = '<img src="images/commsyicons_msie6/22x22/'.$file_name.'_grey.gif" style="vertical-align:bottom;" alt="'.$text.'" id="new_icon_disabled"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/'.$file_name.'_grey.png" style="vertical-align:bottom;" alt="'.$text.'" id="new_icon_disabled"/>';
      }
      $html .= '&nbsp;&nbsp;&nbsp;<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$text).' "class="disabled">'.$image.'</a>'.LF;
      return $html;
   }
}
?>