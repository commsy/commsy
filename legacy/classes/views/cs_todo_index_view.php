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

$this->includeClass(ROOM_INDEX_VIEW);

/**
 *  class for CommSy list view: todo
 */
class cs_todo_index_view extends cs_room_index_view {

   var $_selected_status = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the page
    * @param string  viewname               e.g. todo_index
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    *
    * @author CommSy Development Group
    */
   function __construct($params) {
      cs_room_index_view::__construct($params);
      $this->setTitle($this->_translator->getMessage('TODO_HEADER'));
      $this->setActionTitle($this->_translator->getMessage('COMMON_TODO'));
      $this->setColspan(6);
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

   function _getGetParamsAsArray() {
      $params = parent::_getGetParamsAsArray();
      $params['selstatus'] = $this->getSelectedStatus();
      return $params;
   }

   function setSelectedStatus ($status) {
      $this->_selected_status = (int)$status;
   }

   function getSelectedStatus () {
      return $this->_selected_status;
   }

   function _getAdditionalActionsAsHTML(){
      $html  = '';
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $hash_manager = $this->_environment->getHashManager();
      $params = $this->_environment->getCurrentParameterArray();
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/abbo.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TODO_ABBO').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/abbo.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TODO_ABBO').'"/>';
      }
      $ical_url = '<a title="'.$this->_translator->getMessage('TODO_ABBO').'"  href="webcal://';
      $ical_url .= $_SERVER['HTTP_HOST'];
      global $c_single_entry_point;
      $ical_url .= str_replace($c_single_entry_point,'ical.php',$_SERVER['PHP_SELF']);
      $ical_url .= '?cid='.$_GET['cid'].'&amp;mod=todo&amp;hid='.$hash_manager->getICalHashForUser($current_user->getItemID()).'">'.$image.'</a>'.LF;
      $html .= $ical_url;
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/export.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TODO_EXPORT').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/export.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TODO_EXPORT').'"/>';
      }
      $html .= '<a title="'.$this->_translator->getMessage('TODO_EXPORT').'"  href="ical.php?cid='.$_GET['cid'].'&amp;mod=todo&amp;hid='.$hash_manager->getICalHashForUser($current_user->getItemID()).'">'.$image.'</a>'.LF;
      unset($params);
      return $html;
   }

   function _getTableheadAsHTML () {
      include_once('functions/misc_functions.php');
      $params = $this->_getGetParamsAsArray();
      $params['from'] = 1;
      $html = '   <tr class="head">'.LF;
      $context = $this->_environment->getCurrentContextItem();
      if ($context->withTodoManagement()){
         $html .= '      <td class="head" style="width:35%;" colspan="2">';
      }else{
         $html .= '      <td class="head" style="width:40%;" colspan="2">';
      }
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
      if ( empty($params['download'])
           or $params['download'] != 'zip'
         ) {
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                $params, $this->_translator->getMessage('COMMON_TITLE'), '', '', $this->getFragment(),'','','','class="head"');
      } else {
         $html .= $this->_translator->getMessage('COMMON_TITLE');
      }
      $html .= $picture;
      $html .= '</td>'.LF;


      $html .= '      <td style="width:15%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'status' ) {
         $params['sort'] = 'status_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'status_rev' ) {
         $params['sort'] = 'status';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'status';
         $picture ='&nbsp;';
      }
      if ( empty($params['download'])
           or $params['download'] != 'zip'
         ) {
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                 $params, $this->_translator->getMessage('TODO_STATUS'), '', '', $this->getFragment(),'','','','class="head"');
      } else {
         $html .= $this->_translator->getMessage('TODO_STATUS');
      }
      $html .= $picture;
      $html .= '</td>'.LF;

      $html .= '      <td style="width:10%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'date' ) {
         $params['sort'] = 'date_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'date_rev' ) {
         $params['sort'] = 'date';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'date';
         $picture ='&nbsp;';
      }
      if ( empty($params['download'])
           or $params['download'] != 'zip'
         ) {
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                $params, $this->_translator->getMessage('TODO_DATE'), '', '', $this->getFragment(),'','','','class="head"');
      } else {
         $html .= $this->_translator->getMessage('TODO_DATE');
      }
      $html .= $picture;
      $html .= '</td>'.LF;

      if ($context->withTodoManagement()){
         $html .= '<td style="width:8%; font-size:8pt;" class="head">'.LF;
         $html .= $this->_translator->getMessage('TODO_PROCESS');
         $html .= '</td>'.LF;
      }



      $html .= '      <td style="width:33%; font-size:8pt;" class="head">';
      $text = $this->_translator->getMessage('TODO_RESPONSIBILITY');
      $html .= $text;
      $html .= '</td>'.LF;


      $html .= '   </tr>'.LF;

      return $html;
   }



   function _getTablefootAsHTML() {
      $html  = '   <tr class="list">'.LF;
      $context = $this->_environment->getCurrentContextItem();
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions') {
         if ($context->withTodoManagement()){
            $html .= '<td class="foot_left" colspan="4"><input style="font-size:8pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_ATTACH_BUTTON').'" /> <input type="submit"  style="font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'"/>';
         }else{
            $html .= '<td class="foot_left" colspan="3"><input style="font-size:8pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_ATTACH_BUTTON').'" /> <input type="submit"  style="font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'"/>';
         }
      }else{
         if ($context->withTodoManagement()){
            $html .= '<td class="foot_left" colspan="4" style="vertical-align:middle;">'.LF;
         }else{
            $html .= '<td class="foot_left" colspan="3" style="vertical-align:middle;">'.LF;
         }
         $html .= '<span class="select_link">[</span>';
         $params = $this->_environment->getCurrentParameterArray();
         $params['select'] = 'all';
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                          $params, $this->_translator->getMessage('COMMON_ALL_ENTRIES'), '', '', $this->getFragment(),'','','','class="select_link"');
         $html .= '<span class="select_link">]</span>'.LF;
