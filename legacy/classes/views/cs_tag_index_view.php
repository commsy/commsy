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
//include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: announcement
 */
class cs_tag_index_view extends cs_index_view {


var $_item = NULL;
var $_checked_tag_array = array();
var $_count_entries = 0;

   // @segment-begin 80628 cs_announcement_index_view($environment, $with_modifying_actions)-uses-#77035,#48753,#60854
   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the page
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   public function __construct ($params) {
      cs_index_view::__construct($params);
      $this->setTitle($this->_translator->getMessage('COMMON_TAGS'));
      $this->setActionTitle($this->_translator->getMessage('COMMON_TAGS'));
      $this->_colspan = '5';
   }

   function setItem($item){
      $this->_item = $item;
   }



   function asHTML () {
      $html  = LF.'<!-- BEGIN OF LIST VIEW -->'.LF;

      $html .='<div id="profile_content">'.LF;
      $html .= '<form style="padding:0px; margin:0px;" action="';
      $params = $this->_environment->getCurrentParameterArray();
      $html .= curl($this->_environment->getCurrentContextID(),
                    $this->_environment->getCurrentModule(),
                    $this->_environment->getCurrentFunction(),
                    $params
                   ).'" method="post">'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['attach_view']);
      unset($params['attach_type']);
      $params['return_attach_tag_list']= 'true';
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           $this->_environment->getCurrentModule(),
                           $this->_environment->getCurrentFunction(),
                           $params,
                           'X',
                           '','', '', '', '', '', 'class="titlelink"');
      $html .='<div>'.LF;
      $html .= '<div class="profile_title" style="float:right">'.$title.'</div>';
      $html .= '<h2 id="profile_title">'.$this->_translator->getMessage('COMMON_TAG_NEW_ATTACH').'</h2>';
      $html .='</div>'.LF;
      $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
      $current_browser_version = $this->_environment->getCurrentBrowserVersion();
      if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
         $width= ' width:100%; padding-right:10px;';
      }else{
         $width= '';
      }

      $html .='<div style="width:100%; padding-top:5px; vertical-align:bottom;">'.LF;
#      $html .= '<table class="list" style="width: 100%; border-collapse: collapse;" summary="Layout">'.LF;
      $html .= $this->_getContentAsHTML();
