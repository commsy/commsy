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

$this->includeClass(DETAIL_VIEW);
include_once('functions/curl_functions.php');

/**
 *  class for CommSy todo detail-views
 */
class cs_todo_detail_view extends cs_detail_view {

 /** array of ids in clipboard*/
   var $_clipboard_id_array=array();


   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    *
    * @author CommSy Development Group
    */
   function __construct($params) {
      cs_detail_view::__construct($params);
   }

   function setClipboardIDArray($cia) {
      $this->_clipboard_id_array = (array)$cia;
   }

   function _getClipboardIDArray() {
      return $this->_clipboard_id_array;
   }


   /** get the single entry of the list view as HTML
    * this method returns the single entry in HTML-Code
    *
    * @returns string $item as HMTL
    *
    * @param object item     the single list entry
    * @author CommSy Development Group
    */
   function _getItemAsHTML($item) {
      $html  = LF.'<!-- BEGIN OF TODO ITEM DETAIL -->'.LF;
      $user = $this->_environment->getCurrentUser();
      $context = $this->_environment->getCurrentContextItem();
      $formal_data = array();
      $original_date = $item->getDate();
      $date = getDateTimeInLang($original_date);
      $status = $item->getStatus();
      $actual_date = date("Y-m-d H:i:s");
      if ($status !=$this->_translator->getMessage('TODO_DONE') and $original_date < $actual_date){
         $date = '<span class="required">'.$date.'</span>';
      }

      if ($original_date == '9999-00-00 00:00:00'){
      	 $date = $this->_translator->getMessage('TODO_NO_END_DATE_LONG');
      }


      // Members
      $member_html = '';
      $members = $item->getProcessorItemList();
      if ( $members->isEmpty() ) {
         $member_html .= '   '.$this->_translator->getMessage('TODO_NO_PROCESSOR').LF;
      } else {
         $member = $members->getFirst();
         $count = $members->getCount();
         $counter = 0;
         while ($member) {
            $counter++;
            if ( $member->isUser() ){
               $linktext = $this->_text_as_html_short($this->_compareWithSearchText($member->getFullname()));
               if ( $member->maySee($user) ) {
                  $params = array();
                  $params['iid'] = $member->getItemID();
                  $param_zip = $this->_environment->getValueOfParameter('download');
                  if ( empty($param_zip)
                       or $param_zip != 'zip'
                     ) {
                     $member_html .= ahref_curl($this->_environment->getCurrentContextID(),
                                   'user',
                                   'detail',
                                   $params,
                                   $linktext);
                  } else {
                     $member_html .= $linktext;
                  }
                  unset($params);
               } else {
                  $member_html .= '<span class="disabled">'.$linktext.'</span>'.LF;
               }
               if ( $counter != $count) {
                  $member_html .= ', ';
               }
            }else{
               $link_title = $this->_text_as_html_short($this->_compareWithSearchText(chunkText($member->getFullName(),35)));
               $param_zip = $this->_environment->getValueOfParameter('download');
               if ( empty($param_zip)
                    or $param_zip != 'zip'
                  ) {
                  $member_html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                      $this->_environment->getCurrentModule(),
                                      $this->_environment->getCurrentFunction(),
                                      array(),
                                      $link_title,
                                      $this->_translator->getMessage('USER_STATUS_REJECTED'),
                                      '_self',
                                      '',
                                      '',
                                      '',
                                      '',
                                      'class="disabled"',
                                      '',
                                      '',
                                      true);

               } else {
                  $member_html .= $link_title;
               }
               if ( $counter != $count) {
                  $member_html .= ', ';
               }
            }
            $member = $members->getNext();
         }
      }


