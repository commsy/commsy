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
include_once('classes/cs_reader_manager.php');
include_once('functions/text_functions.php');

/**
 *  class for CommSy list-view: material
 */
class cs_material_index_view extends cs_index_view {

   /** array of ids in clipboard*/
   var $_clipboard_id_array=array();
   var $_selected_institution = NULL;
   var $_available_institutions = NULL;
   var $_selected_topic = NULL;
   var $_available_topics = NULL;
   var $_selected_group = NULL;
   var $_available_groups = NULL;


   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environemt of the commsy
    * @param string  viewname               e.g. material_list
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_material_index_view ($params) {
      $this->cs_index_view($params);
      $this->setTitle($this->_translator->getMessage('MATERIAL_INDEX'));
      $this->_show_buzzword_box = true;
   }

   function setClipboardIDArray($cia) {
      $this->_clipboard_id_array = $cia;
   }

   function getClipboardIDArray() {
      return $this->_clipboard_id_array;
   }


   function _getAdditionalRestrictionBoxAsHTML($field_length=14.5){
      $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      $selected_value = $this->_attribute_limit;
      $width = '235';
      $context_item = $this->_environment->getCurrentContextItem();
      $html = '';
      $html .= '<div class="infocolor" style="text-align:left; padding-bottom:5px; font-size: 10pt;">';
      $html .= $this->_translator->getMessage('COMMON_RESTRICT_SEARCH').'<br />'.LF;
      if (isset($this->_search_text) and !empty($this->_search_text) ){
         $html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="attribute_limit" size="1" onChange="javascript:document.indexform.submit()">'.LF;
      }else{
         $html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="attribute_limit" size="1">'.LF;
      }
      $html .= '      <option value="0"';
      if ( !isset($selected_value) || $selected_value == 0 ) {
          $html .= ' selected="selected"';
      }
      $html .= '>*'.$this->_translator->getMessage('MATERIAL_FULL_SEARCH').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
      $html .= '      <option value="1"';
      if ( isset($selected_value) and $selected_value == 'title' ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('MATERIAL_ONLY_TITLE').'</option>'.LF;
      $html .= '      <option value="2"';
      if ( isset($selected_value) and $selected_value == 'author' ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('MATERIAL_ONLY_AUTHOR').'</option>'.LF;


/*      global $c_ftsearch_indexing;
      if ($c_ftsearch_indexing){
         $html .= '      <option value="3"';
         if ( isset($selected_value) and $selected_value == 'file' ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$this->_translator->getMessage('MATERIAL_ONLY_FILE').'</option>'.LF;
      }*/
      $html .= '   </select>'.LF;
      $html .='</div>';
      return $html;
   }


   function getAdditionalRestrictionTextAsHTML(){
/***Activating Code***/
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      if ( !isset($params['selactivatingstatus']) or (isset($params['selactivatingstatus']) and $params['selactivatingstatus'] == 2 ) ){
         $this->_additional_selects = true;
         $html_text ='<tr>'.LF;
         $html_text .='<td>'.LF;
         $html_text .= '<span class="infocolor">'.getMessage('COMMON_ACTIVATION_RESTRICTION').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;
         $html_text .= '<span>'.getMessage('COMMON_SHOW_ONLY_ACTIVATED_ENTRIES').'</span>';
         $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         $new_params['selactivatingstatus'] = 1;
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
      }
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->withActivatingContent()){
         return $html;
      }else{
      	return '';
      }
/*********************/
   }


   function _getAdditionalActionsAsHTML(){
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $params = $this->_environment->getCurrentParameterArray();
      $params['mode']='print';
      if ($current_context->withMaterialImportLink() ){
         $image = '<img src="images/commsyicons/22x22/import.png" style="vertical-align:bottom;" alt="'.getMessage('MATERIAL_IMS_IMPORT').'"/>';
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
                            CS_MATERIAL_TYPE,
                            'ims_import',
                            '',
                            $image,
                            $this->_translator->getMessage('MATERIAL_IMS_IMPORT')).LF;
      }
      return $html;
   }






