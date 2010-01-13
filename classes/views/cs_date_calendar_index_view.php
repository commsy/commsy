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
   var $_used_color_array = array();
   var $_week_start;
   var $_available_color_array = array('#999999','#CC0000','#FF6600','#FFCC00','#FFFF66','#33CC00','#00CCCC','#3366FF','#6633FF','#CC33CC');
   var $_selected_color = NULL;

   // SUNBIRD
   var $use_sunbird = true;
   // SUNBIRD

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
    * @param object  environment            environment of commsy
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_date_calendar_index_view ($params) {
      $this->cs_room_index_view($params);
      $this->setTitle($this->_translator->getMessage('DATES_HEADER'));
      $this->setActionTitle($this->_translator->getMessage('COMMON_DATES'));
      /*
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
      if ( $this->_environment->inPrivateRoom()
           and $this->_with_modifying_actions
         ) {
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
      */
   }

   function setClipboardIDArray($cia) {
      $this->_clipboard_id_array = $cia;
   }

   function getClipboardIDArray() {
      return $this->_clipboard_id_array;
   }

   function setSelectedColor ($color) {
      $this->_selected_color = $color;
   }

   function getSelectedColor () {
      return $this->_selected_color;
   }

   function setAvailableColorArray ($array) {
      $this->_available_color_array = $array;
   }

   function getAvailableColorArray () {
      return $this->_available_color_array;
   }

   function setUsedColorArray ($array) {
      $this->_used_color_array = $array;
   }

   function getUsedColorArray () {
      return $this->_used_color_array;
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

   function _getListInfosAsHTML ($title) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title">'.LF;
      
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
      $html .= '<div style="white-space:nowrap;">'.$tempMessage.'</div>'.LF;
      $html .='</div>'.LF;
      
      $width = '';
      $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
      $current_browser_version = $this->_environment->getCurrentBrowserVersion();
      if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
         $width = 'width:170px;';
      }
      $html .= '<div class="right_box_main" style="'.$width.'">'.LF;
      
      if($this->calendar_with_javascript()){
      	$html .= $this->_getAdditionalCalendarAsHTML().LF;
      }
      
      $html .= $this->_getAdditionalFormFieldsAsHTML().LF;
      
      $html .= '<div class="listinfoborder"></div>'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['week']);
      unset($params['year']);
      unset($params['month']);
      unset($params['presentation_mode']);
      $params['seldisplay_mode'] = 'normal';
      $html .= '<table style="width:100%; padding:0px; margin:0px; border-collapse:collapse;">';
      $html .='<tr>'.LF;
      $html .='<td>'.LF;
      $html .= '<span class="infocolor">'.$this->_translator->getMessage('DATE_ALTERNATIVE_DISPLAY').': </span>';
      $html .='</td>'.LF;
      $html .='<td style="text-align:right;">'.LF;
      $html .= ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$params,$this->_translator->getMessage('DATES_COMMON_DISPLAY')).LF;
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      #$html .='<tr>'.LF;
      #$html .='<td colspan="2">'.LF;
      #$html .= '<select style="width: 10em; font-size:10pt;" name="presentation_mode" size="1" id="submit_form">'.LF;
      #$html .= '<option value="2"';
      #if ($this->_presentation_mode == '2'){
      #   $html .= ' selected="selected"';
      #}
      #$html .= '>'.$this->_translator->getMessage('DATE_MONTH_PRESENTATION').'</option>'.LF;
      #$html .= '      <option value="1"';
      #if ($this->_presentation_mode != '2'){
      #   $html .= ' selected="selected"';
      #}
      #$html .= '>'.$this->_translator->getMessage('DATE_WEEK_PRESENTATION').'</option>'.LF;
      #$html .= '   </select>'.LF;
      #$html .='</td>'.LF;
      #$html .='</tr>'.LF;
      $html .='</table>'.LF;

      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

     return $html;
   }



  function _getSearchAsHTML () {
     $html  = '';
     $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), 'campus_search', 'index','').'" method="get" name="searchform">'.LF;
     $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
     $html .= '   <input type="hidden" name="mod" value="campus_search"/>'.LF;
     $html .= '   <input type="hidden" name="SID" value="'.$this->_environment->getSessionItem()->getSessionID().'"/>'.LF;
     $html .= '   <input type="hidden" name="fct" value="index"/>'.LF;
     $html .= '   <input type="hidden" name="selrubric" value="'.$this->_environment->getCurrentModule().'"/>'.LF;
     $html .= '<input id="searchtext" onclick="javascript:resetSearchText(\'searchtext\');" style="width:130px; font-size:10pt; margin-bottom:0px;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/>'.LF;
     if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
        $html .= '<input type="image" src="images/commsyicons_msie6/22x22/search.gif" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
     } else {
        $html .= '<input type="image" src="images/commsyicons/22x22/search.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
     }
     $html .= '</form>';
     return $html;
  }

   function _getAdditionalActionsAsHTML(){
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $hash_manager = $this->_environment->getHashManager();
      $params = $this->_environment->getCurrentParameterArray();
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/abbo.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATES_ABBO').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/abbo.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATES_ABBO').'"/>';
      }
      $ical_url = '<a title="'.$this->_translator->getMessage('DATES_ABBO').'"  href="webcal://';
      $ical_url .= $_SERVER['HTTP_HOST'];
      global $c_single_entry_point;
      $ical_url .= str_replace($c_single_entry_point,'ical.php',$_SERVER['PHP_SELF']);
      $ical_url .= '?cid='.$_GET['cid'].'&amp;hid='.$hash_manager->getICalHashForUser($current_user->getItemID()).'">'.$image.'</a>'.LF;
      $html .= $ical_url;
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/export.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATES_EXPORT').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/export.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATES_EXPORT').'"/>';
      }
      $html .= '<a title="'.$this->_translator->getMessage('DATES_EXPORT').'"  href="ical.php?cid='.$_GET['cid'].'&amp;hid='.$hash_manager->getICalHashForUser($current_user->getItemID()).'">'.$image.'</a>'.LF;
      unset($params);
      if ( $this->_environment->inPrivateRoom() ) {
         if ( $this->_with_modifying_actions ) {
            $params['import'] = 'yes';
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/import.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_IMS_IMPORT').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/import.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_IMS_IMPORT').'"/>';
            }
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                                'import',
                                $params,
                                $image,
                                $this->_translator->getMessage('COMMON_IMPORT_DATES')).LF;
            unset($params);
         } else {
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/import_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_IMPORT_DATES').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/import_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_IMPORT_DATES').'"/>';
            }
            $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_IMPORT_DATES')).' "class="disabled">'.$image.'</a>'.LF;
         }
      }
      return $html;
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

      $html .='<div>'.LF;
      $html .= '<div id="search_box" style="float:right; width:17%; white-space:nowrap; text-align-left; padding-top:5px; margin:0px;">'.LF;
      $html .= $this->_getSearchAsHTML();
      $html .= '</div>'.LF;

      $html .='<div style="width:80%; padding-left:3px;">'.LF;
      $html .='<div id="action_box" style="margin-right:5px;">';
      $html .= $this->_getListActionsAsHTML();
      $html .='</div>';
      $html .='<div>';

      if($this->use_sunbird){
      	//$html .= $this->_getSwitchIconBar();
      	$html .= '<div style="float:left;padding-right:50px;"><h2 class="pagetitle"><img style="vertical-align: bottom;" src="images/commsyicons/32x32/date.png"/>' . $this->_translator->getMessage('DATES') . '</h2></div>';
      } else {
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
	          $html .= '<h2 class="pagetitle">'.$this->_translator->getMessage('CLIPBOARD_HEADER').' ('.$tempMessage.')';
	      }elseif ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions' ) {
	         $html .= '<h2 class="pagetitle">'.$this->_translator->getMessage('COMMON_ASSIGN').' ('.$tempMessage.')';
	      }else{
	          $html .= '<h2 class="pagetitle">'.$tempMessage;
	      }
   	  }


      $html .= '</h2>'.LF;
      $html .='</div>'.LF;
      $html .='</div>';
      $html .='<div style="font-size:0pt; width:100%; clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;

      $html .='<div>'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(),
                                                                      $this->_environment->getCurrentModule(),
                                                                      $this->_environment->getCurrentFunction(),
                                                                      $params).'" method="get" name="indexform">'.LF;
      $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
      $current_browser_version = $this->_environment->getCurrentBrowserVersion();
      if ( $current_browser == 'msie' ){
         $html .='<div id="right_boxes_area" style="width:17%; padding-top: 10px; font-size:10pt;">'.LF;
      }else{
         $html .='<div id="right_boxes_area" style="width:17%; padding-top: 10px; font-size:10pt;">'.LF;
      }
      $html .='<div style="width:160px;">'.LF;

      $html .='<div id="commsy_panels">'.LF;
      $html .= '<div class="commsy_no_panel" style="margin-bottom:1px;">'.LF;
      $html .= $this->_getListInfosAsHTML($this->_translator->getMessage('DATE_INDEX'));
      $html .='</div>'.LF;
      $html .='</div>'.LF;


      $html .='</div>'.LF;
      if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
         $width= ' width:100%; padding-right:10px;';
      }else{
         $width= ' width:80%;';
      }

      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .='</div>'.LF;
         $html .='<div class="index_content_display_width" style="'.$width.'padding-top:5px; vertical-align:bottom; font-size:10pt;">'.LF;
      }else{
         $html .='</div>'.LF;
         $html .='<div style="width:100%; padding-top:5px; vertical-align:bottom; font-size:10pt;">'.LF;
      }

      $html .= '<table style="width: 100%; border-collapse: collapse;">'.LF;
      // SUNBIRD UMSTELLUNG

      if ($this->_presentation_mode == '2'){
         $session_item = $this->_environment->getSessionItem();
         $with_javascript = false;
         if($session_item->issetValue('javascript')){
            if($session_item->getValue('javascript') == "1"){
               $with_javascript = true;
            }
         }
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         	$with_javascript = false;
         }
         if($with_javascript and $this->use_sunbird){
            $html .= $this->_getTableheadMonthAsHTMLWithJavascript();
         } else {
            $html .= $this->_getTableheadAsHTML();
         }
      } else {
         $session_item = $this->_environment->getSessionItem();
         $with_javascript = false;
         if($session_item->issetValue('javascript')){
            if($session_item->getValue('javascript') == "1"){
               $with_javascript = true;
            }
         }
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         	$with_javascript = false;
         }
         if($with_javascript and $this->use_sunbird){
            $html .= $this->_getTableheadAsHTMLWithJavascript();
         } else {
            $html .= $this->_getTableheadAsHTML();
         }
      }
      $html .='<tr>'.LF;
      $html .='<td colspan="3" style="padding-top:2px; vertical-align:top;">'.LF;
      #$html .= '<table class="list" style="width: 100%; border-collapse: collapse;" summary="Layout">'.LF;