#      $html .= '</table>'.LF;
      $html .= '<table class="list" style="width: 100%; border-collapse: collapse;" summary="Layout">'.LF;
      $html .= $this->_getTablefootAsHTML();
      $html .= '</table>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .= '</form>'.LF;
      $html .='</div>'.LF;
      $html .= '<!-- END OF PLAIN LIST VIEW -->'.LF.LF;
      return $html;
   }


   function _getTagContentAsHTML($item = NULL, $ebene = 0,$selected_id = 0, $father_id_array, $distance = 0) {
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      $i = 0;
      while($i <= count($father_id_array)){
        if (isset($params['seltag_'.$i])){
           unset($params['seltag_'.$i]);
        }
        $i++;
      }
      $this->_count_entries = 0;
      $is_selected = false;
      if ( isset($item) ) {
         $list = $item->getChildrenList();
         if ( isset($list) and !$list->isEmpty() ) {
            $this->_count_entries++;
            if ($ebene == 1){
               $html.= '<div style="padding-bottom:5px;">'.LF;
            }else{
               $html.= '<div style="padding-bottom:0px;">'.LF;
            }
            $current_item = $list->getFirst();
            $distance = $distance +1;
            $font_weight ='normal';
            $font_color = 30;
            $font_style = 'normal';
            while ( $current_item ) {
               $is_selected = false;
               $id = $current_item->getItemID();
               if ( empty($selected_id) ){
                  $tag2tag_manager = $this->_environment->getTag2TagManager();
                  $count = count($tag2tag_manager->getFatherItemIDArray($id));
                  $font_size = round(13 - (($count*0.2)+$count));
                  if ($font_size < 8){
                     $font_size = 8;
                  }
                  $font_color = 20 + $this->getTagColorLogarithmic($count);
               }else{
                  if ( in_array($id,$father_id_array) ){
                     $tag2tag_manager = $this->_environment->getTag2TagManager();
                     $id_array = $tag2tag_manager->getFatherItemIDArray($id);
                     $count = 0;
                     foreach($id_array as $temp_id){
                        if ( !in_array($temp_id,$father_id_array) ){
                           $count ++;
                        }
                     }
                     if( !isset($id_array[0]) and isset($father_id_array[0]) ){
                        $count = 1;
                     }
#                     $font_size = 14;
                     $font_size = round(13 - (($count*0.2)+$count));
                     if ($font_size < 8){
                        $font_size = 8;
                     }
                     $font_color = 20 + $this->getTagColorLogarithmic($count);
                     $font_weight = 'bold';
                     $font_style = 'normal';
                  }else{
                     $tag2tag_manager = $this->_environment->getTag2TagManager();
                     $id_array = $tag2tag_manager->getFatherItemIDArray($id);
                     $count = 0;
                     $found = false;
                     if ( isset($id_array[0]) ){
                        foreach($id_array as $temp_id){
                           if ( !in_array($temp_id,$father_id_array) ){
                              $count ++;
                           }else{
                             $found = true;
                           }
                        }
                        if (!$found){
                           $count = $count + count($father_id_array);
                        }
                     }elseif( !isset($id_array[0]) and isset($father_id_array[0]) ){
                        $count = count($father_id_array);
                     }
                     $font_size = 14 - $this->getTagSizeLogarithmic($count);
                     $font_size = round(13 - (($count*0.2)+$count));
                     if ($font_size < 8){
                        $font_size = 8;
                     }
                     $font_color = 20 + $this->getTagColorLogarithmic($count);
                     $font_weight='normal';
                     $font_style = 'normal';
                  }
               }
               if ($current_item->getItemID() == $selected_id){
                  $is_selected = true;
                  $font_size = 14;
                  $font_color = 20;
                  $font_style = 'normal';
               }
               $font_color = 20;
               $this->_count_entries++;

               if (($ebene*15) <= 30){
                  $html .= '<div style="padding-left:'.($ebene*30).'px; font-style:'.$font_style.'; font-size:'.$font_size.'px; font-weight:'.$font_weight.';">';
               }else{
                  $html .= '<div style="padding-left:40px; font-size:'.$font_size.'px; font-style:'.$font_style.'; font-weight:'.$font_weight.';">';
               }
               $title = $this->_text_as_html_short($current_item->getTitle());
               $params['seltag_'.$ebene] = $current_item->getItemID();
               if( isset($params['seltag']) ){
                  $i = $ebene+1;
                  while( isset($params['seltag_'.$i]) ){
                     unset($params['seltag_'.$i]);
                     $i++;
                  }
               }
               $params['seltag'] = 'yes';
               $checkbox = '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" type="checkbox" name="taglist['.$current_item->getItemID().']" value="1"';
               if ( isset($this->_checked_tag_array) and !empty($this->_checked_tag_array) and in_array($current_item->getItemID(), $this->_checked_tag_array)) {
                  $checkbox .= ' checked="checked"'.LF;
               }
               $checkbox .= '/>'.LF;
               $checkbox .= '         <input type="hidden" name="shown['.$this->_text_as_form($current_item->getItemID()).']" value="1"/>'.LF;
               $html .= '<div class="entry" style="white-space:nowrap; font-size:'.$font_size.'px;">'.LF;
               $html .= $checkbox;
               $html .= $title;
               $html .= '</div>'.LF;
               $html .= '</div>';
               $html .= $this->_getTagContentAsHTML($current_item, $ebene+1, $selected_id, $father_id_array, $distance);
               $current_item = $list->getNext();
            }
            $html.='</div>'.LF;
         }
      }
      return $html;
   }

   function _getTagContentAsHTMLWithJavascript($item = NULL, $ebene = 0,$selected_id = 0, $father_id_array, $distance = 0, $with_div=false) {
      // MUSEUM
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      $i = 0;
      while($i <= count($father_id_array)){
        if (isset($params['seltag_'.$i])){
           unset($params['seltag_'.$i]);
        }
        $i++;
      }
      $this->_count_entries = 0;
      $is_selected = false;
      if ( isset($item) ) {
         $list = $item->getChildrenList();
         if ( isset($list) and !$list->isEmpty() ) {
            $this->_count_entries++;
            if($with_div){
               $html .= '<div id="tag_tree">';
            }
            $html .= '<ul>'.LF; // oberstes <ul>
            $current_item = $list->getFirst();
            $distance = $distance +1;
            $font_weight ='normal';
            $font_color = 30;
            $font_style = 'normal';
            while ( $current_item ) {
               $is_selected = false;
               $id = $current_item->getItemID();
               if ( empty($selected_id) ){
                  $tag2tag_manager = $this->_environment->getTag2TagManager();
                  $count = count($tag2tag_manager->getFatherItemIDArray($id));
                  $font_size = round(13 - (($count*0.2)+$count));
                  if ($font_size < 8){
                     $font_size = 8;
                  }
                  $font_color = 20 + $this->getTagColorLogarithmic($count);
               }else{
                  if ( in_array($id,$father_id_array) ){
                     $tag2tag_manager = $this->_environment->getTag2TagManager();
                     $id_array = $tag2tag_manager->getFatherItemIDArray($id);
                     $count = 0;
                     foreach($id_array as $temp_id){
                        if ( !in_array($temp_id,$father_id_array) ){
                           $count ++;
                        }
                     }
                     if( !isset($id_array[0]) and isset($father_id_array[0]) ){
                        $count = 1;
                     }
                     $font_size = round(13 - (($count*0.2)+$count));
                     if ($font_size < 8){
                        $font_size = 8;
                     }
                     $font_color = 20 + $this->getTagColorLogarithmic($count);
                     $font_weight = 'bold';
                     $font_style = 'normal';
                  }else{
                     $tag2tag_manager = $this->_environment->getTag2TagManager();
                     $id_array = $tag2tag_manager->getFatherItemIDArray($id);
                     $count = 0;
                     $found = false;
                     if ( isset($id_array[0]) ){
                        foreach($id_array as $temp_id){
                           if ( !in_array($temp_id,$father_id_array) ){
                              $count ++;
                           }else{
                             $found = true;
                           }
                        }
                        if (!$found){
                           $count = $count + count($father_id_array);
                        }
                     }elseif( !isset($id_array[0]) and isset($father_id_array[0]) ){
                        $count = count($father_id_array);
                     }
                     $font_size = 14 - $this->getTagSizeLogarithmic($count);
                     $font_size = round(13 - (($count*0.2)+$count));
                     if ($font_size < 8){
                        $font_size = 8;
                     }
                     $font_color = 20 + $this->getTagColorLogarithmic($count);
                     $font_weight='normal';
                     $font_style = 'normal';
                  }
               }
               if ($current_item->getItemID() == $selected_id){
                  $is_selected = true;
                  $font_size = 14;
                  $font_color = 20;
                  $font_style = 'normal';
               }
               $font_color = 20;
               $color = 'rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
               $this->_count_entries++;

               $html .= '<li id="' . $current_item->getItemID() . '" data="checkbox: \'' . $current_item->getItemID() . '\'" style="color:#545454; font-style:normal; font-size:8pt; font-weight:normal;">'.LF;
               
               $title = $this->_text_as_html_short($current_item->getTitle());
               $params['seltag_'.$ebene] = $current_item->getItemID();
               if( isset($params['seltag']) ){
                  $i = $ebene+1;
                  while( isset($params['seltag_'.$i]) ){
                     unset($params['seltag_'.$i]);
                     $i++;
                  }
               }
               $params['seltag'] = 'yes';
               $checkbox = '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px; display:none;" type="checkbox" name="taglist['.$current_item->getItemID().']" id="taglist_'.$current_item->getItemID().'" value="1"';
               if ( isset($this->_checked_tag_array) and !empty($this->_checked_tag_array) and in_array($current_item->getItemID(), $this->_checked_tag_array)) {
                  $checkbox .= ' checked="checked"'.LF;
               }
               $checkbox .= '/>'.LF;
               $checkbox .= '         <input type="hidden" name="shown['.$this->_text_as_form($current_item->getItemID()).']" value="1"/>'.LF;
               $html .= '<div class="entry" style="white-space:nowrap; font-size:9pt; font-weight:normal;">'.LF;
               $html .= $checkbox;
               $html .= $title;
               $html .= '</div>'.LF;
               $html .= $this->_getTagContentAsHTMLWithJavascript($current_item, $ebene+1, $selected_id, $father_id_array, $distance);
               $current_item = $list->getNext();
               $html.='</li>'.LF;
            }
            $html.='</ul>'.LF;
            if($with_div){
               $html .= '</div>'.LF;
            }
         }
      }
      return $html;
   }

   function _getContentAsHTML() {
      if( $this->_environment->getCurrentFunction() == 'edit'){
         $session = $this->_environment->getSessionItem();
         $this->_checked_tag_array = $session->getValue('cid'.$this->_environment->getCurrentContextID().'_'.$this->_environment->getCurrentModule().'_tag_ids');
      }else{
         $tag_list = $this->_item->getTagList();
         $this->_checked_tag_array = array();
         if ($tag_list->getCount() > 0) {
            $tag_item = $tag_list->getFirst();
            while ($tag_item) {
               $this->_checked_tag_array[] = $tag_item->getItemID();
               $tag_item = $tag_list->getNext();
            }
         }
      }
      if(empty($this->_checked_tag_array)){
         $this->_checked_tag_array= array();
      }
      $html = '';
      $list = $this->getList();
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $tag_manager = $this->_environment->getTagManager();
      $root_item = $tag_manager->getRootTagItem();
      $params = $this->_environment->getCurrentParameterArray();
      $selected_id = '';
      $father_id_array = array();
      $tag_array = array();
      $count = (count($tag_array));
      if ($count >0){
         $selected_id = $tag_array[0];
         $tag2tag_manager =  $this->_environment->getTag2TagManager();
         $father_id_array = $tag2tag_manager->getFatherItemIDArray($selected_id);
      }
      $html .= '<div style="padding:5px;">';
      
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
      // UMSTELLUNG MUSEUM
      if($with_javascript){
         $html_text = $this->_getTagContentAsHTMLWithJavascript($root_item,0,$selected_id, $father_id_array,0,true);
      } else {
         $html_text = $this->_getTagContentAsHTML($root_item,0,$selected_id, $father_id_array);
      }
      
      if ( empty($html_text) ){
         $html_text = '<span class="disabled" style="font-size:10pt;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</span>';
      }
      $html .= $html_text.'</div>';
      return $html;
   }

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

   function _getTablefootAsHTML() {
      $html  = '   <tr id="index_table_foot" class="list">'.LF;
      $html .= '<td class="foot_left" colspan="2" style="vertical-align:middle;">'.LF;
      $html .= $this->_getViewActionsAsHTML();
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;

   }

   function _getViewActionsAsHTML () {
      $html = '   <input type="hidden" name="return_attach_tag_list" value="true"/>'.LF;
      $html .= '<input type="submit" style="font-size:10pt;" name="option"';
      $html .= ' value="'.$this->_translator->getMessage('COMMON_TAG_NEW_ATTACH').'"';
      $html .= '/>'.LF;

      return $html;
   }

}
?>