      $temp_array[0] = $this->_translator->getMessage('TODO_VALIDITY_DATE');
      $temp_array[1] = $date;
      $formal_data[] = $temp_array;
      $temp_array[0] = $this->_translator->getMessage('TODO_STATUS');
      $temp_array[1] = $item->getStatus();
      $formal_data[] = $temp_array;
      if ($context->withTodoManagement()){
         $step_html = '';
         $step_minutes = 0;
         $step_item_list = $item->getStepItemList();
         if ( $step_item_list->isEmpty() ) {
            $step_html .= '   '.$this->_translator->getMessage('TODO_NO_STEPS').LF;
         } else {
            $step = $step_item_list->getFirst();
            $count = $step_item_list->getCount();
            $counter = 0;

            while ($step) {
               $counter++;
               $step_minutes = $step_minutes + $step->getMinutes();
               $fileicons = $this->_getItemFiles($step,true);
               if ( !empty($fileicons) ) {
                  $fileicons = '&nbsp;'.$fileicons;
               }
               $params = array();
               $params['iid'] = $item->getItemID();
               $hover = str_replace('"','&quot;',$this->_text_as_html_short($step->getTitle()));
               $param_zip = $this->_environment->getValueOfParameter('download');
               if ( empty($param_zip)
                    or $param_zip != 'zip'
                  ) {
                  $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_TODO_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($this->_compareWithSearchText($step->getTitle())),
                           $hover,
                           '',
                           'anchor'.$step->getItemID());
               } else {
                  $title = $this->_text_as_html_short($this->_compareWithSearchText($step->getTitle()));
               }
               $step_html .= $counter.'. '.$title.$fileicons;
               $step_html .= $this->_getItemChangeStatus($step).' '.'<br/>';
               $step = $step_item_list->getNext();
            }
         }
         $done_time = '';

         $done_percentage = 100;
         if ($item->getPlannedTime() > 0){
            $done_percentage = $step_minutes / $item->getPlannedTime() * 100;
         }

         $time_type = $item->getTimeType();
         $tmp_message = $this->_translator->getMessage('COMMON_MINUTES');
         $step_minutes_text = $step_minutes;
         switch ($time_type){
            case 2:
               $step_minutes_text = '';
               $exact_minutes = $step_minutes/60;
#               $step_minutes = round($exact_minutes);
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
               break;
            case 3:
               $exact_minutes = ($step_minutes/60)/8;
#               $step_minutes = round($exact_minutes);
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
               break;
             default:
               $step_minutes = round($step_minutes,1);
               if ($this->_translator->getSelectedLanguage() == 'de'){
                  $step_minutes = str_replace('.',',',$step_minutes);
               }
               break;
         }


         if($done_percentage <= 100){
            $style = ' height: 16px; background-color: #75ab05; ';
            $done_time .= '      <div style="border: 1px solid #444;  margin-left: 0px; height: 16px; width: 300px;">'.LF;
            if ( $done_percentage >= 30 ) {
               $done_time .= '         <div style="font-size: 10pt; '.$style.'width:'.$done_percentage.'%; color:#000000;">'.$step_minutes_text.' '.$tmp_message.'</div>'.LF;
            } else {
               $done_time .= '<div style="float:right; font-size: 10pt;">'.$step_minutes_text.' '.$tmp_message.'</div>';
               $done_time .= '         <div style="font-size: 10pt; '.$style.'width:'.$done_percentage.'%; color:#000000;">&nbsp;</div>'.LF;
            }
            $done_time .= '      </div>'.LF;
         }elseif($done_percentage <= 120){
            $done_percentage = (100 / $done_percentage) *100;
            $style = ' height: 16px; border: 1px solid #444; background-color: #f2f030; ';
            $done_time .= '         <div style="width: 300px; font-size: 10pt; '.$style.' color:#000000;">'.LF;
            $done_time .= '      <div style="border-right: 1px solid #444; padding-top:0px; margin-left: 0px; height:16px;  background-color:none; width:'.$done_percentage.'%;">'.LF;
            $done_time .= '&nbsp;'.$step_minutes_text.' '.$tmp_message;
            $done_time .= '      </div>'.LF;
            $done_time .= '</div>'.LF;
         }else{
            $done_percentage = (100 / $done_percentage) *100;
            $style = ' height: 16px; border: 1px solid #444; background-color: #f23030; ';
            $done_time .= '         <div style="width: 300px; font-size: 10pt; '.$style.' color:#000000;">'.LF;
            $done_time .= '      <div style="border-right: 1px solid #444; margin-left: 0px; height:16px;  background-color:none; width:'.$done_percentage.'%;">'.LF;
            $done_time .= '&nbsp;'.$step_minutes_text.' '.$tmp_message;
            $done_time .= '      </div>'.LF;
            $done_time .= '</div>'.LF;
         }