// if room is archived deactivate dropdown
         if(!($context->isProjectRoom() and $context->isClosed())){
         	$html .= $this->_getViewActionsAsHTML();
         }
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
   function _getItemAsHTML($item,$pos=0,$with_links=true) {
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
            $html .= '                     <tr class="list"><td '.$style.' width="100%" style="font-weight:bold;" colspan="5">'."\n";
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
      if ( !(isset($_GET['mode']) and $_GET['mode']=='print')
           or ( !empty($download)
                and $download == 'zip'
              )
         ) {
         $html .= '      <td '.$style.' style="vertical-align:middle;" width="2%">'.LF;
         if ( empty($download)
              or $download != 'zip'
            ) {
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
         }
         $html .= '      </td>'.LF;
         if ($item->isNotActivated()){
            $title = $item->getTitle();
            $title = $this->_compareWithSearchText($title);
            $user = $this->_environment->getCurrentUser();
            if($item->getCreatorID() == $user->getItemID() or $user->isModerator()){
               $params = array();
               $params['iid'] = $item->getItemID();
               $title = ahref_curl( $this->_environment->getCurrentContextID(),
                                  CS_TODO_TYPE,
                                  'detail',
                                  $params,
                                  $title,
                                  '','', '', '', '', '', '', '',
                                  CS_TODO_TYPE.$item->getItemID());
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
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getStatus($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getDateInLang($item).'</td>'.LF;
      $context = $this->_environment->getCurrentContextItem();
      if ($context->withTodoManagement()){
         $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getProcess($item).'</td>'.LF;
      }
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getProcessors($item).'</td>'.LF;
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
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_TODO_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($title),
                           '','', '', '', '', '', '', '',
            CS_TODO_TYPE.$item->getItemID());

      unset($params);
      if ( !$this->_environment->inPrivateRoom() and !$item->isNotActivated()) {
         $title .= $this->_getItemChangeStatus($item);
         $title .= $this->_getItemAnnotationChangeStatus($item);
         $title .= $this->_getItemStepChangeStatus($item);
      }
      return $title;
   }

      /** get the date of the item
    * this method returns the item date in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getDateInLang($item){
      $original_date = $item->getDate();
      $date = getDateInLang($original_date);
      $date = $this->_compareWithSearchText($date);
      $status = $item->getStatus();
      $actual_date = date("Y-m-d H:i:s");
      if ($status !=$this->_translator->getMessage('TODO_DONE') and $original_date < $actual_date){
         $date = '<span class="required">'.$date.'</span>';
      }
      if ($original_date == '9999-00-00 00:00:00'){
          $date = $this->_translator->getMessage('TODO_NO_END_DATE');
      }
      return $date;
   }

   function _getProcess($item){
      $step_html = '';
      $step_minutes = 0;
      $step_item_list = $item->getStepItemList();
      if ( $step_item_list->isEmpty() ) {
         $status = $this->_compareWithSearchText($item->getStatus());
      } else {
         $step = $step_item_list->getFirst();
         $count = $step_item_list->getCount();
         $counter = 0;
         while ($step) {
            $counter++;
            $step_minutes = $step_minutes + $step->getMinutes();
            $step = $step_item_list->getNext();
         }
      }
      $done_time = '';
      $done_percentage = 100;
      if ($item->getPlannedTime() > 0){
         $done_percentage = $step_minutes / $item->getPlannedTime() * 100;
      }

      $tmp_message = $this->_translator->getMessage('COMMON_MINUTES');
      $step_minutes_text = $step_minutes;
      if (($step_minutes/60)>1 and ($step_minutes/60)<=8){
         $step_minutes_text = '';
         $exact_minutes = $step_minutes/60;
         $step_minutes = round($exact_minutes,1);
         if ($step_minutes != $exact_minutes){
            $step_minutes_text .= 'ca. ';
         }
         if ($this->_translator->getSelectedLanguage() == 'de'){
            $step_minutes = str_replace('.',',',$step_minutes);
         }
         $step_minutes_text .= $step_minutes;
         $tmp_message = $this->_translator->getMessage('COMMON_HOURS');
         if ($step_minutes == 1){
            $tmp_message = $this->_translator->getMessage('COMMON_HOUR');
         }
       }elseif(($step_minutes/60)>8){
         $exact_minutes = ($step_minutes/60)/8;
         $step_minutes = round($exact_minutes,1);
         $step_minutes_text = '';
         if ($step_minutes != $exact_minutes){
            $step_minutes_text .= 'ca. ';
         }
         if ($this->_translator->getSelectedLanguage() == 'de'){
            $step_minutes = str_replace('.',',',$step_minutes);
         }
         $step_minutes_text .= $step_minutes;
         $tmp_message = $this->_translator->getMessage('COMMON_DAYS');
         if ($step_minutes == 1){
            $tmp_message = $this->_translator->getMessage('COMMON_DAY');
         }
      }else{
         $step_minutes = round($step_minutes,1);
         if ($this->_translator->getSelectedLanguage() == 'de'){
            $step_minutes = str_replace('.',',',$step_minutes);
         }
      }

      $display_plannend_time = $item->getPlannedTime();
      $shown_time = $step_minutes_text.' '.$tmp_message;
      $display_time_text_addon = $display_plannend_time.' '.$this->_translator->getMessage('COMMON_MINUTES');
      if (($display_plannend_time/60)>1){
         $display_time_text_addon = round($display_plannend_time/60);
         if ($this->_translator->getSelectedLanguage() == 'de'){
            $display_time_text_addon = str_replace('.',',',$display_time_text_addon);
         }
         if ($display_time_text_addon == 1){
            $display_time_text_addon .= ' '.$this->_translator->getMessage('COMMON_HOUR');
         }else{
            $display_time_text_addon .= ' '.$this->_translator->getMessage('COMMON_HOURS');
         }
      }
      if ($display_plannend_time/60>8){
         $display_time_text_addon = round($display_plannend_time/60/8,1);
         if ($this->_translator->getSelectedLanguage() == 'de'){
            $display_time_text_addon = str_replace('.',',',$display_time_text_addon);
         }
         if ($display_time_text_addon == 1){
            $display_time_text_addon .= ' '.$this->_translator->getMessage('COMMON_DAY');
         }else{
            $display_time_text_addon .= ' '.$this->_translator->getMessage('COMMON_DAYS_AKK');
         }
      }
      $display_time_text = $shown_time.' '.$this->_translator->getMessage('COMMON_FROM2').' '.$display_time_text_addon.' - '.round($done_percentage).'% '.$this->_translator->getMessage('TODO_DONE');
      if($done_percentage <= 100){
         $style = ' height: 8px; background-color: #75ab05; ';
         $done_time .= '      <div title="'.$display_time_text.'" style="border: 1px solid #444;  margin-left: 0px; height: 8px; width: 60px;">'.LF;
         if ( $done_percentage >= 30 ) {
            $done_time .= '         <div style="font-size: 2pt; '.$style.'width:'.$done_percentage.'%; color:#000000;">&nbsp;</div>'.LF;
         } else {
            $done_time .= '         <div style="font-size: 2pt; '.$style.'width:'.$done_percentage.'%; color:#000000;">&nbsp;</div>'.LF;
         }
         $done_time .= '      </div>'.LF;
      }elseif($done_percentage <= 120){
         $done_percentage = (100 / $done_percentage) *100;
         $style = ' height: 10px; border: 1px solid #444; background-color: #f2f030; ';
         $done_time .= '         <div title="'.$display_time_text.'" style="width: 60px; font-size: 2pt; '.$style.' color:#000000;">'.LF;
         $done_time .= '      <div style="border-right: 1px solid #444; margin-left: 0px; height:10px;  background-color:none; width:'.$done_percentage.'%;">'.LF;
         $done_time .= '      </div>'.LF;
         $done_time .= '</div>'.LF;
      }else{
         $done_percentage = (100 / $done_percentage) *100;
         $style = ' height: 8px; border: 1px solid #444; background-color: #f23030; ';
         $done_time .= '         <div title="'.$display_time_text.'" style="width: 60px; font-size: 2pt; '.$style.' color:#000000;">'.LF;
         $done_time .= '      <div style="border-right: 1px solid #444; margin-left: 0px; height:8px;  background-color:none; width:'.$done_percentage.'%;">'.LF;
         $done_time .= '      </div>'.LF;
         $done_time .= '</div>'.LF;
      }
      if ($item->getPlannedTime() > 0){
         $process = $done_time;
      }else{
         $process = $shown_time;
      }
      return $process;
   }

   /** get the status of the item
    * this method returns the item date in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getStatus($item){
      $user = $this->_environment->getCurrentUser();
      $status = $this->_compareWithSearchText($item->getStatus());
      return $status;
   }

   function _getProcessors($item){
     $user = $this->_environment->getCurrentUser();
     $html ='';
     $members = $item->getProcessorItemList();
      if ( $members->isEmpty() ) {
         $html .= '   <span class="disabled">'.$this->_translator->getMessage('TODO_NO_PROCESSOR').'</span>'.LF;
      } else {
         $member = $members->getFirst();
         if ( $member->isUser() ){
            $linktext = $member->getFullname();
            $params = array();
            $params['iid'] = $member->getItemID();
            if ( $this->_environment->inProjectRoom() and $member->maySee($user) ) {
               $html .= ahref_curl($this->_environment->getCurrentContextID(),
                             'user',
                             'detail',
                             $params,
                             $linktext);
            } else {
               $html .= '<span class="disabled">'.$linktext.'</span>';
            }
            unset($params);
         }
         $member = $members->getNext();
         while ($member) {
            if ( $member->isUser() ){
               $linktext = ', '.$member->getFullname();
               $params = array();
               $params['iid'] = $member->getItemID();
               if ( $this->_environment->inProjectRoom() and $member->maySee($user) ) {
                  $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                'user',
                                'detail',
                                $params,
                                $linktext);
               } else {
                  $html .= '<span class="disabled">'.$linktext.'</span>';
               }
               unset($params);
            }
            $member = $members->getNext();
         }
      }
      return $html;
   }

   function _getAdditionalRestrictionBoxAsHTML($field_length=14.5){
      $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $width = '235';
      $context_item = $this->_environment->getCurrentContextItem();
      $html = '';
      $selstatus = $this->getSelectedStatus();
      $html = '<div class="infocolor" style="text-align:left; padding-bottom:5px; font-size: 10pt;">'.$this->_translator->getMessage('TODO_STATUS').BRLF;
      // jQuery
      //$html .= '   <select name="selstatus" size="1" style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '   <select name="selstatus" size="1" style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" id="submit_form">'.LF;
      // jQuery
      $html .= '      <option value="0"';
      if ( !isset($selstatus) || $selstatus == 0 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>*'.$this->_translator->getMessage('ALL').'</option>'.LF;

      $html .= '      <option value="-2" disabled="disabled"';
      $html .= '>------------------</option>'.LF;

      $html .= '      <option value="1"';
      if ( isset($selstatus) and $selstatus == 1 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('TODO_NOT_STARTED').'</option>'.LF;

      $html .= '      <option value="2"';
      if ( isset($selstatus) and $selstatus == 2 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('TODO_IN_POGRESS').'</option>'.LF;

      $html .= '      <option value="3"';
      if (  isset($selstatus) and $selstatus == 3 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('TODO_DONE').'</option>'.LF;

      $context_item = $this->_environment->getCurrentContextItem();
      $extra_status_array = $context_item->getExtraToDoStatusArray();
      if (!empty($extra_status_array)){
         $html .= '      <option value="-2" disabled="disabled"';
         $html .= '>------------------</option>'.LF;
         foreach ($extra_status_array as $key => $value){
            $html .= '      <option value="'.$key.'"';
            if (  isset($selstatus) and $selstatus == $key ) {
               $html .= ' selected="selected"';
            }
            $html .= '>'.$value.'</option>'.LF;
         }

      }

      $html .= '      <option value="-2" disabled="disabled"';
      $html .= '>------------------</option>'.LF;
      $html .= '      <option value="4"';
      if (  isset($selstatus) and $selstatus == 4 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('TODO_NOT_DONE').'</option>'.LF;
      $html .= '   </select>'.LF;
      $html .='</div>';
      return $html;
   }

   function getAdditionalRestrictionTextAsHTML(){
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      $params['selstatus'] = $this->getSelectedStatus();
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->withActivatingContent()){
         $activation_limit= $this->getActivationLimit();
         if ( $activation_limit == 2 ){
            $this->_additional_selects = true;
            $html_text ='<tr>'.LF;
            $html_text .='<td>'.LF;
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_ACTIVATION_RESTRICTION').': </span>';
            $html_text .='</td>'.LF;
            $html_text .='<td style="text-align:right;">'.LF;
            $html_text .= '<span>'.$this->_translator->getMessage('COMMON_SHOW_ONLY_ACTIVATED_ENTRIES').'</span>';
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            $new_params['selactivatingstatus'] = 1;
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</td>'.LF;
            $html_text .='</tr>'.LF;
            $html .= $html_text;
         }
         return $html;
      }
      $params = $this->_environment->getCurrentParameterArray();
      $params['selstatus'] = $this->getSelectedStatus();

      if ( isset($params['selstatus']) and $params['selstatus'] != '-1' and $params['selstatus'] != '0' and !empty($params['selstatus']) ){
         $this->_additional_selects = true;
         $html_text ='<tr>'.LF;
         $html_text .='<td>'.LF;
         $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('TODO_STATUS').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;
         if (isset($params['selstatus']) and $params['selstatus'] == 1){
            $status_text = $this->_translator->getMessage('TODO_NOT_STARTED');
         }elseif(isset($params['selstatus']) and $params['selstatus'] == 2){
            $status_text = $this->_translator->getMessage('TODO_IN_POGRESS');
         }elseif(isset($params['selstatus']) and $params['selstatus'] == 3){
            $status_text = $this->_translator->getMessage('TODO_DONE');
         }elseif(isset($params['selstatus']) and $params['selstatus'] == 4){
            $status_text = $this->_translator->getMessage('TODO_NOT_DONE');
         }elseif(isset($params['selstatus']) and $params['selstatus'] != 0){
            $context_item = $this->_environment->getCurrentContextItem();
            $todo_status_array = $context_item->getExtraToDoStatusArray();
            $status_text = '';
            if (isset($todo_status_array[$params['selstatus']])){
               $status_text = $todo_status_array[$params['selstatus']];
            }
         }else{
            $status_text = '';
         }
         $html_text .= '<span><a title="'.$status_text.'">'.$status_text.'</a></span>';
         $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         $new_params['selstatus'] = 0;
         // unset($new_params['selstatus']);
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
      }else{
         $this->_additional_selects = true;
         $html_text ='<tr>'.LF;
         $html_text .='<td>'.LF;
         // $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('TODO_STATUS').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;
         //$status_text = $this->_translator->getMessage('TODO_NOT_DONE');
         // $html_text .= '<span><a title="'.$status_text.'">'.$status_text.'</a></span>';
         // $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         // unset($new_params['selstatus']);
         // Loescht aber nicht die Einschränkung
         $new_params['selstatus'] = 0;
         //$html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
      }
      #return $html;
   }

   function _getViewActionsAsHTML () {
      $user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<select name="index_view_action" size="1" style="width:180px; font-size:8pt; font-weight:normal;">'.LF;
      $html .= '   <option selected="selected" value="-1">*'.$this->_translator->getMessage('COMMON_LIST_ACTION_NO').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      if (!$this->_clipboard_mode){
         $html .= '   <option value="1">'.$this->_translator->getMessage('COMMON_LIST_ACTION_MARK_AS_READ').'</option>'.LF;
         $html .= '   <option value="2">'.$this->_translator->getMessage('COMMON_LIST_ACTION_COPY').'</option>'.LF;
         if ( method_exists($this,'_getAdditionalViewActionsAsHTML') ) {
            $html .= $this->_getAdditionalViewActionsAsHTML();
         }

         $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
         if ($user->isModerator()){
            $html .= '   <option value="3" id="delete_check_option">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
         }else{
            $html .= '   <option class="disabled" disabled="disabled">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DELETE').'</option>'.LF;
         }
         $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
         if ($user->isModerator()){
            $html .= '   <option value="4">'.$this->_translator->getMessage('TODO_LIST_ACTION_DONE').'</option>'.LF;
            $html .= '   <option value="5">'.$this->_translator->getMessage('TODO_LIST_ACTION_IN_PROGRESS').'</option>'.LF;
            $html .= '   <option value="6">'.$this->_translator->getMessage('TODO_LIST_ACTION_NOT_STARTED').'</option>'.LF;
         }else{
            $html .= '   <option class="disabled" disabled="disabled">'.$this->_translator->getMessage('TODO_LIST_ACTION_DONE').'</option>'.LF;
            $html .= '   <option class="disabled" disabled="disabled">'.$this->_translator->getMessage('TODO_LIST_ACTION_IN_PROGRESS').'</option>'.LF;
            $html .= '   <option class="disabled" disabled="disabled">'.$this->_translator->getMessage('TODO_LIST_ACTION_NOT_STARTED').'</option>'.LF;
         }
      }else{
         $html .= '   <option value="1">'.$this->_translator->getMessage('CLIPBOARD_PASTE_BUTTON').'</option>'.LF;
         $html .= '   <option value="2">'.$this->_translator->getMessage('CLIPBOARD_DELETE_BUTTON').'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= '<input type="submit" id="delete_confirmselect_option" style="width:70px; font-size:8pt;" name="option"';
      $html .= ' value="'.$this->_translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO').'"';
      $html .= '/>'.LF;

      return $html;
   }

   function _getPrintableTableHeadAsHTML() {
      include_once('functions/misc_functions.php');
      $params = $this->_getGetParamsAsArray();
      $params['from'] = 1;

      $html  = '<tr>';
      if ( $this->hasCheckboxes() ) {
         $html .= '<td class="count" colspan="3">'.$this->_getDescriptionAsHTML().'</td>'.LF;
      } else {
         $html .= '<td class="count" colspan="2">'.$this->_getDescriptionAsHTML().'</td>'.LF;
      }
      $html .= '<td width="5%" class="head_nav" colspan="2">&nbsp;</td>'.LF;
      $html .= '</tr>';
      $html  .= '   <tr class="head">'.LF;

      if ( $this->hasCheckboxes() ) {
         $html .= '      <td class="head" colspan="2">';
      } else {
         $html .= '      <td class="head" width="40%">';
      }
      if ( $this->getSortKey() == 'title' ) {
         $params['sort'] = 'title_rev';
         $text = $this->_translator->getMessage('COMMON_TITLE').' <img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'title_rev' ) {
         $params['sort'] = 'title';
         $text = $this->_translator->getMessage('COMMON_TITLE').' <img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'title';
         $text = $this->_translator->getMessage('COMMON_TITLE');
      }
      $html .= $text;
      $html .= '</td>'.LF;

      $html .= '      <td width="20%" class="head" >';
      if ( $this->getSortKey() == 'date_rev' ) {
         $params['sort'] = 'date';
         $text = $this->_translator->getMessage('TODO_DATE').' <img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'date' ) {
         $params['sort'] = 'date_rev';
         $text = $this->_translator->getMessage('TODO_DATE').' <img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'date';
         $text = $this->_translator->getMessage('TODO_DATE');
      }
      $html .= $text;
      $html .= '</td>'.LF;


      $html .= '      <td width="15%" class="head" >';
      if ( $this->getSortKey() == 'status' ) {
         $params['sort'] = 'status_rev';
         $text = $this->_translator->getMessage('TODO_STATUS').' <img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'status_rev' ) {
         $params['sort'] = 'status';
         $text = $this->_translator->getMessage('TODO_STATUS').' <img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'status';
         $text = $this->_translator->getMessage('TODO_STATUS');
      }
      $html .= $text;
      $html .= '</td>'.LF;
      $html .= '      <td width="25%" class="head" colspan="2">';
      $text = $this->_translator->getMessage('TODO_RESPONSIBILITY');
      $html .= $text;
      $html .= '</td>'.LF;

      $html .= '   </tr>'.LF;

      return $html;
   }

   function _getItemFiles($item, $with_links=true){
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
      return $retour.$file_list;
   }

   public function _getAdditionalViewActionsAsHTML () {
      $retour = '';
      $retour .= '   <option value="download">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DOWNLOAD').'</option>'.LF;
      include_once('functions/misc_functions.php');
      $retour .= plugin_hook_output_all('getAdditionalViewActionsAsHTML',array('module' => CS_MATERIAL_TYPE),LF);
      return $retour;
   }
}
?>