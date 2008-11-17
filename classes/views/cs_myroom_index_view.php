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

$this->includeClass(CONTEXT_INDEX_VIEW);
include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: news
 */
class cs_myroom_index_view extends cs_context_index_view {

   var $_selected_community_room_limit = NULL;

   var $_selected_time = 0;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_myroom_index_view ($params) {
      $this->cs_context_index_view($params);
      $this->_room_type = CS_MYROOM_TYPE;
      $manager = $this->_environment->getProjectManager();
      if ($this->_environment->inCommunityRoom()) {
         $manager->setContextLimit($this->_environment->getCurrentPortalID());
      }
      $this->_max_activity = $manager->getMaxActivityPointsInCommunityRoom($this->_environment->getCurrentContextID());
   }

    function getSelectedTime () {
       return $this->_selected_time;
    }

    function setSelectedTime ($value) {
       $this->_selected_time = $value;
    }


   function _getTableheadAsHTML () {
      $params = $this->_getGetParamsAsArray();
      $params['from'] = 1;
      $html = '   <tr class="head">'.LF;
      $html .= '      <td class="head" style="width:40%;" colspan="2">';
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

      $html .= '      <td style="width:35%; font-size:8pt;" class="head">';
      $html .= $this->_translator->getMessage('ROOM_CONTACT');
      $html .= '</td>'.LF;

      $html .= '      <td style="width:25%; font-size:8pt;" class="head" colspan="2">';
      $html .= $this->_translator->getMessage('CONTEXT_ACTUALITY');
      $html .= $picture;
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
      $html  = '';
      $html .= '<select name="index_view_action" size="1" style="width:160px; font-size:8pt;">'.LF;
      $html .= '   <option selected="selected" value="-1">*'.$this->_translator->getMessage('COMMON_LIST_ACTION_NO').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      if (!$this->_clipboard_mode){
         $html .= '   <option value="1">'.$this->_translator->getMessage('COMMON_LIST_ACTION_MARK_AS_READ').'</option>'.LF;
         $html .= '   <option value="2">'.$this->_translator->getMessage('CONTEXT_SHOW_ON_HOME').'</option>'.LF;
         $html .= '   <option value="3">'.$this->_translator->getMessage('CONTEXT_NOT_SHOW_ON_HOME').'</option>'.LF;
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

   function _getTablefootAsHTML() {
      $html  = '   <tr class="list">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions') {
         $html .= '<td class="foot_left" colspan="3"><input style="font-size:8pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_ATTACH_BUTTON').'" /> <input type="submit"  style="font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'"/>';
      }else{
         $html .= '<td class="head" colspan="3" style="vertical-align:middle;">'.LF;
         $html .= '<span class="select_link">[</span>';
         $params = $this->_environment->getCurrentParameterArray();
         $params['select'] = 'all';
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                          $params, $this->_translator->getMessage('COMMON_ALL_ENTRIES'), '', '', $this->getFragment(),'','','','class="select_link"');
         $html .= '<span class="select_link">]</span>'.LF;

         $html .= $this->_getViewActionsAsHTML();
      }
      $html .= '</td>'.LF;
      $html .= '<td class="head" colspan="3" style="vertical-align:middle; text-align:right; font-size:8pt;">'.LF;
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

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML ($item, $pos) {
     $shown_entry_number = $pos;
      if ($shown_entry_number%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }
      $html  = '   <tr class="list">'.LF;
      $checked_ids = $this->getCheckedIDs();
      $dontedit_ids = $this->getDontEditIDs();
      $key = $item->getItemID();
      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .= '      <td '.$style.' style="vertical-align:middle;" width="2%">'.LF;
         $html .= '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" type="checkbox" onClick="quark(this)" name="attach['.$key.']" value="1"';
         if ( in_array($key, $checked_ids) ) {
            $html .= ' checked="checked"'.LF;
            if ( in_array($key, $dontedit_ids) ) {
               $html .= ' disabled="disabled"'.LF;
            }
         }
         $html .= '/>'.LF;
         $html .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
         $html .= '      </td>'.LF;
         $html .= '      <td '.$style.' style="font-size:10pt;">'.$this->_getItemTitle($item).'</td>'.LF;
      }else{
         $html .= '      <td colspan="2" '.$style.' style="font-size:10pt;">'.$this->_getItemTitle($item).'</td>'.LF;
      }
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemModerator($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;" colspan="2">'.$this->_getItemShowOnHome($item).'</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }




  /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    */
   function _getItemTitle ($item) {
      $title = $item->getTitle();
      $title = $this->_compareWithSearchText($title);
      $params = array();
      $params['iid'] = $item->getItemID();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_manager = $this->_environment->getUserManager();
      $user_manager->setUserIDLimit($current_user->getUserID());
      $user_manager->setAuthSourceLimit($current_user->getAuthSource());
      $user_manager->setContextLimit($item->getItemID());
      $user_manager->select();
      $user_list = $user_manager->get();
      if (!empty($user_list)){
         $room_user = $user_list->getFirst();
      } else {
         $room_user = '';
      }
      if ($current_user->isRoot()) {
         $may_enter = true;
      } elseif (!empty($room_user)) {
         $may_enter = $item->mayEnter($room_user);
      } else {
         $may_enter = false;
      }
      if ($may_enter) {
         $html = ahref_curl($item->getItemID(), 'home',
                                           'index',
                            '',
                            '<img src="images/door_open_small.gif" style="vertical-align: middle" alt="door open"/>').LF;
      } else {
       $html = '<img src="images/door_closed_small.gif" style="vertical-align: middle" alt="door closed"/>'.LF;
     }
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           'myroom',
                           'detail',
                           $params,
                           $this->_text_as_html_short($title));
      unset($params);
      return $html.' '.$title;
   }



