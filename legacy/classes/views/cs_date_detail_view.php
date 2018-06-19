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

$this->includeClass(DETAIL_VIEW);


/**
 *  class for CommSy detail view: date
 */
class cs_date_detail_view extends cs_detail_view {

/** array of ids in clipboard*/
var $_clipboard_id_array=array();


   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
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


   /** get all the actions for this detail view as HTML
    * this method returns the actions in HTML-Code. It checks the access rights!
    *
    * @return string navigation as HMTL
    *
    * @author CommSy Development Group
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
      if ( $item->isParticipant($current_user) ) {
         if ($mod) {
            $params['iid'] = $item->getItemID();
            $params['date_option'] = '2';
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_leave.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATE_LEAVE').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_leave.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATE_LEAVE').'"/>';
            }
            $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       $this->_environment->getCurrentModule(),
                                       'detail',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('DATE_LEAVE')).LF;
         } else {
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_leave_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATE_LEAVE').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_leave_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATE_LEAVE').'"/>';
            }
            $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('DATE_LEAVE')).' "class="disabled">'.$image.'</a>'.LF;
         }
      } else {
         if ($current_user->isUser() and $mod ) {
            $params['iid'] = $item->getItemID();
            $params['date_option'] = '1';
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_enter.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATE_ENTER').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_enter.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATE_ENTER').'"/>';
            }
            $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       $this->_environment->getCurrentModule(),
                                       'detail',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('DATE_ENTER')).LF;
         } else {
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_enter_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATE_ENTER').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_enter_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATE_ENTER').'"/>';
            }
            $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('DATE_ENTER')).' "class="disabled">'.$image.'</a>'.LF;
         }
      }

      // delete
      $html .= $this->_getDeleteAction($item,$current_user);

      return $html.'&nbsp;&nbsp;&nbsp;';
   }


   /** get the single entry of the list view as HTML
    * this method returns the single entry in HTML-Code
    *
    * @returns string $item as HMTL
    *
    * @param object item     the single list entry
    */
   function _getItemAsHTML($item) {
      $html  = LF.'<!-- BEGIN OF DATE ITEM DETAIL -->'.LF;

      // DATE AND TIME //
      $formal_data  = array();

      // set up style of days and times
      $parse_time_start = convertTimeFromInput($item->getStartingTime());
      $conforms = $parse_time_start['conforms'];
      if ($conforms == TRUE) {
         $start_time_print = getTimeLanguage($parse_time_start['datetime']);
      } else {
         $start_time_print = $this->_text_as_html_short($this->_compareWithSearchText($item->getStartingTime()));
      }

      $parse_time_end = convertTimeFromInput($item->getEndingTime());
      $conforms = $parse_time_end['conforms'];
      if ($conforms == TRUE) {
         $end_time_print = getTimeLanguage($parse_time_end['datetime']);
      } else {
         $end_time_print = $this->_text_as_html_short($this->_compareWithSearchText($item->getEndingTime()));
      }

     $parse_day_start = convertDateFromInput($item->getStartingDay(),$this->_environment->getSelectedLanguage());
      $conforms = $parse_day_start['conforms'];
      if ($conforms == TRUE) {
        $start_day_print = $item->getStartingDayName().', '.$this->_translator->getDateInLang($parse_day_start['datetime']);
      } else {
         $start_day_print = $this->_text_as_html_short($this->_compareWithSearchText($item->getStartingDay()));
      }

      $parse_day_end = convertDateFromInput($item->getEndingDay(),$this->_environment->getSelectedLanguage());
      $conforms = $parse_day_end['conforms'];
      if ($conforms == TRUE) {
         $end_day_print =$item->getEndingDayName().', '.$this->_translator->getDateInLang($parse_day_end['datetime']);
      } else {
         $end_day_print =$this->_text_as_html_short($this->_compareWithSearchText($item->getEndingDay()));
      }
      //formating dates and times for displaying
      $date_print ="";
      $time_print ="";

      if ($end_day_print != "") { //with ending day
         $date_print = $this->_translator->getMessage('DATES_AS_OF').' '.$start_day_print.' '.$this->_translator->getMessage('DATES_TILL').' '.$end_day_print;
         if ($parse_day_start['conforms']
             and $parse_day_end['conforms']) { //start and end are dates, not strings
           $date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$this->_translator->getMessage('DATES_DAYS').')';
         }

         if ($start_time_print != "" and $end_time_print =="") { //starting time given
            $time_print = $this->_translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
             if ($parse_time_start['conforms'] == true) {
               $time_print .= ' '.$this->_translator->getMessage('DATES_OCLOCK');
            }
         } elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
            $time_print = $this->_translator->getMessage('DATES_TILL').' '.$end_time_print;
            if ($parse_time_end['conforms'] == true) {
               $time_print .= ' '.$this->_translator->getMessage('DATES_OCLOCK');
            }
         } elseif ($start_time_print != "" and $end_time_print !="") { //all times given
            if ($parse_time_end['conforms'] == true) {
               $end_time_print .= ' '.$this->_translator->getMessage('DATES_OCLOCK');
            }
            if ($parse_time_start['conforms'] == true) {
               $start_time_print .= ' '.$this->_translator->getMessage('DATES_OCLOCK');
            }
            $date_print = $this->_translator->getMessage('DATES_AS_OF').' '.$start_day_print.', '.$start_time_print.'<br />'.
                          $this->_translator->getMessage('DATES_TILL').' '.$end_day_print.', '.$end_time_print;
            if ($parse_day_start['conforms']
                and $parse_day_end['conforms']) {
               $date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$this->_translator->getMessage('DATES_DAYS').')';
            }
         }

      } else { //without ending day
         $date_print = $this->_translator->getMessage('DATES_ON_DAY').' '.$start_day_print;
         if ($start_time_print != "" and $end_time_print =="") { //starting time given
             $time_print = $this->_translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
             if ($parse_time_start['conforms'] == true) {
               $time_print .= ' '.$this->_translator->getMessage('DATES_OCLOCK');
            }
         } elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
            $time_print = $this->_translator->getMessage('DATES_TILL').' '.$end_time_print;
            if ($parse_time_end['conforms'] == true) {
               $time_print .= ' '.$this->_translator->getMessage('DATES_OCLOCK');
            }
         } elseif ($start_time_print != "" and $end_time_print !="") { //all times given
            if ($parse_time_end['conforms'] == true) {
               $end_time_print .= ' '.$this->_translator->getMessage('DATES_OCLOCK');
            }
            if ($parse_time_start['conforms'] == true) {
               $start_time_print .= ' '.$this->_translator->getMessage('DATES_OCLOCK');
            }
            $time_print = $this->_translator->getMessage('DATES_FROM_TIME_LOWER').' '.$start_time_print.' '.$this->_translator->getMessage('DATES_TILL').' '.$end_time_print;
         }
      }

      if ($parse_day_start['timestamp'] == $parse_day_end['timestamp'] and $parse_day_start['conforms'] and $parse_day_end['conforms']) {
         $date_print = $this->_translator->getMessage('DATES_ON_DAY').' '.$start_day_print;
         if ($start_time_print != "" and $end_time_print =="") { //starting time given
             $time_print = $this->_translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
         } elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
            $time_print = $this->_translator->getMessage('DATES_TILL').' '.$end_time_print;
         } elseif ($start_time_print != "" and $end_time_print !="") { //all times given
            $time_print = $this->_translator->getMessage('DATES_FROM_TIME_LOWER').' '.$start_time_print.' '.$this->_translator->getMessage('DATES_TILL').' '.$end_time_print;
         }
      }

      // Date and time
      $temp_array = array();
      $temp_array[] = $this->_translator->getMessage('DATES_DATETIME');
      if ($time_print != '') {
         $temp_array[] = $date_print.BRLF.$time_print;
      } else {
         $temp_array[] = $date_print;
      }
      $formal_data[] = $temp_array;

      // Place
      $place = $item->getPlace();
      if (!empty($place)) {
         $place = $this->_compareWithSearchText($place);
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('DATES_PLACE');
         $temp_array[] = $this->_text_as_html_short($place);
         $formal_data[] = $temp_array;
      }

      // Color
      $color = $item->getColor();
      if (!empty($color)) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('DATES_COLOR');
         $temp_array[] = '<img src="images/spacer.gif" style="height:10px; width:10px; background-color:' . $this->_text_as_html_short($color) . '; border:1px solid #cccccc;"/>';
         $formal_data[] = $temp_array;
      }

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
         $html .= BRLF;
      }

      // Members
      $user = $this->_environment->getCurrentUser();
      $member_html = '';
      $members = $item->getParticipantsItemList();
      if ( $members->isEmpty() ) {
         $member_html .= '   '.$this->_translator->getMessage('TODO_NO_PROCESSOR').LF;
      } else {
         $member = $members->getFirst();
         $count = $members->getCount();
         $counter = 0;
         while ($member) {
            $counter++;
            if ( $member->isUser() ){
               $linktext = $member->getFullname();
               $linktext = $this->_compareWithSearchText($linktext);
               $linktext = $this->_text_converter->text_as_html_short($linktext);
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
               $link_title = chunkText($member->getFullName(),35);
               $link_title = $this->_compareWithSearchText($link_title);
               $link_title = $this->_text_converter->text_as_html_short($link_title);
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
      $temp_array[0] = $this->_translator->getMessage('DATE_PARTICIPANTS');
      $temp_array[1] = $member_html;
      $formal_data = array();
      $formal_data[] = $temp_array;
      if ( !empty($formal_data) ) {
         $html .= $this->_getFormalDataAsHTML($formal_data);
         $html .= BRLF;
      }

      // Description
      $desc = $item->getDescription();
      if ( !empty($desc) ) {
         $desc = $this->_text_as_html_long($this->_compareWithSearchText($this->_cleanDataFromTextArea($desc)));
         $desc = $this->_show_images($desc,$item,true);
         $html .= $this->getScrollableContent($desc,$item,'',true).LF;
      }

      $html  .= '<!-- END OF DATE ITEM DETAIL -->'."\n\n";
      return $html;
   }

	/** get information for header as HTML
    * this method returns information in HTML-Code needs for the header of the HTML-Page
    *
    * @return string javascipt needed for the form
    */
   function getInfoForHeaderAsHTML() {
      $text2 = '';
      if($this->_environment->getCurrentUserItem()->isModerator()) {
         $text2 = $this->_translator->getMessage("COMMON_DELETE_BOX_DESCRIPTION_MODERATOR");
      }
      $return = "
          <script type='text/javascript'>
          <!--
              var headline = '" . $this->_translator->getMessage("COMMON_DELETE_BOX_TITLE") . "';
              var text1 = '" . $this->_translator->getMessage("COMMON_DELETE_BOX_DESCRIPTION") . "';
              var text2 = '" . $text2 . "';";
              if(isset($_GET['iid']) and !empty($_GET['iid'])){
   	             $dates_manager = $this->_environment->getDatesManager();
   	             $date_item = $dates_manager->getItem($_GET['iid']);
   	             $recurrence_id = $date_item->getRecurrenceId();
   	             if (!empty($recurrence_id)){
                    $return .= "
                    var extra_text = '" . $this->_translator->getMessage("COMMON_DELETE_RECURRENCE_BUTTON") . "';
                    var extra_content = '<input type=\"submit\" value=\"" . $this->_translator->getMessage("COMMON_DELETE_RECURRENCE_BUTTON") . "\" name=\"delete_option\" style=\"float: right; margin-right: 3px;\" onClick=\"delete_date_recurring(); return false;\">';
                    ";
   	             }
              }
              $return .= "
              var button_delete = '" . $this->_translator->getMessage("COMMON_DELETE_BUTTON") . "';
              var button_cancel = '" . $this->_translator->getMessage("COMMON_CANCEL_BUTTON") . "';
          -->
          </script>
      ";

      return $return;
   }

/*
   function _getTitleAsHTML () {
      $item = $this->getItem();
      if ( isset($item) ){
         $html = $this->_compareWithSearchText($item->getTitle(),false);
      } else {
         $html = 'NO ITEM';
      }
      $html = $this->_text_as_html_short($html);
      if ($item->issetPrivatDate()){
         $html .=' <span class="changed"><span class="disabled">['.$this->_translator->getMessage('DATE_PRIVATE_ENTRY').']</span></span>';
      }
      return $html;
   }
*/
}
?>