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

/** upper class of the form view
 */
$this->includeClass(VIEW);

/** class for a form view in commsy-style
 * this class implements a form view
 */
class cs_privateroom_home_tag_view extends cs_view {

	var $_selected_tag_array = array();
	
   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->setViewName('tag');
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      $this->_view_title = $this->_translator->getMessage('COMMON_TAGS');
   }

   function asHTML(){
      $html = '';
      $html .= '<div id="'.get_class($this).'">'.LF;
      
      $html .= '<div id="my_tag_content_div">'.LF;
      $tag_manager = $this->_environment->getTagManager();
      $root_item = $tag_manager->getRootTagItem();
      
      $selected_id = '';
      $father_id_array = array();
      $tag_array = $this->_getSelectedTagArray();
      $count = (count($tag_array));
      if ($count >0){
         $selected_id = $tag_array[0];
         $tag2tag_manager =  $this->_environment->getTag2TagManager();
         $father_id_array = $tag2tag_manager->getFatherItemIDArray($selected_id);
      }
      
      $html .= $this->_getTagContentAsHTMLWithJavascript($root_item,0,$selected_id, $father_id_array,0,true);
      $html .= '</div>'.LF;
      
      $html .= '</div>'.LF;
      return $html;
   }
   
   function getPreferencesAsHTML(){
   	$room = $this->_environment->getCurrentContextItem();
      $html = '';
      
      $html .= '<table id="my_tag_form_table" summary="layout">'.LF;
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= cs_ucfirst($this->_translator->getMessage('COMMON_ADD_BUTTON')).LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;

      $tag_manager = $this->_environment->getTagManager();
      $root_item = $tag_manager->getRootTagItem();
      
      $values_tree = array();
      $first_sort_tree = array();
      $second_sort_tree = array();
      if ( isset($root_item) ) {
         $temp_array = array();
         $temp_array['value'] = $root_item->getItemID();
         $temp_array['text'] = '*'.$this->_translator->getMessage('TAG_FORM_ROOT_LEVEL');
         $values_tree[] = $temp_array;
         unset($temp_array);
         $first_sort_tree = $this->_initFormChildren($root_item,0);
         $values_tree = array_merge($values_tree, $first_sort_tree);
         $second_sort_tree = $values_tree;
      }
      
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= '<input type="text" id="my_tag_form_new_tag" value="" maxlength="255" size="30" tabindex="18" class="text"/>'.LF;
      $html .= $this->_translator->getMessage('TAG_WORD_TO').LF;
      $html .= '<select id="my_tag_form_father_id" size="1" tabindex="19">'.LF;
      foreach($values_tree as $value){
         $html .= '<option value="'.$value['value'].'">'.$value['text'].'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= '<input type="submit" id="my_tag_form_button_add" value="'.$this->_translator->getMessage('COMMON_ADD_BUTTON').'" tabindex="20"/>'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
               
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= '<br/>'.LF;
      $html .= ''.$this->_translator->getMessage('COMMON_SORT_BUTTON').''.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
               
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= '<select id="my_tag_form_sort_1" size="1" tabindex="21">'.LF;
      foreach($first_sort_tree as $value){
         $html .= '<option value="'.$value['value'].'">'.$value['text'].'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= '<select id="my_tag_form_sort_action" size="1" tabindex="22">'.LF;
      $html .= '<option value="3">'.$this->_translator->getMessage('TAG_ACTIONS_UNDER').'</option>'.LF;
      $html .= '<option value="1">'.$this->_translator->getMessage('TAG_ACTIONS_BEFORE').'</option>'.LF;
      $html .= '<option value="2">'.$this->_translator->getMessage('TAG_ACTIONS_AFTER').'</option>'.LF;
      $html .= '</select>'.LF;
      $html .= '<select id="my_tag_form_sort_2" size="1" tabindex="23">'.LF;
      foreach($second_sort_tree as $value){
         $html .= '<option value="'.$value['value'].'">'.$value['text'].'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= '<input type="submit" id="my_tag_form_button_sort" value="'.$this->_translator->getMessage('TAG_SORT_BUTTON').'" tabindex="24"/>'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
               
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= '<input type="submit" id="my_tag_form_button_sort_abc" value="'.$this->_translator->getMessage('TAG_SORT_ABC').'" tabindex="25"/>'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
               
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= '<br/>'.LF;
      $html .= cs_ucfirst($this->_translator->getMessage('TAG_COMBINE_BUTTON')).LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
               
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= '<select id="my_tag_form_combine_1" size="1" tabindex="26">'.LF;
      foreach($first_sort_tree as $value){
         $html .= '<option value="'.$value['value'].'">'.$value['text'].'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= '<select id="my_tag_form_combine_2" size="1" tabindex="27">'.LF;
      foreach($first_sort_tree as $value){
         $html .= '<option value="'.$value['value'].'">'.$value['text'].'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= $this->_translator->getMessage('TAG_WORD_TO').LF;
      $html .= '<select id="my_tag_form_combine_father" size="1" tabindex="28">'.LF;
      foreach($second_sort_tree as $value){
         $html .= '<option value="'.$value['value'].'">'.$value['text'].'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= '<input type="submit" id="my_tag_form_button_combine" value="'.$this->_translator->getMessage('TAG_COMBINE_BUTTON').'" tabindex="29"/><br/><br/>'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;

      $html .= '<tr>'.LF;
      $html .= '<td class="infoborder" style="width: 70%;" colspan="3">'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
   
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= 'Bearbeiten'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
               
      $html .= '<tr>'.LF;
      $html .= '<td class="formfield" colspan="3">'.LF;
      $html .= '<table id="my_tag_form_change_table">'.LF;
      $html .= $this->_createFormForChildren($root_item, 0);
      $html .= '</table>'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
      
      $html .= '</table>'.LF;
      
      return $html;
   }
   
   function _getTagContentAsHTMLWithJavascript($item = NULL, $ebene = 0,$selected_id = 0, $father_id_array, $distance = 0, $with_div=false) {
      // MUSEUM
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['from']);
      $i = 0;
      while($i <= count($father_id_array)){
        if (isset($params['seltag_'.$i])){
           unset($params['seltag_'.$i]);
        }
        $i++;
      }
      $is_selected = false;
      if ( isset($item) ) {
         $list = $item->getChildrenList();
         if ( isset($list) and !$list->isEmpty() ) {
            if($with_div){
               if(isset($_GET['seltag'])){
                  $html .= '<div id="tag_tree_privateroom" name="tag_tree_detail">';
               } else {
                  $html .= '<div id="tag_tree_privateroom">';
               }
            }
            $html .= '<ul>'.LF;
            $current_item = $list->getFirst();
            $distance = $distance +1;
            $font_weight ='normal';
            $font_color = 30;
            $font_style = 'normal';
            while ( $current_item ) {
               $is_selected = false;
               $id = $current_item->getItemID();
               $link_name = '';
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
                  #$font_style = 'normal';
                  $link_name = 'selected';
               }
               $color = 'rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
               $title = $this->_text_as_html_short($current_item->getTitle());
               if (!$is_selected){
                  $params['seltag_'.$ebene] = $current_item->getItemID();
                  if( isset($params['seltag']) ){
                     $i = $ebene+1;
                     while( isset($params['seltag_'.$i]) ){
                        unset($params['seltag_'.$i]);
                        $i++;
                     }
                  }
                  $params['seltag'] = 'yes';
                  if ( $this->_environment->inPrivateRoom()
                       and $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE
                       and $this->_display_mode == 'flash'
                     ) {
                     $html .= '<li id="' . $current_item->getItemID() . '" data="StudyLog: \'' . $current_item->getItemID() . '\'" style="color:#545454; font-style:normal; font-size:9pt; font-weight:normal;">'.LF;
                     $html .= '<a href="javascript:callStudyLogSortByTagId('.$current_item->getItemID().')">'.$title.'</a>'.LF;
                  } else {
                     $link = curl($this->_environment->getCurrentContextID(),
                                         'entry',
                                         $this->_environment->getCurrentFunction(),
                                         $params);
                     $html .= '<li id="' . $current_item->getItemID() . '" data="url: \'' . $link . '\'" style="color:#545454; font-style:normal; font-size:9pt; font-weight:normal;">'.LF;
                     $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                         'entry',
                                         $this->_environment->getCurrentFunction(),
                                         $params,
                                         $title,
                                         $title,'','','','','','style="color:#545454; font-size:9pt;"').LF;
                  }
               }else{
                  $params['name'] = $link_name;
                  $link = curl($this->_environment->getCurrentContextID(),
                                         'entry',
                                         $this->_environment->getCurrentFunction(),
                                         $params);
                  $html .= '<li id="' . $current_item->getItemID() . '" data="url: \'' . $link . '\'" style="color:#000000; font-style:normal; font-size:9pt; font-weight:bold;">'.LF;
                  $html .= $title.LF;
               }
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
   
   private function _getSelectedTagArray () {
      return $this->_selected_tag_array;
   }

   public function setSelectedTagArray ($array) {
      $this->_selected_tag_array = $array;
   }
   
   function getTagColorLogarithmic( $count, $mincount=0, $maxcount=5, $minsize=0, $maxsize=40, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
   }
   
   private function _createFormForChildren ( $item, $depth ) {
      $html = '';
      if ( isset($item) ) {
         $children_list = $item->getChildrenList();
         if ( isset($children_list) and $children_list->isNotEmpty() ) {
            $arrows = '';
            $px = 0;
            $width = 250;
            $depth_temp = $depth;
            while ( $depth_temp > 0 ) {
               $arrows .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ';
               $px += 20;
               $width -= 20;
               $depth_temp = $depth_temp-1;
            }
            $len_text_field = 30-($depth*4);
            if ( $depth > 0 ) {
               $len_text_field = $len_text_field - 1;
            }
            $child = $children_list->getFirst();
            while ( $child ) {
               $html .= '<tr>'.LF;
               $html .= '<td class="formfield" style="padding-left:'.$px.'px;">'.LF;
               $html .= '<input type="text" id="my_tag_form_change_value-'.$child->getItemID().'" value="'.$child->getTitle().'" maxlength="255" tabindex="49" class="text" style="width:'.$width.'px;"/>'.LF;
               $html .= '</td><td>'.LF;
               $html .= '<input type="submit" id="my_tag_form_change_button-'.$child->getItemID().'" value="'.$this->_translator->getMessage('BUZZWORDS_CHANGE_BUTTON').'" tabindex="50"/>'.LF;
               $html .= '</td><td>'.LF;
               $html .= '<input type="submit" id="my_tag_form_delete_button-'.$child->getItemID().'" value="'.$this->_translator->getMessage('COMMON_DELETE_BUTTON').'" tabindex="52"/>'.LF;
               $html .= '</td>'.LF;
               $html .= '</tr>'.LF;                  
               $html .= $this->_createFormForChildren($child,$depth+1);
               unset($child);
               $child = $children_list->getNext();
            }
         }
         unset($children_list);
      }
      unset($item);
      return $html;
   }
   
   private function _initFormChildren ( $item, $depth ) {
      $retour = array();
      if ( isset($item) ) {
         $children_list = $item->getChildrenList();
         if ( isset($children_list) and $children_list->isNotEmpty() ) {
            $child = $children_list->getFirst();
            $arrows = '';
            $depth_temp = $depth;
            while ( $depth_temp > 0 ) {
               $arrows .= '> ';
               $depth_temp = $depth_temp-1;
            }
            while ( $child ) {
               $temp_array = array();
               $temp_array['value'] = $child->getItemID();
               $temp_array['text']  = $arrows.$child->getTitle();
               $retour[] = $temp_array;
               $retour = array_merge($retour,$this->_initFormChildren($child,$depth+1));
               unset($child);
               $child = $children_list->getNext();
            }

         }
         unset($children_list);
      }
      $this->_first_sort_tree = $retour;
      return $retour;
   }
}
?>