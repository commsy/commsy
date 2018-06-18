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
class cs_project_index_view extends cs_context_index_view {

   var $_selected_community_room_limit = NULL;

   var $_selected_time = 0;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_context_index_view::__construct($params);
      $this->_room_type = CS_PROJECT_TYPE;
      $manager = $this->_environment->getProjectManager();
      if ($this->_environment->inCommunityRoom()) {
         $manager->setContextLimit($this->_environment->getCurrentPortalID());
      }
      global $c_cache_cr_pr;
      if ( !isset($c_cache_cr_pr) or !$c_cache_cr_pr ) {
         $this->_max_activity = $manager->getMaxActivityPointsInCommunityRoom($this->_environment->getCurrentContextID());
      } else {
         $current_context_item = $this->_environment->getCurrentContextItem();
         $this->_max_activity = $manager->getMaxActivityPointsInCommunityRoomInternal($current_context_item->getInternalProjectIDArray());
         unset($current_context_item);
      }
   }

    function getSelectedTime () {
       return $this->_selected_time;
    }

    function setSelectedTime ($value) {
       $this->_selected_time = $value;
    }


   /** get View-Actions of this index view
    * this method returns the index actions as html
    *
    * @return string index actions
    */
   function _getViewActionsAsHTML () {
      $user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<select name="index_view_action" size="1" style="width:160px; font-size:8pt; font-weight:normal;">'.LF;
      $html .= '   <option selected="selected" value="-1">*'.$this->_translator->getMessage('COMMON_LIST_ACTION_NO').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      if (!$this->_clipboard_mode){
         $html .= '   <option value="1">'.$this->_translator->getMessage('COMMON_LIST_ACTION_MARK_AS_READ').'</option>'.LF;
         $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
         if ($user->isModerator()){
            $html .= '   <option value="3">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
         }else{
            $html .= '   <option class="disabled" disabled="disabled">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
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


   function _getAdditionalFormFieldsAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ($left_menue_status !='disapear'){
        $width = '14.3';
      }else{
        $width = '18.3';
      }
     $html = '';

     // institutions and topics
      $html .= parent::_getAdditionalFormFieldsAsHTML();

     // time (clock pulses)
     $current_context = $this->_environment->getCurrentContextItem();
     $portal_item = $current_context->getContextItem();
     if ( $this->_environment->inCommunityRoom()
          and $current_context->showTime()
         and $portal_item->showTime()
        ) {
         $seltime = $this->getSelectedTime();
       $time_list = $portal_item->getTimeListRev();

       $this->translatorChangeToPortal();
         $html .= '<div style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_TIME_NAME').BRLF;
       $this->translatorChangeToCurrentContext();
         // jQuery
         //$html .= '   <select style="width: '.$width.'em; font-size:8pt; margin-bottom:5px;" name="seltime" size="1" onChange="javascript:document.indexform.submit()">'.LF;
         $html .= '   <select style="width: '.$width.'em; font-size:8pt; margin-bottom:5px;" name="seltime" size="1" id="submit_form">'.LF;
         // jQuery
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