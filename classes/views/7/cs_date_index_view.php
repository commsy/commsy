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

$this->includeClass(ROOM_INDEX_VIEW);
include_once('classes/cs_reader_manager.php');

/**
 *  class for CommSy list view: date
 */
class cs_date_index_view extends cs_room_index_view {

   /** array of ids in clipboard*/
   var $_clipboard_id_array = array();

   var $_selected_displaymode = NULL;
   var $_available_displaymode = NULL;
   var $_selected_status = NULL;
   var $_display_mode = NULL;
   var $_alternative_display = 'show';
   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of commsy
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    *
    * @author CommSy Development Group
    */
   function cs_date_index_view ($params) {
      $this->cs_room_index_view($params);
      $this->setTitle($this->_translator->getMessage('DATES_HEADER'));
      $this->setActionTitle($this->_translator->getMessage('COMMON_DATES'));
   }


   function setClipboardIDArray($cia) {
      $this->_clipboard_id_array = $cia;
   }

   function getClipboardIDArray() {
      return $this->_clipboard_id_array;
   }

   function setDisplayMode($status){
      $this->_display_mode = $status;
   }

   function setSelectedStatus ($status) {
      $this->_selected_status = (int)$status;
   }

   function getSelectedStatus () {
      return $this->_selected_status;
   }

   function _getGetParamsAsArray() {
      $params = parent::_getGetParamsAsArray();
      $params['selstatus'] = $this->getSelectedStatus();
      return $params;
   }


