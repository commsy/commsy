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
//include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: group
 */
class cs_group_index_view extends cs_index_view {

   var $_selected_topic = NULL;
   var $_available_topics = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the commsy
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function __construct($params) {
      cs_index_view::__construct($params);
      $this->setTitle($this->_translator->getMessage('GROUP_HEADER'));
   }

   /** set the content of the list view
    * this method sets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    *
    * @author CommSy Development Group
    */
    function setList ($list) {
       $this->_list = $list;
       if (!empty($this->_list)){
          $id_array = array();
          $item = $list->getFirst();
          while($item){
             $id = $item->getModificatorID();
             if (!in_array($id, $id_array)){
                $id_array[] = $id;
             }
             $item = $list->getNext();
          }
          $user_manager = $this->_environment->getUserManager();
          $user_manager->getRoomUserByIDsForCache($this->_environment->getCurrentContextID(),$id_array);
       }
    }


   function setSelectedTopic ($topic_id) {
      $this->_selected_topic = (int)$topic_id;
   }

   function getSelectedTopic () {
      return $this->_selected_topic;
   }

   function setAvailableTopics ($topic_list) {
      $this->_available_topics = $topic_list;
   }

   function getAvailableTopics () {
      return $this->_available_topics;
   }

   function _getGetParamsAsArray() {
      $params = parent::_getGetParamsAsArray();
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->withRubric(CS_TOPIC_TYPE)){
         $params['seltopic'] = $this->getSelectedTopic();
      }
      return $params;
   }

   function _getTableheadAsHTML () {
      include_once('functions/misc_functions.php');
      $params = $this->_getGetParamsAsArray();
      $params['from'] = 1;

      $html  = '<tr>';
      $html .= '      <td class="head" style="width:55%;" colspan="2">';
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
                             $params, $this->_translator->getMessage('COMMON_NAME'), '', '', $this->getFragment(),'','','','class="head"');
      $html .= $picture;
      $html .= '</td>'.LF;
      $html .= '      <td style="width:25%; font-size:8pt;" class="head">E-Mail</td>'.LF;

      $html .= '      <td style="width:20%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'modificator' ) {
         $params['sort'] = 'modificator_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'modificator_rev' ) {
         $params['sort'] = 'modificator';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'modificator';
         $picture ='&nbsp;';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('COMMON_MODIFIED_BY'), '', '', $this->getFragment(),'','','','class="head"');
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
      $html  = '';
      $html .= '<select name="index_view_action" size="1" style="width:160px; font-size:8pt; font-weight:normal;">'.LF;
      $html .= '   <option selected="selected" value="-1">*'.$this->_translator->getMessage('COMMON_LIST_ACTION_NO').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      $html .= '   <option value="1">'.$this->_translator->getMessage('COMMON_LIST_ACTION_MARK_AS_READ').'</option>'.LF;
