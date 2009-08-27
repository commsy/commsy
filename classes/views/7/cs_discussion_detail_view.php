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

$this->includeClass(DETAIL_VIEW);
include_once('functions/curl_functions.php');

/**
 *  class for CommSy detail view: discussion
 */
class cs_discussion_detail_view extends cs_detail_view {
   var $_show_all = false;


 /** array of ids in clipboard*/
   var $_clipboard_id_array=array();


   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_discussion_detail_view ($params) {
      $this->cs_detail_view($params);
   }

   function _getTitleAsHTML() {
      $item = $this->getItem();
      $html = $this->_text_as_html_short($this->_compareWithSearchText($item->getTitle(),false));
      if ( $item->isClosed() ) {
         $html .= ' <span style="font-size:smaller">('.$this->_translator->getMessage('DISCUSSION_IS_CLOSED').')</span>';
      }
      return $html;
   }


   function setClipboardIDArray($cia) {
      $this->_clipboard_id_array = (array)$cia;
   }

   function _getClipboardIDArray() {
      return $this->_clipboard_id_array;
   }

   function _getDiscussionFormAsHTML(){
        if(!(isset($_GET['mode']) and $_GET['mode'] == 'print')) {
         $html = '<!-- BEGIN OF DISCARTICLE FORM VIEW -->'.LF.LF;
         $item = $this->getItem();
         $discussion_type = $item->getDiscussionType();
         $disabled = '';
         if ( $discussion_type == 'simple') {
            $html .='</div>'.LF;
            $html .='</div>'.LF;
            $html .='<div class="sub_item_main" style="border-top: 1px solid #B0B0B0; margin-left:70px; margin-top:20px; padding-top:5px; background-color:white;">'.LF;
            $html .='<div style="width:100%;" >'.LF;
            $html .= '<a name="form"></a>'.LF;
            $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(),'discarticle', 'edit','').'" method="post" enctype="multipart/form-data" name="f">'.LF;
            $html .= '   <input type="hidden" name="iid" value=""/>'.LF;
            $html .= '   <input type="hidden" name="discussion_id" value="'.$item->getItemID().'"/>'.LF;
            $html .= '<table style="width:100%; border-collapse:collapse; margin-bottom:0px; padding-bottom:0px;" summary="Layout">'.LF;
            $html .= '<tr>'.LF;
            $html .= '<td style="width:1%; padding-top:5px; vertical-align:middle;">'.LF;
            $count = 1;
            $subitems = $this->getSubItemList();
            if ( isset($subitems) and !empty($subitems) ){
               $count = $subitems->getCount();
               $count++;
            }
            $html .= '<h3 class="subitemtitle">'.$count.'.&nbsp;</h3>';
            $html .= '</td>'.LF;
            $html .= '<td style="width:99%; padding-top:5px; padding-bottom:5px; vertical-align:top; text-align:left;">'.LF;
            $html .= '<input name="subject" style="width:98%; font-size:14pt; font-weight:bold; font-family: Arial, Nimbus Sans L, sans-serif;" value="" maxlength="200" tabindex="8" type="text"/>';
            $html .= '</td>'.LF;
            $html .= '<td rowspan="3" style="width:28%; padding-top:5px; vertical-align:top; ">'.LF;
            $html .= '</td>'.LF;
            $html .= '</tr>'.LF;
            $html .= '</table>'.LF;
            $html .= '<div style=" margin:0px;padding:0px;">'.LF;
            $normal = '<textarea style="font-size:10pt; width:98%;" name="description" rows="10" tabindex="8"></textarea>';
            $text = '';
            global $c_html_textarea;
            $current_context = $this->_environment->getCurrentContextItem();
            $with_htmltextarea = $current_context->withHtmlTextArea();
            $html_status = $current_context->getHtmlTextAreaStatus();
            $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
            $current_browser_version = $this->_environment->getCurrentBrowserVersion();
            if ( !isset($c_html_textarea)
                 or !$c_html_textarea
                 or !$with_htmltextarea
               ) {
               $html .= $normal;
               $title = '&nbsp;'.getMessage('COMMON_TEXT_FORMATING_HELP_FULL');
               $html .= '<div style="padding-top:5px;">';
               $text .= '<div class="bold" style="padding-bottom:5px;">'.getMessage('HELP_COMMON_FORMAT_TITLE').':</div>';
               $text .= getMessage('COMMON_TEXT_FORMATING_FORMAT_TEXT');
               $text .= '<div class="bold" style="padding-bottom:5px;">'.getMessage('COMMON_TEXT_INCLUDING_MEDIA').':</div>';
               $text .= getMessage('COMMON_TEXT_INCLUDING_MEDIA_TEXT');
               $html .='<img id="toggle'.$current_context->getItemID().'" src="images/more.gif"/>';
               $html .= $title;
               $html .= '<div id="creator_information'.$current_context->getItemID().'">'.LF;
               $html .= '<div style="padding:2px;">'.LF;
               $html .= '<div id="form_formatting_box" style="width:98%">'.LF;
               $html .= $text;
               $html .= '</div>'.LF;
               $html .= '</div>'.LF;
               $html .= '</div>'.LF;
               $html .= '</div>'.LF;
            } elseif ( ($current_browser != 'msie'
                    and $current_browser != 'firefox'
                    and $current_browser != 'netscape'
                    and $current_browser != 'mozilla'
                    and $current_browser != 'camino'
                    and $current_browser != 'opera'
                    and $current_browser != 'safari')
               ) {
               $html .= $normal;
               $title = '&nbsp;'.getMessage('COMMON_TEXT_FORMATING_HELP_FULL');
               $html .= '<div style="padding-top:5px;">';
               $text .= '<div class="bold" style="padding-bottom:5px;">'.getMessage('HELP_COMMON_FORMAT_TITLE').':</div>';
               $text .= getMessage('COMMON_TEXT_FORMATING_FORMAT_TEXT');
               $text .= '<div class="bold" style="padding-bottom:5px;">'.getMessage('COMMON_TEXT_INCLUDING_MEDIA').':</div>';
               $text .= getMessage('COMMON_TEXT_INCLUDING_MEDIA_TEXT');
               $html .='<img id="toggle'.$current_context->getItemID().'" src="images/more.gif"/>';
               $html .= $title;
               $html .= '<div id="creator_information'.$current_context->getItemID().'">'.LF;
               $html .= '<div style="padding:2px;">'.LF;
               $html .= '<div id="form_formatting_box" style="width:98%">'.LF;
               $html .= $text;
               $html .= '</div>'.LF;
               $html .= '</div>'.LF;
               $html .= '</div>'.LF;
               $html .= '</div>'.LF;
            } else {
               $session = $this->_environment->getSessionItem();
                if ($session->issetValue('javascript')) {
                  $javascript = $session->getValue('javascript');
                  if ($javascript == 1) {
                     include_once('classes/cs_html_textarea.php');
                     $html_area = new cs_html_textarea();
                     $html .= $html_area->getAsHTML( 'description',
                                              '',
                                              20,
                                              $html_status,
                                              '',
                                              '',
                                              false
                                            );
                     $html .= '<input type="hidden" name="description_is_textarea" value="1" />';
                     $title = '&nbsp;'.getMessage('COMMON_TEXT_FORMATING_HELP_SHORT');
                     $html .= '<div style="padding-top:0px;">';
                     $text .= '<div class="bold" style="padding-bottom:5px;">'.getMessage('COMMON_TEXT_INCLUDING_MEDIA').':</div>';
                     $text .= getMessage('COMMON_TEXT_INCLUDING_MEDIA_TEXT');
                     $html .='<img id="toggle'.$current_context->getItemID().'" src="images/more.gif"/>';
                     $html .= $title;
                     $html .= '<div id="creator_information'.$current_context->getItemID().'">'.LF;
                     $html .= '<div style="padding:2px;">'.LF;
                     $html .= '<div id="form_formatting_box" style="width:98%">'.LF;
                     $html .= $text;
                     $html .= '</div>'.LF;
                     $html .= '</div>'.LF;
                     $html .= '</div>'.LF;
                     $html .= '</div>'.BRLF;
                  } else {
                     $html .= $normal;
                     $title = '&nbsp;'.getMessage('COMMON_TEXT_FORMATING_HELP_FULL');
                     $html .= '<div style="padding-top:5px;">';
                     $text .= '<div class="bold" style="padding-bottom:5px;">'.getMessage('HELP_COMMON_FORMAT_TITLE').':</div>';
                     $text .= getMessage('COMMON_TEXT_FORMATING_FORMAT_TEXT');
                     $text .= '<div class="bold" style="padding-bottom:5px;">'.getMessage('COMMON_TEXT_INCLUDING_MEDIA').':</div>';
                     $text .= getMessage('COMMON_TEXT_INCLUDING_MEDIA_TEXT');
                     $html .='<img id="toggle'.$current_context->getItemID().'" src="images/more.gif"/>';
                     $html .= $title;
                     $html .= '<div id="creator_information'.$current_context->getItemID().'">'.LF;
                     $html .= '<div style="padding:2px;">'.LF;
                     $html .= '<div id="form_formatting_box" style="width:98%">'.LF;
                     $html .= $text;
                     $html .= '</div>'.LF;
                     $html .= '</div>'.LF;
                     $html .= '</div>'.LF;
                     $html .= '</div>'.LF;
                  }
               } else {
                  $html .= $normal;
                  $title = '&nbsp;'.getMessage('COMMON_TEXT_FORMATING_HELP_FULL');
                  $html .= '<div style="padding-top:5px;">';
                  $text .= '<div class="bold" style="padding-bottom:5px;">'.getMessage('HELP_COMMON_FORMAT_TITLE').':</div>';
                  $text .= getMessage('COMMON_TEXT_FORMATING_FORMAT_TEXT');
                  $text .= '<div class="bold" style="padding-bottom:5px;">'.getMessage('COMMON_TEXT_INCLUDING_MEDIA').':</div>';
                  $text .= getMessage('COMMON_TEXT_INCLUDING_MEDIA_TEXT');
                  $html .='<img id="toggle'.$current_context->getItemID().'" src="images/more.gif"/>';
                  $html .= $title;
                  $html .= '<div id="creator_information'.$current_context->getItemID().'">'.LF;
                  $html .= '<div style="padding:2px;">'.LF;
                  $html .= '<div id="form_formatting_box" style="width:98%">'.LF;
                  $html .= $text;
                  $html .= '</div>'.LF;
                  $html .= '</div>'.LF;
                  $html .= '</div>'.LF;
                  $html .= '</div>'.LF;
               }
            }
            $html .= '</div>';
            // files
            $html .= '<table style="width:100%; border-collapse:collapse;" summary="Layout">'.LF;
            $html .= '<tr>'.LF;
            $html .= '<td class="key" style="width:10%; padding-top:5px; vertical-align:top; ">'.LF;
            $html .= getMessage('MATERIAL_FILES').':';
            $html .= '</td>'.LF;
            $html .= '<td style="width:90%; padding-top:5px; padding-bottom:5px; vertical-align:top; text-align:left;">'.LF;
            $val = ini_get('upload_max_filesize');
            $val = trim($val);
            $last = $val[mb_strlen($val)-1];
            switch($last) {
               case 'k':
               case 'K':
                  $val = $val * 1024;
                  break;
               case 'm':
               case 'M':
                  $val = $val * 1048576;
                  break;
            }
            $meg_val = round($val/1048576);
            $html .= '   <input type="hidden" name="MAX_FILE_SIZE" value="'.$val.'"/>'.LF;
            if ( !$this->_with_modifying_actions ) {
               $disabled = ' disabled="disabled"';
            }
            $html .= '   <input type="file" name="upload" size="12" tabindex="5"/>&nbsp;<input type="submit" name="option" value="'.$this->_translator->getMessage('MATERIAL_UPLOADFILE_BUTTON').'" tabindex="6" style="width:9.61538461538em; font-size:10pt;"'.$disabled.'/>'.LF;
            $html .= BRLF;
            #$px = '245';
            $px = '331';
            $browser = $this->_environment->getCurrentBrowser();
            if ($browser == 'MSIE') {
               $px = '351';
            } elseif ($browser == 'OPERA') {
               $px = '321';
            } elseif ($browser == 'KONQUEROR') {
               $px = '361';
            } elseif ($browser == 'SAFARI') {
               $px = '380';
            } elseif ($browser == 'FIREFOX') {
               $operation_system = $this->_environment->getCurrentOperatingSystem();
               if (mb_strtoupper($operation_system, 'UTF-8') == 'LINUX') {
                  $px = '360';
               } elseif (mb_strtoupper($operation_system, 'UTF-8') == 'MAC OS') {
                  $px = '352';
               }
            } elseif ($browser == 'MOZILLA') {
               $operation_system = $this->_environment->getCurrentOperatingSystem();
               if (mb_strtoupper($operation_system, 'UTF-8') == 'MAC OS') {
                  $px = '336'; // camino
               }
            }
            $em = $px/13;
            $html .= '<input name="option" value="'.getMessage('MATERIAL_BUTTON_MULTI_UPLOAD_YES').'" tabindex="7" type="submit" style="width: '.$em.'em;"'.$disabled.'/>'.LF;
            $html .= BRLF;
            $html .= '<span class="multiupload_discussion_detail">'.getMessage('MATERIAL_MAX_FILE_SIZE',$meg_val).'</span>'.LF;
            $html .= '</td>'.LF;
            $html .= '</tr>'.LF;

            $html .= '<tr>'.LF;
            $html .= '<td class="key" style="padding-top:10px; vertical-align:top; ">'.LF;
            $html .= '&nbsp;';
            $html .= '</td>'.LF;
            $html .= '<td style="padding-top:10px; vertical-align:top; white-space:nowrap;">'.LF;
            $html .= '<input name="option" value="'.getMessage('DISCARTICLE_CHANGE_BUTTON').'" tabindex="8" type="submit"'.$disabled.'/>';
            $current_user = $this->_environment->getCurrentUser();
            if ( $current_user->isAutoSaveOn() ) {
               $html .= '<span class="formcounter">'.LF;
               global $c_autosave_mode;
               if ( $c_autosave_mode == 1 ) {
                  $currTime = time();
                  global $c_autosave_limit;
                  $sessEnds = $currTime + ($c_autosave_limit * 60);
                  $sessEnds = date("H:i", $sessEnds);
                  $html .= '&nbsp;'.$this->_translator->getMessage('COMMON_SAVE_AT_TIME').' '.$sessEnds.LF;
               } elseif ( $c_autosave_mode == 2 ) {
                  $html .= '&nbsp;'.$this->_translator->getMessage('COMMON_SAVE_AT_TIME').' <input type="text" size="5" name="timerField" value="..." class="formcounterfield" />'.LF;
               }
               $html .= '</span>'.LF;
            }
            $html .= '</td>'.LF;
            $html .= '</tr>'.LF;
            $html .= '</table>'.BRLF;
            $html .= '</form>';

            $html .='<script type="text/javascript">initTextFormatingInformation("'.$current_context->getItemID().'",false)</script>';
            if ( $current_user->isAutoSaveOn() ) {
               $html .= '   <script type="text/javascript">'.LF;
               $html .= '      <!--'.LF;
               $html .= '         var breakCrit = "'.getMessage('DISCARTICLE_CHANGE_BUTTON').'"'.';'.LF;
               $html .= '         startclock();'.LF;
               $html .= '      -->'.LF;
               $html .= '   </script>'.LF;
            }

            $html .= '<!-- END OF DISCARTICLE FORM VIEW -->'.LF.LF;
         }
         return $html;
        }
   }

   function _getButtonAsHTML ($button_text, $button_name, $width = '', $is_disabled = false, $style='', $font_size='10') {
     $html  = '';
      $html .= '<input type="submit" name="'.$button_name.'"';
      $html .= ' value="'.$button_text.'"';
      if ( !empty($width) ){
         $button_width = $width/13;
         $html .= 'style="width:'.$button_width.'em; font-size:'.$font_size.'pt;"';
      }else{
         $html .= 'style="font-size:'.$font_size.'pt;"';
      }
      if ( $is_disabled ){
         $html .= ' disabled="disabled"';
      }
      $html .= '/>';
      return $html;
   }

   /** get the single entry of the list view as HTML
    * this method returns the single entry in HTML-Code
    *
    * @returns string $item as HMTL
    *
    * @param object item     the single list entry
    */
   function _getItemAsHTML($item) {
      $subitems = $this->_subitems;

      $html  = LF.'<!-- BEGIN OF DISCUSSION ITEM DETAIL -->'.LF;

      if ($item->isExportToWiki()) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('MATERIAL_EXPORT_TO_WIKI_LINK');
         $temp_array[] = $item->getExportToWikiLink();
         $formal_data1[] = $temp_array;
      }
      if ( !empty($formal_data1) ) {
         $html .= $this->_getFormalDataAsHTML($formal_data1);
      }

      // Index
      if ( isset($subitems) ) {
         $id_array = array();
         $temp_item = $subitems->getFirst();
         while ($temp_item){
            $id_array[] = $temp_item->getItemID();
            $temp_item = $subitems->getNext();
         }
         $noticed_manager = $this->_environment->getNoticedManager();
         $noticed_manager->getLatestNoticedByIDArray($id_array);
         $rest_subitems = clone($subitems);
         $html .= '<table id="discussionSummary" style="width:100%; padding:0px; margin:0px; border-collapse:collapse;" summary="Layout">'.LF;
         $article = $subitems->getFirst();
         if(!empty($article)){
            $article_old = clone($article);
         }

         $pos_number = 1;
         $picture_array = array();
         $picture_array[] = '';
         while ( $article ) {
            $rest_subitems->removeElement($article);
            $position_length =  count(explode('.',$article->getPosition()));

            // Initialisierung
            $picture_array_new = array();
            for ($j = 1; $j < $position_length; $j++ ){
               if (!isset($picture_array[$j])){
                  $picture_array_new[$j] =  '<img src="images/disc12.gif" style="margin:0px; padding:0px; height:1.2em;" alt="threaded-picture"/>';
               }else{
                  $picture_array_new[$j] = $picture_array[$j];
               }
            }
            $picture_array = $picture_array_new;
            $pic_pos = $position_length-1;
            $picture_array[$pic_pos] = '<img src="images/disc13.gif" style="margin:0px; padding:0px; height:1.2em;" alt="threaded-picture"/>';


            //aktuelles Element
            $next_article = $rest_subitems->getFirst();
            $smaller_in_array = false;
            while ($next_article and !$smaller_in_array){
               $next_position_length =  count(explode('.',$next_article->getPosition()));
               if ($next_position_length < $position_length){
                  $smaller_in_array = true;
               }
               if ($next_position_length == $position_length and !$smaller_in_array){
                  $picture_array[$pic_pos] = '<img src="images/disc11.gif" style="margin:0px; padding:0px; height:1.2em;" alt="threaded-picture"/>';
               }
               $next_article = $rest_subitems->getNext();
            }

            //Element davor
            if (isset($article_old)){
               $old_position_length =  count(explode('.',$article_old->getPosition()));
               if ($old_position_length < $position_length){
                  $pic_pos = $old_position_length-1;
                  if (isset($picture_array[$pic_pos]) and $picture_array[$pic_pos] == '<img src="images/disc13.gif" style="margin:0px; padding:0px; height:1.2em;" alt="threaded-picture"/>'){
                     $picture_array[$pic_pos] = '<img src="images/disc12.gif" style="margin:0px; padding:0px; height:1.2em;" alt="threaded-picture"/>';
                  }else{
                     $picture_array[$pic_pos] = '<img src="images/disc10.gif" style="margin:0px; padding:0px; height:1.2em;" alt="threaded-picture"/>';
                  }
               }
            }

            // files
            $fileicons = $this->_getItemFiles($article,true);
            if ( !empty($fileicons) ) {
               $fileicons = '&nbsp;'.$fileicons.'&nbsp;';
            }

            $creator = $article->getCreatorItem();
            if ( isset($creator) ) {
               $current_user_item = $this->_environment->getCurrentUserItem();
               if ( $current_user_item->isGuest()
                    and $creator->isVisibleForLoggedIn()
                  ) {
                  $creator_fullname = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
               } else {
                  $creator_fullname = $creator->getFullName();
               }
               unset($current_user_item);
            } else {
               $creator_fullname = '';
            }
            $html .= '<tr style="padding:0px; margin:0px;">'.LF;

            // discussion type
            $discussion_type = $item->getDiscussionType();
            if ($discussion_type == 'threaded'){
               $position_length =  count(explode('.',$article->getPosition()));
               $display_subject = $article->getSubject();
               $length = mb_strlen($display_subject);
               $max = 28 - $position_length;
               $new = $this->_getItemChangeStatus($article);
               if ( !empty($new) ) {
                  if ( mb_stristr($new,$this->_translator->getMessage('COMMON_NEW')) ) {
                     $max = $max-mb_strlen($this->_translator->getMessage('COMMON_NEW'));
                  } elseif ( mb_stristr($new,$this->_translator->getMessage('COMMON_CHANGED')) ) {
                     $max = $max-mb_strlen($this->_translator->getMessage('COMMON_CHANGED'));
                  }
               }
               if ($length > $max){
                  $display_subject = mb_substr($display_subject,0,$max).'...';
               }
               $hover = str_replace('"','&quot;',$this->_text_as_html_short($article->getSubject()));
               $em = $position_length-2;
               $old_postion_length = count(explode('.',$article_old->getPosition()));
               if ($pos_number != 1){
                  $html .= '   <td style="padding:0px; margin:0px; vertical-align:top;"><div style="float:left;">';
                  $pictures = $position_length;
                  for ($i = 1; $i < $pictures; $i++){
                     $html .=  $picture_array[$i];
                  }
                  $html .='</div><div>';
                  $params = array();
                  $params['iid'] = $item->getItemID();
                  $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DISCUSSION_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($this->_compareWithSearchText($display_subject)),
                           $hover,
                           '',
                           'anchor'.$article->getItemID());
                  $html .= $this->_getItemChangeStatus($article).' ';
                  $html .= $title.$fileicons;
#                  $html .= $this->_getItemChangeStatus($article);
                  $html .='</div></td><td>';
               }
               else{
                  $html .= '   <td colspan="2" style="vertical-align:top; padding:0px; margin:0px; padding-left:'.$em.'em; width: 55%;">';
                  $params = array();
                  $params['iid'] = $item->getItemID();
                  $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DISCUSSION_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($this->_compareWithSearchText($display_subject)),
                           $hover,
                           '',
                           'anchor'.$article->getItemID());
                  $html .= $this->_getItemChangeStatus($article).' ';
                  $html .= $title.$fileicons;
               }
               $html .= '   <td style="white-space:nowrap; width: 30%;">'.$this->_text_as_html_short($this->_compareWithSearchText($creator_fullname)).'&nbsp; </td>'.LF;
               $html .= '   <td style="white-space:nowrap; width: 25%;">'.$this->_text_as_html_short($this->_compareWithSearchText(getDateTimeInLang($article->getModificationDate(),false))).'</td>'.LF;
            }

            // lineare diskussion
            else {
               if ( empty($fileicons) ) {
                  $fileicons = '&nbsp;';
               }
               $display_subject = $article->getSubject();
               $length = mb_strlen($display_subject);
               $max = 28;
               if ($length > $max){
                  $display_subject = mb_substr($display_subject,0,$max).'...';
               }
               $hover = str_replace('"','\'',$this->_text_as_html_short($article->getSubject()));
               $html .= '   <td style="width: 2%; vertical-align:bottom">'.$pos_number.'. '.'</td>'.LF;
               $html .= '   <td style="width: 46%;">';
               $params = array();
               $params['iid'] = $item->getItemID();
               $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DISCUSSION_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($this->_compareWithSearchText($display_subject)),
                           $hover,
                           '',
                           'anchor'.$article->getItemID());

               $html .= $this->_getItemChangeStatus($article).' ';
               $html .= $fileicons.'</td>'.LF;
               $html .= '   <td style="vertical-align:bottom; white-space:nowrap; width: 30%;">'.$this->_text_as_html_short($this->_compareWithSearchText($creator_fullname)).'&nbsp; </td>'.LF;
               $html .= '   <td style="vertical-align:bottom; white-space:nowrap; width: 22%;">'.$this->_text_as_html_short($this->_compareWithSearchText(getDateTimeInLang($article->getModificationDate(), false))).'</td>'.LF;
            }
            $html .= '</tr>'.LF;
            $article_old = clone($article);
            $article = $subitems->getNext();
            $pos_number++;
         }
         $html .= '</table>'.LF;
      }

      $html  .= '<!-- END OF DISCUSSION ITEM DETAIL -->'.LF.LF;
      return $html;
   }


   function _getDetailItemActionsAsHTML($item){
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';

      // edit
      $html .= $this->_getEditAction($item,$current_user);

      $discussion_type = $item->getDiscussionType();
      if ( $current_user->isUser() and $this->_with_modifying_actions and $discussion_type == 'simple') {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new_section.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DISCARTICLE_ENTER_NEW').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new_section.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DISCARTICLE_ENTER_NEW').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('DISCARTICLE_ENTER_NEW').'" href="#form">'.$image.'</a>'.LF;
      } elseif ($discussion_type == 'simple') {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new_section_grey.gif" style="vertical-align:bottom;" alt="'.getMessage('DISCARTICLE_ENTER_NEW').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new_section_grey.png" style="vertical-align:bottom;" alt="'.getMessage('DISCARTICLE_ENTER_NEW').'"/>';
         }
         $html .= '<span class="disabled">'.$image.'</span>'.LF;
      }

      // delete
      $html .= $this->_getDeleteAction($item,$current_user);
      return $html.'&nbsp;&nbsp;&nbsp;';
   }

   function _getAdditionalActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';

      // wiki
      $html .= $this->_getWikiAction($item,$current_user,$current_context);

      return $html;
   }


   function _getSubItemDetailActionsAsHTML ($subitem) {
      $user = $this->_environment->getCurrentUserItem();
      $item = $this->getItem();
      $html = '';

      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= $this->_getEditAction($subitem,$current_user,'discarticle');
      $discussion_type = $item->getDiscussionType();
      if ( $user->isUser() and $this->_with_modifying_actions and $discussion_type == 'threaded') {
         $params = array();
         $params['iid'] = 'NEW';
         $params['discussion_id'] = $item->getItemID();
         $params['ref_position'] = 1;
         $ref_position = $subitem->getPosition();
         if(!empty($ref_position)){
            $params['ref_position'] = $subitem->getPosition();
         }
         $params['ref_did'] = $subitem->getItemID();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new_section.gif" style="vertical-align:bottom;" alt="'.getMessage('DISCARTICLE_ANSWER_NEW').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new_section.png" style="vertical-align:bottom;" alt="'.getMessage('DISCARTICLE_ANSWER_NEW').'"/>';
         }
         $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                    'discarticle',
                                    'edit',
                                    $params,
                                    $image,
                                    $this->_translator->getMessage('DISCARTICLE_ANSWER_NEW')).LF;
         unset($params);
      } elseif ($discussion_type == 'threaded') {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new_section_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DISCARTICLE_ANSWER_NEW').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new_section_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DISCARTICLE_ANSWER_NEW').'"/>';
         }
         $html .= $this->_translator->getMessage('DISCARTICLE_ANSWER_NEW').LF;
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('DISCARTICLE_ANSWER_NEW')).' "class="disabled">'.$image.'</a>'.LF;
      }
      if ( $subitem->mayEdit($user) and $this->_with_modifying_actions  ) {
        $params = $this->_environment->getCurrentParameterArray();
        $params['action'] = 'delete';
        $params['discarticle_iid'] = $subitem->getItemID();
        $params['iid'] = $item->getItemID();
        $params['discarticle_action'] = 'delete';
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
                                       'anchor'.$subitem->getItemID()).LF;
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
      $subitems = $this->getSubItemList();
      if ( isset($subitems) and !empty($subitems) ){
         $count=$subitems->getCount();
      }
      if ( isset($subitems) and !$subitems->isEmpty() ) {
         $current_item = $subitems->getFirst();
         $pos_number = 1;
         while ( $current_item ) {
            $discussion_type = $item->getDiscussionType();
            $html .='<tr class="detail_discussion_entries">'.LF;
            if ($discussion_type == 'threaded'){
               $html .='<td style="width:100%; padding-top:0px;  vertical-align:bottom;">'.LF;
               $html .='<table style="width:100%;" summary="Layout">'.LF;
               $html .='<tr>'.LF;
               $position_length =  count(explode('.',$current_item->getPosition()));
               if ( $position_length < 6 ) {
                  $px = ($position_length-1)*20;
               } elseif ( $position_length < 11 ) {
                  $px = 5*20;
                  $px += ($position_length-6)*15;
               } elseif ( $position_length < 14 ) {
                  $px = 5*20;
                  $px += 5*15;
                  $px += ($position_length-11)*10;
               } else {
                  $px = 5*20;
                  $px += 5*15;
                  $px += 3*10;
               }
               if ($px > 0) {
                  $html .='<td style="width:'.$px.'px;">&nbsp;'.LF;
                  $html .='</td>'.LF;
                  $html .='<td>'.LF;
               } else {
                  $html .='<td>'.LF;
               }
               $image = $this->_getItemPicture($current_item->getModificatorItem());
               $html .='<table>'.LF;
               $html .='<tr>'.LF;
               $html .= '<td rowspan="3" style="width:60px; vertical-align:top; padding:20px 5px 5px 5px;">'.$image.'</td>'.LF;
               $html .='<td style="width:70%; padding-top:5px; vertical-align:bottom;">'.LF;
               $html .='<div style="padding-top:10px;">'.LF;
               $html .= '<a id="anchor'.$pos_number.'" name="anchor'.$pos_number.'"></a>'.LF;
               $html .= '<a id="anchor'.$current_item->getItemID().'" name="anchor'.$current_item->getItemID().'"></a>'.LF;
               $pos = $current_item->getPosition();
               $number_array = explode('.',$pos);
               $number = '';
               foreach($number_array as $num){
                  if ( empty($number) ){
                     $number = '1';
                  }else{
                     $len = mb_strlen($num);
                     $tmp_num = mb_substr($num,1,$len);
                     $first = mb_substr($tmp_num,0,1);
                     while($first == '0'){
                        $tmp_num = mb_substr($tmp_num,1,mb_strlen($tmp_num));
                        $first = mb_substr($tmp_num,0,1);
                     }
                     $number .= '.'.$tmp_num;
                  }
               }
               $number = substr($number,2);
               if ( $position_length > 10 and !empty($number) ) {
                  $range = floor($position_length/3.5)-1;
                  $number_array = explode('.',$number);
                  $middle = count($number_array)/2;
                  if ( $middle % 2 ) {
                     $middle -= 0.5;
                  }
                  $number = '';
                  $print = false;
                  foreach ($number_array as $key => $value) {
                     if ( $key < $middle-$range or $key > $middle+$range ) {
                        $number .= $value.'.';
                     } elseif ( !$print ) {
                        $number .= '...';
                        $print = true;
                     }
                  }
                  $number = substr($number,0,strlen($number)-1);
               }
               $html .= '<h3 class="subitemtitle">'.$this->_getSubItemTitleAsHTML($current_item, $number);
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
                  $html .='<td class="discarticleCreatorInformation" style="padding-top:5px; padding-bottom:30px; vertical-align:top; ">'.LF;
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

                  $html .='</table>'.LF;
                  $html .='</td>'.LF;
                  $html .='</tr>'.LF;
                  $html .='</table>'.LF;
                  $html .='</td>'.LF;
                  $html .='</tr>'.LF;
               }
            }else{
               $image = $this->_getItemPicture($current_item->getModificatorItem());
               $html .= '<td rowspan="3" style="width:60px; vertical-align:top; padding:20px 5px 5px 5px;">'.$image.'</td>'.LF;
               $html .='<td style="width:70%; padding-top:5px; vertical-align:bottom;">'.LF;
               if ( $current_item->isA(CS_DISCARTICLE_TYPE) ) {
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


   function _getSubItemTitleAsHTML ($item, $pos_number) {
      $retour = '';
      if ( !empty($pos_number) ) {
         $retour .= $pos_number.'. ';
      }
      $retour .= $this->_environment->getTextConverter()->parseText2ID($this->_text_as_html_short($this->_compareWithSearchText($item->getSubject())));
      return $retour;
   }

   function _getSubItemTitleWithOutNumberAsHTML ($item) {
      return $this->_text_as_html_short($this->_compareWithSearchText($item->getSubject()));
   }

   function _getSubItemAsHTML ($item, $anchor_number) {
      $retour  = '';
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
      return $retour;
   }

   function setShowAllArticles ($show) {
      $this->_show_all = $show;
   }

   function showAllArticles () {
      return $this->_show_all;
   }


   function _getBrowsingIconsAsHTML($current_item, $pos_number, $count){
#      $html ='<a id="anchor'.$pos_number.'" name="anchor'.$pos_number.'"></a>';
      $html ='';
      $i =0;
      if ( $pos_number == 1 ) {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/16x16/browse_left2.gif" alt="&lt;" style="vertical-align:bottom;"/>';
         } else {
            $image = '<img src="images/commsyicons/16x16/browse_left2.png" alt="&lt;" style="vertical-align:bottom;"/>';
         }
         $html .= '<a href="#top">'.$image.'</a>'.LF;
      }elseif ( $pos_number > 1 ) {
         $i = $pos_number-1;
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/16x16/browse_left2.gif" alt="&lt;" style="vertical-align:bottom;"/>';
         } else {
            $image = '<img src="images/commsyicons/16x16/browse_left2.png" alt="&lt;" style="vertical-align:bottom;"/>';
         }
         $html .= '<a href="#anchor'.$i.'">'.$image.'</a>'.LF;
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $html .= '         <span class="disabled"><img src="images/commsyicons_msie6/16x16/browse_left_grey2.gif" alt="&lt;" style="vertical-align:bottom;"/></span>'.LF;
         } else {
            $html .= '         <span class="disabled"><img src="images/commsyicons/16x16/browse_left_grey2.png" alt="&lt;" style="vertical-align:bottom;"/></span>'.LF;
         }
      }
      $html .= '';
      if ( $pos_number < $count) {
         $i = $pos_number+1;
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/16x16/browse_right2.gif" alt="&gt;" style="vertical-align:bottom;"/>';
         } else {
            $image = '<img src="images/commsyicons/16x16/browse_right2.png" alt="&gt;" style="vertical-align:bottom;"/>';
         }
         $html .= '<a href="#anchor'.$i.'">'.$image.'</a>'.LF;
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $html .= '         <span class="disabled"><img src="images/commsyicons_msie6/16x16/browse_right_grey2.gif" alt="&gt;" style="vertical-align:bottom;"/></span>'.LF;
         } else {
            $html .= '         <span class="disabled"><img src="images/commsyicons/16x16/browse_right_grey2.png" alt="&gt;" style="vertical-align:bottom;"/></span>'.LF;
         }
      }
      return $html;
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
               if ( mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'png')
                 or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpg')
                 or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpeg')
                 or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'gif')
                  ) {
                   $this->_with_slimbox = true;
                   $file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.$this->_text_as_html_short($this->_compareWithSearchText($displayname)).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
               }else{
                  $file_list.='<a href="'.$url.'" title="'.$this->_text_as_html_short($this->_compareWithSearchText($displayname)).' ('.$filesize.' kb)" target="blank" >'.$fileicon.'</a> ';
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