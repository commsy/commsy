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

$this->includeClass(INDEX_VIEW);
//include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: announcement
 */
class cs_entry_index_view extends cs_index_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the page
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */

   var $_sellist = '';
   var $_selbuzzword = '';
   var $_selmatrix = '';
   var $_seltag = '';

   var $_dropdown_image_array = array();

   var $_dropdown_message_array = array();

   var $_dropdown_rubrics_new = array();

   var $_selected_tag_array = array();
   
   public function __construct ($params) {
      cs_index_view::__construct($params);
      $this->setTitle($this->_translator->getMessage('COMMON_ENTRIES'));
      $this->setActionTitle($this->_translator->getMessage('COMMON_ENTRY'));
      $this->_colspan = '4';

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

      $context_item = $this->_environment->getCurrentContextItem();
      $home_conf = $context_item->getHomeConf();
      $home_conf_array = explode(',',$home_conf);

      if(isset($_GET['mod'])){
         $dropdown_mod = $_GET['mod'];
      } elseif(isset($_POST['mod'])){
         $dropdown_mod = $_POST['mod'];
      } else {
         $dropdown_mod = '';
      }

      #foreach($home_conf_array as $rubric){
      #   $temp_rubric_array = explode('_',$rubric);
      #   $temp_rubric = $temp_rubric_array[0];
      #   if($temp_rubric == 'announcement'){ #and $dropdown_mod != 'announcement'){
      #      $this->_dropdown_rubrics_new[] = CS_ANNOUNCEMENT_TYPE;
      #   } elseif($temp_rubric == 'date'){ # and $dropdown_mod != 'date'){
            $this->_dropdown_rubrics_new[] = CS_DATE_TYPE;
      #   }  elseif($temp_rubric == 'material'){ # and $dropdown_mod != 'material'){
            $this->_dropdown_rubrics_new[] = CS_MATERIAL_TYPE;
      #   }  elseif($temp_rubric == 'discussion'){ # and $dropdown_mod != 'discussion'){
            $this->_dropdown_rubrics_new[] = CS_DISCUSSION_TYPE;
      #   }  elseif($temp_rubric == 'group'){ # and $dropdown_mod != 'group'){
      #      $this->_dropdown_rubrics_new[] = CS_GROUP_TYPE;
      #   }  elseif($temp_rubric == 'todo'){ # and $dropdown_mod != 'todo'){
            $this->_dropdown_rubrics_new[] = CS_TODO_TYPE;
      #   }  elseif($temp_rubric == 'topic'){ # and $dropdown_mod != 'topic'){
      #      $this->_dropdown_rubrics_new[] = CS_TOPIC_TYPE;
      #   }  elseif($temp_rubric == 'institution'){ # and $dropdown_mod != 'topic'){
      #      $this->_dropdown_rubrics_new[] = CS_INSTITUTION_TYPE;
      #   }
      #}
   }

   function setSelectedMyList($limit){
    $this->_sellist = $limit;
   }

   function setSelectedBuzzword($limit){
    $this->_selbuzzword = $limit;
   }

   function setSelectedMatrix($limit){
    $this->_selmatrix = $limit;
   }

   function setSelectedTag($limit){
    $this->_seltag = $limit;
   }

    function setList ($list) {
       $this->_list = $list;
    }

    function getSearchText (){
       if (empty($this->_search_text)){
        $this->_search_text = $this->_translator->getMessage('COMMON_SEARCH_IN_ENTRIES');
       }
       return $this->_search_text;
    }

   function setInterval($interval){
      $this->_interval = $interval;
   }

   function setPos($pos){
      $this->_pos = $pos;
   }

   function setMaxPos($max_pos){
      $this->_max_pos = $max_pos;
   }

   function setBrowsePrev($browse_prev){
      $this->_browse_prev = $browse_prev;
   }

   function setBrowseNext($browse_next){
      $this->_browse_next = $browse_next;
   }

   function _getSearchBoxAsHTML(){
      $html = '<div class="portlet" id="my_search_box">'.LF;
      $html .= '<div class="portlet-header" style="cursor:default;">'.LF;
      $html .= $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_SEARCH_BOX').LF;
      #$html .= '<div style="float:right;"><a name="myentries_remove" style="cursor:pointer;"><img src="images/commsyicons/16x16/delete.png" /></a></div>';
      $html .= '</div>'.LF;
      $html .= '<div class="portlet-content">'.LF;
      $html .= '<div id="search_left" style="width:50%; float:left;">'.LF;
      $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), 'entry', 'index','').'" method="get" name="form">'.LF;
      $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
      $html .= '   <input type="hidden" name="mod" value="entry"/>'.LF;
      $html .= '   <input type="hidden" name="fct" value="index"/>'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      if (isset($params['selbuzzword']) and !empty($params['selbuzzword'])){
         $html .= '   <input type="hidden" name="sellbuzzword" value="'.$params['selbuzzword'].'"/>'.LF;
      }
      if (isset($params['sellist']) and !empty($params['sellist'])){
         $html .= '   <input type="hidden" name="sellist" value="'.$params['sellist'].'"/>'.LF;
      }
      $html .= '<input id="searchtext" onclick="javascript:resetSearchText(\'searchtext\');" style="font-size:10pt; margin-bottom:0px;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/>';
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $html .= '<input type="image" src="images/commsyicons_msie6/22x22/search.gif" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
      } else {
         $html .= '<input type="image" src="images/commsyicons/22x22/search.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
      }
      $html .='</form>'.LF;
      $html .= '</div>'.LF;
      $html .= '<div id="search_right" style="width:50%; float:right; text-align:right; margin-bottom:3px;">'.LF;
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image_new_material = '<img src="images/commsyicons_msie6/22x22/material.gif" style="vertical-align:bottom;"/>';
         $image_new_date = '<img src="images/commsyicons_msie6/22x22/date.gif" style="vertical-align:bottom;"/>';
         $image_new_discussion = '<img src="images/commsyicons_msie6/22x22/discussion.gif" style="vertical-align:bottom;"/>';
         $image_new_todo = '<img src="images/commsyicons_msie6/22x22/todo.gif" style="vertical-align:bottom;"/>';
      } else {
         $image_new_material = '<img src="images/commsyicons/22x22/material.png" style="vertical-align:bottom;"/>';
         $image_new_date = '<img src="images/commsyicons/22x22/date.png" style="vertical-align:bottom;"/>';
         $image_new_discussion = '<img src="images/commsyicons/22x22/discussion.png" style="vertical-align:bottom;"/>';
         $image_new_todo = '<img src="images/commsyicons/22x22/todo.png" style="vertical-align:bottom;"/>';
      }

      $html .= ahref_curl($this->_environment->getCurrentContextID(),'material','edit',array('iid' => 'NEW'),$image_new_material, $this->_translator->getMessage('COMMON_ENTER_NEW_MATERIAL'));
      $html .= '&nbsp;&nbsp;&nbsp;';
      $html .= ahref_curl($this->_environment->getCurrentContextID(),'date','edit',array('iid' => 'NEW'),$image_new_date, $this->_translator->getMessage('COMMON_ENTER_NEW_DATE'));
      $html .= '&nbsp;&nbsp;&nbsp;';
      $html .= ahref_curl($this->_environment->getCurrentContextID(),'discussion','edit',array('iid' => 'NEW'),$image_new_discussion, $this->_translator->getMessage('COMMON_ENTER_NEW_DISCUSSION'));
      $html .= '&nbsp;&nbsp;&nbsp;';
      $html .= ahref_curl($this->_environment->getCurrentContextID(),'todo','edit',array('iid' => 'NEW'),$image_new_todo, $this->_translator->getMessage('COMMON_ENTER_NEW_TODO'));
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<script type="text/javascript">'.LF;
      $html .= '<!--'.LF;
      $html .= 'var reset_search_text_message = "'.$this->_text_as_form($this->getSearchText()).'"'.LF;
      $html .= '-->'.LF;
      $html .= '</script>'.LF;
      return $html;
   }

   function _getMylistsBoxAsHTML(){
      $params = $this->_environment->getCurrentParameterArray();
      $font_style = '';
      if (!empty($this->_sellist) and $this->_sellist == 'new'){
        $font_style = ' font-weight:bold;';
      }
      $current_user = $this->_environment->getCurrentUserItem();
      $mylist_manager = $this->_environment->getLabelManager();
      $mylist_manager->resetLimits();
      $mylist_manager->setContextLimit($this->_environment->getCurrentContextID());
      $mylist_manager->setTypeLimit('mylist');
      $mylist_manager->setGetCountLinks();
      $mylist_manager->select();
      $mylist_list = $mylist_manager->get();
      $html = '<div class="portlet" id="my_list_box">'.LF;
      $html .= '<div class="portlet-header">'.LF;
      $html .= $this->_translator->getMessage('PRIVATEROOM_MY_LISTS_BOX').LF;
      $html .= '<div style="float:right;"><a name="myentries_remove" style="cursor:pointer;"><img src="images/commsyicons/16x16/delete.png" /></a></div>';
      $html .= '</div>'.LF;
      $html .= '<div class="portlet-content">'.LF;
      $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), 'entry', 'index','').'" method="post" name="mylist_form">'.LF;
      $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
      $html .= '   <input type="hidden" name="mod" value="entry"/>'.LF;
      $html .= '   <input type="hidden" name="fct" value="index"/>'.LF;
      $html .= '   <input id="new_list" onclick="javascript:resetMyListText(\'new_list\');" style="width:160px; font-size:10pt; margin-bottom:0px;" name="new_list" type="text" size="20" value="'.$this->_text_as_form($this->_translator->getMessage('PRIVATEROOM_MY_LISTS_BOX_NEW_ENTRY')).'"/>';
      $html .= '   <input name="option" value="'.$this->_text_as_form($this->_translator->getMessage('PRIVATEROOM_MY_LISTS_BOX_NEW_ENTRY_BUTTON')).'" tabindex="23" style="width: 150px; font-size: 10pt;" type="submit"/>'.LF;
      $html .='</form>'.LF;

      $html .= '<div style="margin:10px 0px 0px 0px; padding:0px;">'.LF;