   /** get the moderator of the item
    * this method returns the item moderator in the right formatted style
    *
    * @return string title
    */
   function _getItemShowOnHome($item) {
      $retour = '';
      $user = $this->_environment->getCurrentUserItem();
      $user_id = $user->getUserID();
      if ( $item->isShownInPrivateRoomHome($user_id) ){
         $title = getMessage('CONTEXT_SHOWN_ON_HOME');
      }else{
         $title = getMessage('CONTEXT_NOT_SHOWN_ON_HOME');
      }
      return $title;
   }




   function _getAdditionalFormFieldsAsHTML () {
     $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ($left_menue_status !='disapear') {
        $width = '14.3';
      } else {
        $width = '18.3';
      }
     $html = '';

     // institutions and topics
      $html .= parent::_getAdditionalFormFieldsAsHTML();

     // time (clock pulses)
     $current_context = $this->_environment->getCurrentContextItem();
     $portal_item = $current_context->getContextItem();
     if ( $portal_item->showTime()
          and ( ( $this->_environment->inCommunityRoom() and $current_context->showTime() )
               or ( $this->_environment->inPrivateRoom() )
               )
        ) {
         $seltime = $this->getSelectedTime();
       $time_list = $portal_item->getTimeListRev();

       $this->translatorChangeToPortal();
         $html .= '<div style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_TIME_NAME').BRLF;
       $this->translatorChangeToCurrentContext();
         $html .= '   <select style="width: '.$width.'em; font-size:8pt; margin-bottom:5px;" name="seltime" size="1" onChange="javascript:document.indexform.submit()">'.LF;
         $html .= '      <option value="-3"';
         if ( !isset($seltime) or $seltime == 0 or $seltime == -3) {
            $html .= ' selected="selected"';
         }
         $html .= '>*'.$this->_translator->getMessage('COMMON_NO_SELECTION').'</option>'.LF;
         $html .= '      <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
       if ($time_list->isNotEmpty()) {
         $time_item = $time_list->getFirst();
         while ($time_item) {
               $html .= '      <option value="'.$time_item->getItemID().'"';
               if ( !empty($seltime) and $seltime == $time_item->getItemID() ) {
                  $html .= ' selected="selected"';
               }
               $html .= '>'.$this->_translator->getTimeMessage($time_item->getTitle()).'</option>'.LF;
            $time_item = $time_list->getNext();
         }
       }

         $html .= '      <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
         $html .= '      <option value="-1"';
         if ( isset($seltime) and $seltime == -1) {
            $html .= ' selected="selected"';
         }
         $html .= '>*'.$this->_translator->getMessage('COMMON_NOT_LINKED').'</option>'.LF;
         $html .= '   </select>'.LF;
         $html .= '</div>'.LF;
     }

      return $html;
   }


   function getSelectedCommunityRoom () {
      return $this->_selected_community_room_limit;
   }

   function setSelectedCommunityRoom ($value) {
      $this->_selected_community_room_limit = (int)$value;
   }
}
?>