#      $html .= '   <option value="2">'.$this->_translator->getMessage('COMMON_LIST_ACTION_COPY').'</option>'.LF;
#      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      if ($this->_environment->inPrivateRoom()){
         $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
         $html .= '   <option value="3">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
      }else{
         $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
         $user = $this->_environment->getCurrentUserItem();
         if ($user->isModerator()){
            $html .= '   <option value="3" id="delete_check_option">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
         }else{
            $html .= '   <option class="disabled" disabled="disabled">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
         }
      }
      $html .= '</select>'.LF;
      $html .= '<input type="submit" id="delete_confirmselect_option" style="width:70px; font-size:8pt;" name="option"';
      $html .= ' value="'.$this->_translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO').'"';
      $html .= '/>'.LF;

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
// if room is archived deactivate dropdown
		 $context = $this->_environment->getCurrentContextItem();
         if(!($context->isProjectRoom() and $context->isClosed())){
         	$html .= $this->_getViewActionsAsHTML();
         }
         unset($context);
      }
      $html .= '</td>'.LF;
      $html .= '<td class="foot_right" colspan="2" style="vertical-align:middle; text-align:right; font-size:8pt;">'.LF;
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
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    *
    * @author CommSy Development Group
    */
   function _getItemAsHTML($item,$pos) {
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
         $html .= '      <td '.$style.' style="font-size:10pt;">'.$this->_getItemName($item).'</td>'.LF;
      }else{
         $html .= '      <td colspan="2" '.$style.' style="font-size:10pt;">'.$this->_getItemName($item).'</td>'.LF;
      }
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getEmailToGroup($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;" colspan="2">'.$this->_getItemModificator($item).'</td>'.LF;
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
   function _getItemName($item) {
      $name_text = $item->getName();
      $name_text = $this->_compareWithSearchText($name_text);
      $params = array();
      $params['iid'] = $item->getItemID();
      $name = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_GROUP_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($name_text),
                           '','', '', '', '', '', '', '',
                           CS_GROUP_TYPE.$item->getItemID());

      unset($params);
      $name .= $this->_getItemChangeStatus($item);
      return $name;
   }

   function _getPrintableItemName($item) {
      $name_text = $item->getName();
      $name_text = $this->_compareWithSearchText($name_text);
      $name = $this->_text_as_html_short($name_text);
      $name .= $this->_getItemChangeStatus($item);
      return $name;
   }

      /** get a link to the group_mail page for this group
    *
    * @param TBD
    *
    * @author CommSy Development Group
    */
   function _getEmailToGroup($item){
      $email_text = $this->_translator->getMessage('GROUPS_EMAIL_TO_GROUP_BIG');
      $params = array();
      $params['iid'] = $item->getItemID();
      $text = ahref_curl($item->getContextID(), 'group', 'mail', $params, $email_text);
      unset($params);
      return $text;
   }



  function _getExpertSearchAsHTML(){
     $html  = '';
     $context_item = $this->_environment->getCurrentContextItem();
     $module = $this->_environment->getCurrentModule();
     if ($context_item->withActivatingContent()
          or $module == CS_DATE_TYPE
          or $module == CS_USER_TYPE
          or $module == CS_MATERIAL_TYPE
          or $module == CS_TODO_TYPE
          or $module == 'campus_search'
      ){
         $width = '235';
         $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
         $html .= '<div class="right_box">'.LF;
         $html .= '         <noscript>';
         $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_RESTRICTIONS').'</div>';
         $html .= '         </noscript>';
         $html .= '<div class="right_box_main" style="padding-top:5px;">'.LF;
         $html .= $this->_getAdditionalRestrictionBoxAsHTML('14.5').LF;
         $html .= $this->_getAdditionalFormFieldsAsHTML().LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
      }
      return $html;
  }


   function _getAdditionalFormFieldsAsHTML () {
      $context_item = $this->_environment->getCurrentContextItem();
      $width = '235';
      if ($context_item->withRubric(CS_TOPIC_TYPE)){
         $topic_list = $this->getAvailableTopics();
         $seltopic = $this->getSelectedTopic();
         $html = '<div class="infocolor" style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_TOPIC').BRLF;
         // jQuery
         //$html .= '   <select name="seltopic" size="1" style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" onChange="javascript:document.indexform.submit()">'.LF;
         $html .= '   <select name="seltopic" size="1" style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" id="submit_form">'.LF;
         // jQuery
         $html .= '      <option value="0"';
         if ( !isset($seltopic) || $seltopic == 0 ) {
            $html .= ' selected="selected"';
         }
         $html .= '>*'.$this->_translator->getMessage('COMMON_NO_SELECTION').LF;
         $html .= '   <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
         $topic = $topic_list->getFirst();
         while ( $topic ) {
            $html .= '      <option value="'.$this->_text_as_form($topic->getItemID()).'"';
            if ( isset($seltopic) and $seltopic == $topic->getItemID() ) {
               $html .= ' selected="selected"';
            }
            $html .= '>'.$this->_Name2SelectOption($topic->getName()).'</option>'.LF;
            $topic = $topic_list->getNext();
         }
         $html .= '   <option class="disabled" disabled="disabled" value="-1">------------------------------</option>'.LF;
         $html .= '      <option value="-1"';
         if ( !isset($seltopic) || $seltopic == -1 ) {
            $html .= ' selected="selected"';
         }
         $html .= '>*'.$this->_translator->getMessage('COMMON_NOT_LINKED').'</option>'.LF;
         $html .= '   </select>'.LF;
         $html .='</div>';
         return $html;
      }
   }

}
?>