/*      $html .= '<div style="display:block; margin:0px;'.$font_style.'" class="even">'.LF;
      $html .= '<div style="float:right; padding-top:2px;">'.LF;
      $html .= '<img src="images/commsyicons/16x16/copy_grey.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('ENTRY_COPY_MYLIST').'"/>'.LF;
      $html .= '<img src="images/commsyicons/16x16/delete_grey.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('ENTRY_DELETE_MYLIST').'"/>'.LF;
      $html .= '</div>'.LF;
      $html .=' <p class="droppable_list_newest_entries">'.LF;
      $params['sellist'] = 'new';
      $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_ENTRY_TYPE,
                                       'index',
                                       $params,
                                       $this->_translator->getMessage('COMMON_NEWEST_ENTRIES'),
                                       $this->_translator->getMessage('COMMON_NEWEST_ENTRIES')).LF;
      $html .= '</p></div>'.LF;*/

      $mylist_item = $mylist_list->getFirst();
      $counter = 0;
      while($mylist_item){
         $params = $this->_environment->getCurrentParameterArray();
         if ($counter%2 == 0){
            $style='class="even"';
         }else{
            $style='class="odd"';
         }
         if ($this->_sellist == $mylist_item->getItemID()){
            $font_style = ' font-weight:bold;';
         }else{
            $font_style = '';
         }
         $count = $mylist_item->getCountLinks();
         $html .= '<div '.$style.' style="display:block; margin:0px;'.$font_style.'" >'.LF;
         $html .= '<div id="mylist_action_'.$mylist_item->getItemID().'" style="float:right; padding-top:2px;">'.LF;

/*
         $html .= '<a href="#"><img src="images/commsyicons/16x16/new_home.png" id="new_icon_'.$mylist_item->getItemID().'" style="vertical-align:top;" alt=""/></a>';
*/
/*       
         $image = '<img src="images/commsyicons/16x16/copy.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('ENTRY_COPY_MYLIST').'"/>'.LF;
         $params['copy_list'] = $mylist_item->getItemID();
         $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_ENTRY_TYPE,
                                       'index',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('ENTRY_COPY_MYLIST')).LF;
         unset($params['copy_list']);
*/
         $params['delete_list'] = $mylist_item->getItemID();
         $image = '<img src="images/commsyicons/16x16/delete.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('ENTRY_DELETE_MYLIST').'"/>'.LF;
         $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_ENTRY_TYPE,
                                       'index',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('ENTRY_DELETE_MYLIST')).LF;
         unset($params['delete_list']);

         $html .= '</div>'.LF;

         $html .= ' <p class="droppable_list" id="mylist_'.$mylist_item->getItemID().'">'.LF;
         $params['sellist'] = $mylist_item->getItemID();
         $params['pos'] = 0;
         $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_ENTRY_TYPE,
                                       'index',
                                       $params,
                                       $mylist_item->getName(),
                                       $mylist_item->getName()).LF;
         $html .= '(<span id="mylist_count_'.$mylist_item->getItemID().'">'.$count.'</span>)'.LF;
         $html .= '</p></div>'.LF;
         $counter++;
         $mylist_item = 	$mylist_list->getNext();
      }
      $html .= $this->_initDropDownMenuForList($mylist_list);
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      // Preferences link
#      $html .= '<div class="portlet-turn portlet-front" style="float:right;">'.LF;
#      $html .= '<a class="preferences_flip" name="portlet_preferences" style="cursor:pointer;"><img src="images/config_home.png" /></a>'.LF;
#      $html .= '&nbsp;</div>'.LF;




      $html .= '</div>'.LF;
      return $html;
   }