   function _getAdditionalActionsAsHTML(){
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $hash_manager = $this->_environment->getHashManager();
      $params = $this->_environment->getCurrentParameterArray();
      $image = '<img src="images/commsyicons/22x22/abbo.png" style="vertical-align:bottom;" alt="'.getMessage('DATES_ABBO').'"/>';
      $ical_url = '<a title="'.getMessage('DATES_ABBO').'"  href="webcal://';
      $ical_url .= $_SERVER['HTTP_HOST'];
      $ical_url .= str_replace('commsy.php','ical.php',$_SERVER['PHP_SELF']);
      $ical_url .= '?cid='.$_GET['cid'].'&amp;hid='.$hash_manager->getICalHashForUser($current_user->getItemID()).'">'.$image.'</a>'.LF;
      $html .= $ical_url;
      $image = '<img src="images/commsyicons/22x22/export.png" style="vertical-align:bottom;" alt="'.getMessage('DATES_EXPORT').'"/>';
      $html .= '<a title="'.getMessage('DATES_EXPORT').'"  href="ical.php?cid='.$_GET['cid'].'&amp;hid='.$hash_manager->getICalHashForUser($current_user->getItemID()).'">'.$image.'</a>'.LF;
      unset($params);
      if ( $this->_environment->inPrivateRoom() ) {
         if ( $this->_with_modifying_actions ) {
            $params['import'] = 'yes';
            $image = '<img src="images/commsyicons/22x22/import.png" style="vertical-align:bottom;" alt="'.getMessage('MATERIAL_IMS_IMPORT').'"/>';
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                               'ims_import',
                               $params,
                               $image,
                               $this->_translator->getMessage('COMMON_IMPORT_DATES')).LF;
            unset($params);
         } else {
           $image = '<img src="images/commsyicons/22x22/import_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_IMS_IMPORT').'"/>';
           $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION').' "class="disabled">'.$image.'</a>'.LF;
         }
      }
      return $html;
   }

   function _getViewActionsAsHTML () {
      $user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<select name="index_view_action" size="1" style="width:160px; font-size:8pt; font-weight:normal;">'.LF;
      $html .= '   <option selected="selected" value="-1">*'.$this->_translator->getMessage('COMMON_LIST_ACTION_NO').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      if (!$this->_clipboard_mode){
         $html .= '   <option value="1">'.$this->_translator->getMessage('COMMON_LIST_ACTION_MARK_AS_READ').'</option>'.LF;
         $html .= '   <option value="2">'.$this->_translator->getMessage('COMMON_LIST_ACTION_COPY').'</option>'.LF;
         if ($this->_environment->inPrivateRoom()){
            $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
            $html .= '   <option value="3">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
         }else{
            $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
            if ($user->isModerator()){
               $html .= '   <option value="3">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
            }else{
               $html .= '   <option class="disabled" disabled="disabled">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
            }
         }
      }else{
         $html .= '   <option value="1">'.$this->_translator->getMessage('CLIPBOARD_PASTE_BUTTON').'</option>'.LF;
         $html .= '   <option value="2">'.$this->_translator->getMessage('CLIPBOARD_DELETE_BUTTON').'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= '<input type="submit" style="width:70px; font-size:8pt;" name="option"';
      $html .= ' value="'.$this->_translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO').'"';
      $html .= '/>'.LF;

      return $html;
   }



   function _getAdditionalRestrictionBoxAsHTML($field_length=14.5){
      $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $width = '235';
      $context_item = $this->_environment->getCurrentContextItem();
      $html = '';
      $selstatus = $this->getSelectedStatus();
      $html = '<div class="infocolor" style="text-align:left; padding-bottom:5px; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_DATE_STATUS').BRLF;
      $html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="selstatus" size="1" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '      <option value="2"';
      if ( empty($selstatus) || $selstatus == 2 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>*'.$this->_translator->getMessage('COMMON_NO_SELECTION').'</option>'.LF;

      $html .= '   <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
      $html .= '      <option value="3"';
      if ( !empty($selstatus) and $selstatus == 3 ) {
         $html .= ' selected="selected"';
      }
      $text = $this->_translator->getMessage('DATES_PUBLIC');
      $html .= '>'.$text.'</option>'.LF;

      $html .= '      <option value="4"';
      if ( !empty($selstatus) and $selstatus == 4 ) {
         $html .= ' selected="selected"';
      }
      $text = $this->_translator->getMessage('DATES_NON_PUBLIC');
      $html .= '>'.$text.'</option>'.LF;

      $html .= '   </select>'.LF;
      $html .='</div>';
      return $html;
   }


   function getAdditionalRestrictionTextAsHTML(){
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->withActivatingContent()){
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
      }
      $params = $this->_environment->getCurrentParameterArray();
      if ( !isset($params['selstatus']) or $params['selstatus'] == 4  or $params['selstatus'] == 3 ){
         $this->_additional_selects = true;
         $html_text ='<tr>'.LF;
         $html_text .='<td>'.LF;
         $html_text .= '<span class="infocolor">'.getMessage('COMMON_DATE_STATUS').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;
         $status_text = '';
         if (isset($params['selstatus']) and $params['selstatus'] == 4){
            $status_text = $this->_translator->getMessage('DATES_NON_PUBLIC');
         }elseif(!isset($params['selstatus']) or $params['selstatus'] == 3){
            $status_text = $this->_translator->getMessage('DATES_PUBLIC');
         }
         $html_text .= '<span>'.$status_text.'</span>';
         $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         $new_params['selstatus'] = 2;
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
      }
      return $html;
   }


   function _getTableheadAsHTML() {
      $params = $this->_getGetParamsAsArray();
      $params['from'] = 1;
      $html = '   <tr class="head">'.LF;
      $html .= '      <td class="head" style="width:55%;" colspan="2">';
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
      $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('COMMON_TITLE'), '', '', $this->getFragment(),'','','','class="head"');
      $html .= $picture;
      $html .= '</td>'.LF;

      $html .= '      <td style="width:20%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'time' ) {
         $params['sort'] = 'time_rev';
         $picture = '&nbsp;<img src="images/sort_up.gif" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'time_rev' ) {
         $params['sort'] = 'time';
         $picture = '&nbsp;<img src="images/sort_down.gif" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'time';
         $picture ='&nbsp;';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('DATES_TIME'), '', '', $this->getFragment(),'','','','class="head"');
      $html .= $picture;
      $html .= '</td>'.LF;

      $html .= '      <td style="width:25%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'place' ) {
         $params['sort'] = 'place_rev';
         $picture = '&nbsp;<img src="images/sort_up.gif" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'place_rev' ) {
         $params['sort'] = 'place';
         $picture = '&nbsp;<img src="images/sort_down.gif" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'place';
         $picture ='&nbsp;';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('DATES_PLACE'), '', '', $this->getFragment(),'','','','class="head"');
      $html .= $picture;
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }

   function _getTablefootAsHTML() {
      $html  = '   <tr class="list">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions') {
         $html .= '<td class="foot_left" colspan="3"><input style="font-size:8pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_ATTACH_BUTTON').'" /> <input type="submit"  style="font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'"/>';
      }else{
         $html .= '<td class="foot_left" colspan="3" style="vertical-align:middle;">'.LF;
         $html .= '<span class="select_link">[</span>';
         $params = $this->_environment->getCurrentParameterArray();
         $params['select'] = 'all';
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                          $params, $this->_translator->getMessage('COMMON_ALL_ENTRIES'), '', '', $this->getFragment(),'','','','class="select_link"');
         $html .= '<span class="select_link">]</span>'.LF;

         $html .= $this->_getViewActionsAsHTML();
      }
      $html .= '</td>'.LF;
      $html .= '<td class="foot_right" style="vertical-align:middle; text-align:right; font-size:8pt;">'.LF;
      if ( $this->hasCheckboxes() ) {
         if (count($this->getCheckedIDs())=='1'){
            $html .= ''.$this->_translator->getMessage('COMMON_SELECTED_ONE',count($this->getCheckedIDs()));
         }else{
            $html .= ''.$this->_translator->getMessage('COMMON_SELECTED',count($this->getCheckedIDs()));
         }
      }
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }

   function _getListInfosAsHTML ($title) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title">'.LF;
      $html .= $this->_getBrowsingIconsAsHTML().LF;
      $html .= '<div style="white-space:nowrap;">'.getMessage('COMMON_PAGE').' '.$this->_getForwardLinkAsHTML().'</div>'.LF;
      $html .='</div>'.LF;


      $html .= '<div class="right_box_main" >'.LF;
      $html .= '<table style="width:100%; padding:0px; margin:0px; border-collapse:collapse;">';
      $html .='<tr>'.LF;
      $html .='<td>'.LF;
      $html .= '<span class="infocolor">'.getMessage('COMMON_LIST_SHOWN_ENTRIES').' </span>';
      $html .='</td>'.LF;
      $html .='<td style="text-align:right;">'.LF;
      $html .= '<span class="index_description">'.$this->_getDescriptionAsHTML().'</span>'.LF;
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .= $this->_getRestrictionTextAsHTML();
      $html .= '</table>'.LF;


      $html .= '<div class="listinfoborder"></div>'.LF;

      $html .= '<table style="width:100%; padding:0px; margin:0px; border-collapse:collapse;">';
      $html .='<tr>'.LF;
      $html .='<td>'.LF;
      $connection = $this->_environment->getCurrentModule();
      $text = '';
      $text .= $this->_translator->getMessage('DATES');
      $html .= '<span class="infocolor">'.getMessage('COMMON_ALL_LIST_ENTRIES',$text).':</span> ';
      $html .='</td>'.LF;
      $html .='<td style="text-align:right;">'.LF;
      $html .= $this->_count_all.''.LF;
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .='<tr>'.LF;
      $html .= '<td class="infocolor">';
      $html .= $this->_translator->getMessage('COMMON_PAGE_ENTRIES').':';
      $html .='</td>'.LF;
      $html .='<td style="text-align:right;">'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      if (!isset($params['mode']) or $params['mode'] == 'browse'){
         $params['mode'] = 'list_actions';
      }
      unset($params['select']);
      if ( $this->_interval == 20 ) {
         $html .= '<span style="color:black">20</span>';
      } else {
         $params['interval'] = 20;
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                   $this->_module,
                                   $this->_function,
                                   $params,
                                   '20',
                                   '',
                                   '',
                                   ''
                                  );
      }

      if ( $this->_interval == 50 ) {
         $html .= ' | <span style="color:black">50</span>';
      } else {
         $params['interval'] = 50;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(),
                                   $this->_module,
                                   $this->_function,
                                   $params,
                                   '50',
                                   '',
                                   '',
                                   ''
                                  );
      }

      if ( $this->_interval == 0 ) {
         $html .= ' | <span style="color:black">'.$this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL').'</span>';
      } else {
         $params['interval'] = 0;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(),
                                   $this->_module,
                                   $this->_function,
                                   $params,
                                   $this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL'),
                                   '',
                                   '',
                                   ''
                                  );
      }
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .='</table>'.LF;

      $html .= '<div class="listinfoborder"></div>'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['week']);
      unset($params['year']);
      unset($params['month']);
      unset($params['presentation_mode']);
      $params['seldisplay_mode'] = 'calendar';
      $html .= '<div style="float:right;">'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$params,$this->_translator->getMessage('DATES_CHANGE_CALENDAR')).'</div>'.LF;
      $html .= '<span class="infocolor">'.getMessage('DATE_ALTERNATIVE_DISPLAY').': </span>'.LF;

      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

     return $html;
   }



   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML($item, $pos=0, $with_links=TRUE) {
      $html = '';
      $shown_entry_number = $pos;
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
            $html .= '                     <tr class="list"><td '.$style.' width="100%" style="font-weight:bold;" colspan="5">'.LF;
            if ( empty($sort_room) ) {
               $community_manager = $this->_environment->getCommunityManager();
               $sort_community = $community_manager->getItem($sort_criteria);
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM').'&nbsp;'.$this->_translator->getMessage('COMMON_COMMUNITY_ROOM_TITLE').'&nbsp;"'.$sort_community->getTitle().'"'."\n";
            } elseif( $sort_room->isPrivateRoom() ){
               $user = $this->_environment->getCurrentUserItem();
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM_PRIVATEROOM').'&nbsp;"'.$user->getFullname().'"'."\n";
            }elseif( $sort_room->isGroupRoom() ){
              $html .= '                        '.$this->_translator->getMessage('COPY_FROM_GROUPROOM').'&nbsp;"'.$sort_room->getTitle().'"'.LF;
            }else {
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM_PROJECTROOM').'&nbsp;"'.$sort_room->getTitle().'"'."\n";
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
      $fileicons = $this->_getItemFiles($item, $with_links);
      if ( !empty($fileicons) ) {
         $fileicons = ' '.$fileicons;
      }
      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .= '      <td '.$style.' style="vertical-align:middle;" width="2%">'.LF;
         $html .= '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" type="checkbox" onClick="quark(this)" name="attach['.$key.']" value="1"';
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
         $html .= '/>'.LF;
         $html .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
         $html .= '      </td>'.LF;
         if ($item->isNotActivated()){
            $title = $item->getTitle();
            $title = $this->_compareWithSearchText($title);
            $user = $this->_environment->getCurrentUser();
            if($item->getCreatorID() == $user->getItemID() or $user->isModerator()){
               $params = array();
               $params['iid'] = $item->getItemID();
               $title = ahref_curl( $this->_environment->getCurrentContextID(),
                                  CS_DATE_TYPE,
                                  'detail',
                                  $params,
                                  $title,
                                  '','', '', '', '', '', '', '',
                                  CS_DATE_TYPE.$item->getItemID());
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
                $html .= '      <td '.$style.'>'.$this->_getItemTitle($item).$fileicons.LF;
             } else {
                $title = $this->_text_as_html_short($item->getTitle());
                $html .= '      <td '.$style.'>'.$title.LF;
             }
         }
      } else {
         $html .= '      <td colspan="2" '.$style.' style="font-size:10pt;">'.$this->_getItemTitle($item).$fileicons.'</td>'.LF;
      }
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemDate($item);
      $time = $this->_getItemTime($item);
      $starting_time = $item->getStartingTime();
      if (!empty($time) and !empty($starting_time)) {
         $html .= ', '.$time;
      }
      $html .='</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemPlace($item).'</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }


   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemTitle($item){
      $title = $item->getTitle();
      $title = $this->_compareWithSearchText($title);
      $params = array();
      $params['iid'] = $item->getItemID();
      if ($item->issetPrivatDate()){
         $title ='<i>'.$this->_text_as_html_short($title).'</i>';
         $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DATE_TYPE,
                           'detail',
                           $params,
                           $title,
                           '','', '', '', '', '', '', '',
                           CS_DATE_TYPE.$item->getItemID());
         $title .= ' <span class="changed"><span style="color:black"><i>['.getMessage('DATE_PRIVATE_ENTRY').']</i></span></span>';
      }else{
         $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DATE_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($title),
                           '', '', '', '', '', '', '', '',
                           CS_DATE_TYPE.$item->getItemID());

         unset($params);
         $title .= $this->_getItemChangeStatus($item);
         $title .= $this->_getItemAnnotationChangeStatus($item);
      }
      return $title;
   }

   /** get the place of the item
    * this method returns the item place in the right formatted style
    *
    * @return string title
    */
   function _getItemPlace($item){
      $place = $item->getPlace();
      if ($item->issetPrivatDate()){
         $title ='<i>'.$this->_text_as_html_short($place).'</i>';
      }else{
         $place = $this->_compareWithSearchText($place);
      }
      return $this->_text_as_html_short($place);
   }

   /** get the time of the item
    * this method returns the item place in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemTime($item){
      $parse_time_start = convertTimeFromInput($item->getStartingTime());
      $conforms = $parse_time_start['conforms'];
      if ($conforms == TRUE) {
         $time = getTimeLanguage($parse_time_start['datetime']);
      } else {
         $time = $item->getStartingTime();
      }
      if ($item->issetPrivatDate()){
         $time ='<i>'.$this->_text_as_html_short($time).'</i>';
      }else{
         $time = $this->_text_as_html_short($this->_compareWithSearchText($time));
      }
      return $time;
   }

   /** get the date of the item
    * this method returns the item place in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemDate($item){
      $parse_day_start = convertDateFromInput($item->getStartingDay(),$this->_environment->getSelectedLanguage());
      $conforms = $parse_day_start['conforms'];
      if ($conforms == TRUE) {
         $date = $this->_translator->getDateInLang($parse_day_start['datetime']);
      } else {
         $date = $item->getStartingDay();
      }
      $date = $this->_compareWithSearchText($date);
      if ($item->issetPrivatDate()){
         $date ='<i>'.$this->_text_as_html_short($date).'</i>';
         return $date;
      }else{
         return $this->_text_as_html_short($date);
      }
   }


}
?>