   function _getTableheadAsHTML($with_links=TRUE) {
      $params = $this->_getGetParamsAsArray();
      $params['from'] = 1;
      $picture ='';
      $html ='';
      $html .= '   <tr class="head">'.LF;
      $html .= '      <td style="width:62%;" class="head" colspan="2">';

      if ( $this->getSortKey() == 'title' ) {
         $params['sort'] = 'title_rev';
         $picture = '&nbsp;<img src="images/sort_up.gif" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'title_rev' ) {
         $params['sort'] = 'title';
         $picture = '&nbsp;<img src="images/sort_down.gif" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'title';
         $picture ='&nbsp;';
      }
      if($with_links) {
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('MATERIAL_TITLE'), '', '', $this->getFragment(),'','','','class="head"');
         $html .= $picture;
      } else {
         $html.=  '<span class="index_link">'.$this->_translator->getMessage('MATERIAL_TITLE').'</span>';
         $html .= $picture;
      }


      $html .= '</td>'.LF;

      $html .= '      <td style="width:25%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'author' ) {
         $params['sort'] = 'author_rev';
         $picture = '&nbsp;<img src="images/sort_up.gif" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'author_rev' ) {
         $params['sort'] = 'author';
         $picture = '&nbsp;<img src="images/sort_down.gif" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'author';
         $picture ='&nbsp;';
      }
      if($with_links) {
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('MATERIAL_AUTHORS'), '', '', $this->getFragment(),'','','','class="head"');
         $html .= $picture;
      } else {
         $html .= '<span class="index_link">'.$this->_translator->getMessage('MATERIAL_AUTHORS').'</span>';
         $html .= $picture;
      }
      $html .= '</td>'.LF;

      $html .= '      <td style="width:13%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'date' ) {
         $params['sort'] = 'date_rev';
         $picture = '&nbsp;<img src="images/sort_up.gif" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'date_rev' ) {
         $params['sort'] = 'date';
         $picture = '&nbsp;<img src="images/sort_down.gif" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'date';
         $picture ='&nbsp;';
      }
      if($with_links) {
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                          $params, $this->_translator->getMessage('COMMON_MODIFIED_AT'), '', '', $this->getFragment(),'','','','class="head"');
         $html .= $picture;
      } else {
         $html .= '<span class="index_link">'.$this->_translator->getMessage('COMMON_MODIFIED_AT').'</span>';
         $html .= $picture;
      }
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }

   function _getTablefootAsHTML() {
      $html  = '   <tr class="list">'.LF;
      $html .= '<td colspan="4" style="padding:0px; margin:0px;">'.LF;
      $html .= '<table style="width:100%; margin:0px; padding:0px; border-collapse:collapse;" summary="Layout">'.LF;
      $html .= '<tr class="list">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions') {
         $html .= '<td class="foot_left" ><input style="font-size:8pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_ATTACH_BUTTON').'" /> <input type="submit"  style="font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'"/>';
      }else{
         $html .= '<td class="foot_left" style="vertical-align:middle;">'.LF;
         $html .= '<span class="select_link">[</span>';
         $params = $this->_environment->getCurrentParameterArray();
         $params['select'] = 'all';
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                          $params, $this->_translator->getMessage('COMMON_ALL_ENTRIES'), '', '', $this->getFragment(),'','','','class="select_link"');
         $html .= '<span class="select_link">]</span>'.LF;

         $html .= $this->_getViewActionsAsHTML();
      }
      $html .= '</td>'.LF;
      $html .= '<td class="foot_right"  style="vertical-align:middle; text-align:right; font-size:8pt;">'.LF;
      if ( $this->hasCheckboxes() ) {
         if (count($this->getCheckedIDs())=='1'){
            $html .= ''.$this->_translator->getMessage('COMMON_SELECTED_ONE',count($this->getCheckedIDs()));
         }else{
            $html .= ''.$this->_translator->getMessage('COMMON_SELECTED',count($this->getCheckedIDs()));
         }
      }
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      $html .= '   </table>'.LF;
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;

   }

   /** get View-Actions of this index view
    * this method returns the index actions as html
    *
    * @return string index actions
    */
   function _getViewActionsAsHTML () {
   	  $user = $this->_environment->getCurrentUserItem();
      if ($this->_clipboard_mode){
         $html = parent::_getViewActionsAsHTML();
      }else{
         $html  = '';
         $html .= '<select name="index_view_action" size="1" style="width:160px; font-size:8pt; font-weight:normal;">'.LF;
         $html .= '   <option selected="selected" value="-1">*'.$this->_translator->getMessage('COMMON_LIST_ACTION_NO').'</option>'.LF;
         $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
         $html .= '   <option value="1">'.$this->_translator->getMessage('COMMON_LIST_ACTION_MARK_AS_READ').'</option>'.LF;
         $html .= '   <option value="2">'.$this->_translator->getMessage('COMMON_LIST_ACTION_COPY').'</option>'.LF;
         $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
         if ($user->isModerator()){
            $html .= '   <option value="3">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
         }else{
            $html .= '   <option class="disabled" disabled="disabled">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
         }
         $html .= '</select>'.LF;
         $html .= '<input type="submit" style="width:70px; font-size:8pt;" name="option"';
         $html .= ' value="'.$this->_translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO').'"';
         $html .= '/>'.LF;
      }
      return $html;
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    *
    * @author CommSy Development Group
    */
   function _getItemAsHTML($item, $pos=0, $with_links=TRUE) {
      $html = '';
      $shown_entry_number = $pos + $this->_count_headlines;
      if ($shown_entry_number%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }
      if ($this->_clipboard_mode){
         $sort_criteria = $item->getContextID();
         if ( $sort_criteria != $this->_last_sort_criteria ) {
            $this->_last_sort_criteria = $sort_criteria;
            $this->_count_headlines ++;
            $room_manager = $this->_environment->getProjectManager();
            $sort_room = $room_manager->getItem($sort_criteria);
            $html .= '                     <tr class="list"><td '.$style.' width="100%" style="font-weight:bold;" colspan="5">'."\n";
            if ( empty($sort_room) ) {
               $community_manager = $this->_environment->getCommunityManager();
               $sort_community = $community_manager->getItem($sort_criteria);
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM').'&nbsp;'.$this->_translator->getMessage('COMMON_COMMUNITY_ROOM_TITLE').'&nbsp;"'.$sort_community->getTitle().'"'."\n";
            } elseif( $sort_room->isPrivateRoom() ){
               $user = $this->_environment->getCurrentUserItem();
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM_PRIVATEROOM').'&nbsp;"'.$user->getFullname().'"'.LF;
            } elseif( $sort_room->isCommunityRoom() ){
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM_COMMUNITYROOM').'&nbsp;"'.$sort_room->getTitle().'"'.LF;
            }elseif( $sort_room->isGroupRoom() ){
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM_GROUPROOM').'&nbsp;"'.$sort_room->getTitle().'"'.LF;
            }else {
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM_PROJECTROOM').'&nbsp;"'.$sort_room->getTitle().'"'.LF;
            }
            $html .= '                     </td></tr>'."\n";
            if ( $style=='class="odd"' ){
               $style='class="even"';
            }else{
               $style='class="odd"';
            }
         }
      }
      $html  .= '   <tr class="list">'.LF;
      $checked_ids = $this->getCheckedIDs();
      $dontedit_ids = $this->getDontEditIDs();
      $key = $item->getItemID();
      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
        $html .= '      <td '.$style.' style="vertical-align:middle;" width="2%">'.LF;
         $html .= '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" type="checkbox" onClick="quark(this)" name="attach['.$key.']" value="1"';
/***Activating Code***/
         $user = $this->_environment->getCurrentUser();
         if($item->isNotActivated() and !($item->getCreatorID() == $user->getItemID() or $user->isModerator()) ){
            $html .= ' disabled="disabled"'.LF;
         }elseif ( isset($checked_ids)
              and !empty($checked_ids)
              and in_array($key, $checked_ids)
            ) {
            $html .= ' checked="checked"'.LF;
            if ( in_array($key, $dontedit_ids) ) {
               $html .= ' disabled="disabled"'.LF;
            }
         }
/*********************/
         $html .= '/>'.LF;
         $html .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
         $html .= '      </td>'.LF;
/***Activating Code***/
         if ($item->isNotActivated()){
            $title = $item->getTitle();
            $title = $this->_compareWithSearchText($title);
            $user = $this->_environment->getCurrentUser();
            if($item->getCreatorID() == $user->getItemID() or $user->isModerator()){
               $params = array();
               $params['iid'] = $item->getItemID();
               $title = ahref_curl( $this->_environment->getCurrentContextID(),
                                  CS_ANNOUNCEMENT_TYPE,
                                  'detail',
                                  $params,
                                  $title,
                                  '','', '', '', '', '', '', '',
                                  CS_ANNOUNCEMENT_TYPE.$item->getItemID());
               unset($params);
               if ($this->_environment->inProjectRoom()) {
                  $title .= $this->_getItemChangeStatus($item);
                  $title .= $this->_getItemAnnotationChangeStatus($item);
               }
            }
            $activating_date = $item->getActivatingDate();
            if (strstr($activating_date,'9999-00-00')){
               $title .= BR.getMessage('COMMON_NOT_ACTIVATED');
            }else{
               $title .= BR.getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($item->getActivatingDate());
            }
            $title = '<span class="disabled">'.$title.'</span>';
            $html .= '      <td '.$style.'>'.$title.LF;
         }else{
             if($with_links) {
                $html .= '      <td '.$style.'>'.$this->_getItemTitle($item).LF;
             } else {
                $title = $this->_text_as_html_short($item->getTitle());
                $html .= '      <td '.$style.'>'.$title.LF;
             }
         }
/*********************/
      }else{
            if($with_links) {
               $html .= '      <td colspan="2" '.$style.'>'.$this->_getItemTitle($item).LF;
            } else {
               $title = $this->_text_as_html_short($item->getTitle());
               $html .= '      <td colspan="2" '.$style.'>'.$title.LF;
            }
      }
      $html .= '          '.$this->_getItemFiles($item, $with_links).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemAuthor($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemModificationDate($item).'</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    */
   function _getItemTitle($item){
      $title_text = $item->getTitle();
      $title_text = $this->_compareWithSearchText($title_text);
      $user = $this->_environment->getCurrentUser();
      if (!$this->_environment->inProjectRoom() and !$item->isPublished() and !$user->isUser() ){
         $title = '<span class="disabled">'.$title_text.'</span>'.LF;
      } else {
         $params = array();
         $params['iid'] = $item->getItemID();
         $title = ahref_curl( $this->_environment->getCurrentContextID(),
                              CS_MATERIAL_TYPE,
                              'detail',
                              $params,
                              $this->_text_as_html_short($title_text),
                              '','', '', '', '', '', '', '',
                              CS_MATERIAL_TYPE.$item->getItemID());
         unset($params);
         if ( $this->_environment->inProjectRoom() ) {
            $title .= $this->_getItemChangeStatus($item);
            $title .= $this->_getItemAnnotationChangeStatus($item);
         }
      }
      return $title;
   }

   /** get the publishing info of the item
    * this method returns the item publishing info in the right formatted style
    *
    * @return string publishing info
    *
    * @author CommSy Development Group
    */
   function _getItemPublishingInfo($item){
      $publishing_info = '';
      $user = $this->_environment->getCurrentUser();
      if (!$this->_environment->inProjectRoom() and !$item->isPublished() and !$user->isUser() ){
         $publishing_info = $this->_translator->getMessage('MATERIAL_NOT_PUBLISHED');
      }
      return $publishing_info;
   }


   /** get the author of the item
    * this method returns the item author in the right formatted style
    *
    * @return string author
    *
    * @author CommSy Development Group
    */
   function _getItemAuthor($item){
         $author = $item->getAuthor();
         $author = $this->_compareWithSearchText($author);
         return $this->_text_as_html_short($author);
   }

   /** get the publishing date of the item
    * this method returns the item publishing date in the right formatted style
    *
    * @return string publishing date
    */
   function _getItemPublishingDate($item){
      $publishing_date = $this->_compareWithSearchText($item->getPublishingDate());
//      $publishing_date = '('.$publishing_date.')';
      return $this->_text_as_html_short($publishing_date);
   }

   /** get the lable of the item
    * this method returns the item lable in the right formatted style
    *
    * @return string file lable
    */
   function _getItemLabel($item){
      $label = $item->getLabel();
      if (!empty($label)){
         $label = $this->_compareWithSearchText($label);
      }else{
         $label='';
      }
      return $label;
   }

   /** get the file list of the item
    * this method returns the item file list in the right formatted style
    *
    * @return string file list
    */
   function _getItemFiles($item, $with_links=true){
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
            if ( isset($_GET['mode']) and $_GET['mode']=='print' ) {
               $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
            } else {
	            if ( stristr(strtolower($file->getFilename()),'png')
	                 or stristr(strtolower($file->getFilename()),'jpg')
	                 or stristr(strtolower($file->getFilename()),'jpeg')
	                 or stristr(strtolower($file->getFilename()),'gif')
	               ) {
                      $this->_with_slimbox = true;
	                   $file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
	               }else{
	                  $file_list.='<a href="'.$url.'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" target="blank" >'.$fileicon.'</a> ';
	               }
	           }
         } else {
            $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
         }
         $file = $files->getNext();
      }
      return $retour.$file_list;
   }
}
?>