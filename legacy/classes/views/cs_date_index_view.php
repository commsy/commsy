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

/**
 *  class for CommSy list view: date
 */
class cs_date_index_view extends cs_index_view {

   /** array of ids in clipboard*/
   var $_clipboard_id_array = array();

   var $_selected_displaymode = NULL;
   var $_available_displaymode = NULL;
   var $_selected_status = NULL;
   var $_available_color_array = array('#999999','#CC0000','#FF6600','#FFCC00','#FFFF66','#33CC00','#00CCCC','#3366FF','#6633FF','#CC33CC');
   var $_selected_color = NULL;
   var $_used_color_array = array();
   var $_display_mode = NULL;
   var $_alternative_display = 'show';
   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of commsy
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    *
    * @author CommSy Development Group
    */
   function __construct($params) {
      cs_index_view::__construct($params);
      $this->setTitle($this->_translator->getMessage('DATES_HEADER'));
      $this->setActionTitle($this->_translator->getMessage('COMMON_DATES'));
      $this->_colspan = 6;
   }


   function setClipboardIDArray($cia) {
      $this->_clipboard_id_array = $cia;
   }

   function getClipboardIDArray() {
      return $this->_clipboard_id_array;
   }

   function setDisplayMode($status){
      $this->_display_mode = $status;
   }

   function setSelectedStatus ($status) {
      $this->_selected_status = (int)$status;
   }

   function getSelectedStatus () {
      return $this->_selected_status;
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

   function _getGetParamsAsArray() {
      $params = parent::_getGetParamsAsArray();
      $params['selstatus'] = $this->getSelectedStatus();
      return $params;
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

   function _getAdditionalRestrictionBoxAsHTML($field_length=14.5){
      $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $width = '235';
      $context_item = $this->_environment->getCurrentContextItem();
      $html = '';
      $selstatus = $this->getSelectedStatus();
      $html = '<div class="infocolor" style="text-align:left; padding-bottom:5px; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_DATE_STATUS').BRLF;
      // jQuery
      //$html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="selstatus" size="1" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="selstatus" size="1" id="submit_form">'.LF;
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
         $html .= '   <select style="color:'.$style_color.'; width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="selcolor" size="1" id="submit_form">'.LF;

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

      return $html;
   }


   function getAdditionalRestrictionTextAsHTML(){
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->withActivatingContent()){
         $activation_limit = $this->getActivationLimit();
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
      }
      $params = $this->_environment->getCurrentParameterArray();
      if ( !isset($params['selstatus']) or $params['selstatus'] == 4  or $params['selstatus'] == 3 ){
         $this->_additional_selects = true;
         $html_text ='<tr>'.LF;
         $html_text .='<td>'.LF;
         $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_DATE_STATUS').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;
         $status_text = '';
         if (isset($params['selstatus']) and $params['selstatus'] == 4){
            $status_text = $this->_translator->getMessage('DATES_NON_PUBLIC');
         }elseif(!isset($params['selstatus']) or $params['selstatus'] == 3){
            $status_text = $this->_translator->getMessage('DATES_PUBLIC');
         }
         $html_text .= '<span>'.$status_text.'</span>';
         $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         $new_params['selstatus'] = 2;
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
      }
      $params = $this->_environment->getCurrentParameterArray();
/*
      if ( isset($params['selcolor']) and $params['selcolor'] != 2 ){
         $this->_additional_selects = true;
         $html_text ='<tr>'.LF;
         $html_text .='<td>'.LF;
         $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_DATE_COLOR').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;
         $color_text = '';

         $color_text = '';
         switch ('#'.$params['selcolor']){
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
         $html_text .= '<span style="color:#'.$params['selcolor'].';">'.$color_text.'</span>';
         $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         $new_params['selcolor'] = 2;
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
      }*/
      return $html;
   }