         if ($item->getPlannedTime() >0 ){
            $minutes = $item->getPlannedTime();
            $time_type = $item->getTimeType();
            $tmp_message = $this->_translator->getMessage('COMMON_MINUTES');
            switch ($time_type){
               case 2:
                  $minutes = $minutes/60;
                  $tmp_message = $this->_translator->getMessage('COMMON_HOURS');
                  if ($minutes == 1){
                     $tmp_message = $this->_translator->getMessage('COMMON_HOUR');
                  }
                  break;
               case 3:
                  $minutes = ($minutes/60)/8;
                  $tmp_message = $this->_translator->getMessage('COMMON_DAYS');
                  if ($minutes == 1){
                     $tmp_message = $this->_translator->getMessage('COMMON_DAY');
                  }
                  break;
            }
            if ($this->_translator->getSelectedLanguage() == 'de'){
               $minutes = str_replace('.',',',$minutes);
            }

            $temp_array[0] = $this->_translator->getMessage('TODO_MINUTES');
            $temp_array[1] = $minutes.' '.$tmp_message;
            $formal_data[] = $temp_array;
         }

         elseif ($item->getPlannedTime() == 0 and $done_percentage >0 ){
            $tmp_message = $this->_translator->getMessage('COMMON_MINUTES');
            $done_time = $step_minutes;
            if (($step_minutes/60)>1 and ($step_minutes/60)<=8){
               $step_minutes_text = '';
               $exact_minutes = $step_minutes/60;
               $step_minutes = round($exact_minutes,1);
               $done_time = '';
               if ($step_minutes != $exact_minutes){
                  $done_time .= 'ca. ';
               }
               if ($this->_translator->getSelectedLanguage() == 'de'){
                  $step_minutes = str_replace('.',',',$step_minutes);
               }
               $done_time .= $step_minutes;
               $tmp_message = $this->_translator->getMessage('COMMON_HOURS');
               if ($step_minutes == 1){
                  $tmp_message = $this->_translator->getMessage('COMMON_HOUR');
               }

            }elseif(($step_minutes/60)>8){
               $exact_minutes = ($step_minutes/60)/8;
               $step_minutes = round($exact_minutes,1);
               $done_time = '';
               if ($step_minutes != $exact_minutes){
                  $done_time .= 'ca. ';
               }
               $tmp_message = $this->_translator->getMessage('COMMON_DAYS');
               if ($step_minutes == 1){
                  $tmp_message = $this->_translator->getMessage('COMMON_DAY');
               }
               if ($this->_translator->getSelectedLanguage() == 'de'){
                  $step_minutes = str_replace('.',',',$step_minutes);
               }
               $done_time .= $step_minutes;

            }else{
               $step_minutes = round($step_minutes,1);
               if ($this->_translator->getSelectedLanguage() == 'de'){
                  $step_minutes = str_replace('.',',',$step_minutes);
               }
            }
            $done_time .= ' '.$tmp_message;
         }


