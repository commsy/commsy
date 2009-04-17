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
include_once('functions/text_functions.php');
include_once('functions/date_functions.php');

/**
 *  class for CommSy list view: date
 */
class cs_date_calendar_index_view extends cs_room_index_view {

   var $_clipboard_id_array = array();
   var $_month;
   var $_year;
   var $_week;
   var $_with_modifying_actions;
   var $_selected_status = NULL;
   var $_display_mode = NULL;
   var $_presentation_mode = '1';
   var $_week_start;

   /** set the content of the list view
    * this method sets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    */
    function setList ($list) {
       $this->_list = $list;
       if (!empty($this->_list)){
          $id_array = array();
          $item = $list->getFirst();
          while($item){
             $id_array[] = $item->getModificatorID();
             $item = $list->getNext();
          }
          $user_manager = $this->_environment->getUserManager();
          $user_manager->getRoomUserByIDsForCache($this->_environment->getCurrentContextID(),$id_array);
       }
    }

    function setPresentationMode($mode){
       $this->_presentation_mode = $mode;
    }

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_date_calendar_index_view ($params) {
      $this->cs_room_index_view($params);
      $this->setTitle($this->_translator->getMessage('DATES_HEADER'));
      $this->setActionTitle($this->_translator->getMessage('COMMON_DATES'));
      if ( $this->_with_modifying_actions ) {
         $params = array();
         $params['iid'] = 'NEW';
         $parameter_array = $this->_environment->getCurrentParameterArray();
         if (isset ($parameter_array['year'])){
            $params['year'] = $parameter_array['year'];
         }
         if (isset ($parameter_array['month'])){
            $params['month'] = $parameter_array['month'];
         }
         $anAction = ahref_curl( $this->_environment->getCurrentContextID(),
                                 CS_DATE_TYPE,
                                 'edit',
                                 $params,
                                 $this->_translator->getMessage('COMMON_NEW_DATE')).' | ';
         $this->addAction($anAction);
      } else {
         $anAction = '<span class="disabled">'.$this->_translator->getMessage('COMMON_NEW_DATE').'</span> | ';
         $this->addAction($anAction);
      }
      if ( $this->_environment->inPrivateRoom() ){
            $params['import'] = 'yes';
            $anAction = ahref_curl( $this->_environment->getCurrentContextID(),
                                 CS_DATE_TYPE,
                                 'import',
                                 $params,
                                 $this->_translator->getMessage('COMMON_IMPORT_DATES')).' | ';
            unset($params);
            $this->addAction($anAction);
      }
      $params = array();
      $params['seldisplay_mode'] = 'normal';
      $anAction = ahref_curl( $this->_environment->getCurrentContextID(),
                                 CS_DATE_TYPE,
                                 'index',
                                 $params,
                                 $this->_translator->getMessage('DATES_COMMON_DISPLAY'));
      unset($params);
      $this->addAction($anAction);
   }

   function setClipboardIDArray($cia) {
      $this->_clipboard_id_array = $cia;
   }

   function getClipboardIDArray() {
      return $this->_clipboard_id_array;
   }


   function setYear($year) {
      $this->_year = $year;
   }

   function setWeek($week) {
      $this->_week = $week;
      $this->_week_start  = $week;
   }

   function setMonth($month) {
      $this->_month = $month;
   }

   function setDisplayMode($status){
      $this->_display_mode = $status;
   }

