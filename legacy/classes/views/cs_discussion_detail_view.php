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
   function __construct($params) {
      cs_detail_view::__construct($params);
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

   function _getDiscussionFormAsHTMLForThreadedView() {
      if(!(isset($_GET['mode']) and $_GET['mode'] == 'print')) {
         $html = '<!-- BEGIN OF DISCARTICLE FORM VIEW -->'.LF.LF;
         $html .= '<div style="border-top: 1px dotted; border-bottom: 1px dotted;">'.LF;
         $item = $this->getItem();
         $discussion_type = $item->getDiscussionType();
         $disabled = '';

         if(!(isset($_GET['discarticle_action']) && $_GET['discarticle_action'] == 'edit')) {
            ////////////////////////////////////////////////////////
            // calculate index
            // find largest child position
            $father_position = $_GET['ref_position'];
            $father_position_length = mb_strlen($father_position);

            $max = 1000;
            $subitems_list = clone($this->getSubItemList());
            $subitem = $subitems_list->getFirst();
            while($subitem) {
               $subitem_position = $subitem->getPosition();
               $subitem_position_length = mb_strlen($subitem_position);

               if(   $subitem_position_length > $father_position_length &&
               mb_substr($subitem_position, 0, $father_position_length) == $father_position) {
                  $postfix = mb_substr($subitem_position, $father_position_length+1);

                  if(((int) $postfix) > $max) {
                     $max = (int) $postfix;
                  }
               }

               $subitem = $subitems_list->getNext();
            }
            $pos = $father_position . "." . ++$max;

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
            $position_length =  count(explode('.',$pos));
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

            ////////////////////////////////////////////////////////

            $class_factory = $this->_environment->getClassFactory();
            $class_params = array();
            $class_params['environment'] = $this->_environment;
            $form = $class_factory->getClass(DISCARTICLE_FORM,$class_params);

            $session_item = $this->_environment->getSessionItem();
            if($session_item->issetValue('back_to_discussion_detail_view_postvars')) {
               // load from postvars
               $session_post_vars = $session_item->getValue('back_to_discussion_detail_view_postvars');
               $form->setFormPost($session_post_vars);
               $session_item->unsetValue('back_to_discussion_detail_view_postvars');
            } else {
               // load from database
               //$form->setItem($subitem);
            }


            $clear_add_files = false;
            if($session_item->issetValue('back_to_discussion_detail_view_last_upload')) {
               if($session_item->getValue('back_to_discussion_detail_view_last_upload') != "new" . $_GET['answer_to']) {
                  $clear_add_files = true;
               }
            }

            if($clear_add_files) {
               $session_item->unsetValue('discarticle_add_files');
            }

            if($session_item->issetValue('discarticle_add_files')) {
               $form->setSessionFileArray($session_item->getValue('discarticle_add_files'));
            }
            unset($session_item);

            $form->setDetailMode($number);
            $form->setRefPosition($father_position);
            $form->setDiscussionID($item->getItemID());
            unset($class_params);
            $form->prepareForm();
            $form->loadValues();
            $class_params = array();
            $class_params['environment'] = $this->_environment;
            $class_params['with_modifying_actions'] = true;
            $form_view = $class_factory->getClass(FORM_DETAIL_VIEW,$class_params);
            unset($class_params);
            $params = array();
            $params['back_to_discussion_detail_view'] = 'new';
            $params['answer_to'] = $_GET['answer_to'];
            $form_view->setAction(curl($this->_environment->getCurrentContextID(),'discarticle','edit',$params));
            unset($params);
            $form_view->setForm($form);

            $html .= $form_view->asHTML();
         } else if(isset($_GET['discarticle_iid'])) {
            $discarticle_iid = $_GET['discarticle_iid'];

            $subitems_list = clone($this->getSubItemList());
            $subitem = $subitems_list->getFirst();
            while($subitem) {
               if($subitem->getItemID() == $discarticle_iid) {
                  break;
               }

               $subitem = $subitems_list->getNext();
            }
            $pos = $subitem->getPosition();

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
            $position_length =  count(explode('.',$pos));
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

            //////////////////////////////////////////////////////////////

            $class_factory = $this->_environment->getClassFactory();
            $class_params = array();
            $class_params['environment'] = $this->_environment;
            $form = $class_factory->getClass(DISCARTICLE_FORM,$class_params);

            $session_item = $this->_environment->getSessionItem();
            if($session_item->issetValue('back_to_discussion_detail_view_postvars')) {
               // load from postvars
               $session_post_vars = $session_item->getValue('back_to_discussion_detail_view_postvars');
               $form->setFormPost($session_post_vars);
               $session_item->unsetValue('back_to_discussion_detail_view_postvars');
            } else {
               // load from database
               $form->setItem($subitem);
            }

            $clear_add_files = false;
            if($session_item->issetValue('back_to_discussion_detail_view_last_upload')) {
               if($session_item->getValue('back_to_discussion_detail_view_last_upload') != "edit" . $_GET['discarticle_iid']) {
                  $clear_add_files = true;
               }
            }

            if($clear_add_files) {
               $session_item->unsetValue('discarticle_add_files');
            }

            if($session_item->issetValue('discarticle_add_files')) {
               $form->setSessionFileArray($session_item->getValue('discarticle_add_files'));
            }
            unset($session_item);

            $form->setDetailMode($number);
            $form->setDiscussionID($item->getItemID());
            unset($class_params);
            $form->prepareForm();
            $form->loadValues();
            $class_params = array();
            $class_params['environment'] = $this->_environment;
            $class_params['with_modifying_actions'] = true;
            $form_view = $class_factory->getClass(FORM_DETAIL_VIEW,$class_params);
            unset($class_params);
            $params = array();
            $params['back_to_discussion_detail_view'] = 'edit';
            $form_view->setAction(curl($this->_environment->getCurrentContextID(),'discarticle','edit',$params));
            unset($params);
            $form_view->setForm($form);

            $html .= $form_view->asHTML();
         }

         $html .= '</div>';
         $html .= '<!-- END OF DISCARTICLE FORM VIEW -->'.LF.LF;

         return $html;
      }
   }

   function _getDiscussionFormAsHTML(){
      if(!(isset($_GET['mode']) and $_GET['mode'] == 'print')) {
         $html = '<!-- BEGIN OF DISCARTICLE FORM VIEW -->'.LF.LF;
         $item = $this->getItem();
         $discussion_type = $item->getDiscussionType();
         $disabled = '';
         if ( $discussion_type == 'simple') {

            $count = 1;
            $subitems = $this->getSubItemList();
            if ( isset($subitems) and !empty($subitems) ){
               $count = $subitems->getCount();
               $count++;
            }

            $html .= '</div>'.LF;
            $html .= '</div>'.LF;
            $html .= '<div class="sub_item_main" style="border-top: 1px solid #B0B0B0; margin-top:20px; padding-top:5px; background-color:white;">'.LF;
            $html .= '<div style="width:100%;" >'.LF;
            $html .= '<a name="form"></a>'.LF;

            $class_factory = $this->_environment->getClassFactory();
            $class_params = array();
            $class_params['environment'] = $this->_environment;
            $form = $class_factory->getClass(DISCARTICLE_FORM,$class_params);
            $form->setDetailMode($count);
            $form->setDiscussionID($item->getItemID());
            unset($class_params);
            $form->prepareForm();
            $form->loadValues();
            $class_params = array();
            $class_params['environment'] = $this->_environment;
            $class_params['with_modifying_actions'] = true;
            $form_view = $class_factory->getClass(FORM_DETAIL_VIEW,$class_params);
            unset($class_params);
            $form_view->setAction(curl($this->_environment->getCurrentContextID(),'discarticle','edit',array()));
            $form_view->setForm($form);
            $html .= $form_view->asHTML();

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

   function _getItemAsHTMLThreadedWithJavaScript($item) {
      $html = '<div id="discussion_tree_progressbar_wrap">' . LF;
      $html .= '<div style="float: left; width: 180px;">' . $this->_translator->getMessage("DISCUSSION_THREADED_LOADING") . '</div>' . LF;
      $html .= '<div style="float: right; width: 50px; text-align: center;">' . LF;
      $html .= '<span id="discussion_tree_progressbar_percent"></span>' . LF;
      $html .= '%</div>' . LF;
      $html .= '<div id="discussion_tree_progressbar" style="margin-left: 180px; margin-right: 50px;"></div>' . LF;
      $html .= '</div>' . LF;
	  
	  // show all / hide all link
	  $html .= '<span id="discussion_show_hide_all"></span>';
	  
      $html .= '<div id="discussion_tree" style="position: relative; display:none;">'.LF;

      // build list of articles
      $last_position = 0;
      $subitems = $this->_subitems;
      $article = $subitems->getFirst();

      $font_weight = 'normal';

      while($article) {
         // files
         $fileicons = $this->_getItemFiles($article,true);
         if(empty($fileicons)) {
            $fileicons = '&nbsp;';
         } else {
            $fileicons = '&nbsp;'.$fileicons.'&nbsp;';
         }

         // creator
         $creator = $article->getCreatorItem();
         if(isset($creator)) {
            $current_user_item = $this->_environment->getCurrentUserItem();
            if($current_user_item->isGuest() && $creator->isVisibleForLoggedIn()) {
               $creator_fullname = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
            } else {
               $creator_fullname = $creator->getFullName();
            }
            unset($current_user_item);
         } else {
            $creator_fullname = '';
         }

         // Because articles are deep ordered, building the tree is getting easier
         $position = count(explode('.', $article->getPosition()));
         $display_subject = $article->getSubject();

         // limit display text
         $length = mb_strlen($display_subject);
         $max = 28 - $position;
         $new = $this->_getItemChangeStatus($article);
         if(!empty($new)) {
            if(mb_stristr($new,$this->_translator->getMessage('COMMON_NEW'))) {
               $max -= mb_strlen($this->_translator->getMessage('COMMON_NEW'));
            } else if(mb_stristr($new,$this->_translator->getMessage('COMMON_CHANGED'))) {
               $max -= mb_strlen($this->_translator->getMessage('COMMON_CHANGED'));
            }
         }

         // full length for tooltip
         $display_subject_fulllength = $display_subject;

         // limit display length of subject
         if ($length > $max){
            $display_subject = mb_substr($display_subject,0,$max).'...';
         }

         // limit display length of creator
         if(mb_strlen($creator_fullname) > 28) {
            $creator_fullname = mb_substr($creator_fullname, 0, 28) . '...';
         }

         // open sublist if position > last position
         if($position > $last_position) {
            $html .= '<ul style="padding: 0px 0px 0px 10px; margin: 0px 0px 0px 10px;">'.LF;
         }

         // close sublist if position < last position
         while($position < $last_position) {
            $html .= '</ul>'.LF;
            $last_position--;
         }

         // add list item
         $params = array();
         $params['iid'] = $item->getItemID();
         $param_zip = $this->_environment->getValueOfParameter('download');
         $display = $this->_text_as_html_short($this->_compareWithSearchText($display_subject));
         if(empty($param_zip) || $param_zip != 'zip') {
            $link = curl(   $this->_environment->getCurrentContextID(),
                            CS_DISCUSSION_TYPE,
                            'detail',
                            $params,
                            'anchor' . $article->getItemID());
            $html .= '<li id="' . $article->getItemID() . '" data="url: \'' . $link . '\'">';
            $html .= ahref_curl(   $this->_environment->getCurrentContextID(),
                                   CS_DISCUSSION_TYPE,
                                   'detail',
                                   $params,
                                   $display,
                                   $display_subject_fulllength,'',
                                   'anchor' . $article->getItemID(),'','','',
                                   'style="color:#545454; font-size:10pt; font-weight:' . $font_weight . ';"').LF;
         } else {
            $html .= '<li>' . $this->_text_as_html_short($this->_compareWithSearchText($display_subject));
         }

         if(!empty($new)) {
	         //$html .= '<a id="discussion_tree_' . $article->getItemID() . '_change_status_text" style="color:#545454; font-size:10pt; font-weight:' . $font_weight . ';">';
	         $html .= '<a style="color:#545454; font-size:10pt; font-weight:' . $font_weight . ';text-decoration:none;">';
             $html .= $new;
	         $html .= '</a>';
         }
         $html .= $fileicons.LF;
         //$html .= '<img id="discussion_tree_' . $article->getItemID() . '_creator_space" src="images/spacer.gif">';
         //$html .= '<a class="discussion_detail_view_threaded_tree_creator" id="discussion_tree_' . $article->getItemID() . '_creator_text" style="color:#545454; font-size:10pt; font-weight:' . $font_weight . ';text-decoration:none">';
         $html .= '<a class="discussion_detail_view_threaded_tree_creator" style="color:#545454; font-size:10pt; font-weight:' . $font_weight . '; text-decoration:none;">';
         $html .= $this->_text_as_html_short($this->_compareWithSearchText($creator_fullname))/*.'&nbsp;'*/.LF;
         $html .= '</a>';

         //$html .= '<img id="discussion_tree_' . $article->getItemID() . '_date_space" src="images/spacer.gif">';
         $html .= '<a class="discussion_detail_view_threaded_tree_date" style="color:#545454; font-size:10pt; font-weight:' . $font_weight . '; text-decoration:none;">';
         $html .= $this->_text_as_html_short($this->_compareWithSearchText(getDateTimeInLang($article->getModificationDate(), false))).LF;
         $html .= '</a>';

         // update last position
         $last_position = $position;

         $article = $subitems->getNext();
      }

      // close remaining lists
      while($position > 0) {
         $html .= '</ul>'.LF;
         $position--;
      }

      $html .= '</div>'.LF;
      return $html;
   }

   function _getItemAsHTMLThreadedWithoutJavascript($item) {
      $subitems = $this->_subitems;
      $rest_subitems = clone($subitems);
      $article = $subitems->getFirst();
      if(!empty($article)){
         $article_old = clone($article);
      }

      $pos_number = 1;
      $picture_array = array();
      $picture_array[] = '';
      $html = '';
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

         $position_length =  count(explode('.',$article->getPosition()));
         $display_subject = $article->getSubject();

         // limit display text
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

         // limit display length of subject
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
            $param_zip = $this->_environment->getValueOfParameter('download');
            if ( empty($param_zip)
            or $param_zip != 'zip'
            ) {
               $title = ahref_curl( $this->_environment->getCurrentContextID(),
               CS_DISCUSSION_TYPE,
	                             'detail',
               $params,
               $this->_text_as_html_short($this->_compareWithSearchText($display_subject)),
               $hover,
	                             '',
	                             'anchor'.$article->getItemID());
            } else {
               $title = $this->_text_as_html_short($this->_compareWithSearchText($display_subject));
            }
            $html .= $new . ' ';
            $html .= $title.$fileicons;
            #                 $html .= $this->_getItemChangeStatus($article);
            $html .='</div></td><td>';

            $html .= '   <td style="white-space:nowrap; width: 30%;">'.$this->_text_as_html_short($this->_compareWithSearchText($creator_fullname)).'&nbsp; </td>'.LF;
            $html .= '   <td style="white-space:nowrap; width: 25%;">'.$this->_text_as_html_short($this->_compareWithSearchText(getDateTimeInLang($article->getModificationDate(),false))).'</td>'.LF;
         }
         $html .= '</tr>'.LF;
         $article_old = clone($article);
         $article = $subitems->getNext();
         $pos_number++;
      }
      return $html;
   }

   function _getItemAsHTMLLinear($item) {
      $subitems = $this->_subitems;
      $article = $subitems->getFirst();
      $pos_number = 1;
      $html = '';
      while($article) {
         $html .= '<tr style="padding:0px; margin:0px;">'.LF;

         // files
         $fileicons = $this->_getItemFiles($article,true);
         if(empty($fileicons)) {
            $fileicons = '&nbsp;';
         } else {
            $fileicons = '&nbsp;'.$fileicons.'&nbsp;';
         }

         // creator
         $creator = $article->getCreatorItem();
         if(isset($creator)) {
            $current_user_item = $this->_environment->getCurrentUserItem();
            if($current_user_item->isGuest() && $creator->isVisibleForLoggedIn()) {
               $creator_fullname = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
            } else {
               $creator_fullname = $creator->getFullName();
            }
            unset($current_user_item);
         } else {
            $creator_fullname = '';
         }

         $display_subject = $article->getSubject();
         $length = mb_strlen($display_subject);
         $max = 28;
         if($length > $max) {
            $display_subject = mb_substr($display_subject,0,$max).'...';
         }
         $hover = str_replace('"','\'',$this->_text_as_html_short($article->getSubject()));
         $html .= '   <td style="width: 2%; vertical-align:bottom">'.$pos_number.'. '.'</td>'.LF;
         $html .= '   <td style="width: 46%;">';
         $params = array();
         $params['iid'] = $item->getItemID();
         $param_zip = $this->_environment->getValueOfParameter('download');
         if(empty($param_zip) || $param_zip != 'zip') {
            $html .= ahref_curl(   $this->_environment->getCurrentContextID(),
            CS_DISCUSSION_TYPE,
	                              		 'detail',
            $params,
            $this->_text_as_html_short($this->_compareWithSearchText($display_subject)),
            $hover,
	                              		 '',
	                              		 'anchor'.$article->getItemID());
         } else {
            $html .= $this->_text_as_html_short($this->_compareWithSearchText($display_subject));
         }
         $html .= $this->_getItemChangeStatus($article).' ';
         $html .= $fileicons.'</td>'.LF;
         $html .= '   <td style="vertical-align:bottom; white-space:nowrap; width: 30%;">'.$this->_text_as_html_short($this->_compareWithSearchText($creator_fullname)).'&nbsp; </td>'.LF;
         $html .= '   <td style="vertical-align:bottom; white-space:nowrap; width: 22%;">'.$this->_text_as_html_short($this->_compareWithSearchText(getDateTimeInLang($article->getModificationDate(), false))).'</td>'.LF;

         $html .= '</tr>'.LF;
         $article = $subitems->getNext();
         $pos_number++;
      }
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
      if(isset($this->_subitems)) {
         $html .= '<table id="discussionSummary" style="width:100%; padding:0px; margin:0px; border-collapse:collapse;" summary="Layout">'.LF;
         $discussion_type = $item->getDiscussionType();
         if($discussion_type == 'threaded') {
            // threaded view

            // check for javascript
            $session_item = $this->_environment->getSessionItem();
            if($session_item->issetValue('javascript')){
               if($session_item->getValue('javascript') == "1"){
                  $with_javascript = true;
               }else{
                  $with_javascript = false;
               }
            } else {
            	$with_javascript = true;
            }
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $with_javascript = false;
            }
            // UMSTELLUNG MUSEUM
            if($with_javascript and true){
               // javascript version
               $html .= $this->_getItemAsHTMLThreadedWithJavaScript($item);
            } else {
               // old version(non-javascript)
               $html .= $this->_getItemAsHTMLThreadedWithoutJavaScript($item);
            }
         } else {
            // linear view
            $html .= $this->_getItemAsHTMLLinear($item);
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
            $image = '<img src="images/commsyicons_msie6/22x22/new_section_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DISCARTICLE_ENTER_NEW').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new_section_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DISCARTICLE_ENTER_NEW').'"/>';
         }
         $html .= '<span class="disabled">'.$image.'</span>'.LF;
      }

      // closed discussion
      if ( $item->isClosed() ) {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/discussion_close.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DISCUSSION_CLOSE_ALL_ARTICLES').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/discussion_close.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DISCUSSION_CLOSE_ALL_ARTICLES').'"/>';
         }
         $params = $this->_environment->getCurrentParameterArray();
         $params['status'] = 'all_articles';
         $html .= ahref_curl(	$this->_environment->getCurrentContextID(),
         						$this->_environment->getCurrentModule(),
                                'detail',
         						$params,
         						$image,
         						$this->_translator->getMessage('DISCUSSION_CLOSE_ALL_ARTICLES')).LF;
         unset($params);
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
      $discussion_type = $item->getDiscussionType();
      if($discussion_type == 'threaded') {
         if ( $subitem->mayEdit($user) and $this->_with_modifying_actions ) {
            $params = array();
            $params['iid'] = $item->getItemID();
            $params['discarticle_action'] = 'edit';
            $params['discarticle_iid'] = $subitem->getItemID();
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/edit.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/edit.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
            }
            $html .= ahref_curl(   $this->_environment->getCurrentContextID(),
            $this->_environment->getCurrentModule(),
	                                'detail',
            $params,
            $image,
            $this->_translator->getMessage('COMMON_EDIT_ITEM'),
	                                '',
                                	'discarticle_form') . LF;
            unset($params);
         } else {
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/edit_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/edit_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
            }
            $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_EDIT_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
         }
      } else {
         $html .= $this->_getEditAction($subitem,$current_user,'discarticle');
      }

      if ( $user->isUser() and $this->_with_modifying_actions and $discussion_type == 'threaded') {
         $params = array();
         //$params['iid'] = 'NEW';
         $params['iid'] = $item->GetItemID();
         //$params['discussion_id'] = $item->getItemID();

         $params['ref_position'] = 1;
         $ref_position = $subitem->getPosition();
         if(!empty($ref_position)){
            $params['ref_position'] = $subitem->getPosition();
         }
         //$params['ref_did'] = $subitem->getItemID();
         $params['answer_to'] = $subitem->getItemID();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new_section.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DISCARTICLE_ANSWER_NEW').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new_section.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DISCARTICLE_ANSWER_NEW').'"/>';
         }

         // in threaded view, we want to put the form directly into the detail view and not on a single page

         $html .= ahref_curl(   $this->_environment->getCurrentContextID(),
                                'discussion',
                                'detail',
         $params,
         $image,
         $this->_translator->getMessage('DISCARTICLE_ANSWER_NEW'),
                                '',
                                'discarticle_form').LF;
         /*
          $html .= ahref_curl(   $this->_environment->getCurrentContextID(),
          'discarticle',
          'edit',
          $params,
          $image,
          $this->_translator->getMessage('DISCARTICLE_ANSWER_NEW')).LF;
          */
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
         $html .= ahref_curl( 		   $this->_environment->getCurrentContextID(),
                                       $this->_environment->getCurrentModule(),
                                       'detail',
                                       $params,
                                       $image,
                                       '',
                                       '',
                                       '',//anchor'.$subitem->getItemID(),
        							   '',
       								   '',
       								   '',
        							   '',
       								   '',
        							   'delete_confirm_disarc'.$subitem->getItemID()).LF;
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

         // calculate id after which the answer-form should appear
         if(isset($_GET['answer_to'])) {
            $father_position = $_GET['ref_position'];
            $father_position_length = mb_strlen($father_position);
            
            $subitems_list = clone($this->getSubItemList());
        	$subitem = $subitems_list->getFirst();
        	
        	// if father didnt had some childs before, the answer after id, is the id of the father itselfs
        	$insert_after_id = $_GET['answer_to'];
        	$last_position = $father_position;
        	
        	// go through each item
			while($subitem) {
				$subitem_position = $subitem->getPosition();
	            $subitem_position_length = mb_strlen($subitem_position);
	            
	            // if the father item had some childs before, the subitems position must be larger then the fathers position
	            if($subitem_position_length > $father_position_length) {
	            	// father position must be included in the new item	            	
	            	if(mb_substr($subitem_position, 0, $father_position_length) == $father_position) {
		            	// compare position
		            	if($subitem_position > $last_position) {
		            		$last_position = $subitem_position;
		            		$insert_after_id = $subitem->getItemID();
		            	}
	            	}
	            }
				
				$subitem = $subitems_list->getNext();
			}
         }

         $reader_manager = $this->_environment->getReaderManager();
         $noticed_manager = $this->_environment->getNoticedManager();
         $marked_reader_ids = array();
         $marked_noticed_ids = array();
         while ( $current_item ) {
            $discussion_type = $item->getDiscussionType();
            $html .='<tr class="detail_discussion_entries">'.LF;
            if ($discussion_type == 'threaded'){
               if(   isset($_GET['discarticle_action']) &&
                     isset($_GET['discarticle_iid']) &&
                     $_GET['discarticle_action'] == 'edit' &&
                     $_GET['discarticle_iid'] == $current_item->getItemID()) {
                  $html .='<td style="width:100%; padding-top:0px;  vertical-align:bottom;">'.LF;
                  $html .= $this->_getDiscussionFormAsHTMLForThreadedView();
                  $html .= '</td>'.LF;
                  $html .= '</tr>'.LF;
               } else {
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
                  /*if(!(isset($_GET['mode']) and $_GET['mode']=='print')){*/
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

                  // add answer form if requestet
                  if(isset($insert_after_id)){
	                  if($insert_after_id == $current_item->getItemID()) {
	                     $html .='<tr class="detail_discussion_entries">'.LF;
	                     $html .='<td>'.LF;
	                     $html .= $this->_getDiscussionFormAsHTMLForThreadedView();
	                     $html .='</td>'.LF;
	                     $html .='</tr>'.LF;
	                  }
                  }
               }

               /*} else {
                  $html .='</table>'.LF;
                  $html .='</td>'.LF;
                  $html .='</tr>'.LF;
                  $html .='</table>'.LF;
                  $html .='</td>'.LF;
                  $html .='</tr>'.LF;
               }*/
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

               /*if(!(isset($_GET['mode']) and $_GET['mode']=='print')){*/
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
               /*}else{
                $html .='<tr>'.LF;
                $html .='<td style="padding-top:5px; padding-bottom:40px; vertical-align:top; ">'.LF;
                $html .='</td>'.LF;
                $html .='</tr>'.LF;
                }*/
            }

            // collection ids for reader and noticed update
            if(   empty($reader) ||
                  $reader['read_date'] < $current_item->getModificationDate() ) {
               $marked_reader_ids[] = $current_item->getItemID();
            }
            if(   empty($noticed) ||
                  $noticed['read_date'] < $current_item->getModificationDate() ) {
               $marked_noticed_ids[] = $current_item->getItemID();
            }

            $current_item = $subitems->getNext();
            $pos_number++;
         } // end while
         
         // update reader and noticed entries
         $reader_manager->markItemsAsRead($marked_reader_ids, 0);
         $noticed_manager->markItemsAsNoticed($marked_noticed_ids, 0);
         unset($reader_manager);
         unset($noticed_manager);
      }

      $html .= '</table>'.LF;
      $html .= '<!-- END OF SUB ITEM DETAIL VIEW -->'.LF.LF;
      return $html;
   }


   function _getSubItemTitleAsHTML ($item, $pos_number) {
      if ($item->getItemType() == 'annotation'){
          return  parent::_getSubItemTitleAsHTML ($item, $pos_number);
      }
      $retour = '';
      if ( !empty($pos_number) ) {
         $retour .= $pos_number.'. ';
      }
      $title = $this->_text_as_html_short($this->_compareWithSearchText($item->getSubject()));
      $text_converter = $this->_environment->getTextConverter();
      if ( isset($text_converter) ) {
         $title = $text_converter->parseText2ID($title);
         unset($text_converter);
      }
      $retour .= $title;
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
               if(in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
                   $this->_with_slimbox = true;
                   // jQuery
                   //$file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.str_replace('"','&quot;',$this->_text_as_html_short($displayname)).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                   $file_list.='<a href="'.$url.'" rel="lightbox-gallery'.$item->getItemID().'" title="'.str_replace('"','&quot;',$this->_text_as_html_short($displayname)).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                   // jQuery
               }else{
                  $file_list.='<a href="'.$url.'" title="'.str_replace('"','&quot;',$this->_text_as_html_short($displayname)).' ('.$filesize.' kb)" target="blank" >'.$fileicon.'</a> ';
               }
            }
         } else {
            $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
         }
         $file = $files->getNext();
      }
      return $file_list;
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
              var text1 = '" . $this->_translator->getMessage("COMMON_DELETE_BOX_DESCRIPTION_DISCUSSION") . "';
              var text2 = '" . $text2 . "';
              var button_delete = '" . $this->_translator->getMessage("COMMON_DELETE_BUTTON") . "';
              var button_cancel = '" . $this->_translator->getMessage("COMMON_CANCEL_BUTTON") . "';
              var show_all = '" . $this->_translator->getMessage("DISCUSSION_THREADED_SHOW_ALL") . "';
              var hide_all = '" . $this->_translator->getMessage("DISCUSSION_THREADED_HIDE_ALL") . "';
          -->
          </script>
      ";

      return $return;
   }
}
?>