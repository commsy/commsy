<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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
include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: news
 */
class cs_context_index_view extends cs_index_view {

   var $_max_activity = NULL;

   var $_room_type = NULL;

   var $_selected_institution = NULL;
   var $_available_institutions = NULL;
   var $_selected_topic = NULL;
   var $_available_topics = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_index_view::__construct($params);
   }

   function _getGetParamsAsArray() {
     $current_context = $this->_environment->getCurrentContextItem();
      $params = parent::_getGetParamsAsArray();
      if ($this->_environment->inCommunityRoom()) {
       if ($current_context->withRubric(CS_INSTITUTION_TYPE)) {
            $params['selinstitution'] = $this->getSelectedInstitution();
       }
       if ($current_context->withRubric(CS_TOPIC_TYPE)) {
            $params['seltopic'] = $this->getSelectedTopic();
       }
      }elseif( $this->_environment->inPrivateRoom() or $this->_environment->inCommunityRoom()){
            $params['seltime'] = $this->getSelectedTime();
      }
      return $params;
   }


   function _getTableheadAsHTML () {
      include_once('functions/misc_functions.php');
      $params = $this->_getGetParamsAsArray();
      $params['from'] = 1;
      $html = '   <tr class="head">'.LF;
      $html .= '      <td class="head" style="width:58%;" colspan="2">';
      if ( $this->getSortKey() == 'title' ) {
         $params['sort'] = 'title_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'title_rev' ) {
         $params['sort'] = 'title';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'title';
         $picture ='&nbsp;';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('COMMON_TITLE'), '', '', $this->getFragment(),'','','','class="head"');
      $html .= $picture;
      $html .= '</td>'.LF;

      $html .= '      <td style="width:30%; font-size:8pt;" class="head">';
      $html .= $this->_translator->getMessage('ROOM_CONTACT');
      $html .= '</td>'.LF;

      $html .= '      <td style="width:18%; font-size:8pt;" class="head" colspan="2">';
      if ( $this->getSortKey() == 'activity' ) {
         $params['sort'] = 'activity_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'activity_rev' ) {
         $params['sort'] = 'activity';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'activity';
         $picture ='&nbsp;';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('CONTEXT_ACTIVITY'), '', '', $this->getFragment(),'','','','class="head"');
      $html .= $picture;
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }


   function _getTablefootAsHTML() {
      $html  = '   <tr class="list">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions') {
         $html .= '<td class="foot_left" colspan="2"><input style="font-size:8pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_ATTACH_BUTTON').'" /> <input type="submit"  style="font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'"/>';
      }else{
         $html .= '<td class="foot_left" colspan="2" style="vertical-align:middle;">'.LF;
         $html .= '<span class="select_link">[</span>';
         $params = $this->_environment->getCurrentParameterArray();
         $params['select'] = 'all';
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                          $params, $this->_translator->getMessage('COMMON_ALL_ENTRIES'), '', '', $this->getFragment(),'','','','class="select_link"');
         $html .= '<span class="select_link">]</span>'.LF;

         $html .= $this->_getViewActionsAsHTML();
      }
      $html .= '</td>'.LF;
 // @segment-begin 37558
 // @segment-comment This is a comment
      $html .= '<td class="foot_right" colspan="3" style="vertical-align:middle; text-align:right; font-size:8pt;">'.LF;
      if ( $this->hasCheckboxes() ) {
         if (count($this->getCheckedIDs())=='1'){
            $html .= ''.$this->_translator->getMessage('COMMON_SELECTED_ONE',count($this->getCheckedIDs()));
         }else{
            $html .= ''.$this->_translator->getMessage('COMMON_SELECTED',count($this->getCheckedIDs()));
         }
      }
      $html .= '</td>'.LF;

 // @segmentation-end 37558
      $html .= '   </tr>'.LF;
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
   function _getItemAsHTML($item,$pos=0) {
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
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getContactPersonString($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;" colspan="2">'.$this->_getItemActivity($item).'</td>'.LF;
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
                           $this->_room_type,
                           'detail',
                           $params,
                           $this->_text_as_html_short($title));
      unset($params);
      $info_text = '';
      if ($item->isClosed()){
         $info_text =' ('.$this->_translator->getMessage('COMMON_CLOSE').')';
      }
      if ($item->isLocked()){
         $info_text =' ('.$this->_translator->getMessage('PROJECTROOM_LOCKED').')';
      }
      return $html.' '.$title.$info_text;
   }

   function _getContactPersonString ($item) {
      $retour = trim($item->getContactPersonString());
      if ( !empty($retour) ) {
         $retour = $this->_text_as_html_short($this->_compareWithSearchText($retour));
      } else {
         $retour = $this->_getItemModerator($item);
      }
      return $retour;
   }

   /** get the moderator of the item
    * this method returns the item moderator in the right formatted style
    *
    * @return string title
    */
   function _getItemModerator ($item) {
      $retour = '';
      $list = $item->getContactModeratorList();
      if ( $list->isNotEmpty() ) {
         $first = true;
         $mod_item = $list->getFirst();
         $mod_array = array();
         while ($mod_item) {
            $current_user_item = $this->_environment->getCurrentUserItem();
            if ( $current_user_item->isGuest() and $mod_item->isVisibleForLoggedIn() ) {
               $mod_array[] = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
            } else {
               $mod_array[] = $mod_item->getFullname();
            }
            unset($current_user_item);
            $mod_item = $list->getNext();
         }
         $mod_array = array_unique($mod_array);
         $retour = implode(', ',$mod_array);
         $retour = $this->_compareWithSearchText($retour);
         return $this->_text_as_html_short($retour);
      }else{
         $retour .= '<span class="disabled">'.$this->_translator->getMessage('COMMON_NO_CONTACTS').'</span>';
         return $retour;
      }
   }

   /** get the activity of the item
    * this method returns the item activity in the right formatted style
    *
    * @return string title
    */
   function _getItemActivity ($item) {
     if (!empty($item)) {
         if ( $this->_max_activity != 0 ) {
            $percentage = $item->getActivityPoints();
            if ( empty($percentage) ) {
               $percentage = 0;
            } else {
              $teiler = $this->_max_activity/20;
               $percentage = log(($percentage/$teiler)+1);
             if ($percentage < 0) {
               $percentage = 0;
             }
             $max_activity = log(($this->_max_activity/$teiler)+1);
               $percentage = round(($percentage / $max_activity) * 100,2);
            }
         } else {
            $percentage = 0;
        }
         $display_percentage = $percentage;
         $html  = '         <div class="gauge">'.LF;
         $html .= '            <div class="gauge-bar" style="width:'.$display_percentage.'%;">&nbsp;</div>'.LF;
         $html .= '         </div>'.LF;
     } else {
        $html = '';
     }
      return $html;
   }
}
?>