   /** get list view as HTML
    * this method returns the list view in HTML-Code
    *
    * @return string list view as HMTL
    *
    * @author CommSy Development Group
    */
   function asHTML () {
      $html  = LF.'<!-- BEGIN OF LIST VIEW -->'.LF;
      $html .='<div style="width:100%;">'.LF;
      // @segment-end 16772
      // @segment-begin 61726 complete:asHTML():style_cell_1:1
      $html .= '<div class="indexdate" style="width: 27%; float:right; padding-top:8px; padding-right:3px; text-align:right;">'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['week']);
      unset($params['year']);
      unset($params['month']);
      unset($params['presentation_mode']);
      $params['seldisplay_mode'] = 'normal';
      $html .= getMessage('DATE_ALTERNATIVE_DISPLAY').': '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$params,$this->_translator->getMessage('DATES_COMMON_DISPLAY')).LF;
      $html .= '</div>'.LF;
      $html .='<div style="width:71%; padding-left:3px;">'.LF;
     $html .='<div>'.LF;
      // @segment-end 17331
      // @segment-begin 64852 asHTML():display_rubrik_title/rubrik_clipboard_title
      $date = date("Y-m-d");
      $date_array = explode('-',$date);
      $month = mb_substr($this->_month,4,2);
      $first_char = mb_substr($month,0,1);
      if ($first_char == '0'){
         $month = mb_substr($month,1,2);
      }
      $month_array = array($this->_translator->getMessage('DATES_JANUARY_LONG'),
            $this->_translator->getMessage('DATES_FEBRUARY_LONG'),
            $this->_translator->getMessage('DATES_MARCH_LONG'),
            $this->_translator->getMessage('DATES_APRIL_LONG'),
            $this->_translator->getMessage('DATES_MAY_LONG'),
            $this->_translator->getMessage('DATES_JUNE_LONG'),
            $this->_translator->getMessage('DATES_JULY_LONG'),
            $this->_translator->getMessage('DATES_AUGUST_LONG'),
            $this->_translator->getMessage('DATES_SEPTEMBER_LONG'),
            $this->_translator->getMessage('DATES_OCTOBER_LONG'),
            $this->_translator->getMessage('DATES_NOVEMBER_LONG'),
            $this->_translator->getMessage('DATES_DECEMBER_LONG'));
      $tempMessage = $month_array[$month-1].' '.$this->_year;
      if ($this->_clipboard_mode){
          $html .= '<h2 class="pagetitle">'.getMessage('CLIPBOARD_HEADER').' ('.$tempMessage.')';
      }elseif ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions' ) {
         $html .= '<h2 class="pagetitle">'.getMessage('COMMON_ASSIGN').' ('.$tempMessage.')';
      }else{
          $html .= '<h2 class="pagetitle">'.$tempMessage;
      }
      $html .= '</h2>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;

      $params = $this->_environment->getCurrentParameterArray();
      $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(),
                                                                      $this->_environment->getCurrentModule(),
                                                                      $this->_environment->getCurrentFunction(),
                                                                      $params).'" method="get" name="indexform">'.LF;
      $html .= '<table>'.LF;
      $html .= $this->_getTableheadAsHTML();
      $html .='<tr>'.LF;
      $html .='<td colspan="3" style="padding-top:2px; vertical-align:top; ">'.LF;
      $html .= '<table class="list" style="width: 100%; border-collapse: collapse; border: 0px;" summary="Layout">'.LF;
      if ($this->_presentation_mode == '2'){
         $html .= $this->_getMonthContentAsHTML();
      }else{
         $html .= $this->_getWeekContentAsHTML();
      }
      $html .= '</table>'.LF;
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .= $this->_getTableFootAsHTML();
      $html .= '</table>'.BRLF;
      $html .= '</form>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .= '<!-- END OF PLAIN LIST VIEW -->'.LF.LF;
      return $html;
   }


   /** get the description of the list view title as HTML
    * this method returns the description in HTML-Code
    *
    * @return string $this->_description as HMTL
    *
    * @author CommSy Development Group
    */
   function _getDescriptionAsHTML() {
      // short names for easy reading
      $from      = $this->_from;
      $interval  = $this->_interval;
      $count_all = $this->_count_all;
      $count_all_shown = $this->_count_all_shown;
      $html ='';
      if ($count_all_shown == $count_all){
         $description = $this->_translator->getMessage('COMMON_X_ENTRIES', $count_all_shown);
      }else{
         $description = $this->_translator->getMessage('COMMON_X_ENTRIES_FROM_ALL',
                                                       $count_all_shown,
                                                       $count_all
                                                      );
      }
      if ( !empty($description) ) {
         $html .= $description;
      }
      return $html;
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

   function _getAdditionalFormFieldsAsHTML () {
       $current_context = $this->_environment->getCurrentContextItem();
       $width = '12';
      $html  ='<table style="border-collapse:collapse;" summary="Layout">';
      $html .='<tr>';
      $html .= '<td class="key">'.getMessage('COMMON_SEARCHFIELD').BRLF;
      // Search / select form
      $session_item = $this->_environment->getSessionItem();
      $session_id = $session_item->getSessionID();
      unset($session_item);
      $html .= '   <input type="hidden" name="SID" value="'.$this->_text_as_form($session_id).'"/>'.LF;
      $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
      $html .= '   <input type="hidden" name="mod" value="'.$this->_text_as_form($this->_module).'"/>'.LF;
      $html .= '   <input type="hidden" name="fct" value="'.$this->_text_as_form($this->_function).'"/>'.LF;
      $html .= '   <input type="hidden" name="sort" value="'.$this->_text_as_form($this->getSortKey()).'"/>'.LF;
      $html .= '<input style="width:'.$width.'em; font-size:10pt;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/>'.LF;
      $selstatus = $this->getSelectedStatus();
      $html .= '<td class="key">'.$this->_translator->getMessage('COMMON_DATE_STATUS').BRLF;
      $html .= '   <select name="selstatus" size="1" style="width:12em;" onChange="javascript:document.indexform.submit()">'.LF;
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
      $html .='</td>';
      $context_item = $this->_environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  array();
      }
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none' ) {
            if ($context_item->_is_perspective($link_name[0]) and $context_item->withRubric($link_name[0])) {
               $list = $this->getAvailableRubric($link_name[0]);
               $selrubric = $this->getSelectedRubric($link_name[0]);
               $temp_link = mb_strtoupper($link_name[0], 'UTF-8');
               switch ( $temp_link )
               {
                  case 'GROUP':
                     $html .= '<td class="key">'.$this->_translator->getMessage('COMMON_GROUP');
                     break;
                  case 'INSTITUTION':
                     $html .= '<td class="key">'.$this->_translator->getMessage('COMMON_INSTITUTION');
                     break;
                  case 'TOPIC':
                     $html .= '<td class="key">'.$this->_translator->getMessage('COMMON_TOPIC');
                     break;
                  default:
                     $html .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_datescalendar_index_view(341) ';
                     break;
               }
               $html .= BRLF;

               if ( isset($list)) {
                  $html .= '   <select style="width: '.$width.'em; font-size:10pt;" name="sel'.$link_name[0].'" size="1" onChange="javascript:document.indexform.submit()">'.LF;
                  $html .= '      <option value="0"';
                  if ( !isset($selrubric) || $selrubric == 0 ) {
                     $html .= ' selected="selected"';
                  }
                  $html .= '>*'.$this->_translator->getMessage('COMMON_NO_SELECTION').'</option>'.LF;
                  $html .= '   <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
                  $sel_item = $list->getFirst();
                  while ( $sel_item ) {
                     $html .= '      <option value="'.$sel_item->getItemID().'"';
                     if ( isset($selrubric) and $selrubric == $sel_item->getItemID() ) {
                        $html .= ' selected="selected"';
                     }
                     $text = $this->_Name2SelectOption($sel_item->getTitle());
                     $html .= '>'.$text.'</option>'.LF;
                     $sel_item = $list->getNext();
                 }
                 $html .= '   <option class="disabled" disabled="disabled" value="-1">------------------------------</option>'.LF;
                 $html .= '      <option value="-1"';
                 if ( !isset($selrubric) || $selrubric == -1 ) {
                    $html .= ' selected="selected"';
                 }
                 $html .= '>*'.$this->_translator->getMessage('COMMON_NOT_LINKED').'</option>'.LF;
                 $html .= '   </select>'.LF;
             } else {
           $html.='';
             }
             $html .='</td>';
            }
         }
      }
       $html .= '<td class="key">'.BRLF;
      $html .= '<input style="margin-top:5px; font-size:8p;" name="option" value="'.getMessage('COMMON_SHOW_BUTTON').'" type="submit"/>'.BRLF;
       $html .= '</td>'.LF;

      $html .='</tr>';
      $html .='</table>';
      return $html;
   }


   function _getTableFootAsHTML() {
      $html ='';
      $html .= '   <tr>'.LF;
      $html .= '      <td colspan="3">'.LF;
      $html .= $this->_getAdditionalFormFieldsAsHTML().LF;
      $html .= '      </td>'.LF;
      $html .= '   </tr>'.LF;
      $html .= '   <tr>'.LF;
      $html .= '      <td colspan="3" class="key">'.LF;
      // short names for easy reading
      $from      = $this->_from;
      $interval  = $this->_interval;
      $count_all = $this->_count_all;
      $count_all_shown = $this->_count_all_shown;
      if ($count_all_shown == $count_all){
         $description = $this->_translator->getMessage('COMMON_X_ENTRIES', $count_all_shown);
      }else{
         $description = $this->_translator->getMessage('COMMON_X_ENTRIES_FROM_ALL',
                                                       $count_all_shown,
                                                       $count_all
                                                      );
      }
      if ( !empty($description) and $this->_presentation_mode == '2') {
         $html .= $description;
      }
      $html .= '      </td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }

   function _getTableheadAsHTML() {
      $params = $this->_getGetParamsAsArray();
      // Optimierungsbedarf: Die $this->_translator->getMessage wird 11Mal!!! umsonst aufgerufen
      $current_time = localtime();
      $month = getLongMonthName($current_time[4]);
      $html  = '   <tr>'.LF;
      $html .= '      <td class="infoborderyear"  style="vertical-align:bottom;">'.LF;

      $html .= '   <select style="width: 10em; font-size:10pt;" name="presentation_mode" size="1" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '      <option value="2"';
      if ($this->_presentation_mode == '2'){
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('DATE_MONTH_PRESENTATION').'</option>'.LF;
      $html .= '      <option value="1"';
      if ($this->_presentation_mode != '2'){
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('DATE_WEEK_PRESENTATION').'</option>'.LF;
      $html .= '   </select>'.LF;

      $html .= '</td>'.LF;
      $html .= '<td colspan="2" class="infoborderweek"  style="vertical-align:bottom; text-align:right; white-space:nowrap;">'.LF;
      $html .= $this->_getWeekList();
      $html .= '&nbsp;&nbsp;&nbsp;';
      $html .= $this->_getMonthList();
      $html .= '&nbsp;&nbsp;&nbsp;';
      $html .= $this->_getYearList();

      $html .= '<noscript><input type="submit" style="font-size:10pt; width:2em;" name="room_change" value="'.$this->_translator->getMessage('COMMON_GO_BUTTON2').'"/></noscript>'.LF;

      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }




   function _getYearList() {
      $prev_image = '<img src="images/browse_left3.gif" alt="&lt;" border="0"/>';
      $next_image = '<img src="images/browse_right3.gif" alt="&lt;" border="0"/>';
      if (!isset($this->_year) or empty($this->_year)){
         $year = date("Y");
      }else{
         $year = $this->_year;
      }
      $html = '   <select name="year" size="1" style="width:5em;" onChange="javascript:document.indexform.submit()">'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['week']);
      unset($params['month']);
      for ( $i = $year - 4; $i < $year + 4; $i++ ) {
         $html .= '<option value="'.$i.'"';
         if ( $i == $year ) {
            $html .= ' selected="selected"';
            $params['year'] = $year-1;
            $left = '           '.ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                                'index',
                                $params,
                                $prev_image,
                                '').LF;
            $params['year'] = $year+1;
            $right = '           '.ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                                'index',
                                $params,
                                $next_image,
                                '').LF;
         }
         $html .= '>'.$i.'</option>';
      }
      $html .= '   </select>'.LF;
      return getMessage('COMMON_YEAR').':'.$left.$html.$right;;
   }



   function _getWeekList() {
      $html ='';
      $current_date = getdate();
      $month_array = array($this->_translator->getMessage('DATES_JANUARY_SHORT'),
            $this->_translator->getMessage('DATES_FEBRUARY_SHORT'),
            $this->_translator->getMessage('DATES_MARCH_SHORT'),
            $this->_translator->getMessage('DATES_APRIL_SHORT'),
            $this->_translator->getMessage('DATES_MAY_SHORT'),
            $this->_translator->getMessage('DATES_JUNE_SHORT'),
            $this->_translator->getMessage('DATES_JULY_SHORT'),
            $this->_translator->getMessage('DATES_AUGUST_SHORT'),
            $this->_translator->getMessage('DATES_SEPTEMBER_SHORT'),
            $this->_translator->getMessage('DATES_OCTOBER_SHORT'),
            $this->_translator->getMessage('DATES_NOVEMBER_SHORT'),
            $this->_translator->getMessage('DATES_DECEMBER_SHORT'));
      if (!isset($this->_week) or empty($this->_week)){
         $d_time = mktime(3,0,0,date("m"),date("d"),date("Y") );
         $wday = date("w",$d_time );
         $week = mktime (3,0,0,date("m"),date("d") - ($wday - 1),date("Y"));
      }else{
         $week = $this->_week;
      }
      $html .= '   <select name="week" size="1" style="width:10em;" onChange="javascript:document.indexform.submit()">'.LF;
      for ( $i = -4; $i <= 7; $i++ ) {
         $twkstart = $week + ( 3600 * 24 * 7 * $i );
         $twkend = $twkstart + ( 3600 * 24 * 6 );
         $startmonth = date("m", $twkstart);
         $startmonth = $month_array[$startmonth-1];
         $startday = date("d",$twkstart);
         $endmonat = date("m",$twkend);
         $endmonat = $month_array[$endmonat-1];
         $endtag = date("d",$twkend);
         $language = $this->_environment->getSelectedLanguage();
         if ( $language=='en'){
            $text = $startmonth.' '.$startday.' - '.$endmonat.' '.$endtag;
         }else{
            $first_char = mb_substr($startday,0,1);
            if ($first_char == '0'){
               $startday = mb_substr($startday,1,2);
            }
            $first_char = mb_substr($endtag,0,1);
            if ($first_char == '0'){
               $endtag = mb_substr($endtag,1,2);
            }
            $text = $startday.'. '.$startmonth.' - '.$endtag.'. '.$endmonat;
         }
         $html .='<option value="'. $twkstart.'"';
         if ( $this->_week == $twkstart ){
            $html .=' selected="selected"';
            $this->_week_start = $twkstart;
         }
         $html .= '>';
         $html .= $text;
         $html .= '</option>'.LF;
      }
      $html .= '   </select>'.LF;
      $prev_image = '<img src="images/browse_left3.gif" alt="&lt;" border="0"/>';
      $next_image = '<img src="images/browse_right3.gif" alt="&lt;" border="0"/>';
      $params = $this->_environment->getCurrentParameterArray();
      $week_left = $this->_week_start - ( 3600 * 24 * 7);
      $week_right = $this->_week_start + ( 3600 * 24 * 7);
      $params['browse'] = 'week';
      unset($params['year']);
      unset($params['month']);
      $params['week'] = $week_left;
      $params['presentation_mode'] = '1';
      $left = '           '.ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                                'index',
                                $params,
                                $prev_image,
                                '').LF;
      unset($params['year']);
      unset($params['month']);
      $params['week'] = $week_right;
      $params['presentation_mode'] = '1';
      $right = '           '.ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                                'index',
                                $params,
                                $next_image,
                                '').LF;
      return getMessage('COMMON_WEEK').':'.$left.$html.$right;;
   }

   function _getMonthList() {
      $html ='';
      $params = $this->_getGetParamsAsArray();
      $month_array = array($this->_translator->getMessage('DATES_JANUARY_LONG'),
            $this->_translator->getMessage('DATES_FEBRUARY_LONG'),
            $this->_translator->getMessage('DATES_MARCH_LONG'),
            $this->_translator->getMessage('DATES_APRIL_LONG'),
            $this->_translator->getMessage('DATES_MAY_LONG'),
            $this->_translator->getMessage('DATES_JUNE_LONG'),
            $this->_translator->getMessage('DATES_JULY_LONG'),
            $this->_translator->getMessage('DATES_AUGUST_LONG'),
            $this->_translator->getMessage('DATES_SEPTEMBER_LONG'),
            $this->_translator->getMessage('DATES_OCTOBER_LONG'),
            $this->_translator->getMessage('DATES_NOVEMBER_LONG'),
            $this->_translator->getMessage('DATES_DECEMBER_LONG'));
      $html .= '   <select name="month" size="1" style="width:10em;" onChange="javascript:document.indexform.submit()">'.LF;
      if (!isset($this->_month) or empty($this->_month)){
         $month = date ("Ymd");
      }else{
         $month = $this->_month;
      }
      $year = mb_substr($month,0,4);
      $month = mb_substr($month,4,2);
      $first_char = mb_substr($month,0,1);
      if ($first_char == '0'){
         $month = mb_substr($month,1,2);
      }
      $d_time = mktime ( 3, 0, 0, $month, 1, $year );
      $thisdate = date ( "Ymd", $d_time );
      $year--;
      $month = $month + 6;
      if ($month > 12){
         $year++;
         $month = $month-12;
      }
      for ( $i = 0; $i < 13; $i++ ) {
         $month++;
         if ( $month > 12 ) {
            $month = 1;
            $year++;
         }
         $d = mktime(3,0,0,$month,1,$year);
         $html .= '<option value="' . date("Ymd",$d) . '"';
         if ( date("Ymd",$d) == $thisdate ) {
            $html .= ' selected="selected"';
            $arrow_month =  $month;
            $arrow_year =  $year;
         }
         $html .= '>';
         $html .= $month_array[$month-1].' '.$year;
         $html .= '</option>';
      }
      $html .= '   </select>'.LF;

      $params = $this->_environment->getCurrentParameterArray();
      unset($params['year']);
      unset($params['week']);
      $arrow_month_left = $arrow_month-1;
      $arrow_year_left = $arrow_year;
      if ( $arrow_month_left < 1 ) {
         $arrow_month_left = 12;
         $arrow_year_left = $arrow_year-1;
      }
      $arrow_month_right = $arrow_month+1;
      $arrow_year_right = $arrow_year;
      if ( $arrow_month_right > 12 ) {
         $arrow_month_right = 1;
         $arrow_year_right = $arrow_year+1;
      }
      $prev_image = '<img src="images/browse_left3.gif" alt="&lt;" border="0"/>';
      $next_image = '<img src="images/browse_right3.gif" alt="&lt;" border="0"/>';
      $params['month'] = date("Ymd", mktime(3,0,0,$arrow_month_left,1,$arrow_year_left));
      $params['presentation_mode'] = '2';
      $left = '           '.ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                                'index',
                                $params,
                                $prev_image,
                                '').LF;
      $params['presentation_mode'] = '2';
      $params['month'] = date("Ymd",mktime(3,0,0,$arrow_month_right,1,$arrow_year_right));
      $right = '           '.ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                                'index',
                                $params,
                                $next_image,
                                '').LF;
      return getMessage('COMMON_MONTH').':'.$left.$html.$right;;
   }


   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemTitle($item,$display_text='',$with_links=true) {
      $title = $this->_compareWithSearchText($item->getTitle());
      if ($display_text !='') {
         $title = $display_text;
      }
      $hover = $item->getTitle();
      $creator = $item->getCreatorItem();
      $fullname = $creator->getFullname();
      $hover .= ' ('.$fullname.')'.', ';
      $hover .= $this->_getItemDate($item);
      $place = $item->getPlace();
      if (!empty($place)) {
         $hover .= ', '.$this->_translator->getMessage('DATES_PLACE').': '.$this->_getItemPlace($item);
      }
      $hover = str_replace('"','\'',$hover);
      $user = $this->_environment->getCurrentUser();
      $mode = $item->getDateMode();
      $params = array();
      $params['iid'] = $item->getItemID();
      $params['mode'] = 'private';
      $parameter_array = $this->_environment->getCurrentParameterArray();
      if (isset ($parameter_array['year'])){
         $params['year'] = $parameter_array['year'];
      }
      if (isset ($parameter_array['month'])){
         $params['month'] = $parameter_array['month'];
      }
       if (isset ($parameter_array['week'])){
         $params['week'] = $parameter_array['week'];
      }
      if (isset ($parameter_array['presentation_mode'])){
         $params['presentation_mode'] = $parameter_array['presentation_mode'];
      }
      if ( $item->issetPrivatDate() ){
           $title ='<i>'.$title.'</i>';
           $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DATE_TYPE,
                           'detail',
                           $params,
                           $title,
              $hover);
         }else{
            $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DATE_TYPE,
                           'detail',
                           $params,
                           $title,
              $hover);

         }
      $mod = $this->_with_modifying_actions;

      // fileicons
      $fileicons = $this->_getItemFiles($item, $with_links);
      if ( !empty($fileicons) ) {
         $title .= ' '.$fileicons;
      }
      unset($fileicons);

      // Edit the news item, if the current user may so
      if ( $item->mayEdit($user) and $mod ) {
         $title .= ' '.ahref_curl( $this->_environment->getCurrentContextID(),
                            CS_DATE_TYPE,
                           'edit',
                           $params,
                           '<img alt="edit icon" src="images/color_line2.gif" border="0"/>',
                           '');

      }
      unset($params);
      return $title;
   }

   /** get the place of the item
    * this method returns the item place in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemPlace($item){
      $place = $item->getPlace();
      $place = $this->_compareWithSearchText($place);
      return $place;
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
      $time = $this->_compareWithSearchText($time);
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
      // set up style of days and times
      $shown_time = $item->getShownStartingTime();
      if (!empty($shown_time)){
         $parse_time_start = convertTimeFromInput($shown_time);
      }else{
         $parse_time_start = convertTimeFromInput($item->getStartingTime());
      }
      $conforms = $parse_time_start['conforms'];
      if ($conforms == TRUE) {
         $start_time_print = getTimeLanguage($parse_time_start['datetime']);
      } else {
         if (!empty($shown_time)){
            $start_time_print = $shown_time;
         }else{
            $start_time_print = $item->getStartingTime();
         }
      }

      $parse_time_end = convertTimeFromInput($item->getEndingTime());
      $conforms = $parse_time_end['conforms'];
      if ($conforms == TRUE) {
         $end_time_print = getTimeLanguage($parse_time_end['datetime']);
      } else {
         $end_time_print = $item->getEndingTime();
      }

      $shown_day = $item->getShownStartingDay();
      if (!empty($shown_day)){
         $parse_day_start = convertDateFromInput($shown_day,$this->_environment->getSelectedLanguage());
      }else{
         $parse_day_start = convertDateFromInput($item->getStartingDay(),$this->_environment->getSelectedLanguage());
      }
      $conforms = $parse_day_start['conforms'];
      if ($conforms == TRUE) {
         $start_day_print = $this->_translator->getDateInLang($parse_day_start['datetime']);
      } else {
         if (!empty($shown_day)){
            $start_day_print = $shown_day;
         }else{
            $start_day_print = $item->getStartingDay();
         }
      }

      $parse_day_end = convertDateFromInput($item->getEndingDay(),$this->_environment->getSelectedLanguage());
      $conforms = $parse_day_end['conforms'];
      if ($conforms == TRUE) {
         $end_day_print =getDateLanguage($parse_day_end['datetime']);
      } else {
         $end_day_print =$item->getEndingDay();
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
            $date_print = $this->_translator->getMessage('DATES_AS_OF').' '.$start_day_print.', '.$start_time_print.' '.
                          $this->_translator->getMessage('DATES_TILL').' '.$end_day_print.', '.$end_time_print;
            if ($parse_day_start['conforms']
                and $parse_day_end['conforms']) {
               $date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$this->_translator->getMessage('DATES_DAYS').')';
            }
         }

      } else { //without ending day
         $date_print = $start_day_print;
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
      $date = '';
      if ($time_print != '') {
         $date .= $date_print.', '.$time_print;
      } else {
         $date .= $date_print;
      }
      return $date;
   }



   //0 Sonntag, 6 Samstag
   function weekDayofDate($day,$month,$year) {
      $timestamp = mktime(0,0,0,$month,$day,$year);
      $date = getdate ($timestamp);
      $dayofweek = $date['wday'];
      return $dayofweek;
   }


   /** get the content of the list view as HTML
    * this method returns the content in HTML-Code
    *
    * @return string $this->_list as HMTL
    *
    * @author CommSy Development Group
    */
   function _getMonthContentAsHTML() {
      $current_time = localtime();

      //Do some time calculations
      $month = mb_substr($this->_month,4,2);
      $year = $this->_year;
      $days = daysInMonth($month,$year);
      $first_day_week_day = $this->weekDayofDate(1,$month,$year);

      //Create array with correct daynumber/weekday relationship
      $format_array = array();
      //skip fields at beginning
      $empty_fields = (($first_day_week_day + 6) % 7);
      for ($i =0; $i < $empty_fields; $i++) {
         $format_array[]['day'] = '';
      }
      //fill days
      for ($i =1; $i <= $days;$i++) {
         $format_array[]['day'] = $i;
      }
      //skip at ending
      $sum = $days + $empty_fields;
      $remaining = 42 - $sum;
      for ($i=0;$i<$remaining;$i++) {
         $format_array[]['day'] = '';
      }

      //get Dates in month
      $current_date = $this->_list->getFirst();
      $finish = false;
      while ($current_date) {
         $start_date_month = '';
    $start_date_day = '';
    $start_date_year = '';
    $end_date_month = '';
    $end_date_day = '';
    $end_date_year = '';
         $start_date_array = convertDateFromInput($current_date->getStartingDay(),$this->_environment->getSelectedLanguage());
    if ($start_date_array['conforms'] == true) {
       $start_date_array = getDateFromString($start_date_array['timestamp']);
       $start_date_month = $start_date_array['month'];
       $start_date_day = $start_date_array['day'];
       $start_date_year = $start_date_array['year'];
    }
    $end_date_array = convertDateFromInput($current_date->getEndingDay(),$this->_environment->getSelectedLanguage());
    if ($end_date_array['conforms'] == true) {
       $end_date_array = getDateFromString($end_date_array['timestamp']);
       $end_date_month = $end_date_array['month'];
       $end_date_day =   $end_date_array['day'];
       $end_date_year = $end_date_array['year'];
    }

    if ($start_date_day != '') {

            //date begins at least one month before currently displayed month, ends in currently displayed month
            // OR date begins in a year before the current and ends in
       if ( ($start_date_month < $month OR $start_date_year < $year) AND $end_date_month == $month AND $end_date_year == $year){
               for ($i=0;$i < $end_date_day;$i++) {
             $format_array[$empty_fields+$i]['dates'][] = $current_date;
          }

       //date begins in currently displayed month, ends aftet currently displayed month
       //OR date begins in currently displayed year and ends after currently displayed year
       } elseif ($start_date_month == $month AND $start_date_year == $year AND ($end_date_month > $month OR $end_date_year > $year ) ){
          $rest_month = $days - $start_date_day;
          for ($i=0;$i <= $rest_month;$i++) {
             $format_array[$empty_fields+$start_date_day-1+$i]['dates'][] = $current_date;
          }

            //date begins before and ends after currently displayed month
       } elseif ( ($start_date_month < $month OR ($start_date_year < $year)) AND ($end_date_month > $month OR ($end_date_year > $year))) {
          for ($i=0;$i < $days;$i++) {
             $format_array[$empty_fields+$i]['dates'][] = $current_date;
          }
       }

       else { //Date spans in one month or is on a single day
               $length = 0;
          if ($end_date_day != '') {
             $length = $end_date_day - $start_date_day;
               }
          for ($i=0; $i <= $length; $i++) {
                  $format_array[$empty_fields+$start_date_day-1+$i]['dates'][] = $current_date;
          }
       }
         }

         $current_date = $this->_list->getNext();
      }
      //Create the html part of the calendar
      //title row with weekdays
      $html  = '   <tr class="calendar_head">'.LF;
      $html .= '      <td class="calendar_head_first" style="width:7.5em; text-align:center;">'.$this->_translator->getMessage('COMMON_DATE_MONDAY').'</td>'.LF;
      $html .= '      <td class="calendar_head" style="width:7.5em; text-align:center;">'.$this->_translator->getMessage('COMMON_DATE_TUESDAY').'</td>'.LF;
      $html .= '      <td class="calendar_head" style="width:7.5em; text-align:center;">'.$this->_translator->getMessage('COMMON_DATE_WEDNESDAY').'</td>'.LF;
      $html .= '      <td class="calendar_head" style="width:7.5em; text-align:center;">'.$this->_translator->getMessage('COMMON_DATE_THURSDAY').'</td>'.LF;
      $html .= '      <td class="calendar_head" style="width:7.5em; text-align:center;">'.$this->_translator->getMessage('COMMON_DATE_FRIDAY').'</td>'.LF;
      $html .= '      <td class="calendar_head" style="width:7.5em; text-align:center;">'.$this->_translator->getMessage('COMMON_DATE_SATURDAY').'</td>'.LF;
      $html .= '      <td class="calendar_head" style="border-right:0px solid black; width:7.5em; text-align:center;">'.$this->_translator->getMessage('COMMON_DATE_SUNDAY').'</td>'.LF;
      $html .= '   </tr>'.LF;

      $html .= '   <tr class="listcalendar" style="height:8em;">'.LF;
      //rest of table
      for ($i=0;$i<42;$i++) {
         if ( !$finish ) {
            $dates_on_day = isset($format_array[$i]['dates'])?$format_array[$i]['dates']:'';
            if ($current_time[3]==$format_array[$i]['day'] and $current_time[4]+1==$month and $current_time[5]+1900==$year){
               $html .= '      <td class="calendar_content_focus" style="border: spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:8em; width:7.5em;">';
            } elseif( (($i+1) % 7 == 0) or (($i+2) % 7 == 0) ) {
               $html .= '      <td class="calendar_content" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:8em; width:7.5em;">';
#               $html .= '      <td class="calendar_content_weekend" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:8em; width:7.5em;">';
            }else {
               $html .= '      <td class="calendar_content" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:8em; width:7.5em;">';
            }
            if ( empty($format_array[$i]['day']) ) {
               $html .= '&nbsp;';
            } else {
               $html .= LF.'         <div style="font-size:9px; text-align:right;">'.$format_array[$i]['day'].'</div>'.LF;
            }
            if ( !empty($dates_on_day) ) {
               $html .= '         <div style="font-size: 11px; text-align:left;">';
               $entries = count($dates_on_day);
               $new_date = '';
               foreach ($dates_on_day as $date) {
                  if ( $entries < 4 ) {
                     $length = mb_strlen($date->getTitle());
                     if ( $length > 13 ) {
                        $new_date = mb_substr($date->getTitle(),0,13).'<br />&nbsp;&nbsp;';
                        if ( $length > 26 ) {
                           $new_date .= mb_substr($date->getTitle(),13,13).'...';
                        } else {
                           $new_date .= mb_substr($date->getTitle(),13,$length-13);
                        }
                     } else {
                        $new_date = $date->getTitle();
                     }
                  } else {
                     $length = mb_strlen($date->getTitle());
                     if ($length > 13) {
                        $new_date = mb_substr($date->getTitle(),0,13).'...';
                     } else {
                        $new_date = $date->getTitle();
                     }
                  }
                  $html .= '- '.$this->_getItemTitle($date,$new_date).'<br />';
               }
               $entries = 0;
               $html .= '</div>'.LF;
            }
            $session = $this->_environment->getSession();
            $left_menue_status = $session->getValue('left_menue_status');
            if ($left_menue_status !='disapear'){
               $width = '8em';
               $width2 = '8em';
            }else{
               $width = '10em';
               $width2 = '10em';
            }
            if (!empty($dates_on_day)){
               $entries = count($dates_on_day);
               $link_lines = 6-$entries;
               $params = array();
               $params['iid'] = 'NEW';
               $params['day'] = $format_array[$i]['day'];
               $parameter_array = $this->_environment->getCurrentParameterArray();
               $params['month'] = $this->_month;
               $params['year'] = $year;
               $params['presentation_mode'] = $this->_presentation_mode;
          $params['modus_from'] = 'calendar';
               if ( $this->_with_modifying_actions and !empty($format_array[$i]['day'])) {
                  $anAction = ahref_curl( $this->_environment->getCurrentContextID(),
                                 CS_DATE_TYPE,
                                 'edit',
                                 $params,
                                 '<img style="width:'.$width.'; height:1em;" src="images/spacer.gif" alt="" border="0"/>');

                  if ($link_lines > 0){
                     for($j=1; $j<$link_lines; $j++){
                       $html .= '      <div style="width:'.$width.'; height: 1em;"><span style="width:'.$width.'; height: 1em;">'.$anAction.'</span></div>'.LF;
                     }
                  }
                  $html .= '      <div style="width:'.$width.'; height: 1em;"><span style="width:'.$width.'; height: 1em;">'.$anAction.'</span></div>'.LF;
                  $html .= '      <div style="width:'.$width.'; height: 1em;"><span style="width:'.$width.'; height: 1em;">'.$anAction.'</span></div>'.LF;
               }
       }else{
               $params = array();
               $params['iid'] = 'NEW';
               $params['day'] = $format_array[$i]['day'];
               $parameter_array = $this->_environment->getCurrentParameterArray();
               $params['month'] = $this->_month;
               $params['year'] = $year;
          $params['modus_from'] = 'calendar';
               $params['presentation_mode'] = $this->_presentation_mode;
               if ( $this->_with_modifying_actions and !empty($format_array[$i]['day'])) {
                  $anAction = ahref_curl( $this->_environment->getCurrentContextID(),
                                 CS_DATE_TYPE,
                                 'edit',
                                 $params,
                                 '<img style="width:'.$width.'; height:7em;" src="images/spacer.gif" alt="" border="0"/>');

                  $html .= '         <div style="margin-right:0px; width:'.$width.'; height: 7em;"><span style="width:'.$width.'; height: 7em;">'.$anAction.'</span></div>'.LF;
               }
            }

            $html .= '      </td>'.LF;
       if (($i+1) % 7 == 0) {
               $html .= '   </tr>'.LF;
               if ($i != 41 and isset($format_array[$i+1]['day']) and !empty($format_array[$i+1]['day'])) {
                  $html .= '   <tr class="listcalendar" style="height:8em;">'.LF;
          }else{
                  $finish = true;
               }
            }
         }
      }
      //Create the html part of the calendar
      //title row with weekdays
      $html .= '   <tr class="calendar_head">'.LF;
      $html .= '      <td  colspan="5" class="calendar_head_all" style="text-align:left;">'.$this->_translator->getMessage('DATES_TIPP_FOR_ENTRIES').'</td>'.LF;
      $html .= '      <td  colspan="2" class="calendar_head_all"  style="vertical-align:bottom; text-align:right;">';
      $params = $this->_environment->getCurrentParameterArray();
#      $params['mode']='print';
#      $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$params,$this->_translator->getMessage('COMMON_LIST_PRINTVIEW'),'','','','','','','class="calendar_head_all"').BRLF;
      unset($params);
      $html .= '   </td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }

   /** get the content of the list view as HTML
    * this method returns the content in HTML-Code
    *
    * @return string $this->_list as HMTL
    *
    * @author CommSy Development Group
    */

   function _getWeekContentAsHTML() {
      $week_start = $this->_week_start;
      $html ='';
      $month_array = array($this->_translator->getMessage('DATES_JANUARY_SHORT'),
            $this->_translator->getMessage('DATES_FEBRUARY_SHORT'),
            $this->_translator->getMessage('DATES_MARCH_SHORT'),
            $this->_translator->getMessage('DATES_APRIL_SHORT'),
            $this->_translator->getMessage('DATES_MAY_SHORT'),
            $this->_translator->getMessage('DATES_JUNE_SHORT'),
            $this->_translator->getMessage('DATES_JULY_SHORT'),
            $this->_translator->getMessage('DATES_AUGUST_SHORT'),
            $this->_translator->getMessage('DATES_SEPTEMBER_SHORT'),
            $this->_translator->getMessage('DATES_OCTOBER_SHORT'),
            $this->_translator->getMessage('DATES_NOVEMBER_SHORT'),
            $this->_translator->getMessage('DATES_DECEMBER_SHORT'));
      //get Dates in month
      $current_date = $this->_list->getFirst();
      $finish = false;
      $date_array = array();
      while ($current_date) {
         $start_date_month = '';
    $start_date_day = '';
    $start_date_year = '';
    $end_date_month = '';
    $end_date_day = '';
    $end_date_year = '';
         $start_date_time ='';
         $start_end_time ='';
         $start_date_array = convertDateFromInput($current_date->getStartingDay(),$this->_environment->getSelectedLanguage());
         if ($start_date_array['conforms'] == true) {
       $start_date_array = getDateFromString($start_date_array['timestamp']);
       $start_date_month = $start_date_array['month'];
       $start_date_day = $start_date_array['day'];
       $start_date_year = $start_date_array['year'];
    }
         $start_time_array = convertTimeFromInput($current_date->getStartingTime(),$this->_environment->getSelectedLanguage());
    $end_date_array = convertDateFromInput($current_date->getEndingDay(),$this->_environment->getSelectedLanguage());
    if ($end_date_array['conforms'] == true) {
       $end_date_array = getDateFromString($end_date_array['timestamp']);
       $end_date_month = $end_date_array['month'];
       $end_date_day =   $end_date_array['day'];
       $end_date_year = $end_date_array['year'];
    }
         $end_time_array = convertTimeFromInput($current_date->getEndingTime(),$this->_environment->getSelectedLanguage());
    if ($start_date_day != '') {
            $date_array[$start_date_array['day'].$start_date_array['month'].$start_date_array['year']][] = $current_date;
            $start_day = mb_substr($current_date->getStartingDay(),8,2);
            $start_month = $start_date_array['month'];
            $start_year = mb_substr($current_date->getStartingDay(),0,4);
            $first_char = mb_substr($start_day,0,1);
            if ($first_char == '0'){
               $start_day = mb_substr($start_day,1,2);
            }
            $first_char = mb_substr($start_month,0,1);
            if ($first_char == '0'){
               $start_month = mb_substr($start_month,1,2);
            }
            $end_day = mb_substr($current_date->getEndingDay(),8,2);
            $first_char = mb_substr($end_day,0,1);
            if ($first_char == '0'){
               $end_day = mb_substr($end_day,1,2);
            }
            $end_month = mb_substr($current_date->getEndingDay(),5,2);
            $first_char = mb_substr($end_month,0,1);
            if ($first_char == '0'){
               $end_month = mb_substr($end_month,1,2);
            }
            $end_year = mb_substr($current_date->getEndingDay(),0,4);
            $first_char = mb_substr($end_year,0,1);
            if ($first_char == '0'){
               $end_year = mb_substr($end_year,1,2);
            }
            if (((($start_day != $end_day and !empty($end_day) and $start_month != $end_month and !empty($end_month)) or
                  ($start_day == $end_day and !empty($end_day) and $start_month != $end_month and !empty($end_month)) or
                  ($start_day != $end_day and !empty($end_day) and $start_month == $end_month and !empty($end_month))) or
                  ($start_year < $end_year and !empty($end_year)))){
               while ((($start_day != $end_day and $start_month != $end_month) or
                      ($start_day == $end_day and $start_month != $end_month) or
                      ($start_day != $end_day and $start_month == $end_month)) or
                      ($start_year < $end_year)){
                  $temp_date = clone $current_date;
                  if ($current_date->getStartingTime()){
                     $temp_date->setStartingTime('00:00:00');
                  }
                  $temp_starting_day = $temp_date->getStartingDay();
                  $days = daysInMonth($start_month,$start_year);
                  $start_day ++;
                  if ($start_day > $days){
                     $start_day = 1;
                     $start_month++;
                     if ($start_month > 12){
                        $start_month = 1;
                        $start_year++;
                     }
                  }
                  $temp_start_day = $start_day;
                  if (mb_strlen($temp_start_day) == 1){
                     $temp_start_day = '0'.$temp_start_day;
                  }
                  $temp_start_month = $start_month;
                  if (mb_strlen($temp_start_month) == 1){
                     $temp_start_month = '0'.$temp_start_month;
                  }
                  $temp_starting_day = $start_year.'-'.$temp_start_month.'-'.$temp_start_day;
                  $temp_date->setShownStartingDay($current_date->getStartingDay());
                  $temp_date->setShownStartingTime($current_date->getStartingTime());
                  $temp_date->setStartingDay($temp_starting_day);
                  $date_array[$temp_start_day.$temp_start_month.$start_year][] = $temp_date;
                  unset($temp_date);
               }
            }
         }
         $current_date = $this->_list->getNext();
      }
      //Create the html part of the calendar
      //title row with weekdays
      $html  = '   <tr class="calendar_head">'.LF;
      $html .= '      <td class="calendar_head_first" style="width:1.5em; text-align:center;">'.'</td>'.LF;
      $display_date_array = array();
      for ($i = 1; $i <8; $i++){
         $startday = date ("d",$week_start);
         $startmonth = date ("m",$week_start);
         $startyear = date ("Y",$week_start);
         $startarraymonth = $startmonth;
         $startmonth = $month_array[$startmonth-1];
         $first_char = mb_substr($startday,0,1);
         if ($first_char == '0'){
            $display_startday = mb_substr($startday,1,2);
         }else{
            $display_startday = $startday;
         }
         if ( isset($date_array[$startday.$startarraymonth.$startyear]) ){
            $display_date_array[$i] = $date_array[$startday.$startarraymonth.$startyear];
         }
         switch ($i){
            case 1: $text = 'COMMON_DATE_WEEKVIEW_MONDAY'; break;
            case 2: $text = 'COMMON_DATE_WEEKVIEW_TUESDAY'; break;
            case 3: $text = 'COMMON_DATE_WEEKVIEW_WEDNESDAY'; break;
            case 4: $text = 'COMMON_DATE_WEEKVIEW_THURSDAY'; break;
            case 5: $text = 'COMMON_DATE_WEEKVIEW_FRIDAY'; break;
            case 6: $text = 'COMMON_DATE_WEEKVIEW_SATURDAY'; break;
            case 7: $text = 'COMMON_DATE_WEEKVIEW_SUNDAY'; break;
         }
            if ($i == 7){
               $html .= '      <td class="calendar_head"
                                   style="border-right:0px solid black;
                                   width:7.2em; text-align:center;">';
            } else {
               $html .= '      <td class="calendar_head"
                                   style="width:7.2em;
                                   text-align:center;">';
            }
            switch ( $text ){
               case 'COMMON_DATE_WEEKVIEW_MONDAY':
                  $html .= $this->_translator->getMessage('COMMON_DATE_WEEKVIEW_MONDAY',    $display_startday, $startmonth);
                  break;
               case 'COMMON_DATE_WEEKVIEW_TUESDAY':
                  $html .= $this->_translator->getMessage('COMMON_DATE_WEEKVIEW_TUESDAY',   $display_startday, $startmonth);
                  break;
               case 'COMMON_DATE_WEEKVIEW_WEDNESDAY':
                  $html .= $this->_translator->getMessage('COMMON_DATE_WEEKVIEW_WEDNESDAY', $display_startday, $startmonth);
                  break;
               case 'COMMON_DATE_WEEKVIEW_THURSDAY':
                  $html .= $this->_translator->getMessage('COMMON_DATE_WEEKVIEW_THURSDAY',  $display_startday, $startmonth);
                  break;
               case 'COMMON_DATE_WEEKVIEW_FRIDAY':
                  $html .= $this->_translator->getMessage('COMMON_DATE_WEEKVIEW_FRIDAY',    $display_startday, $startmonth);
                  break;
               case 'COMMON_DATE_WEEKVIEW_SATURDAY':
                  $html .= $this->_translator->getMessage('COMMON_DATE_WEEKVIEW_SATURDAY',  $display_startday, $startmonth);
                  break;
               case 'COMMON_DATE_WEEKVIEW_SUNDAY':
                  $html .= $this->_translator->getMessage('COMMON_DATE_WEEKVIEW_SUNDAY',    $display_startday, $startmonth);
                  break;
               default:
                  // $html .= getMessage('COMMON_MESSAGETAG_ERROR'.' cs_datescalendar_index_view(1380) ');
                  break;
            }
            $html .= '</td>'.LF;
         $week_start = $week_start + ( 3600 * 24);
      }
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ($left_menue_status !='disapear'){
         $width = '8em';
         $width2 = '8em';
      }else{
         $width = '10em';
         $width2 = '10em';
      }
      $html .= '   </tr>'.LF;
      $time = 5;
      for($i = 0; $i<18; $i++){
         if ($i == 0){
            $html .= '   <tr class="listcalendar" style="height:1.2em;">'.LF;
         }else{
            $html .= '   <tr class="listcalendar" style="height:2em;">'.LF;
         }
         for ($j = 1; $j<9; $j++){
            $date_text = array();
            $date_show = array();
            $count_entries = array();
            $is_entry = false;
            $day_entries = $j-1;
            if ( isset($display_date_array[$day_entries]) ){
               foreach($display_date_array[$day_entries] as $date){
                  $starting_time = $date->getStartingTime();
                  if (empty($starting_time)){
                     $length = mb_strlen($date->getTitle());
                     if ( $length > 12 ) {
                        $new_date = mb_substr($date->getTitle(),0,12).'...';
                     } else {
                        $new_date = $date->getTitle();
                     }
                     $title = '- '.$this->_getItemTitle($date,$new_date);
                     if (isset($date_text[1]) and !empty($date_text[1]) ){
                        $date_text[1] .= '<br/>'.$title;
                     }else{
                        $date_text[1] = $title;
                     }
                  }else{
                     $display_start_time = mb_substr($date->getStartingTime(),0,2);
                     $first_char = mb_substr($display_start_time,0,1);
                     if ($first_char == '0'){
                       $display_start_time = mb_substr($display_start_time,1,2);
                     }
                     if ( $display_start_time=='0' ){
                       $display_start_time ='6';
                     }
                     if ( isset($count_entries[$display_start_time]) and $count_entries[$display_start_time] > 1 ) {
                        $length = mb_strlen($date->getTitle());
                        if ($length > 10) {
                           $new_date = mb_substr($date->getTitle(),0,9).'...';
                        } else {
                           $new_date = $date->getTitle();
                        }
                      } else {
                        $length = mb_strlen($date->getTitle());
                        if ( $length > 10 ) {
                           $new_date = mb_substr($date->getTitle(),0,9).'...';
                        } else {
                           $new_date = $date->getTitle();
                        }
                     }
                     $title = '- '.$this->_getItemTitle($date,$new_date);
                     if (isset($date_text[$display_start_time]) and !empty($date_text[$display_start_time]) ){
                        $date_text[$display_start_time] .= '<br/>'.$title;
                        $count_entries[$display_start_time] = $count_entries[$display_start_time]+1;
                     }else{
                        $date_text[$display_start_time] = $title;
                        $count_entries[$display_start_time] = 1;
                     }
                     $ending_time = $date->getEndingTime();
                     if ( !empty($ending_time) ){
                        $display_ending_time = mb_substr($date->getEndingTime(),0,2);
                        $first_char = mb_substr($display_ending_time,0,1);
                        if ($first_char == '0'){
                           $display_ending_time = mb_substr($display_ending_time,1,2);
                        }
                        $display_ending_minutes = mb_substr($date->getEndingTime(),3,2);
                        if ($display_ending_minutes !='00'){
                           $display_ending_time++;
                        }
                        if ($display_ending_time < $display_start_time){
                           $display_ending_time = 24;
                        }
                        $start_day = $date->getStartingDay();
                        $end_day = $date->getEndingDay();
                        if ($start_day < $end_day){
                           $display_ending_time = 24;

                        }
                        $k = $display_start_time;
                        while ($k < $display_ending_time) {
                           if (isset($date_show[$k])){
                              $value = $date_show[$k];
                              $date_show[$k] = $value+1;
                           } else {
                              $date_show[$k] = 1;
                           }
                           $k = $k+1;
                        }
                     }
                  }
               }
            }
            if($j==1){
               if ($i == 0){
                  $entry_html = '      <td class="calendar_content_without_time" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:1em; width:1.5em;">';
                  $entry_html .= '      </td>';
               }else{
                  $entry_html = '      <td class="calendar_content" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:2em; width:1.5em;">';
                  $entry_html .= $time;
                  $entry_html .= '      </td>';
               }
            }elseif ($j==7 or $j == 8){
               if ($i == 0){
                  $entry_html = '      <td class="calendar_content_without_time" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:1em; width:7.2em;">';
               }else{
                  $entry_html = '      <td class="calendar_content" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:2em; width:7.2em;">';
#                  $entry_html = '      <td class="calendar_content_weekend" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:2em; width:7.2em;">';
               }
            }else{
               if ($i == 0){
                  $entry_html = '      <td class="calendar_content_without_time" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:1em; width:7.2em;">';
               }else{
                  $entry_html = '      <td class="calendar_content" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:2em; width:7.2em;">';
               }
            }

            if (isset($date_text[1]) and !empty($date_text[1]) and $i == 0){
               $entry_html = '      <td class="calendar_content_without_time" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:1em; width:7.2em;">';
               $entry_html .= $date_text[1];
               $is_entry = true;
            }elseif( isset($date_text[$time]) and !empty($date_text[$time]) and $i != 0 ){
               $css_text = '';
               if( isset($date_show[$time]) and !empty($date_show[$time]) ){
                  switch ($date_show[$time]){
                     case 1: $css_text = ''; break;
                     case 2: $css_text = 'background-color:#F0F000;'; break;
                     default: $css_text = 'background-color:#F0F000;'; break;
                  }
               }
               $entry_html = '      <td class="calendar_content_with_entry" style="'.$css_text.' spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:2em; width:7.2em;">';
               $entry_html .= $date_text[$time];
               $is_entry = true;
            }elseif( isset($date_show[$time]) and !empty($date_show[$time]) and $i != 0 ){
               $css_text = '';
               switch ($date_show[$time]){
                  case 1: $css_text = ''; break;
                  case 2: $css_text = 'background-color:#F0F000;'; break;
                  default: $css_text = 'background-color:#F0F000;'; break;
               }

               $entry_html = '      <td class="calendar_content_with_entry" style="'.$css_text.' spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:2em; width:7.2em;">';
            }
            $html .= $entry_html;
            if ($j != 1){
               $count = $j-2;
               $week_start = $this->_week_start + ( 3600 * 24 * $count);
               $startday = date ( "d", $week_start);
               $first_char = mb_substr($startday,0,1);
               if ($first_char == '0'){
                  $startday = mb_substr($startday,1,2);
               }
               $startmonth = date ( "Ymd", $week_start );
               $first_char = mb_substr($startmonth,0,1);
               if ($first_char == '0'){
                  $startmonth = mb_substr($startmonth,1,2);
               }
               $startyear = date ( "Y", $week_start );
               $params = array();
               $params['iid'] = 'NEW';
               $params['day'] = $startday;
               $parameter_array = $this->_environment->getCurrentParameterArray();
               $params['month'] = $startmonth;
               $params['year'] = $startyear;
               $params['week'] = $this->_week_start;
               $params['presentation_mode'] = '1';
               if ($i != 0){
                  $params['time'] = $time;
               } else{
                  $params['time'] = 0;
               }
          $params['modus_from'] = 'calendar';
               $anAction ='';
               if ($i == 0){
                  $image = '<img style="width:'.$width.'; height:1em;" src="images/spacer.gif" alt="" border="0"/>';
               }else{
                  $image = '<img style="width:'.$width.'; height:2.2em;" src="images/spacer.gif" alt="" border="0"/>';
               }if ($is_entry){
                  if ($i == 0){
                     $image = '<img style="width:'.$width.'; height:0.5em;" src="images/spacer.gif" alt="" border="0"/>';
                  }else{
                     $image = '<img style="width:'.$width.'; height:1.2em;" src="images/spacer.gif" alt="" border="0"/>';
                  }
               }
               if ( $this->_with_modifying_actions ) {
                  $anAction = ahref_curl( $this->_environment->getCurrentContextID(),
                                 CS_DATE_TYPE,
                                 'edit',
                                 $params,
                                 $image);
               }
               if ($is_entry){
                  if ($i == 0){
                     $html .= '      <div style="width:'.$width.'; height: 1em;"><span style="width:'.$width.'; height: 1em;">'.$anAction.'</span></div>'.LF;
                  }else{
                     $html .= '      <div style="width:'.$width.'; height: 1.2em;"><span style="width:'.$width.'; height: 1em;">'.$anAction.'</span></div>'.LF;
                  }
               }else{
                  if ($i == 0){
                     $html .= '      <div style="width:'.$width.'; height: 1em;"><span style="width:'.$width.'; height: 1em;">'.$anAction.'</span></div>'.LF;
                  }else{
                     $html .= '      <div style="width:'.$width.'; height: 2.2em;"><span style="width:'.$width.'; height: 1em;">'.$anAction.'</span></div>'.LF;
                  }
               }
               $html .= '      </td>';
            }

         }
         $time = $time+1;
         $html .= '   </tr>'.LF;
      }
      $current_context_item = $this->_environment->getCurrentContextItem();
      $current_user_item = $this->_environment->getCurrentUserItem();
      $hash_manager = $this->_environment->getHashManager();
      $ical_url = '<a href="webcal://';
      $ical_url .= $_SERVER['HTTP_HOST'];
      $ical_url .= str_replace('commsy.php','ical.php',$_SERVER['PHP_SELF']);
      $ical_url .= '?cid='.$_GET['cid'].'&amp;hid='.$hash_manager->getICalHashForUser($current_user_item->getItemID()).'" style="color:#fff;">'.getMessage('DATES_ABBO').'</a>'.LF;
      $html .= '   <tr class="calendar_head" style="height: 20px;">'.LF;
      $html .= '      <td  colspan="5" class="calendar_head_all_first" style="text-align:left; font-size:8pt;">'.$this->_translator->getMessage('DATES_WEEK_TIPP_FOR_ENTRIES').'</td>'.LF;
      $html .= '      <td  colspan="3" class="calendar_head_all" style="text-align:right; font-size:8pt; white-space:nowrap;">'.$ical_url.' | <a href="ical.php?cid='.$current_context_item->getItemID().'&amp;hid='.$hash_manager->getICalHashForUser($current_user_item->getItemID()).'" style="color:#fff;">'.$this->_translator->getMessage('DATES_EXPORT').'</a></td>'.LF;

      $html .= '   </tr>'.LF;
      return $html;
   }


   function _getPreviousMonthAndYear($month,$year) {
      $ret['month'] = $month -1;
      $ret['year'] = $year;
      if ($ret['month'] == 0) {
         $ret['month'] = 12;
        $ret['year'] = $year -1;
      }
      return $ret;
   }

   function _getNextMonthAndYear($month,$year) {
      $ret['month'] = $month +1;
      $ret['year'] = $year;
      if ($ret['month'] == 13) {
         $ret['month'] = 1;
        $ret['year'] = $year +1;
      }
      return $ret;
   }
}
?>