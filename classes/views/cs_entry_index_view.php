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

   public function __construct ($params) {
      $this->cs_index_view($params);
      $this->setTitle($this->_translator->getMessage('COMMON_ENTRIES'));
      $this->setActionTitle($this->_translator->getMessage('COMMON_ENTRY'));
      $this->_colspan = '4';
   }

   function setSelectedMyList($limit){
    $this->_sellist = $limit;
   }

   function setSelectedBuzzword($limit){
    $this->_selbuzzword = $limit;
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


   function _getSearchBoxAsHTML(){
      $html = '<div class="portlet" id="my_search_box">'.LF;
      $html .= '<div class="portlet-header">'.LF;
      $html .= $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_SEARCH_BOX').LF;
      $html .= '<div style="float:right;"><a name="myentries_remove" style="cursor:pointer;"><img src="images/commsyicons/16x16/delete.png" /></a></div>';
      $html .= '</div>'.LF;
      $html .= '<div class="portlet-content">'.LF;
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
      $html .= '<input id="searchtext" onclick="javascript:resetSearchText(\'searchtext\');" style="width:80%; font-size:10pt; margin-bottom:0px;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/>';
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $html .= '<input type="image" src="images/commsyicons_msie6/22x22/search.gif" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
      } else {
         $html .= '<input type="image" src="images/commsyicons/22x22/search.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
      }
      $html .='</form>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
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
      $html .= '   <input id="new_list" onclick="javascript:resetSearchText(\'new_list\');" style="width:160px; font-size:10pt; margin-bottom:0px;" name="new_list" type="text" size="20" value="'.$this->_text_as_form($this->_translator->getMessage('PRIVATEROOM_MY_LISTS_BOX_NEW_ENTRY')).'"/>';
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
         $html .= '<div style="float:right; padding-top:2px;">'.LF;
         $image = '<img src="images/commsyicons/16x16/copy.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('ENTRY_COPY_MYLIST').'"/>'.LF;
         $params['copy_list'] = $mylist_item->getItemID();
         $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_ENTRY_TYPE,
                                       'index',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('ENTRY_COPY_MYLIST')).LF;
         unset($params['copy_list']);
         $params['delete_list'] = $mylist_item->getItemID();
         $image = '<img src="images/commsyicons/16x16/delete.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('ENTRY_DELETE_MYLIST').'"/>'.LF;
         $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_ENTRY_TYPE,
                                       'index',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('ENTRY_DELETE_MYLIST')).LF;
         $html .= '</div>'.LF;
         unset($params['delete_list']);
         $html .= ' <p class="droppable_list" id="mylist_'.$mylist_item->getItemID().'">'.LF;
         $params['sellist'] = $mylist_item->getItemID();
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
      $html .= '</div>'.LF;



      $html .= '</div>'.LF;
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
   	$myentries_array = $privateroom_item->getMyEntriesDisplayConfig();
   	
      $html  = LF.'<!-- BEGIN OF LIST VIEW -->'.LF;

      $html .= $this->_getIndexPageHeaderAsHTML().LF;
      $html .= '<div style="width:100%; clear:both;">';
      $html .= '<div class="column" style="width:50%;">'.LF;
 #     $html .= $this->_getCreateNewEntryBoxAsHTML().LF;
      if(in_array("my_list_box", $myentries_array)){
         $html .= $this->_getMylistsBoxAsHTML().LF;
      }
      if(in_array("my_buzzword_box", $myentries_array)){
         $html .= $this->_getBuzzwordBoxAsHTML().LF;
      }
      if(in_array("my_matrix_box", $myentries_array)){
         $html .= $this->_getMatrixBoxAsHTML().LF;
      }
      $html .= '</div>'.LF;
      $html .= '<div class="column" style="width:50%;">'.LF;
      if(in_array("my_search_box", $myentries_array)){
         $html .= $this->_getSearchBoxAsHTML().LF;
      }
      if(in_array("my_entries_box", $myentries_array)){
         $html .= $this->_getContentBoxAsHTML().LF;
      }
      $html .= '</div>'.LF;
      $html .= '</div>';
      $html .='<div style="clear:both;">'.LF;
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
      
      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_SEARCH_BOX');
      $temp_array['value'] = "my_search_box";
      if(in_array("my_search_box", $myentries_array)){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;
      
      $temp_array = array();
      $temp_array['dropdown_image']  = "new_icon";
      $temp_array['text']  = $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_LIST_BOX');
      $temp_array['value'] = "my_entries_box";
      if(in_array("my_entries_box", $myentries_array)){
         $temp_array['checked']  = "checked";
      } else {
         $temp_array['checked']  = "";
      }
      $action_array[] = $temp_array;
      
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
         $html .= '-->'.LF;
         $html .= '</script>'.LF;
      }
      return $html;
   }

   function _getContentBoxAsHTML () {
      $list = $this->_list;
      $params = $this->_environment->getCurrentParameterArray();
      $html = '<div class="portlet" id="my_entries_box">'.LF;
      $html .= '<div class="portlet-header">'.LF;
      $html .= $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_LIST_BOX').LF;
      $html .= '<div style="float:right;"><a name="myentries_remove" style="cursor:pointer;"><img src="images/commsyicons/16x16/delete.png" /></a></div>';
      $html .= '</div>'.LF;
      $html .= '<div id="contentbox" class="portlet-content">'.LF;
      if (
          !empty($this->_sellist)
          or !empty($this->_selbuzzword)
          or (!empty($this->_search_text) and $this->_search_text != $this->_translator->getMessage('COMMON_SEARCH_IN_ENTRIES'))
      ){
         $html .= '<table class="description-background" style="width:100%;">'.LF;
         $html .= '<tr>'.LF;
         $html .= '<td style="vertical-align:top;">'.LF;
         $html .= $this->_translator->getMessage('COMMON_RESTRICTIONS').': ';
         $html .= '</td>'.LF;
         $html .= '<td style="text-align:right;">';
         if (!empty($this->_sellist)){
            $html .= '<div>'.LF;
            if ($this->_sellist == 'new'){
               $html .= $this->_translator->getMessage('COMMON_MYLIST_RESTRICTION').': "'.$this->_translator->getMessage('COMMON_NEWEST_ENTRIES').'"';
      	    }else{
      	       $list_manager = $this->_environment->getMyListManager();
      	       $list_item = $list_manager->getItem($this->_sellist);
      	       $html .= $this->_translator->getMessage('COMMON_MYLIST_RESTRICTION').': "'.$list_item->getName().'"';
      	   }
           $new_aparams = $params;
           unset($new_aparams['sellist']);
           $image = '<img src="images/delete_restriction.gif" style="padding-top:3px;" alt="'.$this->_translator->getMessage('ENTRY_DELETE_RESTRICTION').'"/>'.LF;
           $html .= ' '.ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_ENTRY_TYPE,
                                       'index',
                                       $new_aparams,
                                       $image,
                                       $this->_translator->getMessage('ENTRY_DELETE_RESTRICTION')).LF;
           $html .= '</div>'.LF;
         }
         if (!empty($this->_selbuzzword)){
            $html .= '<div>'.LF;
         	$buzzword_manager = $this->_environment->getBuzzwordManager();
      	    $buzzword_item = $buzzword_manager->getItem($this->_selbuzzword);
      	    $html .= $this->_translator->getMessage('COMMON_BUZZWORD_RESTRICTION').': "'.$buzzword_item->getName().'"';
            $new_aparams = $params;
            unset($new_aparams['selbuzzword']);
            $image = '<img src="images/delete_restriction.gif" style="padding-top:3px;" alt="'.$this->_translator->getMessage('ENTRY_DELETE_RESTRICTION').'"/>'.LF;
            $html .= ' '.ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_ENTRY_TYPE,
                                       'index',
                                       $new_aparams,
                                       $image,
                                       $this->_translator->getMessage('ENTRY_DELETE_RESTRICTION')).LF;
            $html .= '</div>'.LF;
         }
         if (!empty($this->_search_text) and $this->_search_text != $this->_translator->getMessage('COMMON_SEARCH_IN_ENTRIES')){
            $html .= '<div>'.LF;
      	    $html .= $this->_translator->getMessage('COMMON_SEARCH_RESTRICTION').': "'.$this->_search_text.'"';
            $new_aparams = $params;
            unset($new_aparams['search']);
            $image = '<img src="images/delete_restriction.gif" style="padding-top:3px;" alt="'.$this->_translator->getMessage('ENTRY_DELETE_RESTRICTION').'"/>'.LF;
            $html .= ' '.ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_ENTRY_TYPE,
                                       'index',
                                       $new_aparams,
                                       $image,
                                       $this->_translator->getMessage('ENTRY_DELETE_RESTRICTION')).LF;
            $html .= '</div>'.LF;
         }
         $html .= '<td>';
         $html .= '</tr>'.LF;
         $html .= '</table>'.LF;
      }
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
      $html_table .= '<table style="width:100%; border:1px solid #CCCCCC;">';
      $html_table .= '<tr>'.LF;
      $html_table .= '<td style="background-color:#CCCCCC;">'.LF;
      $html_table .= '</td>'.LF;
      foreach($matrix_column_title_array as $column_title){
         $html_table .= '<td style="background-color:#CCCCCC;">'.$column_title.LF;
         $html_table .= '</td>'.LF;

      }
      $html_table .= '</tr>'.LF;
      foreach($matrix_row_title_array as $row_key => $row){
         $html_table .= '<tr>'.LF;
         $html_table .= '<td style="background-color:#CCCCCC;">'.$row.LF;
         $html_table .= '</td>'.LF;
         foreach($matrix_column_title_array as $column_key => $column){
            $html_table .= '<td class="droppable_matrix" id="id_'.$row_key.'_'.$column_key.'" style="text-align:center;"><a></a>'.LF;
            $count = $matrix_manager->getEntriesInPosition($column_key,$row_key);
            $html_table .= $count;
            $html_table .= '</td>'.LF;
         }
         $html_table .= '</tr>'.LF;
      }
      $html_table .= '</table>';

      $html .= $html_table.LF;

      $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), 'entry', 'index','').'" method="post" name="matrix-form">'.LF;
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
      while($matrix_item){
         $html .= '<input name="matrix_'.$matrix_item->getItemID().'" value="'.$matrix_item->getItemID().'" checked="checked" type="checkbox">';
         $html .= $matrix_item->getName().BRLF;
         $matrix_item = $matrix_row_list->getNext();
         $count_rows++;
      }
      $html .= '   <input type="hidden" name="new_matrix_row_count" value="'.$count_rows.'"/>'.LF;
      $html .= '   <input id="new_matrix_row" onclick="javascript:resetSearchText(\'new_matrix_row\');" style="width:250px; font-size:10pt; margin-bottom:0px;" name="new_matrix_row" type="text" size="20" value="'.$this->_text_as_form($this->_translator->getMessage('PRIVATEROOM_MATRIX_NEW_ROW_ENTRY')).'"/>'.BR.BRLF;

      $matrix_manager->resetLimits();
      $matrix_manager->setContextLimit($this->_environment->getCurrentContextID());
      $matrix_manager->setColumnLimit();
      $matrix_manager->select();
      $matrix_column_list = $matrix_manager->get();
      $matrix_item = $matrix_column_list->getFirst();
      $count_columns = 0;
      while($matrix_item){
         $html .= '<input name="matrix_'.$matrix_item->getItemID().'" value="'.$matrix_item->getItemID().'" checked="checked" type="checkbox">';
         $html .= $matrix_item->getName().BRLF;
         $matrix_item = $matrix_column_list->getNext();
         $count_columns++;
      }
      $html .= '   <input type="hidden" name="new_matrix_column_count" value="'.$count_columns.'"/>'.LF;
      $html .= '   <input id="new_matrix_column" onclick="javascript:resetSearchText(\'new_matrix_column\');" style="width:250px; font-size:10pt; margin-bottom:0px;" name="new_matrix_column" type="text" size="20" value="'.$this->_text_as_form($this->_translator->getMessage('PRIVATEROOM_MATRIX_NEW_COLUMN_ENTRY')).'"/>'.BRLF;
      $html .= '   <input name="option" value="'.$this->_text_as_form($this->_translator->getMessage('PRIVATEROOM_MATRIX_SAVE_BUTTON')).'" style="width: 250px; font-size: 10pt;" type="submit"/>'.LF;
      $html .='</form>'.LF;

      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
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
      $html .= $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_BUZZWORD_BOX').LF;
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
            $title  = '<span  id="buzzword_'.$buzzword->getItemID().'" class="droppable_buzzword" '.$style_text.'>'.LF;
            $title .= $this->_text_as_html_short($buzzword->getName()).LF;
            $title .= '</span> ';

            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                'entry',
                                'index',
                                $params,
                                $title,$title).LF;
         }
         $buzzword = $buzzword_list->getNext();
      }
      $html .= '<div style="width:100%; text-align:right; padding-right:2px; padding-top:5px;">';
      if ($current_user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         $params['module'] = $this->_environment->getCurrentModule();
         $html .= ahref_curl($this->_environment->getCurrentContextID(),'buzzwords','edit',$params,$this->_translator->getMessage('COMMON_EDIT')).LF;
         unset($params);
      } else {
         $html .= '<span class="disabled">'.$this->_translator->getMessage('COMMON_EDIT').'</span>'.LF;
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
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
      if (!$this->_environment->inPrivateRoom()){
         $html = '<h2 class="pagetitle">'.$this->_translator->getMessage('ENTRY_INDEX');
         $html .= '</h2>'.LF;
      } else {
         $html = '<div style="width:100%;"><div style="float:left;"><h2 class="pagetitle">'.$this->_translator->getMessage('ENTRY_INDEX');
         $html .= '</h2></div>'.LF;
         $html .= '<div style="float:right;"><a href="#"><img id="new_icon" src="images/commsyicons/48x48/config/privateroom_home_options.png" height=24></a></div></div>';
      }
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
               if ( mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'png')
                    or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpg')
                    or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpeg')
                    or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'gif')
                  ) {
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
              if ( mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'png')
                    or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpg')
                    or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpeg')
                    or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'gif')
                  ) {
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
              if ( mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'png')
                    or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpg')
                    or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpeg')
                    or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'gif')
                  ) {
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
         ){
         $params = $this->_environment->getCurrentParameterArray();
         $params['delete_item'] = $item->getItemID();
         $text = '';
         if (!empty($this->_selbuzzword)){
            $text = $this->_translator->getMessage('ENTRY_DELETE_ENTRY_FROM_BUZZWORD');
         }elseif(!empty($this->_sellist)){
            $text = $this->_translator->getMessage('ENTRY_DELETE_ENTRY_FROM_MYLIST');
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

}
?>