#      $html .= '<table class="list" style="width: 100%; border-collapse: collapse; border: 0px;" summary="Layout">'.LF;
      if ($this->_presentation_mode == '2'){
         $session_item = $this->_environment->getSessionItem();
         $with_javascript = false;
         if($session_item->issetValue('javascript')){
            if($session_item->getValue('javascript') == "1"){
               $with_javascript = true;
            }
         }
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         	$with_javascript = false;
         }
         if($with_javascript and $this->use_sunbird){
            $html .= $this->_getMonthContentAsHTMLWithJavaScript();
         } else {
            $html .= '<table class="list" style="width: 100%; border-collapse: collapse;" summary="Layout">'.LF;
            $html .= $this->_getMonthContentAsHTML();
            $html .= '</table>'.LF;
         }
      }else{
         $with_javascript = false;
         $session_item = $this->_environment->getSessionItem();
         if($session_item->issetValue('javascript')){
            if($session_item->getValue('javascript') == "1"){
               $with_javascript = true;
            }
         }
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         	$with_javascript = false;
         }
         if($with_javascript and $this->use_sunbird){
            $html .= $this->_getWeekContentAsHTMLWithJavaScript();
         } else {
            $html .= '<table class="list" style="width: 100%; border-collapse: collapse;" summary="Layout">'.LF;
            $html .= $this->_getWeekContentAsHTML();
            $html .= '</table>'.LF;
         }
      }
      #$html .= '</table>'.LF;
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .= '</table>'.BRLF;
      $html .='</div>'.LF;
      $html .= '</form>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
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
      // Search / select form
      $session_item = $this->_environment->getSessionItem();
      $session_id = $session_item->getSessionID();
      unset($session_item);
      $html  = '   <input type="hidden" name="SID" value="'.$this->_text_as_form($session_id).'"/>'.LF;
      $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
      $html .= '   <input type="hidden" name="mod" value="'.$this->_text_as_form($this->_module).'"/>'.LF;
      $html .= '   <input type="hidden" name="fct" value="'.$this->_text_as_form($this->_function).'"/>'.LF;
      $html .= '   <input type="hidden" name="sort" value="'.$this->_text_as_form($this->getSortKey()).'"/>'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      if (isset($params['presentation_mode'])){
         $html .= '   <input type="hidden" name="presentation_mode" value="'.$params['presentation_mode'].'"/>'.LF;
      }else{
         $html .= '   <input type="hidden" name="presentation_mode" value="1"/>'.LF;
      }
      if (isset($params['week'])){
         $html .= '   <input type="hidden" name="week" value="'.$params['week'].'"/>'.LF;
      }
      if (isset($params['month'])){
         $html .= '   <input type="hidden" name="week" value="'.$params['month'].'"/>'.LF;
      }
      $selstatus = $this->getSelectedStatus();
      $html .= '<div class="infocolor" style="padding-bottom:5px;">'.$this->_translator->getMessage('COMMON_DATE_STATUS').BRLF;
      // jQuery
      //$html .= '   <select name="selstatus" size="1" style="width:150px;" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '   <select name="selstatus" size="1" style="width:150px;" id="submit_form">'.LF;
      // jQuery
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


      if (isset($this->_used_color_array[0])){
         $selcolor = $this->_selected_color;
         $html .= '<div class="infocolor" style="text-align:left; padding-bottom:5px; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_DATE_COLOR').BRLF;
         if ( !empty($selcolor)) {
            $style_color = '#'.$selcolor;
         }else{
           $style_color = '#000000';
         }
         $html .= '   <select style="color:'.$style_color.'; width: 150px; font-size:10pt; margin-bottom:5px;" name="selcolor" size="1" id="submit_form">'.LF;

         $html .= '      <option style="color:#000000;" value="2"';
         if ( empty($selcolor) || $selcolor == 2 ) {
            $html .= ' selected="selected"';
         }
         $html .= '>*'.$this->_translator->getMessage('COMMON_NO_SELECTION').'</option>'.LF;

         $html .= '   <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
         $color_array = $this->getAvailableColorArray();
         foreach ($color_array as $color){
            $html .= '      <option style="color:'.$color.'" value="'.str_replace('#','',$color).'"';
            if ( !empty($selcolor) and $selcolor == str_replace('#','',$color) ) {
               $html .= ' selected="selected"';
            }
            $color_text = '';
            switch ($color){
               case '#999999': $color_text = getMessage('DATE_COLOR_GREY');break;
               case '#CC0000': $color_text = getMessage('DATE_COLOR_RED');break;
               case '#FF6600': $color_text = getMessage('DATE_COLOR_ORANGE');break;
               case '#FFCC00': $color_text = getMessage('DATE_COLOR_DEFAULT_YELLOW');break;
               case '#FFFF66': $color_text = getMessage('DATE_COLOR_LIGHT_YELLOW');break;
               case '#33CC00': $color_text = getMessage('DATE_COLOR_GREEN');break;
               case '#00CCCC': $color_text = getMessage('DATE_COLOR_TURQUOISE');break;
               case '#3366FF': $color_text = getMessage('DATE_COLOR_BLUE');break;
               case '#6633FF': $color_text = getMessage('DATE_COLOR_DARK_BLUE');break;
               case '#CC33CC': $color_text = getMessage('DATE_COLOR_PURPLE');break;
               default: $color_text = getMessage('DATE_COLOR_UNKNOWN');
            }
            $html .= '>'.$color_text.'</option>'.LF;
         }
         $html .= '   </select>'.LF;
         $html .='</div>';
      }



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
            if (($context_item->_is_perspective($link_name[0]) and $context_item->withRubric($link_name[0]))
                or ( $link_name[0] == CS_USER_TYPE and $context_item->withRubric($link_name[0]))
            ) {
               $list = $this->getAvailableRubric($link_name[0]);
               $selrubric = $this->getSelectedRubric($link_name[0]);
               $temp_link = mb_strtoupper($link_name[0], 'UTF-8');
               switch ( $temp_link )
               {
                  case 'GROUP':
                     $html .= '<div class="infocolor" style="padding-bottom:5px;">'.$this->_translator->getMessage('COMMON_GROUP');
                     break;
                  case 'INSTITUTION':
                     $html .= '<div class="infocolor" style="padding-bottom:5px;">'.$this->_translator->getMessage('COMMON_INSTITUTION');
                     break;
                  case 'TOPIC':
                     $html .= '<div class="infocolor" style="padding-bottom:5px;">'.$this->_translator->getMessage('COMMON_TOPIC');
                     break;
                  case 'USER':
                     $html .= '<div class="infocolor" style="padding-bottom:5px;">'.$this->_translator->getMessage('COMMON_USER');
                     break;
                  default:
                     $html .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_datescalendar_index_view(341) ';
                     break;
               }
               $html .= BRLF;

               if ( isset($list)) {
                  // jQuery
                  //$html .= '   <select style="width: 150px; font-size:10pt;" name="sel'.$link_name[0].'" size="1" onChange="javascript:document.indexform.submit()">'.LF;
                  $html .= '   <select style="width: 150px; font-size:10pt;" name="sel'.$link_name[0].'" size="1" id="submit_form">'.LF;
                  // jQuery
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
                     if ($link_name[0] == CS_USER_TYPE){
                        $text = $this->_Name2SelectOption($sel_item->getFullName());
                     }else{
                        $text = $this->_Name2SelectOption($sel_item->getTitle());
                     }
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
             $html .='</div>';
            }
         }
      }
     return $html;
   }


   function _getTableheadAsHTML() {
      $params = $this->_getGetParamsAsArray();
      // Optimierungsbedarf: Die $this->_translator->getMessage wird 11Mal!!! umsonst aufgerufen
      $current_time = localtime();
      $month = getLongMonthName($current_time[4]);
      $html  = '   <tr>'.LF;
      $html .= '      <td class="infoborderyear"  style="vertical-align:bottom;">'.LF;

      // jQuery
      //$html .= '   <select style="width: 10em; font-size:10pt;" name="presentation_mode" size="1" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '   <select style="width: 10em; font-size:10pt;" name="presentation_mode" size="1" id="submit_form">'.LF;
      // jQuery

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

   function _getTableheadAsHTMLWithJavaScript() {
      $params = $this->_getGetParamsAsArray();
      // Optimierungsbedarf: Die $this->_translator->getMessage wird 11Mal!!! umsonst aufgerufen
      $current_time = localtime();
      $month = getLongMonthName($current_time[4]);
      $html  = '   <tr>'.LF;
      #$html .= '      <td class="infoborderyear"  style="vertical-align:bottom;">'.LF;

      #// jQuery
      #//$html .= '   <select style="width: 10em; font-size:10pt;" name="presentation_mode" size="1" onChange="javascript:document.indexform.submit()">'.LF;
      #$html .= '   <select style="width: 10em; font-size:10pt;" name="presentation_mode" size="1" id="submit_form">'.LF;
      #// jQuery
      #
      #$html .= '      <option value="2"';
      #if ($this->_presentation_mode == '2'){
      #   $html .= ' selected="selected"';
      #}
      #$html .= '>'.$this->_translator->getMessage('DATE_MONTH_PRESENTATION').'</option>'.LF;
      #$html .= '      <option value="1"';
      #if ($this->_presentation_mode != '2'){
      #   $html .= ' selected="selected"';
      #}
      #$html .= '>'.$this->_translator->getMessage('DATE_WEEK_PRESENTATION').'</option>'.LF;
      #$html .= '   </select>'.LF;

      #$html .= '</td>'.LF;
      $html .= '<td colspan="3" class="infoborderweek"  style="vertical-align:bottom; text-align:left; white-space:nowrap;">'.LF;
      $html .= $this->_getWeekListWithJavascript();
      #$html .= '&nbsp;&nbsp;&nbsp;';
      #$html .= $this->_getMonthList();
      #$html .= '&nbsp;&nbsp;&nbsp;';
      #$html .= $this->_getYearList();

      $html .= '<noscript><input type="submit" style="font-size:10pt; width:2em;" name="room_change" value="'.$this->_translator->getMessage('COMMON_GO_BUTTON2').'"/></noscript>'.LF;

      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }

   function _getTableheadMonthAsHTMLWithJavascript() {
      $params = $this->_getGetParamsAsArray();
      // Optimierungsbedarf: Die $this->_translator->getMessage wird 11Mal!!! umsonst aufgerufen
      $current_time = localtime();
      $month = getLongMonthName($current_time[4]);
      $html  = '   <tr>'.LF;
      #$html .= '      <td class="infoborderyear"  style="vertical-align:bottom;">'.LF;

      #// jQuery
      #//$html .= '   <select style="width: 10em; font-size:10pt;" name="presentation_mode" size="1" onChange="javascript:document.indexform.submit()">'.LF;
      #$html .= '   <select style="width: 10em; font-size:10pt;" name="presentation_mode" size="1" id="submit_form">'.LF;
      #// jQuery

      #$html .= '      <option value="2"';
      #if ($this->_presentation_mode == '2'){
      #   $html .= ' selected="selected"';
      #}
      #$html .= '>'.$this->_translator->getMessage('DATE_MONTH_PRESENTATION').'</option>'.LF;
      #$html .= '      <option value="1"';
      #if ($this->_presentation_mode != '2'){
      #   $html .= ' selected="selected"';
      #}
      #$html .= '>'.$this->_translator->getMessage('DATE_WEEK_PRESENTATION').'</option>'.LF;
      #$html .= '   </select>'.LF;

      $html .= '</td>'.LF;
      $html .= '<td colspan="3" class="infoborderweek"  style="vertical-align:bottom; text-align:left; white-space:nowrap;">'.LF;
      #$html .= $this->_getWeekList();
      #$html .= '&nbsp;&nbsp;&nbsp;';
      $html .= $this->_getMonthListWithJavascript();
      #$html .= '&nbsp;&nbsp;&nbsp;';
      #$html .= $this->_getYearList();

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
      // jQuery
      //$html = '   <select name="year" size="1" style="width:5em;" onChange="javascript:document.indexform.submit()">'.LF;
      $html = '   <select name="year" size="1" style="width:5em;" id="submit_form">'.LF;
      // jQuery
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
      return $this->_translator->getMessage('COMMON_YEAR').':'.$left.$html.$right;;
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
      // jQuery
      //$html .= '   <select name="week" size="1" style="width:10em;" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '   <select name="week" size="1" style="width:10em;" id="submit_form">'.LF;
      // jQuery
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
      return $this->_translator->getMessage('COMMON_WEEK').':'.$left.$html.$right;;
   }

   function _getWeekListWithJavascript() {
      $html ='';
      #$current_date = getdate();
      #$month_array = array($this->_translator->getMessage('DATES_JANUARY_SHORT'),
      #      $this->_translator->getMessage('DATES_FEBRUARY_SHORT'),
      #      $this->_translator->getMessage('DATES_MARCH_SHORT'),
      #      $this->_translator->getMessage('DATES_APRIL_SHORT'),
      #      $this->_translator->getMessage('DATES_MAY_SHORT'),
      #      $this->_translator->getMessage('DATES_JUNE_SHORT'),
      #      $this->_translator->getMessage('DATES_JULY_SHORT'),
      #      $this->_translator->getMessage('DATES_AUGUST_SHORT'),
      #      $this->_translator->getMessage('DATES_SEPTEMBER_SHORT'),
      #      $this->_translator->getMessage('DATES_OCTOBER_SHORT'),
      #      $this->_translator->getMessage('DATES_NOVEMBER_SHORT'),
      #      $this->_translator->getMessage('DATES_DECEMBER_SHORT'));
      #if (!isset($this->_week) or empty($this->_week)){
      #   $d_time = mktime(3,0,0,date("m"),date("d"),date("Y") );
      #   $wday = date("w",$d_time );
      #   $week = mktime (3,0,0,date("m"),date("d") - ($wday - 1),date("Y"));
      #}else{
      #   $week = $this->_week;
      #}
      #// jQuery
      #//$html .= '   <select name="week" size="1" style="width:10em;" onChange="javascript:document.indexform.submit()">'.LF;
      #$html .= '   <select name="week" size="1" style="width:10em;" id="submit_form">'.LF;
      #// jQuery
      #for ( $i = -4; $i <= 7; $i++ ) {
      #   $twkstart = $week + ( 3600 * 24 * 7 * $i );
      #   $twkend = $twkstart + ( 3600 * 24 * 6 );
      #   $startmonth = date("m", $twkstart);
      #   $startmonth = $month_array[$startmonth-1];
      #   $startday = date("d",$twkstart);
      #   $endmonat = date("m",$twkend);
      #   $endmonat = $month_array[$endmonat-1];
      #   $endtag = date("d",$twkend);
      #   $language = $this->_environment->getSelectedLanguage();
      #   if ( $language=='en'){
      #      $text = $startmonth.' '.$startday.' - '.$endmonat.' '.$endtag;
      #   }else{
      #      $first_char = mb_substr($startday,0,1);
      #      if ($first_char == '0'){
      #         $startday = mb_substr($startday,1,2);
      #      }
      #      $first_char = mb_substr($endtag,0,1);
      #      if ($first_char == '0'){
      #         $endtag = mb_substr($endtag,1,2);
      #      }
      #      $text = $startday.'. '.$startmonth.' - '.$endtag.'. '.$endmonat;
      #   }
      #   $html .='<option value="'. $twkstart.'"';
      #   if ( $this->_week == $twkstart ){
      #      $html .=' selected="selected"';
      #      $this->_week_start = $twkstart;
      #   }
      #   $html .= '>';
      #   $html .= $text;
      #   $html .= '</option>'.LF;
      #}
      #$html .= '   </select>'.LF;
      $prev_image = '<img src="images/calendar_prev.gif" alt="&lt;" border="0"/>';
      $today_image = '<img src="images/calendar_today.gif" alt="&lt;" border="0"/>';
      $next_image = '<img src="images/calendar_next.gif" alt="&lt;" border="0"/>';
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
      $day = date('D');
      if($day == 'Mon'){
         $params['week'] = time();
      } elseif ($day == 'Tue'){
         $params['week'] = time() - (3600 * 24);
      } elseif ($day == 'Wed'){
         $params['week'] = time() - (3600 * 24 * 2);
      } elseif ($day == 'Thu'){
         $params['week'] = time() - (3600 * 24 * 3);
      } elseif ($day == 'Fri'){
         $params['week'] = time() - (3600 * 24 * 4);
      } elseif ($day == 'Sat'){
         $params['week'] = time() - (3600 * 24 * 5);
      } elseif ($day == 'Sun'){
         $params['week'] = time() - (3600 * 24 * 6);
      }
      $params['presentation_mode'] = '1';
      $today = '           '.ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                                'index',
                                $params,
                                $today_image,
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
      #return $this->_translator->getMessage('COMMON_WEEK').':'.$left.$html.$right;
      $return = '<div style="width:100%; height:30px; position:relative">';
      $return .= '<div id="calendar_switch" style="position:absolute; bottom:0px; left:0px; z-index:1000;">';
      $return .= $left . $today . $right . '&nbsp;&nbsp;';
      $return .= '<span style="color: #2e4e73; font-size:1.3em;">';
      $return .= date('d.m.Y', $this->_week_start) . ' - ';
      $return .= date('d.m.Y', $this->_week_start + ( 3600 * 24 * 6));
      $return .= '</span>';
      $return .= '</div>';
      $return .= '<div style="position:absolute; bottom:0px; left:0px; width:100%; text-align:center;">';
      $return .= $this->_getSwitchIconBar();
      $return .= '</div>';
      $return .= '<div id="calendar_calendarweek" style="position:absolute; bottom:0px; right:0px;">';
      $return .= '<span style="color: #2e4e73; font-size:1.3em;">';
      $calendar_week = date('W', $this->_week_start);
      if($calendar_week[0] == '0'){
         $calendar_week = $calendar_week[1];
      }
      $return .= $this->_translator->getMessage('DATES_CALENDARWEEK') . ': ' . $calendar_week;
      $return .= '</span>';
      $return .= '</div>';
      $return .= '</div>';
      return  $return;
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
      // jQuery
      //$html .= '   <select name="month" size="1" style="width:10em;" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '   <select name="month" size="1" style="width:10em;" id="submit_form">'.LF;
      // jQuery
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
      return $this->_translator->getMessage('COMMON_MONTH').':'.$left.$html.$right;;
   }

   function _getMonthListWithJavascript() {
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
      // jQuery
      //$html .= '   <select name="month" size="1" style="width:10em;" onChange="javascript:document.indexform.submit()">'.LF;
      #$html .= '   <select name="month" size="1" style="width:10em;" id="submit_form">'.LF;
      // jQuery

      //Do some time calculations
      $month = mb_substr($this->_month,4,2);
      $year = $this->_year;
      $days = daysInMonth($month,$year);
      $first_day_week_day = $this->weekDayofDate(1,$month,$year);

      //Create array with correct daynumber/weekday relationship
      $format_array = array();
      $current_month = array();
      $current_year = array();
      //skip fields at beginning
      $empty_fields = (($first_day_week_day + 6) % 7);
      if($month != '01'){
         $prev_month = $month - 1;
         $prev_month_year = $year;
      } else {
         $prev_month = 12;
         $prev_month_year = $year - 1;
      }
      $prev_month_days = daysInMonth($prev_month,$prev_month_year);
      for ($i =0; $i < $empty_fields; $i++) {
         $format_array[]['day'] = $prev_month_days-($empty_fields - $i)+1;
         $current_month[] = $prev_month;
         $current_year[] = $prev_month_year;
      }
      //fill days
      for ($i =1; $i <= $days;$i++) {
         $format_array[]['day'] = $i;
         $current_month[] = $month;
         $current_year[] = $year;
      }
      //skip at ending
      $sum = $days + $empty_fields;
      $remaining = 42 - $sum;
      if($month != '12'){
         $next_month = $month + 1;
         $next_month_year = $year;
      } else {
         $next_month = 1;
         $next_month_year = $year + 1;
      }
      for ($i=0;$i<$remaining;$i++) {
         $format_array[]['day'] = $i + 1;
         $current_month[] = $next_month;
         $current_year[] = $next_month_year;
      }
      $calendar_week_first = date('W', mktime(3,0,0,$current_month[0],$format_array[0]['day'],$current_year[0]));
      if($calendar_week_first[0] == '0'){
         $calendar_week_first = $calendar_week_first[1];
      }
      $calendar_week_last = date('W', mktime(3,0,0,$current_month[35],$format_array[35]['day'],$current_year[35]));
      if($calendar_week_last[0] == '0'){
         $calendar_week_last = $calendar_week_last[1];
      }

      if (!isset($this->_month) or empty($this->_month)){
         $month = date ("Ymd");
      }else{
         $month = $this->_month;
      }
      $year = mb_substr($month,0,4);
      $month = mb_substr($month,4,2);
      if($month != 1 and $month != 12){
      	$prev_month = $month-1;
      	$next_month = $month+1;
      	$prev_month_year = $year;
      	$next_month_year = $year;
      } elseif ($month == 1){
      	$prev_month = 12;
      	$next_month = 2;
      	$prev_month_year = $year-1;
      	$next_month_year = $year;
      } elseif ($month == 12){
      	$prev_month = 11;
      	$next_month = 1;
      	$prev_month_year = $year;
      	$next_month_year = $year+1;
      }

      $params = $this->_environment->getCurrentParameterArray();
      unset($params['year']);
      unset($params['week']);
      
      $prev_image = '<img src="images/calendar_prev.gif" alt="&lt;" border="0"/>';
      $today_image = '<img src="images/calendar_today.gif" alt="&lt;" border="0"/>';
      $next_image = '<img src="images/calendar_next.gif" alt="&lt;" border="0"/>';
      $params['presentation_mode'] = '2';
      $params['month'] = date("Ymd", mktime(3,0,0,$prev_month,1,$prev_month_year));
      $left = '           '.ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                                'index',
                                $params,
                                $prev_image,
                                '').LF;
      $params['presentation_mode'] = '2';
      $params['month'] = date("Ymd");
      $today = '           '.ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                                'index',
                                $params,
                                $today_image,
                                '').LF;
      $params['presentation_mode'] = '2';
      $params['month'] = date("Ymd",mktime(3,0,0,$next_month,1,$next_month_year));
      $right = '           '.ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                                'index',
                                $params,
                                $next_image,
                                '').LF;
      $return = '<div style="width:100%; height:30px; position:relative;">';
      $return .= '<div id="calendar_switch" style="position:absolute; bottom:0px; left:0px; z-index:1000;">';
      $return .= $left . $today . $right . '&nbsp;&nbsp;';
      $return .= '<span style="color: #2e4e73; font-size:1.3em;">';
      $return .= $month_array[$month -1] . ' ' . $year;
      $return .= '</span>';
      $return .= '</div>';
      $return .= '<div style="position:absolute; bottom:0px; left:0px; width:100%; text-align:center;">';
      $return .= $this->_getSwitchIconBar();
      $return .= '</div>';
      $return .= '<div id="calendar_calendarweek" style="position:absolute; bottom:0px; right:0px;">';
      $return .= '<span style="color: #2e4e73; font-size:1.3em;">';
      $calendar_week = date('W', $this->_week_start);
      if($calendar_week[0] == '0'){
         $calendar_week = $calendar_week[1];
      }
      $return .= $this->_translator->getMessage('DATES_CALENDARWEEKS') . ': ' . $calendar_week_first . ' - ' . $calendar_week_last;
      $return .= '</span>';
      $return .= '</div>';
      $return .= '</div>';
      return  $return;
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
                           $hover, // Sunbird-Vorbereitung -> hover durch '' ersetzen
                           '',
                           '',
                           '',
                           '',
                           'calendar_link_' . $params['iid']);
         }else{
            $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DATE_TYPE,
                           'detail',
                           $params,
                           $title,
                           $hover, // Sunbird-Vorbereitung -> hover durch '' ersetzen
                           '',
                           '',
                           '',
                           '',
                           'calendar_link_' . $params['iid']);

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
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $title .= '<br/>'.ahref_curl( $this->_environment->getCurrentContextID(),
                            CS_DATE_TYPE,
                           'edit',
                           $params,
                           '<img title="'.$this->_translator->getMessage('COMMON_EDIT').'" alt="'.$this->_translator->getMessage('COMMON_EDIT').'" src="images/commsyicons_msie6/12x12/edit.gif" border="0"/>',
                           '');
         } else {
            $title .= '<br/>'.ahref_curl( $this->_environment->getCurrentContextID(),
                            CS_DATE_TYPE,
                           'edit',
                           $params,
                           '<img title="'.$this->_translator->getMessage('COMMON_EDIT').'" alt="'.$this->_translator->getMessage('COMMON_EDIT').'" src="images/commsyicons/12x12/edit.png" border="0"/>',
                           '');
         }
      }
      // Sunbird-Vorbereitung
      //$jQuery_hover = '<div id="calendar_hover_' . $params['iid'] . '" style="width: 180px; height: 45px;position: relative; top: -85px;left: -15px;text-align: center;padding: 20px 12px 10px;font-style: normal;z-index: 2;display: none;">' . $hover . '</div>';
      //$title = $title . $jQuery_hover;
      unset($params);
      return $title;
   }

  /** get the link to the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemLink($item, $text) {
      $text = $this->_compareWithSearchText($text);
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
                           $text,
                           '',
                           '',
                           '',
                           '',
                           '',
                           'calendar_link_' . $params['iid']);
         }else{
            $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DATE_TYPE,
                           'detail',
                           $params,
                           $text,
                           '',
                           '',
                           '',
                           '',
                           '',
                           'calendar_link_' . $params['iid']);

         }
      unset($params);
      return $title;
   }

  /** get the link to the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemLinkWithJavascript($item, $text) {
      $text = $this->_compareWithSearchText($text);
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
      $link_color = '#000000';
      if ($item->getColor() != ''){
      	if(($item->getColor() == '#3366FF')
      	   or ($item->getColor() == '#6633FF')
      	   or ($item->getColor() == '#CC33CC')
      	   or ($item->getColor() == '#CC0000')
      	   or ($item->getColor() == '#FF6600')
      	   or ($item->getColor() == '#00CCCC')
      	   or ($item->getColor() == '#999999')){
      		$link_color = '#FFFFFF';
      	}
      }
      if ( $item->issetPrivatDate() ){
           $title ='<i>'.$title.'</i>';
           $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DATE_TYPE,
                           'detail',
                           $params,
                           $text,
                           '',
                           '',
                           '',
                           '',
                           '',
                           'calendar_link_' . $params['iid'],
                           'style="color:' . $link_color .';"');
         }else{
            $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DATE_TYPE,
                           'detail',
                           $params,
                           $text,
                           '',
                           '',
                           '',
                           '',
                           '',
                           'calendar_link_' . $params['iid'],
                           'style="color:' . $link_color .';"');

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
      $html .= '      <td class="calendar_head_first" style="width:14%; text-align:center;">'.$this->_translator->getMessage('COMMON_DATE_MONDAY').'</td>'.LF;
      $html .= '      <td class="calendar_head" style="width:14%; text-align:center;">'.$this->_translator->getMessage('COMMON_DATE_TUESDAY').'</td>'.LF;
      $html .= '      <td class="calendar_head" style="width:14%; text-align:center;">'.$this->_translator->getMessage('COMMON_DATE_WEDNESDAY').'</td>'.LF;
      $html .= '      <td class="calendar_head" style="width:14%; text-align:center;">'.$this->_translator->getMessage('COMMON_DATE_THURSDAY').'</td>'.LF;
      $html .= '      <td class="calendar_head" style="width:14%; text-align:center;">'.$this->_translator->getMessage('COMMON_DATE_FRIDAY').'</td>'.LF;
      $html .= '      <td class="calendar_head" style="width:14%; text-align:center;">'.$this->_translator->getMessage('COMMON_DATE_SATURDAY').'</td>'.LF;
      $html .= '      <td class="calendar_head" style="border-right:0px solid black; width:14%; text-align:center;">'.$this->_translator->getMessage('COMMON_DATE_SUNDAY').'</td>'.LF;
      $html .= '   </tr>'.LF;

      $html .= '   <tr class="listcalendar" style="height:8em;">'.LF;
      //rest of table
      for ($i=0;$i<42;$i++) {
         if ( !$finish ) {
            $dates_on_day = isset($format_array[$i]['dates'])?$format_array[$i]['dates']:'';
            if ($current_time[3]==$format_array[$i]['day'] and $current_time[4]+1==$month and $current_time[5]+1900==$year){
               $html .= '      <td class="calendar_content_focus" style="border: spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:8em; width:14%;">';
            } elseif( (($i+1) % 7 == 0) or (($i+2) % 7 == 0) ) {
               $html .= '      <td class="calendar_content" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:8em; width:14%;">';
#               $html .= '      <td class="calendar_content_weekend" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:8em; width:14%;">';
            }else {
               $html .= '      <td class="calendar_content" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:8em; width:14%;">';
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
                     if ( $length > 20 ) {
                        $new_date = mb_substr($date->getTitle(),0,20).'<br />&nbsp;&nbsp;';
                        if ( $length > 40 ) {
                           $new_date .= mb_substr($date->getTitle(),20,20).'...';
                        } else {
                           $new_date .= mb_substr($date->getTitle(),20,$length-20);
                        }
                     } else {
                        $new_date = $date->getTitle();
                     }
                  } else {
                     $length = mb_strlen($date->getTitle());
                     if ($length > 20) {
                        $new_date = mb_substr($date->getTitle(),0,20).'...';
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
            $width = '100%';
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

   function _getMonthContentAsHTMLWithJavascript() {
      $current_time = localtime();
	  $today = '';

      //Do some time calculations
      $month = mb_substr($this->_month,4,2);
      $year = $this->_year;
      $days = daysInMonth($month,$year);
      $first_day_week_day = $this->weekDayofDate(1,$month,$year);

      //Create array with correct daynumber/weekday relationship
      $format_array = array();
      $current_month = array();
      $current_year = array();
      //skip fields at beginning
      $empty_fields = (($first_day_week_day + 6) % 7);
      if($month != '01'){
         $prev_month = $month - 1;
         $prev_month_year = $year;
      } else {
         $prev_month = 12;
         $prev_month_year = $year - 1;
      }
      $prev_month_days = daysInMonth($prev_month,$prev_month_year);
      for ($i =0; $i < $empty_fields; $i++) {
         $format_array[]['day'] = $prev_month_days-($empty_fields - $i)+1;
         $current_month[] = $prev_month;
         $current_year[] = $prev_month_year;
      }
      //fill days
      for ($i =1; $i <= $days;$i++) {
         $format_array[]['day'] = $i;
         $current_month[] = $month;
         $current_year[] = $year;
      }
      //skip at ending
      $sum = $days + $empty_fields;
      $remaining = 42 - $sum;
      if($month != '12'){
         $next_month = $month + 1;
         $next_month_year = $year;
      } else {
         $next_month = 1;
         $next_month_year = $year + 1;
      }
      for ($i=0;$i<$remaining;$i++) {
         $format_array[]['day'] = $i + 1;
         $current_month[] = $next_month;
         $current_year[] = $next_month_year;
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
      $html = '';
      $html .= '<div id="calender_month_frame" style="width:100%; background-color:#ffffff; border-top:1px solid black; border-left:1px solid black; padding:0px;">'.LF;

      $html .= '<div class="calendar_month_entry_head">'.$this->_translator->getMessage('COMMON_DATE_MONDAY').'</div>'.LF;
      $html .= '<div class="calendar_month_entry_head">'.$this->_translator->getMessage('COMMON_DATE_TUESDAY').'</div>'.LF;
      $html .= '<div class="calendar_month_entry_head">'.$this->_translator->getMessage('COMMON_DATE_WEDNESDAY').'</div>'.LF;
      $html .= '<div class="calendar_month_entry_head">'.$this->_translator->getMessage('COMMON_DATE_THURSDAY').'</div>'.LF;
      $html .= '<div class="calendar_month_entry_head">'.$this->_translator->getMessage('COMMON_DATE_FRIDAY').'</div>'.LF;
      $html .= '<div class="calendar_month_entry_head">'.$this->_translator->getMessage('COMMON_DATE_SATURDAY').'</div>'.LF;
      $html .= '<div class="calendar_month_entry_head">'.$this->_translator->getMessage('COMMON_DATE_SUNDAY').'</div>'.LF;

      //rest of table
      $anAction_array = array();
      $date_index = 0;
      $tooltips = array();
      $tooltip_last_id = '';
      $tooltip_date = '';
      for ($i=0;$i<42;$i++) {

         if($format_array[$i]['day'].$current_month[$i].$current_year[$i] == date("dmY")){
            $today = $format_array[$i]['day'].$current_month[$i].$current_year[$i];
         }

         if(isset($format_array[$i]['dates']) and !empty($format_array[$i]['dates'])){
            foreach($format_array[$i]['dates'] as $date){
               $link = $this->_getItemLinkWithJavascript($date, $date->getTitle());
               $link_array = split('"', $link);
               $href = $link_array[1];
               if($date->getColor() != ''){
                  $color = $date->getColor();
               } else {
                  $color = '#FFFF66';
               }
               $color_border = '#CCCCCC';
               $current_month_temp = $current_month[$i];
               if($current_month_temp[0] == 0){
                  $current_month_temp = $current_month_temp[1];
               }
               $date_array_for_jQuery[] = 'new Array(' . $format_array[$i]['day'] . ',' . $current_month_temp . ',\'' . $link . '\',' . count($format_array[$i]['dates']) . ',\'' . $color . '\'' . ',\'' . $color_border . '\'' . ',\'' . $href . '\'' . ',\'sticky_' . $date_index . '\')';
               $tooltip = array();
               $tooltip['title'] = $date->getTitle();

               if($date->getItemID() != $tooltip_last_id){
                  $tooltip_last_id = $date->getItemID();
                  // set up style of days and times
                  $parse_time_start = convertTimeFromInput($date->getStartingTime());
                  $conforms = $parse_time_start['conforms'];
                  if ($conforms == TRUE) {
                     $start_time_print = getTimeLanguage($parse_time_start['datetime']);
                  } else {
                     $start_time_print = $this->_text_as_html_short($this->_compareWithSearchText($date->getStartingTime()));
                  }

                  $parse_time_end = convertTimeFromInput($date->getEndingTime());
                  $conforms = $parse_time_end['conforms'];
                  if ($conforms == TRUE) {
                     $end_time_print = getTimeLanguage($parse_time_end['datetime']);
                  } else {
                     $end_time_print = $this->_text_as_html_short($this->_compareWithSearchText($date->getEndingTime()));
                  }

                 $parse_day_start = convertDateFromInput($date->getStartingDay(),$this->_environment->getSelectedLanguage());
                  $conforms = $parse_day_start['conforms'];
                  if ($conforms == TRUE) {
                    $start_day_print = $date->getStartingDayName().', '.$this->_translator->getDateInLang($parse_day_start['datetime']);
                  } else {
                     $start_day_print = $this->_text_as_html_short($this->_compareWithSearchText($date->getStartingDay()));
                  }

                  $parse_day_end = convertDateFromInput($date->getEndingDay(),$this->_environment->getSelectedLanguage());
                  $conforms = $parse_day_end['conforms'];
                  if ($conforms == TRUE) {
                     $end_day_print =$date->getEndingDayName().', '.$this->_translator->getDateInLang($parse_day_end['datetime']);
                  } else {
                     $end_day_print =$this->_text_as_html_short($this->_compareWithSearchText($date->getEndingDay()));
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
                  $tooltip_date = $temp_array;
               }
               $tooltip['date'] = $tooltip_date;
               $tooltip['place'] = $date->getPlace();
               $tooltip['participants'] = $date->getParticipantsItemList();
               #$tooltip['desc'] = $date->getDescription();
               $tooltip['color'] = $color;
               $tooltips['sticky_' . $date_index] = $tooltip;
               $date_index++;
            }
         }

               $params = array();
               $params['iid'] = 'NEW';
               $params['day'] = $format_array[$i]['day'];
               $parameter_array = $this->_environment->getCurrentParameterArray();
               //$params['month'] = $this->_month;
               $params['month'] = $current_year[$i].$current_month[$i].'01';
               $params['year'] = $current_year[$i];
               $params['presentation_mode'] = $this->_presentation_mode;
               $params['modus_from'] = 'calendar';
//               if ( $this->_with_modifying_actions and !empty($format_array[$i]['day'])) {
                  $anAction = ahref_curl( $this->_environment->getCurrentContextID(),
                                 CS_DATE_TYPE,
                                 'edit',
                                 $params,
                                 '<img style="width:100%; height:100%" src="images/spacer.gif" alt="" border="0"/>');
                  $anAction_array[] = $anAction;
      }
      $i = 0;
      for ($index_week = 0; $index_week < 6; $index_week++) {
         for ($index_day = 0; $index_day < 7; $index_day++) {
            $current_month_temp = $current_month[$i];
            if($current_month_temp[0] == 0){
               $current_month_temp = $current_month_temp[1];
            }
            $html .= '<div class="calendar_month_entry" id="calendar_month_entry_' . $format_array[$i]['day'] .'_' . $current_month_temp . '" style="';
            if($current_month[$i] != mb_substr($this->_month,4,2)){
               $html .= 'background-color:#dddddd;';
            }
            if($index_day == 0){
      	     $html .= 'clear:both;';
   	      }
      	   $html .= ' position:relative;">' . $format_array[$i]['day'] . '<div style="position: absolute; top:0px; left:0px; height:100%; width:100%;">' . $anAction_array[$i] . '</div></div>'.LF;
      	   $i++;
   	   }
      }
      //Create the html part of the calendar
      //title row with weekdays
      $params = $this->_environment->getCurrentParameterArray();
      unset($params);
      $html .= '<div id="calendar_month_footer" class="calendar_month_footer">' . $this->_translator->getMessage('DATES_TIPP_FOR_ENTRIES') . '</div>'.LF;
      $html .= '</div>'.LF;

      $html .= '<div id="mystickytooltip" class="stickytooltip"><div style="border:1px solid #cccccc;">';
      foreach($tooltips as $id => $tooltip){
         $html .= '<div id="' . $id . '" class="atip" style="padding:5px; border:2px solid ' . $tooltip['color'] . '">'.LF;
         $html .= '<table>'.LF;
         $html .= '<tr><td colspan="2"><b>' . $tooltip['title'] . '</b></td></tr>'.LF;
         $html .= '<tr><td style="vertical-align:top;"><b>' . $this->_translator->getMessage('DATES_DATETIME') . ':</b></td><td>' .  $tooltip['date'][1] . '</td></tr>'.LF;
         if($tooltip['place'] != ''){
            $html .= '<tr><td style="vertical-align:top;"><b>' . $this->_translator->getMessage('DATES_PLACE') . ':</b></td><td>' . $tooltip['place'] . '</td></tr>'.LF;
         }
         $html .= '<tr><td style="vertical-align:top;"><b>' . $this->_translator->getMessage('DATE_PARTICIPANTS') . ':</b></td><td>'.LF;
         if($tooltip['participants']->isEmpty()){
            $html .= $this->_translator->getMessage('TODO_NO_PROCESSOR');
         } else {
            $participant = $tooltip['participants']->getFirst();
            $count = $tooltip['participants']->getCount();
            $counter = 1;
            while ($participant) {
               $html .= $participant->getFullName();
               if ( $counter < $count) {
                  $html .= ', ';
               }
               $participant = $tooltip['participants']->getNext();
               $counter++;
            }
         }
         $html .= '</td></tr>'.LF;
         #$html .= '<tr><td colspan="2">' . $tooltip['desc'] . '</td></tr>'.LF;
         $html .= '</table>'.LF;
         $html .= '</div>'.LF;
      }
      $html .= '</div></div>';
      $html .= '<script type="text/javascript">'.LF;
      $html .= '<!--'.LF;
      $html .= 'var calendar_dates = new Array(';
      if(isset($date_array_for_jQuery) and !empty($date_array_for_jQuery)){
         $last = count($date_array_for_jQuery)-1;
         for ($index = 0; $index < count($date_array_for_jQuery); $index++) {
            $html .= $date_array_for_jQuery[$index];
            if($index < $last){
              $html .= ',';
            }
         }
      }
      $html .= ');'.LF;
      $html .= 'var today = "' . $today . '";' .LF;
      $html .= '-->'.LF;
      $html .= '</script>'.LF;

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
            if ( is_numeric($start_day)
                 and is_numeric($end_day)
                 and is_numeric($start_month)
                 and is_numeric($end_month)
                 and is_numeric($start_year)
                 and is_numeric($end_year)
               ) {
               if (((($start_day != $end_day and !empty($end_day) and $start_month != $end_month and !empty($end_month)) or
                     ($start_day == $end_day and !empty($end_day) and $start_month != $end_month and !empty($end_month)) or
                     ($start_day != $end_day and !empty($end_day) and $start_month == $end_month and !empty($end_month))) or
                     ($start_year < $end_year and !empty($end_year)))){
                  while ( ( ($start_day != $end_day and $start_month != $end_month) or
                            ($start_day == $end_day and $start_month != $end_month) or
                            ($start_day != $end_day and $start_month == $end_month)
                          )
                          or ($start_year < $end_year)
                        ) {
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
                                   width:14%; text-align:center;">';
            } else {
               $html .= '      <td class="calendar_head"
                                   style="width:14%;
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
                  break;
            }
            $html .= '</td>'.LF;
         $week_start = $week_start + ( 3600 * 24);
      }
      $session = $this->_environment->getSession();
      $width = '100%';
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
                     if ( $length > 20 ) {
                        $new_date = mb_substr($date->getTitle(),0,20).'...';
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
                     if ( $display_start_time=='0' or !is_numeric($display_start_time) ){
                        $display_start_time ='6';
                     }
                     if ( isset($count_entries[$display_start_time]) and $count_entries[$display_start_time] > 1 ) {
                        $length = mb_strlen($date->getTitle());
                        if ($length > 20) {
                           $new_date = mb_substr($date->getTitle(),0,19).'...';
                        } else {
                           $new_date = $date->getTitle();
                        }
                      } else {
                        $length = mb_strlen($date->getTitle());
                        if ( $length > 20 ) {
                           $new_date = mb_substr($date->getTitle(),0,19).'...';
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
                        if ( !is_numeric($display_ending_time)
                             and is_numeric($display_start_time)
                           ) {
                           $display_ending_time = $display_start_time+1;
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
                  $entry_html = '      <td class="calendar_content_without_time" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:1em; width:14%;">';
               }else{
                  $entry_html = '      <td class="calendar_content" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:2em; width:14%;">';
#                  $entry_html = '      <td class="calendar_content_weekend" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:2em; width:7.2em;">';
               }
            }else{
               if ($i == 0){
                  $entry_html = '      <td class="calendar_content_without_time" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:1em; width:14%;">';
               }else{
                  $entry_html = '      <td class="calendar_content" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:2em; width:14%;">';
               }
            }

            if (isset($date_text[1]) and !empty($date_text[1]) and $i == 0){
               $entry_html = '      <td class="calendar_content_without_time" style="spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:1em; width:14%;">';
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
               $entry_html = '      <td class="calendar_content_with_entry" style="'.$css_text.' spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:2em; width:14%;">';
               $entry_html .= $date_text[$time];
               $is_entry = true;
            }elseif( isset($date_show[$time]) and !empty($date_show[$time]) and $i != 0 ){
               $css_text = '';
               switch ($date_show[$time]){
                  case 1: $css_text = ''; break;
                  case 2: $css_text = 'background-color:#F0F000;'; break;
                  default: $css_text = 'background-color:#F0F000;'; break;
               }

               $entry_html = '      <td class="calendar_content_with_entry" style="'.$css_text.' spacing:0px; padding:1px 1px 0px 2px; vertical-align:top; height:2em; width:14%;">';
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
      $html .= '   <tr class="calendar_head" style="height: 20px;">'.LF;
      $html .= '      <td  colspan="8" class="calendar_head_all_first" style="text-align:left; font-size:8pt;">'.$this->_translator->getMessage('DATES_WEEK_TIPP_FOR_ENTRIES').'</td>'.LF;
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

   function _getWeekContentAsHTMLWithJavaScript() {
      $week_start = $this->_week_start;
      $today = '';
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
            if ( is_numeric($start_day)
                 and is_numeric($end_day)
                 and is_numeric($start_month)
                 and is_numeric($end_month)
                 and is_numeric($start_year)
                 and is_numeric($end_year)
               ) {
               if (((($start_day != $end_day and !empty($end_day) and $start_month != $end_month and !empty($end_month)) or
                     ($start_day == $end_day and !empty($end_day) and $start_month != $end_month and !empty($end_month)) or
                     ($start_day != $end_day and !empty($end_day) and $start_month == $end_month and !empty($end_month))) or
                     ($start_year < $end_year and !empty($end_year)))){
                  while ( ( ($start_day != $end_day and $start_month != $end_month) or
                            ($start_day == $end_day and $start_month != $end_month) or
                            ($start_day != $end_day and $start_month == $end_month)
                          )
                          or ($start_year < $end_year)
                        ) {
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
         }
         $current_date = $this->_list->getNext();
      }
      //Create the html part of the calendar
      //title row with weekdays
      $html .= '<div id="calender_frame" style="width:100%; background-color:#ffffff; border:1px solid black; padding:0px;">'.LF;
      $html .= '<div id="calender_dates" style="width:100%; clear:both;">'.LF;
      $html .= '<div class="calendar_time_head" id="calendar_time"><div class="data">&nbsp;</div></div>'.LF;
      $display_date_array = array();
      for ($i = 1; $i <8; $i++){
         $startday = date ("d",$week_start);
         $startmonth = date ("m",$week_start);
         $startyear = date ("Y",$week_start);
         if($startday.$startmonth.$startyear == date("dmY")){
            $today = $startday.$startmonth.$startyear;
         }
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
            $html .='<div class="calendar_entry_head" id="calendar_head_' . ($i-1) . '_' . date("dmY", $week_start) . '"><div class="data">'.LF;
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
                  break;
            }
            $html .= '</div></div>'.LF;

         $week_start = $week_start + ( 3600 * 24);
      }
      #$html .= '<div style="width:12px; float:left;">&nbsp;</div>'.LF;
      $session = $this->_environment->getSession();
      $width = '100%';
      $html .= '</div>'.LF;
      $html .= '<div id="calender_days" style="width:100%; clear:both;">'.LF;
      $html .= '<div class="calendar_time" id="calendar_time"><div class="data">&nbsp;</div></div>'.LF;
      for($index=0; $index <7; $index++){
         $week_start = $this->_week_start + ( 3600 * 24 * $index);
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
         $params['time'] = 0;
         $params['modus_from'] = 'calendar';
         $anAction ='';
         if ($i == 0){
            $image = '<img style="width:'.$width.'; height:1em;" src="images/spacer.gif" alt="" border="0"/>';
         }else{
            $image = '<img style="width:'.$width.'; height:2.2em;" src="images/spacer.gif" alt="" border="0"/>';
         }
         if ( $this->_with_modifying_actions ) {
            $anAction = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DATE_TYPE,
                           'edit',
                           $params,
                           $image);
         }
         $html .= '<div class="calendar_entry" id="calendar_entry_' . $index . '"><div class="data" id="calendar_entry_date_div_' . $index . '">'.$anAction.'</div></div>'.LF;
      }
      #$html .= '<div style="width:11px; float:left;">&nbsp;</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<div id="calender_main" style="height:450px; overflow:none; clear:both;">'.LF;
      $current_element = 0;
      $html_javascript = '<script type="text/javascript"><!--'.LF;
      $html_javascript .= 'var new_dates = new Array('.LF;
      for($index=0; $index <24; $index++){
      $html .= '<div class="calendar_time" id="calendar_time_' . $index . '"><div class="data">' . $index . '</div></div>'.LF;
      for($index_day=0; $index_day <7; $index_day++){
         $week_start = $this->_week_start + ( 3600 * 24 * $index_day);
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
            $params['time'] = $index;
         } else{
            $params['time'] = 0;
         }
         $params['modus_from'] = 'calendar';
         $anAction ='';
         if ($i == 0){
            $image = '<img style="width:'.$width.'; height:1em;" src="images/spacer.gif" alt="" border="0"/>';
         }else{
            $image = '<img style="width:'.$width.'; height:2.2em;" src="images/spacer.gif" alt="" border="0"/>';
         }
         if ( $this->_with_modifying_actions ) {
            $anAction = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DATE_TYPE,
                           'edit',
                           $params,
                           $image);
         }
         #$html .= '<div class="calendar_entry" id="calendar_entry_' . $index . '"><div class="data" id="calendar_entry_date_div_' . $index . '_' . $index_day . '">' . $anAction . '</div></div>'.LF;
         $html .= '<div class="calendar_entry" id="calendar_entry_' . $index . '_' . $index_day . '"><div class="data" id="calendar_entry_date_div_' . $index . '_' . $index_day . '"></div></div>'.LF;

         $html_javascript .= 'new Array(\'#calendar_entry_date_div_' . $index . '_' . $index_day . '\',\'<div name="calendar_new_date" id="calendar_entry_background_div_' . $index . '_' . $index_day . '" style="position:absolute; top: 0px; left: 0px; height: 100%; width: 100%; z-index:900;"><div style="width:100%; text-align:left;">' . $anAction . '</div></div>\')';
         if($current_element < (24*7)-1){
            $html_javascript .= ','.LF;
         } else {
            $html_javascript .= LF;
         }
         $current_element++;
      }
      }
      $html_javascript .= ');'.LF;
      $html_javascript .= '--></script>'.LF;
      $html .= $html_javascript;
      $html .= '</div>'.LF;
      $html .= '<div class="calendar_footer">&nbsp;' . $this->_translator->getMessage('DATES_WEEK_TIPP_FOR_ENTRIES') . '</div>';
      $html .= '</div>'.LF;
      $date_array_for_jQuery = array();
      $date_array_for_jQuery_temp = array();
      $date_index = 0;
      $tooltips = array();
      $tooltip_date = '';
      $tooltip_last_id = '';
      for ($day = 1; $day<9; $day++){
         $day_entries = $day-1;
         $left_position = 0;
         if ( isset($display_date_array[$day_entries]) ){
         	#$overlap_array = $this->overlap_display_date_array($display_date_array[$day_entries]);
         	#pr($overlap_array);
         	#$sort_array = array();
            foreach($display_date_array[$day_entries] as $date){
            	$is_date_for_whole_day = false;
               $start_hour = mb_substr($date->getStartingTime(),0,2);
               if(mb_substr($start_hour,0,1) == '0'){
                  $start_hour = mb_substr($start_hour,1,1);
               }
               $start_minutes = mb_substr($date->getStartingTime(),3,2);
               if(mb_substr($start_minutes,0,1) == '0'){
                  $start_minutes = mb_substr($start_minutes,1,1);
               }

               if(($date->getStartingDay() != $date->getEndingDay()) and ($date->getEndingDay() != '')){
               	if($date->getEndingTime() != ''){
                     $end_hour = 23;
                     $end_minutes = 60;
               	} else {
               		$end_hour = 0;
                     $end_minutes = 0;
                     $is_date_for_whole_day = true;
               	}
               } else {
                  $end_hour = mb_substr($date->getEndingTime(),0,2);
                  $end_minutes = mb_substr($date->getEndingTime(),3,2);
               }
               if(mb_substr($end_hour,0,1) == '0'){
                  $end_hour = mb_substr($end_hour,1,1);
               }
               if(mb_substr($end_minutes,0,1) == '0'){
                  $end_minutes = mb_substr($end_minutes,1,1);
               }

               // umrechnen in Minuten, für jede viertelstunde 10 px drauf nach vier noch einen pixel drauf
               $start_minutes = $start_hour*60 + $start_minutes;
               $end_minutes = $end_hour*60 + $end_minutes;

               $start_quaters = mb_substr(($start_minutes / 15),0,2);
               $start_quaters_addon = mb_substr(($start_quaters / 4),0,2);
               $end_quaters = mb_substr(($end_minutes / 15),0,2);
               $end_quaters_addon = mb_substr(($end_quaters / 4),0,2);

               if($start_quaters == 0 and $end_quaters == 0){
               	$is_date_for_whole_day = true;
               }
               
               $top = $start_quaters*10;

               $left = 19 + 129*($day_entries-1) + $left_position;
               $width = 129 / count($display_date_array[$day_entries]) - 4;
               $height = ($end_quaters - $start_quaters) * 10;
               if($date->getColor() != ''){
                  $color = $date->getColor();
               } else {
                  $color = '#FFFF66';
               }
               $color_border = '#CCCCCC';
               $link = $this->_getItemLinkWithJavascript($date, $date->getTitle());
               $link_array = split('"', $link);
               $href = $link_array[1];
               
               $overlap = 1;
               if(!$is_date_for_whole_day){	
	               $display_date = $date;
                  foreach($display_date_array[$day_entries] as $display_date_compare){
                  	$compare_is_date_for_whole_day = false;
                  	if(($display_date_compare->getStartingDay() != $display_date_compare->getEndingDay()) and ($display_date_compare->getEndingDay() != '')){
                  		$compare_is_date_for_whole_day = true;
                  	}
                  	if(!$compare_is_date_for_whole_day and ($display_date->getItemID() != $display_date_compare->getItemID())){
                  	   if($this->overlap($display_date, $display_date_compare)){
                           $overlap++;
                        }
                  	}
                  }
               }

               $date_array_for_jQuery[] = 'new Array(' . $day_entries . ',\'' . $link . '\',' . $start_quaters . ',' . $end_quaters . ',' . count($display_date_array[$day_entries]) . ',\'' . $color . '\'' . ',\'' . $color_border . '\'' . ',\'' . $href . '\'' . ',\'sticky_' . $date_index . '\'' . ',\'' . $is_date_for_whole_day . '\')';
               $date_array_for_jQuery_php[] = array($day_entries, $link, $start_quaters, $end_quaters, count($display_date_array[$day_entries]), $color, $color_border, $href, 'sticky_' . $date_index, $is_date_for_whole_day);
               #$date_array_for_jQuery_temp[] = 'new Array(' . $day_entries . ',\'' . $link . '\',' . $start_quaters . ',' . $end_quaters . ',' . $overlap_array[$date->getItemID()] . ',\'' . $color . '\'' . ',\'' . $color_border . '\'' . ',\'' . $href . '\'' . ',\'sticky_' . $date_index . '\'' . ',\'' . $is_date_for_whole_day . '\')';
               $tooltip = array();
               $tooltip['title'] = $date->getTitle();

               if($date->getItemID() != $tooltip_last_id){
                  $tooltip_last_id = $date->getItemID();
                  // set up style of days and times
                  $parse_time_start = convertTimeFromInput($date->getStartingTime());
                  $conforms = $parse_time_start['conforms'];
                  if ($conforms == TRUE) {
                     $start_time_print = getTimeLanguage($parse_time_start['datetime']);
                  } else {
                     $start_time_print = $this->_text_as_html_short($this->_compareWithSearchText($date->getStartingTime()));
                  }

                  $parse_time_end = convertTimeFromInput($date->getEndingTime());
                  $conforms = $parse_time_end['conforms'];
                  if ($conforms == TRUE) {
                     $end_time_print = getTimeLanguage($parse_time_end['datetime']);
                  } else {
                     $end_time_print = $this->_text_as_html_short($this->_compareWithSearchText($date->getEndingTime()));
                  }

                  $parse_day_start = convertDateFromInput($date->getStartingDay(),$this->_environment->getSelectedLanguage());
                  $conforms = $parse_day_start['conforms'];
                  if ($conforms == TRUE) {
                    $start_day_print = $date->getStartingDayName().', '.$this->_translator->getDateInLang($parse_day_start['datetime']);
                  } else {
                     $start_day_print = $this->_text_as_html_short($this->_compareWithSearchText($date->getStartingDay()));
                  }

                  $parse_day_end = convertDateFromInput($date->getEndingDay(),$this->_environment->getSelectedLanguage());
                  $conforms = $parse_day_end['conforms'];
                  if ($conforms == TRUE) {
                     $end_day_print =$date->getEndingDayName().', '.$this->_translator->getDateInLang($parse_day_end['datetime']);
                  } else {
                     $end_day_print =$this->_text_as_html_short($this->_compareWithSearchText($date->getEndingDay()));
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
                  $tooltip_date = $temp_array;
               }

               $tooltip['date'] = $tooltip_date;
               $tooltip['place'] = $date->getPlace();
               $tooltip['participants'] = $date->getParticipantsItemList();
               #$tooltip['desc'] = $date->getDescription();
               $tooltip['color'] = $color;
               $tooltips['sticky_' . $date_index] = $tooltip;
               $date_index++;
               $left_position = $left_position + $width + 4;
            }
         }
      }
      $html .= '<div id="mystickytooltip" class="stickytooltip"><div style="border:1px solid #cccccc;">';
      foreach($tooltips as $id => $tooltip){
         $html .= '<div id="' . $id . '" class="atip" style="padding:5px; border:2px solid ' . $tooltip['color'] . '">'.LF;
         $html .= '<table>'.LF;
         $html .= '<tr><td colspan="2"><b>' . $tooltip['title'] . '</b></td></tr>'.LF;
         $html .= '<tr><td style="vertical-align:top;"><b>' . $this->_translator->getMessage('DATES_DATETIME') . ':</b></td><td>' .  $tooltip['date'][1] . '</td></tr>'.LF;
         if($tooltip['place'] != ''){
            $html .= '<tr><td style="vertical-align:top;"><b>' . $this->_translator->getMessage('DATES_PLACE') . ':</b></td><td>' . $tooltip['place'] . '</td></tr>'.LF;
         }
         $html .= '<tr><td style="vertical-align:top;"><b>' . $this->_translator->getMessage('DATE_PARTICIPANTS') . ':</b></td><td>'.LF;
         if($tooltip['participants']->isEmpty()){
            $html .= $this->_translator->getMessage('TODO_NO_PROCESSOR');
         } else {
            $participant = $tooltip['participants']->getFirst();
            $count = $tooltip['participants']->getCount();
            $counter = 1;
            while ($participant) {
               $html .= $participant->getFullName();
               if ( $counter < $count) {
                  $html .= ', ';
               }
               $participant = $tooltip['participants']->getNext();
               $counter++;
            }
         }
         $html .= '</td></tr>'.LF;
         #$html .= '<tr><td colspan="2">' . $tooltip['desc'] . '</td></tr>'.LF;
         $html .= '</table>'.LF;
         $html .= '</div>'.LF;
      }
      $html .= '</div></div>';
      $html .= '<script type="text/javascript">'.LF;
      $html .= '<!--'.LF;
      $html .= 'var calendar_dates = new Array(';
      #pr($date_array_for_jQuery_php);
      #pr($date_array_for_jQuery_temp);
      
      // die maximale breite bei nebeneinander liegenden Termine
      
      $overlap_array = array();
      $max_overlap_array = array();
      $date_array_for_jQuery_php_with_position = array();
      for ($int = 0; $int < 7; $int++) {
         $temp_quaters_array = array();
         for ($j = 0; $j < 96; $j++) {
            $temp_quaters_array[] = 0;
         }
         for ($i = 0; $i < sizeof($date_array_for_jQuery_php); $i++) {
            $day = $date_array_for_jQuery_php[$i][0]-1;
            if($day == $int){
               $start_quaters = $date_array_for_jQuery_php[$i][2];
               $end_quaters = $date_array_for_jQuery_php[$i][3];
               for ($j = $start_quaters; $j < $end_quaters; $j++) {
                  $value = $temp_quaters_array[$j];
                  $temp_quaters_array[$j] = $value + 1;
               }
            }
         }
         $overlap_array[] = $temp_quaters_array;
         $max_overlap = 0;
         for ($i = 0; $i < sizeof($temp_quaters_array); $i++) {
            if($max_overlap < $temp_quaters_array[$i]){
               $max_overlap = $temp_quaters_array[$i];
            }
         }
         $max_overlap_array[] = $max_overlap;
      }

      #pr($max_overlap_array);
      // Arrays zum Sortieren vorbereiten
      $sort_dates_array = array();
      $sort_dates_start_array = array();
      for ($i = 0; $i < 7; $i++) {
      	$temp_sort_array = array();
      	for ($j = 0; $j < $max_overlap_array[$i]; $j++) {
      		$temp_part_array = array();
      		for ($k = 0; $k < 96; $k++) {
      			$temp_part_array[] = 0;
      		}
      		$temp_sort_array[] = $temp_part_array;
      	}
      	// Termine sortieren
         foreach($date_array_for_jQuery_php as $temp_date){
         	$found_position = false;
            if($temp_date[0]-1 == $i){
               $start_quaters = $temp_date[2];
               $end_quaters = $temp_date[3];
               $date_set = false;
               for ($temp_part = 0; $temp_part < sizeof($temp_sort_array); $temp_part++) {
               #foreach($temp_sort_array as $temp_part_array){
               	if(!$date_set){
		               $slot_free = true;
		               for ($time = $start_quaters; $time < $end_quaters; $time++) {
		               	if($temp_sort_array[$temp_part][$time] != 0){
		               	#if($temp_part_array[$time] != 0){
		               		$slot_free = false;
		               	}
		               }
		               if($slot_free){
			               for ($time = $start_quaters; $time < $end_quaters; $time++) {
			                  $temp_sort_array[$temp_part][$time] = 1;
			                  if(!$found_position){
			                  	$temp_date[] = sizeof($temp_sort_array);
			                  	$temp_date[] = $temp_part;
			                  	$temp_date[] = $time;
			                  	$found_position = true;
			                  }
			                  $temp_part_array[$time] = 1;
			               }
			               $date_set = true;
		               }
               	}
               }
               $date_array_for_jQuery_php_with_position[] = $temp_date;
            }
         }
         $sort_dates_array[] = $temp_sort_array;
      }
      
      //pr($sort_dates_array);
      #pr($date_array_for_jQuery_php_with_position);
      // -> Breite des Array
      // -> Länge des Arrays ist Anzahl der Quarters
      // Mit jedem Termin durchgehen, und markieren
      // Alles noch einmal durchgehen, und nach rechts vergrößern, wenn dort nichts liegt
      // Javascript bekommt die Koordinaten des Termins und die Breite als Multiplikator des Breitenanteils
      
      
      $last = count($date_array_for_jQuery)-1;
      #for ($index = 0; $index < count($date_array_for_jQuery); $index++) {
      #	$html .= $date_array_for_jQuery[$index];
      #	#pr($date_array_for_jQuery[$index]);
      #	if($index < $last){
      #	  $html .= ',';
      #	}
      #}
      for ($index = 0; $index < count($date_array_for_jQuery_php_with_position); $index++) {
      	$day_entries = $date_array_for_jQuery_php_with_position[$index][0];
      	$link = $date_array_for_jQuery_php_with_position[$index][1];
      	$start_quaters = $date_array_for_jQuery_php_with_position[$index][2];
      	$end_quaters = $date_array_for_jQuery_php_with_position[$index][3];
      	$dates_on_day = $date_array_for_jQuery_php_with_position[$index][4];
      	$color = $date_array_for_jQuery_php_with_position[$index][5];
      	$color_border = $date_array_for_jQuery_php_with_position[$index][6];
      	$href = $date_array_for_jQuery_php_with_position[$index][7];
      	$date_index = $date_array_for_jQuery_php_with_position[$index][8];
      	$is_date_for_whole_day = $date_array_for_jQuery_php_with_position[$index][9];
      	if(isset($date_array_for_jQuery_php_with_position[$index][10])){
      	   $max_overlap = $date_array_for_jQuery_php_with_position[$index][10];
      	} else {
      		$max_overlap = 0;
      	}
      	if(isset($date_array_for_jQuery_php_with_position[$index][11])){
      	   $start_column = $date_array_for_jQuery_php_with_position[$index][11];
      	} else {
            $start_column = 0;
         }
         if(isset($date_array_for_jQuery_php_with_position[$index][12])){
      	   $start_quarter = $date_array_for_jQuery_php_with_position[$index][12];
      	} else {
            $start_quarter = 0;
         }
         $html .= 'new Array(' . $day_entries . ',\'' . $link . '\',' . $start_quaters . ',' . $end_quaters . ',' . $dates_on_day . ',\'' . $color . '\'' . ',\'' . $color_border . '\'' . ',\'' . $href . '\'' . ',\'' . $date_index . '\'' . ',\'' . $is_date_for_whole_day . '\'' . ',\'' . $max_overlap . '\'' . ',\'' . $start_column . '\'' . ',\'' . $start_quarter . '\')'.LF;
         #pr($date_array_for_jQuery[$index]);
         if($index < $last){
           $html .= ',';
         }
      }
   
      $html .= ');'.LF;
      $html .= 'var today = "' . $today . '";' .LF;
      $html .= '-->'.LF;
      $html .= '</script>'.LF;
      return $html;
   }
   
   function getMktimeForDate($display_date){
   	#pr($display_date->getTitle() . ' ' . $display_date->getItemID());
   	$result = array();
   	if($display_date->getStartingTime() != ''){
         $display_date_starttime_hours = mb_substr($display_date->getStartingTime(),0,2);
         $display_date_starttime_minutes = mb_substr($display_date->getStartingTime(),3,2);
         $display_date_starttime_seconds = mb_substr($display_date->getStartingTime(),6,2);
      } else {
         $display_date_starttime_hours = 0;
         $display_date_starttime_minutes = 0;
         $display_date_starttime_seconds = 0;
      }
      $display_date_starttime_year = mb_substr($display_date->getStartingDay(),0,4);
      $display_date_starttime_month = mb_substr($display_date->getStartingDay(),5,2);
      $display_date_starttime_day = mb_substr($display_date->getStartingDay(),8,2);
      $result['starttime'] = mktime((int)$display_date_starttime_hours, (int)$display_date_starttime_minutes, (int)$display_date_starttime_seconds, (int)$display_date_starttime_month, (int)$display_date_starttime_day, (int)$display_date_starttime_year);
                     
      if($display_date->getEndingTime() != ''){
         $display_date_endtime_hours = mb_substr($display_date->getEndingTime(),0,2);
         $display_date_endtime_minutes = mb_substr($display_date->getEndingTime(),3,2);
         $display_date_endtime_seconds = mb_substr($display_date->getEndingTime(),6,2);
      } else {
         $display_date_endtime_hours = 0;
         $display_date_endtime_minutes = 0;
         $display_date_endtime_seconds = 0;
      }
      if($display_date->getEndingDay() != ''){
         $display_date_endtime_year = mb_substr($display_date->getEndingDay(),0,4);
         $display_date_endtime_month = mb_substr($display_date->getEndingDay(),5,2);
         $display_date_endtime_day = mb_substr($display_date->getEndingDay(),8,2);
      } else {
         $display_date_endtime_year = $display_date_starttime_year;
         $display_date_endtime_month = $display_date_starttime_month;
         $display_date_endtime_day = $display_date_starttime_day;
      }
      $result['endtime'] = mktime((int)$display_date_endtime_hours, (int)$display_date_endtime_minutes, (int)$display_date_endtime_seconds, (int)$display_date_endtime_month, (int)$display_date_endtime_day, (int)$display_date_endtime_year);                
      #pr('.');
      return $result;
   }
   
   function overlap_display_date_array($display_date_array){
   	$return_array = array();
   	$overlap_return_array = array();
   	foreach($display_date_array as $display_date){
   		$return_array[$display_date->getItemID()] = 0;
   	}
   	foreach($display_date_array as $display_date) {
   	#for ($display_index = 0; $display_index < sizeof($display_date_array); $display_index++) {
   		#$display_date = $display_date_array[$display_index];
   		$overlap_array = array();
   	   $date_is_date_for_whole_day = false;
         if(($display_date->getStartingDay() != $display_date->getEndingDay()) and ($display_date->getEndingDay() != '')){
            $date_is_date_for_whole_day = true;
         }
         if(!$date_is_date_for_whole_day){
	   		foreach($display_date_array as $compare_date){
         	#for ($compare_index = $display_index; $compare_index < sizeof($display_date_array); $compare_index++) {
         		#$compare_date = $display_date_array[$compare_index];
	   		   if($display_date->getItemID() != $compare_date->getItemID()){
	   		      $compare_date_is_date_for_whole_day = false;
			         if(($compare_date->getStartingDay() != $compare_date->getEndingDay()) and ($compare_date->getEndingDay() != '')){
			            $compare_date_is_date_for_whole_day = true;
			         }
	               if(!$compare_date_is_date_for_whole_day){
                     if($display_date->getItemID() != $compare_date->getItemID()){
                     	if($this->overlap($display_date, $compare_date)){
                     		$overlap_array[] = $compare_date->getItemID();
                     		#$return_array[] = array($display_date->getItemID(),$compare_date->getItemID());
                     		#$return_array[$display_date->getItemID()] = $return_array[$display_date->getItemID()] + 1;
                     		#$return_array[$compare_date->getItemID()] = $return_array[$compare_date->getItemID()] + 1;
                     	}
                     }
	               }
	   		   }
	   		   $return_array[$display_date->getItemID()] = $overlap_array;
	   		}
         }
   	}
   	
   	$return_array_keys = array_keys($return_array);
   	foreach($return_array_keys as $key){
   		#pr($key);
   		$array = $return_array[$key];
   		$count = count($array);
   		if($count > 1){
   		   for ($compare_index = 0; $compare_index < sizeof($array) - 1; $compare_index++) {
   		   	$compare_key = $array[$compare_index];
   		   	for ($compare_with_index = $compare_index + 1; $compare_with_index < sizeof($array); $compare_with_index++) {
   		   		$compare_with_key = $array[$compare_with_index];
   		   		$compare_array = $return_array[$compare_key];
   		   		if(!in_array($compare_with_key, $compare_array)){
   		   			$count--;
   		   		}
   		   		#pr($compare_key . ' <-> ' . $compare_with_key);
   		   	}
   			}
   		}
   		#pr($count);
   		$overlap_return_array[$key] = $count+1;
   	}
   	#pr($return_array);
   	#return $return_array;
   	return $overlap_return_array;
   }
   
   function overlap($display_date, $compare_date){
   	$result = false;
   	
   	$display_date_times = $this->getMktimeForDate($display_date);
      $display_date_starttime = $display_date_times['starttime'];
      $display_date_endtime = $display_date_times['endtime'];
      
      $display_date_compare_times = $this->getMktimeForDate($compare_date);
      $display_date_compare_starttime = $display_date_compare_times['starttime'];
      $display_date_compare_endtime = $display_date_compare_times['endtime'];
      
      if((($display_date_starttime < $display_date_compare_starttime) and ($display_date_endtime > $display_date_compare_starttime))
         or (($display_date_starttime == $display_date_compare_starttime) and ($display_date_endtime == $display_date_compare_endtime))
         or (($display_date_starttime < $display_date_compare_endtime) and ($display_date_endtime > $display_date_compare_endtime))
         or (($display_date_starttime > $display_date_compare_starttime) and ($display_date_starttime < $display_date_compare_endtime))
         or (($display_date_endtime > $display_date_compare_starttime) and ($display_date_endtime < $display_date_compare_endtime))){
         $result = true;
      }
      
      return $result;
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

   function _getSwitchIconBar(){
   	  //$header = '<div style="float:left;padding-right:50px;"><h2 class="pagetitle"><img style="vertical-align: bottom;" src="images/commsyicons/32x32/date.png"/>' . $this->_translator->getMessage('DATES') . '</h2></div>';
      $params = $this->_environment->getCurrentParameterArray();
      if(isset($params['presentation_mode'])){
         if($params['presentation_mode'] == 1){
   	      $day = date('D');
   	      if($day == 'Mon'){
   	         $params['week'] = time();
   	      } elseif ($day == 'Tue'){
   	         $params['week'] = time() - (3600 * 24);
   	      } elseif ($day == 'Wed'){
   	         $params['week'] = time() - (3600 * 24 * 2);
   	      } elseif ($day == 'Thu'){
   	         $params['week'] = time() - (3600 * 24 * 3);
   	      } elseif ($day == 'Fri'){
   	         $params['week'] = time() - (3600 * 24 * 4);
   	      } elseif ($day == 'Sat'){
   	         $params['week'] = time() - (3600 * 24 * 5);
   	      } elseif ($day == 'Sun'){
   	         $params['week'] = time() - (3600 * 24 * 6);
   	      }
         } elseif($params['presentation_mode'] == 2){
         	$params['month'] = date("Ymd");
         }
      }
      $today = ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                                'index',
                                $params,
                                $this->_translator->getMessage('DATES_CALENDAR_LINK_TODAY'),
                                '',
                                '',
                                '',
                                '',
                                '',
                                '',
                                'style="color:#2e4e73; text-decoration:none;"').LF;
      unset($params['week']);
      unset($params['month']);
      $params['presentation_mode'] = '1';
      $params['week'] = $this->_week;
      $week = ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                                'index',
                                $params,
                                $this->_translator->getMessage('DATES_CALENDAR_LINK_WEEK'),
                                '',
                                '',
                                '',
                                '',
                                '',
                                '',
                                'style="color:#2e4e73; text-decoration:none;"').LF;
      unset($params['week']);
      $params['presentation_mode'] = '2';
      $params['month'] = $this->_month;
      $month = ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                                'index',
                                $params,
                                $this->_translator->getMessage('DATES_CALENDAR_LINK_MONTH'),
                                '',
                                '',
                                '',
                                '',
                                '',
                                '',
                                'style="color:#2e4e73; text-decoration:none;"').LF;
      unset($params['month']);
      $return = '<div id="switch_icon_bar" style="width:417px; margin:auto; text-align:center;">';
      $return .= '<div id="switch_float" style="float:left; clear:both;">';
      $return .= '<div id="switch_icon_bar_today" style="text-align:center; height:25px; width:100px; float:left; background-image:url(images/commsyicons/date_today.png); background-repeat:no-repeat; background-position: 50% 0%;"><div style="position:absolute; bottom:0px; width:100px; font-size:1.3em;">' . $today . '</div></div>';
      $return .= '<div id="switch_icon_bar_week" style="text-align:center; height:25px; width:150px; float:left; background-image:url(images/commsyicons/date_week.png); background-repeat:no-repeat; background-position: 50% 0%;"><div style="position:absolute; bottom:0px; width:150px; font-size:1.3em;">' . $week  . '</div></div>';
      $return .= '<div id="switch_icon_bar_month" style="text-align:center; height:25px; width:150px; float:left; background-image:url(images/commsyicons/date_month.png); background-repeat:no-repeat; background-position: 50% 0%;"><div style="position:absolute; bottom:0px; width:150px; font-size:1.3em;">' . $month . '</div></div>';
      $return .= '</div>';
      $return .= '</div>';
      return $return;
   }
   
   function calendar_with_javascript(){
   	$with_javascript = false;
   	$session_item = $this->_environment->getSessionItem();
      if($session_item->issetValue('javascript')){
         if($session_item->getValue('javascript') == "1"){
            $with_javascript = true;
         }
      }
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $with_javascript = false;
      }
      return $with_javascript;
   }
   
   function _getAdditionalCalendarAsHTML(){
   	$html = '<div id="additional_calendar"></div>';
   	return $html;
   }
}
?>