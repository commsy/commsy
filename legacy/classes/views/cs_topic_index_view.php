<?PHP
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
 *  class for CommSy list view: topic
 */
class cs_topic_index_view extends cs_index_view {


   var $_selected_institution = NULL;
   var $_available_institutions = NULL;
   var $_selected_group = NULL;
   var $_available_groups = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the page
    * @param string  viewname               e.g. topic_index
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    *
    * @author CommSy Development Group
    */
   function __construct($params) {
      cs_index_view::__construct($params);
      $this->setTitle($this->_translator->getMessage('COMMON_TOPICS'));
      $this->setColspan(3);
      $user = $this->_environment->getCurrentUser();
      $context_item = $this->_environment->getCurrentContextItem();
      $rubric_array = $context_item->_getRubricArray(CS_TOPIC_TYPE);
      if (isset($rubric_array[mb_strtoupper($this->_environment->getSelectedLanguage(), 'UTF-8')]['GENUS']) ){
         $genus = $rubric_array[mb_strtoupper($this->_environment->getSelectedLanguage(), 'UTF-8')]['GENUS'];
      } else {
         $genus = $rubric_array['EN']['GENUS'];
      }
      if ($genus =='M'){
         $new = $this->_translator->getMessage('COMMON_NEW_M');
      }
      elseif ($genus =='F'){
         $new = $this->_translator->getMessage('COMMON_NEW_F');
      }
      else {
         $new = $this->_translator->getMessage('COMMON_NEW_N');
      }
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

   function setSelectedInstitution ($institution_id) {
      $this->_selected_institution = (int)$institution_id;
   }

   function getSelectedInstitution () {
      return $this->_selected_institution;
   }

   function setAvailableInstitutions ($institution_list) {
      $this->_available_institutions = $institution_list;
   }

   function getAvailableInstitutions () {
      return $this->_available_institutions;
   }

   function setSelectedGroup ($group_id) {
      $this->_selected_group = (int)$group_id;
   }

   function getSelectedGroup () {
      return $this->_selected_group;
   }

   function setAvailableGroups ($group_list) {
      $this->_available_groups = $group_list;
   }

   function getAvailableGroups () {
      return $this->_available_groups;
   }

   function _getGetParamsAsArray() {
      $params = parent::_getGetParamsAsArray();
      if ($this->_environment->inCommunityRoom()){
         $params['selinstitution'] = $this->getSelectedInstitution();
      } else {
         $params['selgroup'] = $this->getSelectedGroup();
      }
      return $params;
   }



   function _getTableheadAsHTML () {
      include_once('functions/misc_functions.php');
      $params = $this->_getGetParamsAsArray();
      $params['from'] = 1;

      $html  = '<tr>';
      $html .= '      <td class="head" style="width:70%;" colspan="2">';
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
         $user = $this->_environment->getCurrentUser();
         if(   $item->isNotActivated() && !($item->getCreatorID() == $user->getItemID() ||
               $user->isModerator()) ){
            $html .= ' disabled="disabled"'.LF;
         } else if ( in_array($key, $checked_ids) ) {
            $html .= ' checked="checked"'.LF;
            if ( in_array($key, $dontedit_ids) ) {
               $html .= ' disabled="disabled"'.LF;
            }
         }
         $html .= '/>'.LF;
         $html .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
         $html .= '      </td>'.LF;

         if ($item->isNotActivated()) {
            $title = $item->getTitle();
            $title = $this->_compareWithSearchText($title);
            $user = $this->_environment->getCurrentUser();
            if($item->getCreatorID() == $user->getItemID() or $user->isModerator()){
               $params = array();
               $params['iid'] = $item->getItemID();
               $title = ahref_curl( $this->_environment->getCurrentContextID(),
                                  CS_TOPIC_TYPE,
                                  'detail',
                                  $params,
                                  $title,
                                  '','', '', '', '', '', '', '',
                                  CS_TOPIC_TYPE.$item->getItemID());
               unset($params);
            }
            $activating_date = $item->getActivatingDate();
            if (strstr($activating_date,'9999-00-00')){
               $title .= BR.$this->_translator->getMessage('COMMON_NOT_ACTIVATED');
            }else{
               $title .= BR.$this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($item->getActivatingDate());
            }
            $title = '<span class="disabled">'.$title.'</span>';
            $html .= '      <td '.$style.'>'.$title.LF;
         } else {
            $html .= '      <td '.$style.' style="font-size:10pt;">'.$this->_getItemTitle($item).'</td>'.LF;
         }
      }else{
         $html .= '      <td colspan="2" '.$style.' style="font-size:10pt;">'.$this->_getItemTitle($item).'</td>'.LF;
      }
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemModificator($item).'</td>'.LF;
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
      $fileicons = $this->_getItemFiles($item);
      if ( !empty($fileicons) ) {
         $fileicons = ' '.$fileicons;
      }
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_TOPIC_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($title),
                           '','', '', '', '', '', '', '',
            CS_TOPIC_TYPE.$item->getItemID());

      unset($params);
      if (!empty($this->_room_id) and !$this->_environment->inPrivateRoom() and !$item->isNotActivated()) {
         $title .= $this->_getItemChangeStatus($item);
         $title .= $this->_getItemAnnotationChangeStatus($item);
      }
      return $title.$fileicons;
   }

   function _getAdditionalCommunityFormFieldsAsHTML () {
      $html = '';
      $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ($left_menue_status !='disapear'){
        $width = '14.3';
      }else{
        $width = '18.3';
      }
      $context = $this->_environment->getCurrentContextItem();
      $html .= '</div>';
      return $html;
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

   function _getAdditionalProjectFormFieldsAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $width = '235';
      $context_item = $this->_environment->getCurrentContextItem();
      $list = $this->getAvailableGroups();
      $selgroup = $this->getSelectedGroup();
      $html = '<div class="infocolor"  style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_GROUP').'&nbsp;'.BRLF;
      // jQuery
      //$html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="selgroup" size="1" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="selgroup" size="1" id="submit_form">'.LF;
      // jQuery
      $html .= '      <option value="0"';
      if ( !isset($selgroup) || $selgroup == 0 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>*'.$this->_translator->getMessage('COMMON_NO_SELECTION').LF;
      $html .= '   <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
      $group = $list->getFirst();
      while ( $group ) {
         $html .= '      <option value="'.$group->getItemID().'"';
         if ( isset($selgroup) and $selgroup == $group->getItemID() ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$this->_Name2SelectOption($group->getName()).'</option>'.LF;
         $group = $list->getNext();
      }
      $html .= '   <option class="disabled" disabled="disabled" value="-1">------------------------------</option>'.LF;
      $html .= '      <option value="-1"';
      if ( !isset($selgroup) || $selgroup == -1 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>*'.$this->_translator->getMessage('COMMON_NOT_LINKED').'</option>'.LF;
      $html .= '   </select>'.LF;
      $html .='</div>';
      return $html;
   }

   function _getAdditionalFormFieldsAsHTML () {
      if ( $this->_environment->inCommunityRoom() ) {
         return $this->_getAdditionalCommunityFormFieldsAsHTML();
      } elseif( $this->_environment->inProjectRoom() ) {
         $context_item = $this->_environment->getCurrentContextItem();
         if ($context_item->withRubric(CS_GROUP_TYPE)) {
            return $this->_getAdditionalProjectFormFieldsAsHTML();
         }
      }
   }
}
?>