         if ($done_percentage >0 or $item->getPlannedTime() > 0){
            $temp_array[0] = $this->_translator->getMessage('TODO_DONE_MINUTES');
            $temp_array[1] = $done_time;
            $formal_data[] = $temp_array;
         }
      }
      $temp_array[0] = $this->_translator->getMessage('TODO_PROCESSORS');
      $temp_array[1] = $member_html;
      $formal_data[] = $temp_array;

      // Files
      $files = $this->_getFilesForFormalData($item);
     if ( !empty($files) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('MATERIAL_FILES');
         $temp_array[] = implode(BRLF, $files);
         $formal_data[] = $temp_array;
      }

      if ( !empty($formal_data) ) {
         $html .= $this->_getFormalDataAsHTML($formal_data);
      }


      if ($context->withTodoManagement()){
         $temp_array = array();
         $formal_data = array();
         $temp_array[0] = $this->_translator->getMessage('TODO_STEPS');
         $temp_array[1] = $step_html;
         $formal_data[] = $temp_array;
         if ( !empty($formal_data) ) {
            $html .= $this->_getFormalDataAsHTML($formal_data);
            $html .= BRLF;
         }
      }

      // Description
      $desc = $item->getDescription();
      if ( !empty($desc) ) {
         $desc = $this->_text_as_html_long($this->_compareWithSearchText($this->_cleanDataFromTextArea($desc)));
         $desc = $this->_show_images($desc,$item,true);
         $html .= $this->getScrollableContent($desc,$item,'',true).LF;
      }

      // creator, modificator and reference number for printing
      if(isset($_GET['mode']) and $_GET['mode']=='print'){
	      $modificator = $item->getModificatorItem();
	      $creator = $item->getCreatorItem();

	      if(isset($modificator) and !$modificator->isDeleted()){
	      	  $current_user_item = $this->_environment->getCurrentUserItem();
	          if ( $current_user_item->isGuest() ) {
	             $temp_modificator = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
	          } else {
	             $temp_modificator = $modificator->getFullname();
	          }
              unset($current_user_item);
	      } else {
	      	  $temp_modificator = $this->_translator->getMessage('COMMON_DELETED_USER');
	      }

	      if(isset($creator) and !$creator->isDeleted()){
	      	$current_user_item = $this->_environment->getCurrentUserItem();
	            if ( $current_user_item->isGuest() ) {
	               $temp_creator = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
	            } else {
	               $temp_creator = $creator->getFullname();
	            }
            unset($current_user_item);
	      } else {
	      	  $temp_creator = $this->_translator->getMessage('COMMON_DELETED_USER');
	      }

	      $html .= '<table class="creator_info" summary="Layout" style="padding-top:20px">'.LF;

      	  // Modificator information

      	  $html .= '   <tr>'.LF;
      	  $html .= '      <td></td>'.LF;
      	  $html .= '      <td class="key"  style="padding-left:8px;">'.LF;
      	  $html .= '         '.$this->_translator->getMessage('COMMON_LAST_MODIFIED_BY').':&nbsp;'.LF;
      	  $html .= '      </td>'.LF;
      	  $html .= '      <td class="value">'.LF;
      	  $html .= '         '.$temp_modificator.', '.$this->_translator->getDateTimeInLang($item->getModificationDate()).LF;
      	  $html .= '      </td>'.LF;
      	  $html .= '   </tr>'.LF;

      	  // Creator information

	      $html .= '   <tr>'.LF;
      	  $html .= '      <td></td>'.LF;
      	  $html .= '      <td class="key"  style="padding-left:8px;">'.LF;
      	  $html .= '         '.$this->_translator->getMessage('COMMON_CREATED_BY').':&nbsp;'.LF;
      	  $html .= '      </td>'.LF;
      	  $html .= '      <td class="value">'.LF;
      	  $html .= '         '.$temp_creator.', '.$this->_translator->getDateTimeInLang($item->getCreationDate()).LF;
      	  $html .= '      </td>'.LF;
      	  $html .= '   </tr>'.LF;

      	  // Reference number

      	  $html .= '   <tr>'.LF;
      	  $html .= '      <td></td>'.LF;
      	  $html .= '      <td class="key"  style="padding-left:8px;">'.LF;
      	  $html .= '         '.$this->_translator->getMessage('COMMON_REFNUMBER').':&nbsp;'.LF;
      	  $html .= '      </td>'.LF;
      	  $html .= '      <td class="value">'.LF;
      	  $html .= '         '.$item->getItemID();
      	  $html .= '      </td>'.LF;
      	  $html .= '   </tr>'.LF;
      	  $html .= '</table>'.LF;

      }

      $html  .= '<!-- END OF TODO ITEM DETAIL -->'.LF.LF;
      return $html;
   }

   /** get all the actions for this detail view as HTML
    * this method returns the actions in HTML-Code. It checks the access rights!
    *
    * @return string navigation as HMTL
    */
   function _getDetailItemActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $mod = $this->_with_modifying_actions;
      $html  = '';

      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';

      // edit
      $html .= $this->_getEditAction($item,$current_user);

      // Enter or leave the topic
      if ( $item->isProcessor($current_user) ) {
         if ($mod) {
            $params['iid'] = $item->getItemID();
            $params['todo_option'] = '2';
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_leave.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TODO_LEAVE').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_leave.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TODO_LEAVE').'"/>';
            }
            $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       'todo',
                                       'detail',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('TODO_LEAVE')).LF;
         } else {
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_leave_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TODO_LEAVE').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_leave_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TODO_LEAVE').'"/>';
            }
            $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('TODO_LEAVE')).' "class="disabled">'.$image.'</a>'.LF;
         }
      } else {
         if ($current_user->isUser() and $mod ) {
            $params['iid'] = $item->getItemID();
            $params['todo_option'] = '1';
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_enter.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TODO_ENTER').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_enter.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TODO_ENTER').'"/>';
            }
            $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       'todo',
                                       'detail',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('TODO_ENTER')).LF;
         } else {
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_enter_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TODO_ENTER').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_enter_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('TODO_ENTER').'"/>';
            }
            $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('TODO_ENTER')).' "class="disabled">'.$image.'</a>'.LF;
         }
      }

      // delete
      $html .= $this->_getDeleteAction($item,$current_user);

      $html .= $this->_initDropDownMenus();
      return $html.'&nbsp;&nbsp;&nbsp;';
   }


   function _getSubItemDetailActionsAsHTML ($subitem) {
      $user = $this->_environment->getCurrentUserItem();
      $item = $this->getItem();
      $html = '';

      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';

      // edit
      $html .= $this->_getEditAction($subitem,$current_user,'step');

      if ( $subitem->mayEdit($user) and $this->_with_modifying_actions  ) {
        $params = $this->_environment->getCurrentParameterArray();
        $params['action'] = 'delete';
        $params['step_iid'] = $subitem->getItemID();
        $params['iid'] = $item->getItemID();
        $params['step_action'] = 'delete';
        if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
           $image = '<img src="images/commsyicons_msie6/22x22/delete.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
        } else {
           $image = '<img src="images/commsyicons/22x22/delete.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
        }
        $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                       $this->_environment->getCurrentModule(),
                                       'detail',
                                       $params,
                                       $image,
                                       '',
                                       '',
                                       'anchor'.$subitem->getItemID(),
                                       '',
                                       '',
                                       '',
                                       '',
                                       '',
                                       'delete_confirm_entry').LF;
        unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_DELETE_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
      }
      return $html;
   }


   function _getSubItemsAsHTML($item){
      $html  = '';
      $html .= '<!-- BEGIN OF SUB ITEM DETAIL VIEW -->'.LF.LF;
      $html .= '<div style="width:100%; margin-top:40px;">'.LF;
      $html .= '<table style="border-collapse:collapse; width:100%; margin:0px; padding:0px;">'.LF;
      $count = 0;
      $subitems = $item->getStepItemList();
      if ( isset($subitems) and !empty($subitems) ){
         $count=$subitems->getCount();
      }
      if ( isset($subitems) and !$subitems->isEmpty() ) {
         $current_item = $subitems->getFirst();
         $pos_number = 1;
         while ( $current_item ) {
            $html .='<tr class="detail_discussion_entries">'.LF;

               $image = $this->_getItemPicture($current_item->getModificatorItem());
               $html .= '<td rowspan="3" style="width:60px; vertical-align:top; padding:20px 5px 5px 5px;">'.$image.'</td>'.LF;
               $html .='<td style="width:70%; padding-top:5px; vertical-align:bottom;">'.LF;
               if ( $current_item->isA(CS_STEP_TYPE) ) {
                  $html .= '<a id="anchor'.$pos_number.'" name="anchor'.$pos_number.'"></a>'.LF;
               }
               $html .='<div style="padding-top:10px;">'.LF;
               $html .= '<a id="anchor'.$current_item->getItemID().'" name="anchor'.$current_item->getItemID().'"></a>'.LF;
               $html .= '<h3 class="subitemtitle">'.$this->_getSubItemTitleAsHTML($current_item, $pos_number);
               $html .= '</h3>'.LF;
               $html .='</div>'.LF;
               $html .='</td>'.LF;
               if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
                  $html .='<td style="width:28%; padding-top:5px; padding-left:0px; padding-right:3px; vertical-align:bottom; text-align:right;">'.LF;
                  $html .= $this->_getSubItemDetailActionsAsHTML($current_item);
                  $html .='</td>'.LF;
               }else{
                  $html .='<td style="width:28%; padding-top:5px; padding-left:0px; padding-right:3px; vertical-align:bottom; text-align:right;">'.LF;
                  $html .= '&nbsp';
                  $html .='</td>'.LF;
               }
               $html .='</tr>'.LF;
               $html .='<tr>'.LF;
               $html .='<td colspan="2" class="infoborder" style="padding-top:5px; vertical-align:top; ">'.LF;
               if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
                  $html .='<div style="float:right; height:6px; font-size:2pt;">'.LF;
                  $html .= $this->_getBrowsingIconsAsHTML($current_item, $pos_number,$count);
                  $html .='</div>'.LF;
               }
               $html .= $this->_getSubItemAsHTML($current_item, $pos_number).LF;
               $html .='</td>'.LF;
               $html .='</tr>'.LF;
               if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
                  $html .='<tr>'.LF;
                  $html .='<td style="padding-top:5px; padding-bottom:30px; vertical-align:top; ">'.LF;
                  $mode = 'short';
                  if (!$item->isA(CS_USER_TYPE)) {
                     $mode = 'short';
                     if (in_array($current_item->getItemId(),$this->_openCreatorInfo)) {
                        $mode = 'long';
                     }
                     $html .= $this->_getCreatorInformationAsHTML($current_item, 6,$mode).LF;
                  }
                  $html .='</td>'.LF;
                  $html .='</tr>'.LF;
               }else{
                  $html .='<tr>'.LF;
                  $html .='<td style="padding-top:5px; padding-bottom:40px; vertical-align:top; ">'.LF;
                  $html .='</td>'.LF;
                  $html .='</tr>'.LF;
               }
            // set reader
       $reader_manager = $this->_environment->getReaderManager();
             $reader = $reader_manager->getLatestReader($current_item->getItemID());
       if ( empty($reader) or $reader['read_date'] < $current_item->getModificationDate() ) {
          $reader_manager->markRead($current_item->getItemID(),0);
       }
       // set Noticed
       $noticed_manager = $this->_environment->getNoticedManager();
       $noticed = $noticed_manager->getLatestNoticed($current_item->getItemID());
       if ( empty($noticed) or $noticed['read_date'] < $current_item->getModificationDate() ) {
          $noticed_manager->markNoticed($current_item->getItemID(),0);
       }

            $current_item = $subitems->getNext();
            $pos_number++;
         } // end while
      }

      $html .= '</table>'.LF;
      $html .= '<!-- END OF SUB ITEM DETAIL VIEW -->'.LF.LF;
      return $html;
   }


   function _getSubItemAsHTML ($item, $anchor_number) {
      $retour  = '';
      $minutes = $item->getMinutes();
      $time_type = $item->getTimeType();
      $tmp_message = $this->_translator->getMessage('COMMON_MINUTES');
      switch ($time_type){
         case 2:
            $minutes = $minutes/60;
            $tmp_message = $this->_translator->getMessage('COMMON_HOURS');
            if ($minutes == 1){
               $tmp_message = $this->_translator->getMessage('COMMON_HOUR');
            }
            break;
         case 3:
            $minutes = ($minutes/60)/8;
            $tmp_message = $this->_translator->getMessage('COMMON_DAYS');
            if ($minutes == 1){
               $tmp_message = $this->_translator->getMessage('COMMON_DAY');
            }
            break;
      }
      if ( $minutes > 0 ) {
         if ($this->_translator->getSelectedLanguage() == 'de'){
            $minutes = str_replace('.',',',$minutes);
         }
         $temp_array = array();
         $temp_array[0] = $this->_translator->getMessage('TODO_DONE_MINUTES');
         $temp_array[1] = $minutes.' '.$tmp_message;
         $formal_data[] = $temp_array;
         $retour .= $this->_getFormalDataAsHTML($formal_data);
         $formal_data = array();
      }
      $desc = $item->getDescription();
      if ( !empty($desc) ) {
         $desc = $this->_text_as_html_long($this->_compareWithSearchText($this->_cleanDataFromTextArea($desc)));
         $desc = $this->_show_images($desc,$item,true);
         $retour .= $this->getScrollableContent($desc,$item,'',true).LF;
      }
      $retour  = '<div style="margin-left: 3px;">'.$retour.'</div>'.LF;

      // Files
      $retour .= '<div style="clear:both;"></div>'.LF;
      $files = $this->_getFilesForFormalData($item);
      if ( !empty($files) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('MATERIAL_FILES');
         $temp_array[] = implode(BRLF, $files);
         $formal_data[] = $temp_array;
      }

      if ( !empty($formal_data) ) {
         $retour .= $this->_getFormalDataAsHTML($formal_data);
      }


      // Creator / Modificator information
      if(isset($_GET['mode']) and $_GET['mode']=='print'){
      	$modificator = $item->getModificatorItem();
      	$creator = $item->getCreatorItem();

      	if(isset($modificator) and !$modificator->isDeleted()){
	      	  $current_user_item = $this->_environment->getCurrentUserItem();
	          if ( $current_user_item->isGuest() ) {
	             $temp_modificator = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
	          } else {
	             $temp_modificator = $modificator->getFullname();
	          }
              unset($current_user_item);
	      } else {
	      	  $temp_modificator = $this->_translator->getMessage('COMMON_DELETED_USER');
	      }

	      if(isset($creator) and !$creator->isDeleted()){
	      	$current_user_item = $this->_environment->getCurrentUserItem();
	            if ( $current_user_item->isGuest() ) {
	               $temp_creator = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
	            } else {
	               $temp_creator = $creator->getFullname();
	            }
            unset($current_user_item);
	      } else {
	      	  $temp_creator = $this->_translator->getMessage('COMMON_DELETED_USER');
	      }

	      $retour .= '<table class="creator_info" summary="Layout" style="padding-top:20px">'.LF;

      	  // Modificator information
      	  $retour .= '   <tr>'.LF;
      	  $retour .= '      <td></td>'.LF;
      	  $retour .= '      <td class="key"  style="padding-left:8px;">'.LF;
      	  $retour .= '         '.$this->_translator->getMessage('COMMON_LAST_MODIFIED_BY').':&nbsp;'.LF;
      	  $retour .= '      </td>'.LF;
      	  $retour .= '      <td class="value">'.LF;
      	  $retour .= '         '.$temp_modificator.', '.$this->_translator->getDateTimeInLang($item->getModificationDate()).LF;
      	  $retour .= '      </td>'.LF;
      	  $retour .= '   </tr>'.LF;

      	  // Creator information
	      $retour .= '   <tr>'.LF;
      	  $retour .= '      <td></td>'.LF;
      	  $retour .= '      <td class="key"  style="padding-left:8px;">'.LF;
      	  $retour .= '         '.$this->_translator->getMessage('COMMON_CREATED_BY').':&nbsp;'.LF;
      	  $retour .= '      </td>'.LF;
      	  $retour .= '      <td class="value">'.LF;
      	  $retour .= '         '.$temp_creator.', '.$this->_translator->getDateTimeInLang($item->getCreationDate()).LF;
      	  $retour .= '      </td>'.LF;
      	  $retour .= '   </tr>'.LF;

      	  $retour .= '</table>'.LF;

      }
      return $retour;
   }

   function _getTodoFormAsHTML(){
      if(!(isset($_GET['mode']) and $_GET['mode'] == 'print')) {
         $item = $this->getItem();
         $count = 1;
         $subitems = $item->getStepItemList();
         if ( isset($subitems) and !empty($subitems) ){
            $count = $subitems->getCount();
            $count++;
         }

         $html  = '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '<!-- BEGIN OF STEP FORM VIEW -->'.LF.LF;

         $html .= '<div class="sub_item_main" style="border-top: 1px solid #B0B0B0; margin-top:20px; padding-top:5px; background-color:white;">'.LF;
         $html .= '<div style="width:100%;" >'.LF;
         $html .= '<a name="step_form"></a>'.LF;

         $class_factory = $this->_environment->getClassFactory();
         $class_params = array();
         $class_params['environment'] = $this->_environment;
         $form = $class_factory->getClass(STEP_FORM,$class_params);
         $form->setDetailMode($count);
         $form->setRefId($item->getItemID());
         unset($class_params);
         $form->prepareForm();
         $form->loadValues();
         $class_params = array();
         $class_params['environment'] = $this->_environment;
         $class_params['with_modifying_actions'] = true;
         $form_view = $class_factory->getClass(FORM_DETAIL_VIEW,$class_params);
         unset($class_params);
         $form_view->setAction(curl($this->_environment->getCurrentContextID(),'step','edit',array()));
         $form_view->setForm($form);
         $html .= $form_view->asHTML();

         $html .= '<!-- END OF STEP FORM VIEW -->'.LF.LF;
         return $html;
      }
   }

  /** get the file list of the item
    * this method returns the item file list in the right formatted style
    *
    * @return string file list
    */
   function _getItemFiles($item, $with_links=true){
      $file_list='';
      $files = $item->getFileList();
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
      return $file_list;
   }


}
?>