/*   function _getCreateNewEntryBoxAsHTML(){
      $html = '<div class="portlet">'.LF;
      $html .= '<div class="portlet-header">'.LF;
      $html .= $this->_translator->getMessage('PRIVATEROOM_CREATE_NEW_ENTRY_BOX').LF;
      $html .= '</div>'.LF;
      $html .= '<div class="portlet-content">'.LF;

      $params = array();
      $html .= '<div style="vertical-align:bottom; height:25px;">'.LF;
      $image = '<img src="images/commsyicons/22x22/material.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_ENTER_NEW_MATERIAL').'"/>'.LF;
      $params = array();
      $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_MATERIAL_TYPE,
                                       'edit',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('COMMON_ENTER_NEW_MATERIAL')).LF;
      $link = $this->_translator->getMessage('COMMON_ENTER_NEW_MATERIAL').LF;
      $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_MATERIAL_TYPE,
                                       'edit',
                                       $params,
                                       $link,
                                       $this->_translator->getMessage('COMMON_ENTER_NEW_MATERIAL')).LF;
      $html .= '</div>'.LF;

      $html .= '<div style="vertical-align:bottom; height:25px;">'.LF;
       $image = '<img src="images/commsyicons/22x22/date.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_ENTER_NEW_MATERIAL').'"/>'.LF;
      $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_DATE_TYPE,
                                       'edit',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('COMMON_ENTER_NEW_DATE')).LF;
      $link = $this->_translator->getMessage('COMMON_ENTER_NEW_DATE').LF;
      $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_DATE_TYPE,
                                       'edit',
                                       $params,
                                       $link,
                                       $this->_translator->getMessage('COMMON_ENTER_NEW_DATE')).LF;
      $html .= '</div>'.LF;

      $html .= '<div style="vertical-align:bottom; height:25px;">'.LF;
      $image = '<img src="images/commsyicons/22x22/todo.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_ENTER_NEW_MATERIAL').'"/>'.LF;
      $params = array();
      $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_TODO_TYPE,
                                       'edit',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('COMMON_ENTER_NEW_TODO')).LF;
      $link = $this->_translator->getMessage('COMMON_ENTER_NEW_TODO').LF;
      $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_TODO_TYPE,
                                       'edit',
                                       $params,
                                       $link,
                                       $this->_translator->getMessage('COMMON_ENTER_NEW_TODO')).LF;
      $html .= '</div>'.LF;

      $html .= '<div style="vertical-align:bottom; height:25px;">'.LF;
      $image = '<img src="images/commsyicons/22x22/discussion.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_ENTER_NEW_MATERIAL').'"/>'.LF;
      $params = array();
      $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_DISCUSSION_TYPE,
                                       'edit',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('COMMON_ENTER_NEW_DISCUSSION')).LF;
      $link = $this->_translator->getMessage('COMMON_ENTER_NEW_DISCUSSION').LF;
      $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_DISCUSSION_TYPE,
                                       'edit',
                                       $params,
                                       $link,
                                       $this->_translator->getMessage('COMMON_ENTER_NEW_DISCUSSION')).LF;
      $html .= '</div>'.LF;



      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }*/


   function asHTML () {
      $privateroom_item = $this->_environment->getCurrentContextItem();
      $myentries_array_original = $privateroom_item->getMyEntriesDisplayConfig();

      $html  = LF.'<!-- BEGIN OF LIST VIEW -->'.LF;

      $html .= $this->_getIndexPageHeaderAsHTML().LF;
      $html .= '<div style="width:100%; clear:both; margin-top: 10px;">';

      $myentries_array = array();
      foreach($myentries_array_original as $myentries_array_original_entry){
         if(($myentries_array_original_entry == 'my_tag_box' or $myentries_array_original_entry == 'my_tag_box_preferences') and !$privateroom_item->withTags()){   
         } else if(($myentries_array_original_entry == 'my_buzzword_box' or $myentries_array_original_entry == 'my_buzzword_box_preferences') and !$privateroom_item->withBuzzwords()){   
         } else if($myentries_array_original_entry == 'null'){
         } else {
         	$myentries_array[] = $myentries_array_original_entry;
         }
      }
      
      if(!empty($myentries_array)){
         $html .= '<div id="myentries_left" class="column" style="width:50%; float:left;">'.LF;
         foreach($myentries_array as $myentry){
            if($myentry == "my_list_box"){
               $html .= $this->_getMylistsBoxAsHTML().LF;
            }
            if($myentry == "my_buzzword_box" and $privateroom_item->withBuzzwords()){
               $html .= $this->_getBuzzwordBoxAsHTML().LF;
            }
            if($myentry == "my_matrix_box"){
               $html .= $this->_getMatrixBoxAsHTML().LF;
            }
            if($myentry == "my_tag_box" and $privateroom_item->withTags()){
               $html .= $this->_getTagBoxAsHTML().LF;
            }
         }
         $html .= '</div>'.LF;
         $html .= '<div id="myentries_right" style="width:50%; float:right;">'.LF;
         $html .= $this->_getSearchBoxAsHTML().LF;
         $html .= $this->_getContentBoxAsHTML().LF;
         $html .= '</div>'.LF;
      } else {
         $html .= '<div style="width:100%; float:left;">'.LF;
         $html .= $this->_getSearchBoxAsHTML().LF;
         $html .= $this->_getContentBoxAsHTML().LF;
         $html .= '</div>'.LF;
      }

      $html .= '</div>';
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .= '<!-- END OF PLAIN LIST VIEW -->'.LF.LF;
      $html .= $this->_initDropDownMenus();
      return $html;
   }

   function _initDropDownMenus(){
      $privateroom_item = $this->_environment->getCurrentContextItem();
      $action_array = array();
      $html = '';

      $myentries_array = $privateroom_item->getMyEntriesDisplayConfig();

      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('PRIVATEROOM_MY_LISTS_BOX');
      $temp_array['value'] = "my_list_box";
      if(in_array("my_list_box", $myentries_array)){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;

      if ($privateroom_item->withBuzzwords()){
	      $temp_array = array();
	      $temp_array['dropdown_image']  = "new_icon";
	      $temp_array['text']  = $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_BUZZWORD_BOX');
	      $temp_array['value'] = "my_buzzword_box";
	      if(in_array("my_buzzword_box", $myentries_array)){
	         $temp_array['checked']  = "checked";
	      } else {
	         $temp_array['checked']  = "";
	      }
	      $action_array[] = $temp_array;
      }

      if ($privateroom_item->withTags()){
	      $temp_array = array();
	      $temp_array['dropdown_image']  = "new_icon";
	      $temp_array['text']  = $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_TAG_BOX');
	      $temp_array['value'] = "my_tag_box";
	      if(in_array("my_tag_box", $myentries_array)){
	         $temp_array['checked']  = "checked";
	      } else {
	         $temp_array['checked']  = "";
	      }
	      $action_array[] = $temp_array;
      }
      
      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_MATRIX_BOX');
      $temp_array['value'] = "my_matrix_box";
      if(in_array("my_matrix_box", $myentries_array)){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;
      
      #$temp_array = array();
      #$temp_array['dropdown_image']  = "new_icon";
      #$temp_array['text']  = $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_SEARCH_BOX');
      #$temp_array['value'] = "my_search_box";
      #if(in_array("my_search_box", $myentries_array)){
      #   $temp_array['checked']  = "checked";
      #} else {
      #   $temp_array['checked']  = "";
      #}
      #$action_array[] = $temp_array;

      #$temp_array = array();
      #$temp_array['dropdown_image']  = "new_icon";
      #$temp_array['text']  = $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_LIST_BOX');
      #$temp_array['value'] = "my_entries_box";
      #if(in_array("my_entries_box", $myentries_array)){
      #   $temp_array['checked']  = "checked";
      #} else {
      #   $temp_array['checked']  = "";
      #}
      #$action_array[] = $temp_array;

      // init drop down menu
      if ( !empty($action_array)
           and count($action_array) >= 1
         ) {
         $html .= '<script type="text/javascript">'.LF;
         $html .= '<!--'.LF;
         $html .= 'var dropDownMyEntries = new Array(';
         $first = true;
         foreach ($action_array as $action) {
            if ( $first ) {
               $first = false;
            } else {
               $html .= ',';
            }
            $html .= 'new Array("'.$action['dropdown_image'].'","'.$action['checked'].'","'.$action['text'].'","'.$action['value'].'")';
         }
         $html .= ');'.LF;
         $html .= 'var myentriesSaveButton = "'.$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON').'";'.LF;
         $html .= 'var ajax_cid = "'.$privateroom_item->getItemID().'";'.LF;
         $html .= 'var ajax_function = "privateroom_myentries";'.LF;
         $html .= '-->'.LF;
         $html .= '</script>'.LF;
      }
      return $html;
   }

   function _getContentBoxAsHTML () {
      $list = $this->_list;
      $params = $this->_environment->getCurrentParameterArray();
      $html = '<div class="portlet" id="my_entries_box">'.LF;
      $html .= '<div class="portlet-header" style="cursor:default;">'.LF;
      $html .= $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_LIST_BOX').LF;
      #$html .= '<div style="float:right;"><a name="myentries_remove" style="cursor:pointer;"><img src="images/commsyicons/16x16/delete.png" /></a></div>';
      $html .= '</div>'.LF;
      $html .= '<div id="contentbox" class="portlet-content">'.LF;
      $html .= '<table class="description-background" style="width:100%;">'.LF;
      if ( isset($list) && !($list->isEmpty()) ) {
         $interval_20 = '20';
         if($this->_interval != '20'){
           $params['interval'] = 20;
           $params['pos'] = 0;
           $interval_20 = ahref_curl(  $this->_environment->getCurrentContextID(),
                                          'entry',
                                          'index',
                                          $params,
                                          '20').LF;
         }
         $interval_50 = '50';
         if($this->_interval != '50'){
            $params['interval'] = 50;
            $params['pos'] = 0;
            $interval_50 = ahref_curl(  $this->_environment->getCurrentContextID(),
                                          'entry',
                                          'index',
                                          $params,
                                          '50').LF;
         }
/*         $interval_all = $this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL');
         if($this->_interval != 'all'){
            $params['interval'] = 'all';
            $params['pos'] = 0;
            $interval_all = ahref_curl(  $this->_environment->getCurrentContextID(),
                                          'entry',
                                          'index',
                                          $params,
                                          $this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL')).LF;
         }*/

         $browse_first = '&lt;&lt;';
         $browse_prev = '&lt;';
         if($this->_browse_prev){
            $params['interval'] = $this->_interval;
            $params['pos'] = 0;
            $browse_first = ahref_curl(  $this->_environment->getCurrentContextID(),
                                          'entry',
                                          'index',
                                          $params,
                                          '&lt;&lt;',
                                          $this->_translator->getMessage('COMMON_BROWSE_START_DESC')).LF;
            $params['interval'] = $this->_interval;
            $params['pos'] = $this->_pos - 1;
            $browse_prev = ahref_curl(  $this->_environment->getCurrentContextID(),
                                          'entry',
                                          'index',
                                          $params,
                                          '&lt;',
                                          $this->_translator->getMessage('COMMON_BROWSE_LEFT_DESC')).LF;
         }

         $browse_next = '&gt;';
         $browse_last = '&gt;&gt;';
         if($this->_browse_next){
            $params['interval'] = $this->_interval;
            $params['pos'] = $this->_pos + 1;
            $browse_next = ahref_curl(  $this->_environment->getCurrentContextID(),
                                          'entry',
                                          'index',
                                          $params,
                                          '&gt;',
                                          $this->_translator->getMessage('COMMON_BROWSE_RIGHT_DESC')).LF;
            $params['interval'] = $this->_interval;
            $params['pos'] = $this->_max_pos;
            $browse_last = ahref_curl(  $this->_environment->getCurrentContextID(),
                                          'entry',
                                          'index',
                                          $params,
                                          '&gt;&gt;',
                                          $this->_translator->getMessage('COMMON_BROWSE_END_DESC')).LF;
         }

         $current_pos = $this->_pos + 1;
         $whole_ammount = $this->_max_pos+1;

#         $html .= '<tr><td style="text-align:left; font-weight:bold;">'.$interval_20.' | '.$interval_50.' | '.$interval_all.'</td><td style="text-align:right; font-weight:bold;">'.$current_pos.' / '.$whole_ammount.'&nbsp;&nbsp;&nbsp;'.$browse_first.' | '.$browse_prev.' | '.$browse_next.' | '.$browse_last.'</td></tr>';
         $html .= '<tr><td style="text-align:left;">'.$this->_translator->getMessage('COMMON_PAGE_ENTRIES').': '.$interval_20.' | '.$interval_50.'</td><td style="text-align:right;">'.$this->_translator->getMessage('COMMON_PAGE').': '.$current_pos.' / '.$whole_ammount.'&nbsp;&nbsp;&nbsp;'.$browse_first.' | '.$browse_prev.' | '.$browse_next.' | '.$browse_last.'</td></tr>';
      }
      $html .= '</table>'.LF;
      $html .= '<table class="description-background" style="width:100%;">'.LF;
      if (
          !empty($this->_sellist)
          or !empty($this->_selbuzzword)
          or !empty($this->_selmatrix)
          or !empty($this->_seltag)
          or (!empty($this->_search_text) and $this->_search_text != $this->_translator->getMessage('COMMON_SEARCH_IN_ENTRIES'))
      ){
/*         $html .= '<tr>'.LF;
         $html .= '<td colspan="2" style="vertical-align:top; width:180px;">'.LF;
         $html .= $this->_translator->getMessage('COMMON_RESTRICTIONS').': ';
         $html .= '</td>'.LF;
         $html .= '</tr>'.LF;
*/
         if (!empty($this->_sellist)){
            $html .= '<tr>'.LF;
            $html .= '<td style="vertical-align:top; white-space:nowrap; width:60px;">'.LF;
            if ($this->_sellist == 'new'){
               $html .= $this->_translator->getMessage('COMMON_MYLIST_RESTRICTION').': ';
            }else{
                $list_manager = $this->_environment->getMyListManager();
                $list_item = $list_manager->getItem($this->_sellist);
                $html .= $this->_translator->getMessage('COMMON_MYLIST_RESTRICTION').': ';
            }
            $html .= '</td>'.LF;
            $html .= '<td style="text-align:right;">';
            $html .= '"'.$list_item->getName().'"';
            $new_aparams = $params;
            unset($new_aparams['sellist']);
            $image = '<img src="images/delete_restriction.gif" style="padding-top:3px;" alt="'.$this->_translator->getMessage('ENTRY_DELETE_RESTRICTION').'"/>'.LF;
            $html .= ' '.ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_ENTRY_TYPE,
                                       'index',
                                       $new_aparams,
                                       $image,
                                       $this->_translator->getMessage('ENTRY_DELETE_RESTRICTION')).LF;
            $html .= '</td>'.LF;
            $html .= '</tr>'.LF;
         }
         if (!empty($this->_selbuzzword)){
            $html .= '<tr>'.LF;
            $html .= '<td style="vertical-align:top; white-space:nowrap; width:60px;">'.LF;
            $buzzword_manager = $this->_environment->getBuzzwordManager();
            $buzzword_item = $buzzword_manager->getItem($this->_selbuzzword);
            $html .= $this->_translator->getMessage('COMMON_BUZZWORD_RESTRICTION').': ';
            $html .= '</td>'.LF;
            $html .= '<td style="text-align:right;">';
            $html .= '"'.$buzzword_item->getName().'"'.LF;
            $new_aparams = $params;
            unset($new_aparams['selbuzzword']);
            $image = '<img src="images/delete_restriction.gif" style="padding-top:3px;" alt="'.$this->_translator->getMessage('ENTRY_DELETE_RESTRICTION').'"/>'.LF;
            $html .= ' '.ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_ENTRY_TYPE,
                                       'index',
                                       $new_aparams,
                                       $image,
                                       $this->_translator->getMessage('ENTRY_DELETE_RESTRICTION')).LF;
            $html .= '</td>'.LF;
            $html .= '</tr>'.LF;
         }
         if (!empty($this->_selmatrix)){
            $html .= '<tr>'.LF;
            $html .= '<td style="vertical-align:top; white-space:nowrap; width:60px;">'.LF;
            $matrix_manager = $this->_environment->getMatrixManager();
            $id_array = explode('_',$this->_selmatrix);
            $matrix_item_x = $matrix_manager->getItem($id_array[0]);
            $matrix_item_y = $matrix_manager->getItem($id_array[1]);
            $html .= $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_MATRIX_BOX').': ';
            $html .= '</td>'.LF;
            $html .= '<td style="text-align:right;">';
            $html .= '"'.$matrix_item_x->getName().' / '.$matrix_item_y->getName().'"'.LF;
            $new_aparams = $params;
            unset($new_aparams['selmatrix']);
            $image = '<img src="images/delete_restriction.gif" style="padding-top:3px;" alt="'.$this->_translator->getMessage('ENTRY_DELETE_RESTRICTION').'"/>'.LF;
            $html .= ' '.ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_ENTRY_TYPE,
                                       'index',
                                       $new_aparams,
                                       $image,
                                       $this->_translator->getMessage('ENTRY_DELETE_RESTRICTION')).LF;
            $html .= '</td>'.LF;
            $html .= '</tr>'.LF;
         }
         if (!empty($this->_seltag)){
            $html .= '<tr>'.LF;
            $html .= '<td style="vertical-align:top; white-space:nowrap; width:60px;">'.LF;
            $tag_manager = $this->_environment->getTagManager();
            $tag_item = $tag_manager->getItem($this->_seltag);
            $html .= $this->_translator->getMessage('COMMON_TAG_RESTRICTION').': ';
            $html .= '</td>'.LF;
            $html .= '<td style="text-align:right;">';
            $html .= '"'.$tag_item->getTitle().'"'.LF;
            $new_aparams = $params;
            unset($new_aparams['seltag']);
            $i = 0;
            while ( !isset($new_aparams['seltag_'.$i]) ){
               $i++;
            }
            unset($new_aparams['seltag_'.$i]);
            $image = '<img src="images/delete_restriction.gif" style="padding-top:3px;" alt="'.$this->_translator->getMessage('ENTRY_DELETE_RESTRICTION').'"/>'.LF;
            $html .= ' '.ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_ENTRY_TYPE,
                                       'index',
                                       $new_aparams,
                                       $image,
                                       $this->_translator->getMessage('ENTRY_DELETE_RESTRICTION')).LF;
            $html .= '</td>'.LF;
            $html .= '</tr>'.LF;
         }
         if (!empty($this->_search_text) and $this->_search_text != $this->_translator->getMessage('COMMON_SEARCH_IN_ENTRIES')){
            $html .= '<tr>'.LF;
            $html .= '<td style="vertical-align:top; white-space:nowrap; width:60px;">'.LF;
            $html .= $this->_translator->getMessage('COMMON_SEARCH_RESTRICTION').': ';
            $html .= '</td>'.LF;
            $html .= '<td style="text-align:right;">';
            $html .= '"'.$this->_search_text.'"'.LF;
            $new_aparams = $params;
            unset($new_aparams['search']);
            $image = '<img src="images/delete_restriction.gif" style="padding-top:3px;" alt="'.$this->_translator->getMessage('ENTRY_DELETE_RESTRICTION').'"/>'.LF;
            $html .= ' '.ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_ENTRY_TYPE,
                                       'index',
                                       $new_aparams,
                                       $image,
                                       $this->_translator->getMessage('ENTRY_DELETE_RESTRICTION')).LF;
            $html .= '</td>'.LF;
            $html .= '</tr>'.LF;
          }
      }
      $html .= '</table>'.LF;



      if ( !isset($list) || $list->isEmpty() ) {
         $html .= '<div class="odd" style="border-bottom: 0px;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</div>';
      } else {
         $current_item = $list->getFirst();
         $i = 0;
         while ( $current_item ) {
            $html .= $this->_getItemAsHTML($current_item, $i++);
            $current_item = $list->getNext();
         }
      }

      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;

   }


   function _getMatrixBoxAsHTML () {
      $html = '<div class="portlet" id="my_matrix_box">'.LF;
      $html .= '<div class="portlet-header">'.LF;
      $html .= $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_MATRIX_BOX').LF;
      $html .= '<div style="float:right;"><a name="myentries_remove" style="cursor:pointer;"><img src="images/commsyicons/16x16/delete.png" /></a></div>';
      $html .= '</div>'.LF;
      $html .= '<div class="portlet-content">'.LF;
      $count = 0;

      $matrix_manager = $this->_environment->getMatrixManager();
      $matrix_manager->resetLimits();
      $matrix_manager->setContextLimit($this->_environment->getCurrentContextID());
      $matrix_manager->setRowLimit();
      $matrix_manager->select();
      $matrix_row_list = $matrix_manager->get();

      $matrix_manager = $this->_environment->getMatrixManager();
      $matrix_manager->resetLimits();
      $matrix_manager->setContextLimit($this->_environment->getCurrentContextID());
      $matrix_manager->setColumnLimit();
      $matrix_manager->select();
      $matrix_column_list = $matrix_manager->get();

      $matrix_row_title_array = array();
      $matrix_item = $matrix_row_list->getFirst();
      while($matrix_item){
          $matrix_row_title_array[$matrix_item->getItemID()] = $matrix_item->getName();
         $matrix_item = $matrix_row_list->getNext();
      }
      $matrix_column_title_array = array();
      $matrix_item = $matrix_column_list->getFirst();
      while($matrix_item){
          $matrix_column_title_array[$matrix_item->getItemID()] = $matrix_item->getName();
         $matrix_item = $matrix_column_list->getNext();
      }

      $html_table = '';
      $html_table .= '<table id="matrix_table" style="width:100%; border:1px solid #CCCCCC;">';
      $html_table .= '<tr id="matrix_table_header">'.LF;
      $html_table .= '<td id="matrix_table_top_left" style="background-color:#CCCCCC;">'.LF;
      $html_table .= '</td>'.LF;
      foreach($matrix_column_title_array as $column_key => $column_title){
         $html_table .= '<td id="'.$column_key.'" style="background-color:#CCCCCC;">'.$column_title.LF;
         $html_table .= '</td>'.LF;

      }
      $html_table .= '</tr>'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      foreach($matrix_row_title_array as $row_key => $row){
         $html_table .= '<tr id="'.$row_key.'">'.LF;
         $html_table .= '<td name="matrix_table_left" style="background-color:#CCCCCC;">'.$row.LF;
         $html_table .= '</td>'.LF;
         foreach($matrix_column_title_array as $column_key => $column){
         	$selected_cell = false;
         	if(isset($this->_selmatrix) and !empty($this->_selmatrix)){
         		$selmatrix_array = explode('_', $this->_selmatrix);
         		if($selmatrix_array[1] == $column_key and $selmatrix_array[0] == $row_key){
         			$selected_cell = true;
         		}
         	}
         	if($selected_cell){
         		$html_table .= '<td class="droppable_matrix" id="id_'.$row_key.'_'.$column_key.'" style="text-align:center; background-color:#eeeeee; font-weight:bold;"><a></a><span class="matrix_current_count">';
         	} else {
               $html_table .= '<td class="droppable_matrix" id="id_'.$row_key.'_'.$column_key.'" style="text-align:center;"><a></a><span class="matrix_current_count">';
         	}
            $count = $matrix_manager->getEntriesInPosition($column_key,$row_key);
          	$params['selmatrix'] = $row_key.'_'.$column_key;
            $html_table .= ahref_curl($this->_environment->getCurrentContextID(),
                                'entry',
                                'index',
                                $params,
                                $count,
                                '').LF;
            unset($params['selmatrix']);
            $html_table .= '</span></td>'.LF;
         }
         $html_table .= '</tr>'.LF;
      }
      $html_table .= '</table>';

      $html .= $html_table.LF;

      // form

      // /form

      $html .= '</div>'.LF;

      // Preferences link
      $html .= '<div class="portlet-turn portlet-front" style="float:right;">'.LF;
      $html .= '<a class="preferences_flip" name="portlet_preferences" style="cursor:pointer;"><img src="images/config_home.png" /></a>'.LF;
      $html .= '&nbsp;</div>'.LF;

      $html .= '</div>'.LF;

      // Preferences content
      $html .= '<div class="portlet" style="display:none;" id="my_matrix_box_preferences">'.LF;
      $html .= '<div class="portlet-header">'.$this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_MATRIX_BOX').' - '.$this->_translator->getMessage('COMMON_CONFIGURATION').'</div>'.LF;
      $html .= '<div class="portlet-content">'.LF;

      // form
      #$html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), 'entry', 'index','').'" method="post" name="matrix-form">'.LF;
      $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
      $html .= '   <input type="hidden" name="mod" value="entry"/>'.LF;
      $html .= '   <input type="hidden" name="fct" value="index"/>'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      if (isset($params['selbuzzword']) and !empty($params['selbuzzword'])){
         $html .= '   <input type="hidden" name="sellbuzzword" value="'.$params['selbuzzword'].'"/>'.LF;
      }
      if (isset($params['sellist']) and !empty($params['sellist'])){
         $html .= '   <input type="hidden" name="sellist" value="'.$params['sellist'].'"/>'.LF;
      }
      if (isset($params['search']) and !empty($params['search'])){
         $html .= '   <input type="hidden" name="search" value="'.$params['search'].'"/>'.LF;
      }
      $matrix_manager = $this->_environment->getMatrixManager();
      $matrix_manager->resetLimits();
      $matrix_manager->setContextLimit($this->_environment->getCurrentContextID());
      $matrix_manager->setRowLimit();
      $matrix_manager->select();
      $matrix_row_list = $matrix_manager->get();
      $matrix_item = $matrix_row_list->getFirst();
      $count_rows = 0;
      $html .= '<div id="matrix_rows">'.LF;
      while($matrix_item){
         $html .= '<div><input name="matrix_'.$matrix_item->getItemID().'" value="'.$matrix_item->getItemID().'" checked="checked" type="checkbox">';
         $html .= '<input class="matrix_text" name="matrixtext_'.$matrix_item->getItemID().'" type="text" value="'.$matrix_item->getName().'"></div>';
         $matrix_item = $matrix_row_list->getNext();
         $count_rows++;
      }
      $html .= '</div>'.LF;
      $html .= '   <input type="hidden" name="new_matrix_row_count" value="'.$count_rows.'"/>'.LF;
      $html .= '   <input id="new_matrix_row" onclick="javascript:resetSearchTextMatrixRow(\'new_matrix_row\');" style="width:250px; font-size:10pt; margin-bottom:0px;" name="new_matrix_row" type="text" size="20" value="'.$this->_text_as_form($this->_translator->getMessage('PRIVATEROOM_MATRIX_NEW_ROW_ENTRY')).'"/>'.BR.BRLF;

      $matrix_manager->resetLimits();
      $matrix_manager->setContextLimit($this->_environment->getCurrentContextID());
      $matrix_manager->setColumnLimit();
      $matrix_manager->select();
      $matrix_column_list = $matrix_manager->get();
      $matrix_item = $matrix_column_list->getFirst();
      $count_columns = 0;
      $html .= '<div id="matrix_columns">'.LF;
      while($matrix_item){
         $html .= '<div><input name="matrix_'.$matrix_item->getItemID().'" value="'.$matrix_item->getItemID().'" checked="checked" type="checkbox">';
         $html .= '<input class="matrix_text" name="matrixtext_'.$matrix_item->getItemID().'" type="text" value="'.$matrix_item->getName().'"></div>';
         $matrix_item = $matrix_column_list->getNext();
         $count_columns++;
      }
      $html .= '</div>'.LF;
      $html .= '   <input type="hidden" name="new_matrix_column_count" value="'.$count_columns.'"/>'.LF;
      $html .= '   <input id="new_matrix_column" onclick="javascript:resetSearchTextMatrixColumn(\'new_matrix_column\');" style="width:250px; font-size:10pt; margin-bottom:0px;" name="new_matrix_column" type="text" size="20" value="'.$this->_text_as_form($this->_translator->getMessage('PRIVATEROOM_MATRIX_NEW_COLUMN_ENTRY')).'"/>'.BRLF;
      $html .= '   <input name="option" value="'.$this->_text_as_form($this->_translator->getMessage('PRIVATEROOM_MATRIX_SAVE_BUTTON')).'" style="width: 250px; font-size: 10pt;" type="submit"/>'.LF;
      #$html .='</form>'.LF;
      // /form

      $html .= '</div>'.LF;
      $html .= '<div class="portlet-turn portlet-back" style="float:right;"><a class="preferences_flip" name="portlet_preferences_back_button" style="cursor:pointer;"><img src="images/commsyicons/16x16/room.png" height="18" width="18"/></a>&nbsp;</div>'.LF;
      $html .= '</div>'.LF;

      $html .= '<script type="text/javascript">'.LF;
      $html .= '<!--'.LF;
      $html .= 'var new_row_message = "'.$this->_translator->getMessage('PRIVATEROOM_MATRIX_NEW_ROW_ENTRY').'";'.LF;
      $html .= 'var new_column_message = "'.$this->_translator->getMessage('PRIVATEROOM_MATRIX_NEW_COLUMN_ENTRY').'";'.LF;
      $html .= '-->'.LF;
      $html .= '</script>'.LF;

      return $html;

   }

   function _getTagBoxAsHTML () {
      $html = '<div class="portlet" id="my_tag_box">'.LF;
      $html .= '<div class="portlet-header">'.LF;
      $html .= $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_TAG_BOX').LF;
      $html .= '<div style="float:right;"><a name="myentries_remove" style="cursor:pointer;"><img src="images/commsyicons/16x16/delete.png" /></a></div>';
      $html .= '</div>'.LF;
      $html .= '<div class="portlet-content">'.LF;
      $html .= '<div id="my_tag_content_div">'.LF;
      $tag_manager = $this->_environment->getTagManager();
      $root_item = $tag_manager->getRootTagItem();
      
      $selected_id = '';
      $father_id_array = array();
      $tag_array = $this->_getSelectedTagArray();
      $count = (count($tag_array));
      if ($count >0){
         $selected_id = $tag_array[0];
         $tag2tag_manager =  $this->_environment->getTag2TagManager();
         $father_id_array = $tag2tag_manager->getFatherItemIDArray($selected_id);
      }
      
      $html .= $this->_getTagContentAsHTMLWithJavascript($root_item,0,$selected_id, $father_id_array,0,true);
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

      // Preferences link
      $html .= '<div class="portlet-turn portlet-front" style="float:right;">'.LF;
      $html .= '<a class="preferences_flip" name="portlet_preferences" style="cursor:pointer;"><img src="images/config_home.png" /></a>'.LF;
      $html .= '&nbsp;</div>'.LF;

      $html .= '</div>'.LF;

      // Preferences content
      $html .= '<div class="portlet" style="display:none;" id="my_tag_box_preferences">'.LF;
      $html .= '<div class="portlet-header">'.$this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_TAG_BOX').' - '.$this->_translator->getMessage('COMMON_CONFIGURATION').'</div>'.LF;
      $html .= '<div class="portlet-content">'.LF;

      // form
      
      $html .= '<table id="my_tag_form_table" summary="layout">'.LF;
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= cs_ucfirst($this->_translator->getMessage('COMMON_ADD_BUTTON')).LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;

      $values_tree = array();
      $first_sort_tree = array();
      $second_sort_tree = array();
      if ( isset($root_item) ) {
         $temp_array = array();
         $temp_array['value'] = $root_item->getItemID();
         $temp_array['text'] = '*'.$this->_translator->getMessage('TAG_FORM_ROOT_LEVEL');
         $values_tree[] = $temp_array;
         unset($temp_array);
         $first_sort_tree = $this->_initFormChildren($root_item,0);
         $values_tree = array_merge($values_tree, $first_sort_tree);
         $second_sort_tree = $values_tree;
      }
      
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= '<input type="text" id="my_tag_form_new_tag" value="" maxlength="255" size="30" tabindex="18" class="text"/>'.LF;
      $html .= $this->_translator->getMessage('TAG_WORD_TO').LF;
      $html .= '<select id="my_tag_form_father_id" size="1" tabindex="19">'.LF;
      foreach($values_tree as $value){
      	$html .= '<option value="'.$value['value'].'">'.$value['text'].'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= '<input type="submit" id="my_tag_form_button_add" value="'.$this->_translator->getMessage('COMMON_ADD_BUTTON').'" tabindex="20"/>'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
               
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= '<br/>'.LF;
      $html .= ''.$this->_translator->getMessage('COMMON_SORT_BUTTON').''.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
               
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= '<select id="my_tag_form_sort_1" size="1" tabindex="21">'.LF;
      foreach($first_sort_tree as $value){
         $html .= '<option value="'.$value['value'].'">'.$value['text'].'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= '<select id="my_tag_form_sort_action" size="1" tabindex="22">'.LF;
      $html .= '<option value="3">'.$this->_translator->getMessage('TAG_ACTIONS_UNDER').'</option>'.LF;
      $html .= '<option value="1">'.$this->_translator->getMessage('TAG_ACTIONS_BEFORE').'</option>'.LF;
      $html .= '<option value="2">'.$this->_translator->getMessage('TAG_ACTIONS_AFTER').'</option>'.LF;
      $html .= '</select>'.LF;
      $html .= '<select id="my_tag_form_sort_2" size="1" tabindex="23">'.LF;
      foreach($second_sort_tree as $value){
         $html .= '<option value="'.$value['value'].'">'.$value['text'].'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= '<input type="submit" id="my_tag_form_button_sort" value="'.$this->_translator->getMessage('TAG_SORT_BUTTON').'" tabindex="24"/>'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
               
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= '<input type="submit" id="my_tag_form_button_sort_abc" value="'.$this->_translator->getMessage('TAG_SORT_ABC').'" tabindex="25"/>'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
               
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= '<br/>'.LF;
      $html .= cs_ucfirst($this->_translator->getMessage('TAG_COMBINE_BUTTON')).LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
               
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= '<select id="my_tag_form_combine_1" size="1" tabindex="26">'.LF;
      foreach($first_sort_tree as $value){
         $html .= '<option value="'.$value['value'].'">'.$value['text'].'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= '<select id="my_tag_form_combine_2" size="1" tabindex="27">'.LF;
      foreach($first_sort_tree as $value){
         $html .= '<option value="'.$value['value'].'">'.$value['text'].'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= $this->_translator->getMessage('TAG_WORD_TO').LF;
      $html .= '<select id="my_tag_form_combine_father" size="1" tabindex="28">'.LF;
      foreach($second_sort_tree as $value){
         $html .= '<option value="'.$value['value'].'">'.$value['text'].'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= '<input type="submit" id="my_tag_form_button_combine" value="'.$this->_translator->getMessage('TAG_COMBINE_BUTTON').'" tabindex="29"/><br/><br/>'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;

      $html .= '<tr>'.LF;
      $html .= '<td class="infoborder" style="width: 70%;" colspan="3">'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
   
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= 'Bearbeiten'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
               
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= '<table id="my_tag_form_change_table">'.LF;
      $html .= $this->_createFormForChildren($root_item, 0);
      $html .= '</table>'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
      
      $html .= '</table>'.LF;
      
      // /form
      
      $html .= '</div>'.LF;
      $html .= '<div class="portlet-turn portlet-back" style="float:right;"><a class="preferences_flip" name="portlet_preferences_back_button" style="cursor:pointer;"><img src="images/commsyicons/16x16/room.png" height="18" width="18"/></a>&nbsp;</div>'.LF;
      $html .= '</div>'.LF;

      $html .= '<script type="text/javascript">'.LF;
      $html .= '<!--'.LF;
      $html .= 'var tag_message = "";'.LF;
      $html .= '-->'.LF;
      $html .= '</script>'.LF;

      return $html;

   }
   
   private function _createFormForChildren ( $item, $depth ) {
   	$html = '';
      if ( isset($item) ) {
         $children_list = $item->getChildrenList();
         if ( isset($children_list) and $children_list->isNotEmpty() ) {
            $arrows = '';
            $px = 0;
            $width = 250;
            $depth_temp = $depth;
            while ( $depth_temp > 0 ) {
               $arrows .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ';
               $px += 20;
               $width -= 20;
               $depth_temp = $depth_temp-1;
            }
            $len_text_field = 30-($depth*4);
            if ( $depth > 0 ) {
               $len_text_field = $len_text_field - 1;
            }
            $child = $children_list->getFirst();
            while ( $child ) {
               $html .= '<tr>'.LF;
			      $html .= '<td class="formfield" style="padding-left:'.$px.'px;">'.LF;
			      $html .= '<input type="text" id="my_tag_form_change_value-'.$child->getItemID().'" value="'.$child->getTitle().'" maxlength="255" tabindex="49" class="text" style="width:'.$width.'px;"/>'.LF;
			      $html .= '</td><td>'.LF;
			      $html .= '<input type="submit" id="my_tag_form_change_button-'.$child->getItemID().'" value="'.$this->_translator->getMessage('BUZZWORDS_CHANGE_BUTTON').'" tabindex="50"/>'.LF;
			      $html .= '</td><td>'.LF;
			      $html .= '<input type="submit" id="my_tag_form_delete_button-'.$child->getItemID().'" value="'.$this->_translator->getMessage('COMMON_DELETE_BUTTON').'" tabindex="52"/>'.LF;
			      $html .= '</td>'.LF;
			      $html .= '</tr>'.LF;                  
               $html .= $this->_createFormForChildren($child,$depth+1);
               unset($child);
               $child = $children_list->getNext();
            }
         }
         unset($children_list);
      }
      unset($item);
      return $html;
   }

   function _getBuzzwordBoxasHTML(){
      $params = $this->_environment->getCurrentParameterArray();
      $current_user = $this->_environment->getCurrentUserItem();
      $buzzword_manager = $this->_environment->getLabelManager();
      $buzzword_manager->resetLimits();
      $buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
      $buzzword_manager->setTypeLimit('buzzword');
      $buzzword_manager->setGetCountLinks();
      $buzzword_manager->select();
      $buzzword_list = $buzzword_manager->get();
      $html  = '';
      $html .= '<div class="portlet" id="my_buzzword_box">'.LF;
      $html .= '<div class="portlet-header">'.LF;
      $html .= $this->_translator->getMessage('COMMON_BUZZWORD_BOX').LF;
      $html .= '<div style="float:right;"><a name="myentries_remove" style="cursor:pointer;"><img src="images/commsyicons/16x16/delete.png" /></a></div>';
      $html .= '</div>'.LF;
      $html .= '<div class="portlet-content">'.LF;
      $buzzword = $buzzword_list->getFirst();
      if (!$buzzword){
         $html .= '<span class="disabled" style="font-size:10pt;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</span>';
      }
      while ($buzzword){
         $count = $buzzword->getCountLinks();
         if ($count > 0 or true){
            $font_size = $this->getBuzzwordSizeLogarithmic($count);
            $font_color = 100 - $this->getBuzzwordColorLogarithmic($count);
            $params['selbuzzword'] = $buzzword->getItemID();
            $temp_text = '';
            $style_text  = 'style="margin-left:2px; margin-right:2px;';
            if (!empty($this->_selbuzzword) and $this->_selbuzzword == $buzzword->getItemID()){
               $style_text .= ' color:#000000;';
               $style_text .= ' font-weight:bold;';
            }else{
               $style_text .= ' color: rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
            }
            $style_text .= 'font-size:'.$font_size.'px;"';
            $title  = '<span id="buzzword_'.$buzzword->getItemID().'" class="droppable_buzzword" '.$style_text.'>'.LF;
            $title .= $this->_text_as_html_short($buzzword->getName()).LF;
            $title .= '</span> ';

            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                'entry',
                                'index',
                                $params,
                                $title,
                                $buzzword->getName()).LF;
         }
         $buzzword = $buzzword_list->getNext();
      }
      #$html .= '<div style="width:100%; text-align:right; padding-right:2px; padding-top:5px;">';
      #if ($current_user->isUser() and $this->_with_modifying_actions ) {
      #   $params = array();
      #   $params['module'] = $this->_environment->getCurrentModule();
      #   $html .= ahref_curl($this->_environment->getCurrentContextID(),'buzzwords','edit',$params,$this->_translator->getMessage('COMMON_EDIT')).LF;
      #   unset($params);
      #} else {
      #   $html .= '<span class="disabled">'.$this->_translator->getMessage('COMMON_EDIT').'</span>'.LF;
      #}
      #$html .= '</div>'.LF;
      $html .= '</div>'.LF;

      // Preferences link
      $html .= '<div class="portlet-turn portlet-front" style="float:right;">'.LF;
      $html .= '<a class="preferences_flip" name="portlet_preferences" style="cursor:pointer;"><img src="images/config_home.png" /></a>'.LF;
      $html .= '&nbsp;</div>'.LF;

      $html .= '</div>'.LF;

      // Preferences content
      $html .= '<div class="portlet" style="display:none;" id="my_buzzword_box_preferences">'.LF;
      $html .= '<div class="portlet-header">'.$this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_BUZZWORD_BOX').' - '.$this->_translator->getMessage('COMMON_CONFIGURATION').'</div>'.LF;
      $html .= '<div class="portlet-content">'.LF;

      // form
      $html .= '<input type="text" id="portlet_buzzword_new" size="40">';
      $html .= '<input type="submit" id="portlet_buzzword_new_button" value="'.$this->_translator->getMessage('BUZZWORDS_NEW_BUTTON').'">';
      $html .= '<br/><br/>'.LF;

      $html .= '<div id="portlet_buzzword_combine">';
         $html .= '<select id="portal_buzzword_combine_first" size="1" tabindex="15">'.LF;
         $buzzword = $buzzword_list->getFirst();
         while($buzzword){
            $html .= '<option value="'.$buzzword->getItemID().'">'.$buzzword->getName().'</option>'.LF;
            $buzzword = $buzzword_list->getNext();
         }
         $html .= '</select>'.LF;
         $html .= '<select id="portal_buzzword_combine_second" size="1" tabindex="15">'.LF;
         $buzzword = $buzzword_list->getFirst();
         while($buzzword){
            $html .= '<option value="'.$buzzword->getItemID().'">'.$buzzword->getName().'</option>'.LF;
            $buzzword = $buzzword_list->getNext();
         }
         $html .= '</select>'.LF;
         $html .= '<input type="submit" id="portlet_buzzword_combine_button" value="'.$this->_translator->getMessage('BUZZWORDS_COMBINE_BUTTON').'">';
      $html .= '</div><br/>';
      $length = $buzzword_list->getCount();
      if ($length > 7){
         $html .= '<div id="portlet_buzzword_preferences_list" style="height:120px; overflow-y: scroll;">';
      }else{
         $html .= '<div id="portlet_buzzword_preferences_list">';
      }
      $buzzword = $buzzword_list->getFirst();
      while($buzzword){
         $html .= '<div>';
         $html .= '<input type="text" class="portlet_buzzword_textfield" id="portlet_buzzword_'.$buzzword->getItemID().'" value="'.$buzzword->getName().'" size="40">&nbsp;';
         $html .= '<input type="submit" class="portlet_buzzword_change_button" id="'.$buzzword->getItemID().'" value="'.$this->_translator->getMessage('BUZZWORDS_CHANGE_BUTTON').'">&nbsp;';
         $html .= '<input type="submit" class="portlet_buzzword_delete_button" id="'.$buzzword->getItemID().'" value="'.$this->_translator->getMessage('COMMON_DELETE_BUTTON').'">';
         $html .= '</div>';
         $buzzword = $buzzword_list->getNext();
      }
      $html .= '</div>';

      // /form

      $html .= '</div>'.LF;
      $html .= '<div class="portlet-turn portlet-back" style="float:right;"><a class="preferences_flip" name="portlet_preferences_back_button" style="cursor:pointer;"><img src="images/commsyicons/16x16/room.png" height="18" width="18"/></a>&nbsp;</div>'.LF;
      $html .= '</div>'.LF;

      $html .= '<script type="text/javascript">'.LF;
      $html .= '<!--'.LF;
      $html .= 'var buzzword_cid = "'.$this->_environment->getCurrentContextID().'";'.LF;
      $html .= 'var ajax_function = "privateroom_myentries";'.LF;
      $html .= 'var buzzword_message = "'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'";'.LF;
      $html .= '-->'.LF;
      $html .= '</script>'.LF;


      unset($current_user);
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




   function _getIndexPageHeaderAsHTML(){
      $html ='<div style="width:100%;">'.LF;
      $html .='<div style="height:30px;">'.LF;
      if (!$this->_environment->inPrivateRoom()){
         $html .= '<h2 class="pagetitle">'.$this->_translator->getMessage('ENTRY_INDEX');
         $html .= '</h2>'.LF;
      } else {
         $html .= '<div style="width: 100%;"><div style="float:left;"><h2 class="pagetitle">'.$this->_translator->getMessage('ENTRY_INDEX');
         $html .= '</h2></div>'.LF;
         #$html .= '<div style="float:right;"><a href="#"><img id="new_icon" src="images/commsyicons/48x48/config/privateroom_home_options.png" height=24></a></div></div>';
         $html .= '<div style="float:right;">'.LF;
         $html .= '<div class="portlet-configuration">'.LF;
         $html .= '<div class="portlet-header-configuration ui-widget-header" style="width:200px; font-weight:normal;">'.LF;
         $html .= '<span style="font-weight:bold;">'.$this->_translator->getMessage('HOME_ENTRY_CONFIGURATION').'</span>'.LF;
         $html .= '<div style="float:right;">'.LF;
         $html .= '<a href="#"><img id="new_icon" src="images/commsyicons/48x48/config/privateroom_home_options.png" style="height:0px;"/></a>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }



   // @segment-begin 89418 _getItemAsHTML($item,$pos=0)-odd/even-for-announcement-entry-in-index
   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML($item, $pos=0, $with_links=TRUE) {
      $html = '';
      if ($pos%2 == 0){
         $style='class="even"';
      }else{
         $style='class="odd"';
      }
      $type = $item->getItemType();
      $item_manager = $this->_environment->getManager($type);
      $full_item = $item_manager->getItem($item->getItemID());
      if (is_object($full_item)){
         $type = $full_item->getType();
         if ($type =='label'){
            $type = $full_item->getLabelType();
         }
         $fragment = '';    // there is no anchor defined by default
         $link_created = $this->_translator->getDateInLang($full_item->getModificationDate());
         $text = '';
         $creator = $full_item->getCreatorItem();
         if ( isset($creator) and !$creator->isDeleted()) {
            $fullname = $this->_text_as_html_short($creator->getFullname());
         } else {
            $fullname = $this->_translator->getMessage('COMMON_DELETED_USER');
         }
         $room = $full_item->getContextItem();
         $room_title = $room->getTitle();
         switch ( $type ) {
            case CS_DISCARTICLE_TYPE:
               $linked_iid = $full_item->getDiscussionID();
               $fragment = 'anchor'.$full_item->getItemID();
               $discussion_manager = $this->_environment->getDiscussionManager();
               $new_full_item = $discussion_manager->getItem($linked_iid);
               break;
            case CS_STEP_TYPE:
               $linked_iid = $full_item->getToDoID();
               $fragment = 'anchor'.$full_item->getItemID();
               $todo_manager = $this->_environment->getToDoManager();
               $new_full_item = $todo_manager->getItem($linked_iid);
               break;
            case CS_SECTION_TYPE:
               $linked_iid = $full_item->getLinkedItemID();
               $fragment = 'anchor'.$full_item->getItemID();
               $material_manager = $this->_environment->getMaterialManager();
               $new_full_item = $material_manager->getItem($linked_iid);
               break;
            default:
               $linked_iid = $full_item->getItemID();
               $new_full_item = $full_item;
         }
         $type = $new_full_item->getType();
         if ($type =='label'){
            $type = $full_item->getLabelType();
         }
         switch ( mb_strtoupper($type, 'UTF-8') ) {
           case 'ANNOUNCEMENT':
              $text .= $this->_translator->getMessage('COMMON_ONE_ANNOUNCEMENT');
              $img = 'images/commsyicons/16x16/announcement.png';
              break;
           case 'DATE':
              $text .= $this->_translator->getMessage('COMMON_ONE_DATE');
              $img = 'images/commsyicons/16x16/date.png';
              break;
           case 'DISCUSSION':
              $text .= $this->_translator->getMessage('COMMON_ONE_DISCUSSION');
              $img = 'images/commsyicons/16x16/discussion.png';
              break;
           case 'MATERIAL':
              $text .= $this->_translator->getMessage('COMMON_ONE_MATERIAL');
              $img = 'images/commsyicons/16x16/material.png';
              break;
           case 'TODO':
              $text .= $this->_translator->getMessage('COMMON_ONE_TODO');
              $img = 'images/commsyicons/16x16/todo.png';
              break;
           default:
              $text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_detail_view('.__LINE__.') ';
              $img = '';
              break;
        }
        $module = Type2Module($type);
        $link_title = $this->_text_as_html_short($full_item->getTitle());
        $params = array();

        $html .= '<div id="item_'.$item->getItemID().'" class="dragable_item" style="width:100%; vertical-align: middle;">'.LF;
        $html .= '   <table '.$style.' style="width:100%; border-collapse:collapse;">'.LF;
        $html .= '<tr>'.LF;
        $html .= '<td style="vertical-align:center; padding:3px; width:1%;">'.LF;
        $html .= '<span id="item_'.$item->getItemID().'_img"><img src="' . $img . '" style="padding-right:3px;" title=""/></span>';
        $html .= '</td>'.LF;
        $html .= '<td>'.LF;
        $params['iid'] = $linked_iid;
        $html .= ahref_curl( $full_item->getContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       $link_title,
                                       '',
                                       '_self',
                                       $fragment,
                                       '',
                                       '',
                                       '',
                                       '',
                                       '',
                                       '',
                                       '');
         $html .= $this->_getAdditionalInformationAsHTML($type,$full_item);
         $html .= '</td>'.LF;
         $html .= '</tr>'.LF;
         $html .= '</table>'.LF;
         $html .= '   </div>'.LF;
      }

      return $html;
   }


   function _getMaterialItemFiles($item, $with_links=true){
      $retour = '';
      $file_list='';
      $files = $item->getFileListWithFilesFromSections();
      $files->sortby('filename');
      $file = $files->getFirst();
      $user = $this->_environment->getCurrentUser();
      while ($file) {
         $url = $file->getUrl();
         $displayname = $file->getDisplayName();
         $filesize = $file->getFileSize();
         $fileicon = $file->getFileIcon();
         if ($with_links and $this->_environment->inProjectRoom() || (!$this->_environment->inProjectRoom() and ($item->isPublished() || $user->isUser())) ) {
            if ( isset($_GET['mode'])
                 and $_GET['mode']=='print'
                 and ( empty($_GET['download'])
                       or $_GET['download'] != 'zip'
                     )
               ) {
               $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
            } else {
               if(in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
                  $this->_with_slimbox = true;
                  // jQuery
                  //$file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                  $file_list.='<a href="'.$url.'" rel="lightbox-gallery'.$item->getItemID().'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                  // jQuery
               }else{
                  $file_list.='<a href="'.$url.'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" target="blank" >'.$fileicon.'</a> ';
               }
            }
         } else {
            $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
         }
         $file = $files->getNext();
      }
      if (!empty($file_list)){
         $file_list = '</td><td style="float:right; padding:3px;">'.$file_list;

      }
      return $retour.$file_list;
   }

   function _getDiscussionItemFiles($item, $with_links=true){
      $retour = '';
      $file_list='';
      $files = $item->getFileListWithFilesFromArticles();
      $files->sortby('filename');
      $file = $files->getFirst();
      $user = $this->_environment->getCurrentUser();
      while ($file) {
         $url = $file->getUrl();
         $displayname = $file->getDisplayName();
         $filesize = $file->getFileSize();
         $fileicon = $file->getFileIcon();
         if ($with_links and $this->_environment->inProjectRoom() || (!$this->_environment->inProjectRoom() and ($item->isPublished() || $user->isUser())) ) {
            if ( isset($_GET['mode'])
                 and $_GET['mode']=='print'
                 and ( empty($_GET['download'])
                       or $_GET['download'] != 'zip'
                     )
               ) {
               $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
            } else {
              if(in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
                      $this->_with_slimbox = true;
                      // jQuery
                      //$file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                      $file_list.='<a href="'.$url.'" rel="lightbox-gallery'.$item->getItemID().'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                      // jQuery
                  }else{
                     $file_list.='<a href="'.$url.'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" target="blank" >'.$fileicon.'</a> ';
                  }
              }
         } else {
            $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
         }
         $file = $files->getNext();
      }
      if (!empty($file_list)){
        $file_list = '</td><td style="float:right; padding:3px;">'.$file_list;

      }
      return $retour.$file_list;
   }

  function _getTodoItemFiles($item, $with_links=true){
      $retour = '';
      $file_list='';
      $files = $item->getFileListWithFilesFromSteps();
      $files->sortby('filename');
      $file = $files->getFirst();
      $user = $this->_environment->getCurrentUser();
      while ($file) {
         $url = $file->getUrl();
         $displayname = $file->getDisplayName();
         $filesize = $file->getFileSize();
         $fileicon = $file->getFileIcon();
         if ($with_links and $this->_environment->inProjectRoom() || (!$this->_environment->inProjectRoom() and ($item->isPublished() || $user->isUser())) ) {
            if ( isset($_GET['mode'])
                 and $_GET['mode']=='print'
                 and ( empty($_GET['download'])
                       or $_GET['download'] != 'zip'
                     )
               ) {
               $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
            } else {
              if(in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
                      $this->_with_slimbox = true;
                      // jQuery
                      //$file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                      $file_list.='<a href="'.$url.'" rel="lightbox-gallery'.$item->getItemID().'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                      // jQuery
                  }else{
                     $file_list.='<a href="'.$url.'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" target="blank" >'.$fileicon.'</a> ';
                  }
              }
         } else {
            $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
         }
         $file = $files->getNext();
      }
      if (!empty($file_list)){
        $file_list = '</td><td style="float:right; padding:3px;">'.$file_list;

      }
      return $retour.$file_list;
   }

   function _getItemFiles($item, $with_links=TRUE){
      $retour='';
      $file_list='';
      $files = $item->getFileList();
      $files->sortby('filename');
      $file = $files->getFirst();
      $user = $this->_environment->getCurrentUser();
      while ($file) {
         $url = $file->getUrl();
         $displayname = $file->getDisplayName();
         $filesize = $file->getFileSize();
         $fileicon = $file->getFileIcon();
         if ( $with_links
              and $this->_environment->inProjectRoom()
              or ( !$this->_environment->inProjectRoom()
                   and ( $item->isPublished()
                         or $user->isUser()
                       )
                 )
            ) {
            if ( isset($_GET['mode'])
                 and $_GET['mode']=='print'
                 and ( empty($_GET['download'])
                       or $_GET['download'] != 'zip'
                     )
               ) {
               $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
            } else {
               if ( ( empty($_GET['download'])
                      or $_GET['download'] != 'zip'
                    )
                    and
                    ( mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'png')
                      or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpg')
                      or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpeg')
                      or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'gif')
                    )
                  ) {
                  $this->_with_slimbox = true;
                  // jQuery
                  //$file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                  $file_list.='<a href="'.$url.'" rel="lightbox-gallery'.$item->getItemID().'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                  // jQuery
               } else {
                  $file_list.='<a href="'.$url.'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" target="blank" >'.$fileicon.'</a> ';
               }
            }
         } else {
            $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
         }
         $file = $files->getNext();
      }
      if (!empty($file_list)){
        $file_list = '</td><td style="float:right; padding:3px;">'.$file_list;

      }
      return $retour.$file_list;
   }

   function _getMaterialItemAuthor($item){
         $author = $item->getAuthor();
         $author = $this->_compareWithSearchText($author);
         return $this->_text_as_html_short($author);
   }

   function _getMaterialItemPublishingDate($item){
      $publishing_date = $this->_compareWithSearchText($item->getPublishingDate());
      return $this->_text_as_html_short($publishing_date);
   }

   function _getAdditionalInformationAsHTML($type,$item){
      $html = '';
      switch ( mb_strtoupper($type, 'UTF-8') ) {
        case 'ANNOUNCEMENT':
           $html .= ' '.$this->_getItemFiles($item, true);
           break;
        case 'DATE':
           $html .= ' '.$this->_getItemFiles($item, true);
           break;
        case 'DISCUSSION':
           $html .= ' '.$this->_getDiscussionItemFiles($item, true);
           break;
        case 'MATERIAL':
           $author_text = $this->_getMaterialItemAuthor($item);
           $year_text = $this->_getMaterialItemPublishingDate($item);
           $bib_kind = $item->getBibKind() ? $item->getBibKind() : 'none';
           if (!empty($author_text) and $bib_kind !='none'){
              if (!empty($year_text)){
                 $year_text = ', '.$year_text;
              }else{
                 $year_text = '';
              }
              $html .= '<span style="font-size:8pt;"> ('.$this->_getMaterialItemAuthor($item).$year_text.')'.'</span>';
           }
           $html .= ' '.$this->_getMaterialItemFiles($item, true);
           break;
        case 'TODO':
           $html .= ' '.$this->_getTodoItemFiles($item, true);
           break;
        default:
           $html .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_detail_view('.__LINE__.') ';
           break;
      }
      if (
           (!empty($this->_sellist) and $this->_sellist !='new')
           or !empty($this->_selbuzzword)
           or !empty($this->_selmatrix)
         ){
         $params = $this->_environment->getCurrentParameterArray();
         $params['delete_item'] = $item->getItemID();
         $text = '';
         if (!empty($this->_selbuzzword)){
            $text = $this->_translator->getMessage('ENTRY_DELETE_ENTRY_FROM_BUZZWORD');
         }elseif(!empty($this->_sellist)){
            $text = $this->_translator->getMessage('ENTRY_DELETE_ENTRY_FROM_MYLIST');
         }elseif(!empty($this->_selmatrix)){
            $text = $this->_translator->getMessage('ENTRY_DELETE_ENTRY_FROM_MATRIX');
         }
         $image = '<img src="images/commsyicons/16x16/delete.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('ENTRY_DELETE_MYLIST').'"/>'.LF;
         $html .= '</td><td style="padding:3px; width:25px; text-align:right;">'.LF;
         $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_ENTRY_TYPE,
                                       'index',
                                       $params,
                                       $image,
                                       $text).LF;

      }
      return $html;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    */
   function _getItemTitle($item){
      $title = $item->getTitle();
      $title = $this->_compareWithSearchText($title);
      $params = array();
      $params['iid'] = $item->getItemID();
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_ANNOUNCEMENT_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($title),
                           '', '', '', '', '', '', '', '',
                           CS_ANNOUNCEMENT_TYPE.$item->getItemID());
      unset($params);
      return $title;
   }

   function _initDropDownMenuForList($list){
      $action_array = array();
      $html = '';
      $current_context = $this->_environment->getCurrentContextItem();
      //$current_portal = $this->_environment->getCurrentPortalItem();

      $html .= '<script type="text/javascript">'.LF;
      $html .= '<!--'.LF;
      $html .= 'var dropDownForLists = new Array(';

      $list_item = $list->getFirst();

      $first_list = true;
      while($list_item){
         //if ( $current_context->isOpen() ) {
            $action_array = array();
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
               $temp_array['dropdown_image']  = "new_icon_".$list_item->getItemID();
               $temp_array['text']  = $text_new;
               $temp_array['image'] = $image_new;
               $temp_array['href']  = $href_new;
               $action_array[] = $temp_array;
               unset($temp_array);
            }
         //}

         unset($current_context);

         #$temp_array = array();
         #$temp_array['dropdown_image']  = "new_icon_".$list_item->getItemID();
         #$temp_array['text']  = '';
         #$temp_array['image'] = 'seperator';
         #$temp_array['href']  = '';
         #$action_array[] = $temp_array;

         foreach($this->_dropdown_rubrics_new as $rubric){
            //if ( $current_context->isOpen()) {
               if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                  $image_import = 'images/commsyicons_msie6/22x22/'.$this->_dropdown_image_array[$rubric].'.gif';
               } else {
                  $image_import = 'images/commsyicons/22x22/'.$this->_dropdown_image_array[$rubric].'.png';
               }
               $params = array();
               $params['iid'] = 'NEW';
               //$params['linked_item'] = $this->_item->getItemID();
               $params['mylist_id'] = $list_item->getItemID();
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
                  $temp_array['dropdown_image']  = "new_icon_".$list_item->getItemID();
                  $temp_array['text']  = $text_import;
                  $temp_array['image'] = $image_import;
                  $temp_array['href']  = $href_import;
                  $action_array[] = $temp_array;
                  unset($temp_array);
               }
            //}
         }

         #$action_array = array_merge($action_array, $this->_getAdditionalDropDownEntries());

         // init drop down menu
         if ( !empty($action_array)
              and count($action_array) > 1
            ) {
            #$html .= '<script type="text/javascript">'.LF;
            #$html .= '<!--'.LF;
            #$html .= 'var dropDownMenus = new Array(new Array("new_icon",new Array(';
            //$html .= 'var dropDownForList_'.$list_item->getItemID().' = new Array(';
            if ( $first_list ) {
               $first_list = false;
            } else {
               $html .= ',';
            }
            $html .= 'new Array('.$list_item->getItemID().',';
            $html .= 'new Array(';
            $first = true;
            foreach ($action_array as $action) {
               if ( $first ) {
                  $first = false;
               } else {
                  $html .= ',';
               }
               $html .= 'new Array("'.$action['dropdown_image'].'","'.$action['image'].'","'.$action['text'].'","'.$action['href'].'")';
            }
            $html .= ')';
            $html .= ')';
            #$html .= '-->'.LF;
            #$html .= '</script>'.LF;
         }
         $list_item = $list->getNext();
      }
      $html .= ');'.LF;
      $html .= '-->'.LF;
      $html .= '</script>'.LF;
      return $html;
   }
   
   function _getTagContentAsHTMLWithJavascript($item = NULL, $ebene = 0,$selected_id = 0, $father_id_array, $distance = 0, $with_div=false) {
      // MUSEUM
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['from']);
      $i = 0;
      while($i <= count($father_id_array)){
        if (isset($params['seltag_'.$i])){
           unset($params['seltag_'.$i]);
        }
        $i++;
      }
      $is_selected = false;
      if ( isset($item) ) {
         $list = $item->getChildrenList();
         if ( isset($list) and !$list->isEmpty() ) {
            if($with_div){
               if(isset($_GET['seltag'])){
                  $html .= '<div id="tag_tree_privateroom" name="tag_tree_detail">';
               } else {
                  $html .= '<div id="tag_tree_privateroom">';
               }
            }
            $html .= '<ul>'.LF;
            $current_item = $list->getFirst();
            $distance = $distance +1;
            $font_weight ='normal';
            $font_color = 30;
            $font_style = 'normal';
            while ( $current_item ) {
               $is_selected = false;
               $id = $current_item->getItemID();
               $link_name = '';
               if ( empty($selected_id) ){
                  $tag2tag_manager = $this->_environment->getTag2TagManager();
                  $count = count($tag2tag_manager->getFatherItemIDArray($id));
                  $font_size = round(13 - (($count*0.2)+$count));
                  if ($font_size < 8){
                     $font_size = 8;
                  }
                  $font_color = 20 + $this->getTagColorLogarithmic($count);
               }else{
                  if ( in_array($id,$father_id_array) ){
                     $tag2tag_manager = $this->_environment->getTag2TagManager();
                     $id_array = $tag2tag_manager->getFatherItemIDArray($id);
                     $count = 0;
                     foreach($id_array as $temp_id){
                        if ( !in_array($temp_id,$father_id_array) ){
                           $count ++;
                        }
                     }
                     if( !isset($id_array[0]) and isset($father_id_array[0]) ){
                        $count = 1;
                     }
                     $font_size = round(13 - (($count*0.2)+$count));
                     if ($font_size < 8){
                        $font_size = 8;
                     }
                     $font_color = 20 + $this->getTagColorLogarithmic($count);
                     $font_weight = 'bold';
                     $font_style = 'normal';
                  }else{
                     $tag2tag_manager = $this->_environment->getTag2TagManager();
                     $id_array = $tag2tag_manager->getFatherItemIDArray($id);
                     $count = 0;
                     $found = false;
                     if ( isset($id_array[0]) ){
                        foreach($id_array as $temp_id){
                           if ( !in_array($temp_id,$father_id_array) ){
                              $count ++;
                           }else{
                             $found = true;
                           }
                        }
                        if (!$found){
                           $count = $count + count($father_id_array);
                        }
                     }elseif( !isset($id_array[0]) and isset($father_id_array[0]) ){
                        $count = count($father_id_array);
                     }
                     $font_size = round(13 - (($count*0.2)+$count));
                     if ($font_size < 8){
                        $font_size = 8;
                     }
                     $font_color = 20 + $this->getTagColorLogarithmic($count);
                     $font_weight='normal';
                     $font_style = 'normal';
                  }
               }
               if ($current_item->getItemID() == $selected_id){
                  $is_selected = true;
                  $font_size = 14;
                  $font_color = 20;
                  #$font_style = 'normal';
                  $link_name = 'selected';
               }
               $color = 'rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
               $title = $this->_text_as_html_short($current_item->getTitle());
               if (!$is_selected){
                  $params['seltag_'.$ebene] = $current_item->getItemID();
                  if( isset($params['seltag']) ){
                     $i = $ebene+1;
                     while( isset($params['seltag_'.$i]) ){
                        unset($params['seltag_'.$i]);
                        $i++;
                     }
                  }
                  $params['seltag'] = 'yes';
                  if ( $this->_environment->inPrivateRoom()
                       and $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                       and $this->_display_mode == 'flash'
                     ) {
                     $html .= '<li id="' . $current_item->getItemID() . '" data="StudyLog: \'' . $current_item->getItemID() . '\'" style="color:#545454; font-style:normal; font-size:9pt; font-weight:normal;">'.LF;
                     $html .= '<a href="javascript:callStudyLogSortByTagId('.$current_item->getItemID().')">'.$title.'</a>'.LF;
                  } else {
                     $link = curl($this->_environment->getCurrentContextID(),
                                         $this->_environment->getCurrentModule(),
                                         $this->_environment->getCurrentFunction(),
                                         $params);
                     $html .= '<li id="' . $current_item->getItemID() . '" data="url: \'' . $link . '\'" style="color:#545454; font-style:normal; font-size:9pt; font-weight:normal;">'.LF;
                     $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                         $this->_environment->getCurrentModule(),
                                         $this->_environment->getCurrentFunction(),
                                         $params,
                                         $title,
                                         $title,'','','','','','style="color:#545454; font-size:9pt;"').LF;
                  }
               }else{
                  $params['name'] = $link_name;
                  $link = curl($this->_environment->getCurrentContextID(),
                                         $this->_environment->getCurrentModule(),
                                         $this->_environment->getCurrentFunction(),
                                         $params);
                  $html .= '<li id="' . $current_item->getItemID() . '" data="url: \'' . $link . '\'" style="color:#000000; font-style:normal; font-size:9pt; font-weight:bold;">'.LF;
                  $html .= $title.LF;
               }
               $html .= $this->_getTagContentAsHTMLWithJavascript($current_item, $ebene+1, $selected_id, $father_id_array, $distance);
               $current_item = $list->getNext();
               $html.='</li>'.LF;
            }
            $html.='</ul>'.LF;
            if($with_div){
               $html .= '</div>'.LF;
            }
         }
      }
      return $html;
   }
   
   private function _getSelectedTagArray () {
      return $this->_selected_tag_array;
   }

   public function setSelectedTagArray ($array) {
      $this->_selected_tag_array = $array;
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
}
?>