   function _getTableheadAsHTML() {
      include_once('functions/misc_functions.php');
      $params = $this->_getGetParamsAsArray();
      $params['from'] = 1;
      $html = '   <tr class="head">'.LF;
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

      $html .= '      <td style="width:20%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'time' ) {
         $params['sort'] = 'time_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'time_rev' ) {
         $params['sort'] = 'time';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'time';
         $picture ='&nbsp;';
      }
      if ( empty($params['download'])
           or $params['download'] != 'zip'
         ) {
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                $params, $this->_translator->getMessage('DATES_TIME'), '', '', $this->getFragment(),'','','','class="head"');
      } else {
         $html .= $this->_translator->getMessage('DATES_TIME');
      }
      $html .= $picture;
      $html .= '</td>'.LF;

      $html .= '      <td colspan="2" style="width:25%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'place' ) {
         $params['sort'] = 'place_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'place_rev' ) {
         $params['sort'] = 'place';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'place';
         $picture ='&nbsp;';
      }
      if ( empty($params['download'])
           or $params['download'] != 'zip'
         ) {
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                $params, $this->_translator->getMessage('DATES_PLACE'), '', '', $this->getFragment(),'','','','class="head"');
      } else {
         $html .= $this->_translator->getMessage('DATES_PLACE');
      }
      $html .= $picture;
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }

   function _getTablefootAsHTML() {
      $html  = '   <tr class="list">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions') {
         $html .= '<td class="foot_left" colspan="3"><input style="font-size:8pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_ATTACH_BUTTON').'" /> <input type="submit"  style="font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'"/>';
      }else{
         $html .= '<td class="foot_left" colspan="3" style="vertical-align:middle;">'.LF;
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
      $html .= '<td colspan="2" class="foot_right" style="vertical-align:middle; text-align:right; font-size:8pt;">'.LF;
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

   function _getListInfosAsHTML ($title) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title">'.LF;
      $html .= $this->_getBrowsingIconsAsHTML().LF;
      $html .= '<div id="right_box_page_numbers">'.$this->_translator->getMessage('COMMON_PAGE').' '.$this->_getForwardLinkAsHTML().'</div>'.LF;
      $html .='</div>'.LF;

      $width = '';
      $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
      $current_browser_version = $this->_environment->getCurrentBrowserVersion();
      if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
         $width = 'width:250px;';
      }
      $html .= '<div class="right_box_main" style="'.$width.'">'.LF;

      $html .= '<table id="list_info_table" style="width:100%; padding:0px; margin:0px; border-collapse:collapse;">';
      $html .='<tr>'.LF;
      $html .='<td>'.LF;
      $html .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_LIST_SHOWN_ENTRIES').' </span>';
      $html .='</td>'.LF;
      $html .='<td style="text-align:right;">'.LF;
      $html .= '<span class="index_description">'.$this->_getDescriptionAsHTML().'</span>'.LF;
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .= $this->_getRestrictionTextAsHTML();
      $html .= '</table>'.LF;


      $html .= '<div class="listinfoborder"></div>'.LF;

      $html .= '<table id="list_info_table2" style="width:100%; padding:0px; margin:0px; border-collapse:collapse;">';
      $html .='<tr>'.LF;
      $html .='<td>'.LF;
      $connection = $this->_environment->getCurrentModule();
      $text = '';
      $text .= $this->_translator->getMessage('DATES');
      $html .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_ALL_LIST_ENTRIES',$text).':</span> ';
      $html .='</td>'.LF;
      $html .='<td style="text-align:right;">'.LF;
      $html .= $this->_count_all.''.LF;
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .='<tr>'.LF;
      $html .= '<td class="infocolor">';
      $html .= $this->_translator->getMessage('COMMON_PAGE_ENTRIES').':';
      $html .='</td>'.LF;
      $html .='<td style="text-align:right;">'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      if (!isset($params['mode']) or $params['mode'] == 'browse'){
         $params['mode'] = 'list_actions';
      }
      unset($params['select']);
      if ( $this->_interval == 20 ) {
         $html .= '<span style="color:black">20</span>';
      } else {
         $params['interval'] = 20;
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                   $this->_module,
                                   $this->_function,
                                   $params,
                                   '20',
                                   '',
                                   '',
                                   ''
                                  );
      }

      if ( $this->_interval == 50 ) {
         $html .= ' | <span style="color:black">50</span>';
      } else {
         $params['interval'] = 50;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(),
                                   $this->_module,
                                   $this->_function,
                                   $params,
                                   '50',
                                   '',
                                   '',
                                   ''
                                  );
      }

      if ( $this->_interval == 0 ) {
         $html .= ' | <span style="color:black">'.$this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL').'</span>';
      } else {
         $params['interval'] = 0;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(),
                                   $this->_module,
                                   $this->_function,
                                   $params,
                                   $this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL'),
                                   '',
                                   '',
                                   ''
                                  );
      }
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .='</table>'.LF;

      $html .= '<div class="listinfoborder"></div>'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['week']);
      unset($params['year']);
      unset($params['month']);
      unset($params['presentation_mode']);
      $params['seldisplay_mode'] = 'calendar';
      $html .= '<div style="float:right;">'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$params,$this->_translator->getMessage('DATES_CHANGE_CALENDAR')).'</div>'.LF;
      $html .= '<span class="infocolor">'.$this->_translator->getMessage('DATE_ALTERNATIVE_DISPLAY').': </span>'.LF;

      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;

     return $html;
   }



   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML($item, $pos=0, $with_links=TRUE) {
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
            $html .= '                     <tr class="list"><td '.$style.' width="100%" style="font-weight:bold;" colspan="6">'.LF;
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
            $html .= '         <input style="color:blue; font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" type="checkbox" onClick="quark(this)" name="attach['.$key.']" value="1"';
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
                                  CS_DATE_TYPE,
                                  'detail',
                                  $params,
                                  $title,
                                  '','', '', '', '', '', '', '',
                                  CS_DATE_TYPE.$item->getItemID());
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
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemDate($item);
      $time = $this->_getItemTime($item);
      $starting_time = $item->getStartingTime();
      if (!empty($time) and !empty($starting_time)) {
         $html .= ', '.$time;
      }
      $html .='</td>'.LF;
      if (isset($this->_available_color_array[0])){
         $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemPlace($item).'</td>'.LF;
         $color = $item->getColor();
         if (!empty($color)){
           $html .= '<td '.$style.' style="width:1%;"><img src="images/spacer.gif" style="height:10px; width:10px; background-color:' . $this->_text_as_html_short($color) . '; border:1px solid #cccccc;"/>';
#            $html .= '<td '.$style.' style="width:0%;">';
         }else{
            $html .= '<td '.$style.' style="width:0%;">';
         }
         $html .= '</td>';
      }else{
         $html .= '      <td colspan="2"'.$style.' style="font-size:8pt;">'.$this->_getItemPlace($item).'</td>'.LF;
      }
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
      if ($item->issetPrivatDate()){
         $title ='<i>'.$this->_text_as_html_short($title).'</i>';
         $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DATE_TYPE,
                           'detail',
                           $params,
                           $title,
                           '','', '', '', '', '', '', '',
                           CS_DATE_TYPE.$item->getItemID());
         $title .= ' <span class="changed"><span style="color:black"><i>['.$this->_translator->getMessage('DATE_PRIVATE_ENTRY').']</i></span></span>';
      }else{
         $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DATE_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($title),
                           '', '', '', '', '', '', '', '',
                           CS_DATE_TYPE.$item->getItemID());

         unset($params);
         if ( !$this->_environment->inPrivateRoom() and !$item->isNotActivated()) {
            $title .= $this->_getItemChangeStatus($item);
            $title .= $this->_getItemAnnotationChangeStatus($item);
         }
      }
      return $title;
   }

   /** get the place of the item
    * this method returns the item place in the right formatted style
    *
    * @return string title
    */
   function _getItemPlace($item){
      $place = $item->getPlace();
      if ($item->issetPrivatDate()){
         $title ='<i>'.$this->_text_as_html_short($place).'</i>';
      }else{
         $place = $this->_compareWithSearchText($place);
      }
      return $this->_text_as_html_short($place);
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
      if ($item->issetPrivatDate()){
         $time ='<i>'.$this->_text_as_html_short($time).'</i>';
      }else{
         $time = $this->_text_as_html_short($this->_compareWithSearchText($time));
      }
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
      $parse_day_start = convertDateFromInput($item->getStartingDay(),$this->_environment->getSelectedLanguage());
      $conforms = $parse_day_start['conforms'];
      if ($conforms == TRUE) {
         $date = $this->_translator->getDateInLang($parse_day_start['datetime']);
      } else {
         $date = $item->getStartingDay();
      }
      $date = $this->_compareWithSearchText($date);
      if ($item->issetPrivatDate()){
         $date ='<i>'.$this->_text_as_html_short($date).'</i>';
         return $date;
      }else{
         return $this->_text_as_html_short($date);
      }
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