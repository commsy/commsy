<?php
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

include_once('functions/development_functions.php');
if(isset($_GET['do'])){
	$translator = $environment->getTranslationObject();
   
	$tag_page = '';
   $get_keys = array_keys($_GET);
   foreach($get_keys as $get_key){
      if(stristr($get_key, 'tag_page')){
         $tag_page = $_GET[$get_key];
      }
   }
	
	if($_GET['do'] == 'save_new_tag'){
      $new_tag_name = '';
      $new_tag_father = '';
      $get_keys = array_keys($_GET);
      foreach($get_keys as $get_key){
         if(stristr($get_key, 'new_tag_name')){
            $new_tag_name = $_GET[$get_key];
         }
         if(stristr($get_key, 'new_tag_father')){
            $new_tag_father = $_GET[$get_key];
         }
      }
      $tag_manager = $environment->getTagManager();
      $tag_item = $tag_manager->getNewItem();
      $tag_item->setTitle($new_tag_name);
      $tag_item->setContextID($environment->getCurrentContextID());
      $user = $environment->getCurrentUserItem();
      $tag_item->setCreatorItem($user);
      unset($user);
      $tag_item->setCreationDate(getCurrentDateTimeInMySQL());
      $tag_item->setPosition($new_tag_father,1);
      $tag_item->save();
	} else if($_GET['do'] == 'sort_tag'){
      $tag_sort_1 = '';
      $tag_sort_2 = '';
      $tag_sort_action = '';
      $get_keys = array_keys($_GET);
      foreach($get_keys as $get_key){
         if(stristr($get_key, 'tag_sort_1')){
            $tag_sort_1 = $_GET[$get_key];
         }
         if(stristr($get_key, 'tag_sort_2')){
            $tag_sort_2 = $_GET[$get_key];
         }
         if(stristr($get_key, 'tag_sort_action')){
            $tag_sort_action = $_GET[$get_key];
         }
      }
	   $tag2tag_manager = $environment->getTag2TagManager();
      $cat_1 = $tag_sort_1;
      $children_id_array_cat1 = $tag2tag_manager->getRecursiveChildrenItemIDArray($cat_1);
      if ( !in_array($tag_sort_2,$children_id_array_cat1) ) {
         if ($tag_sort_action == 3) {
            $cat_2 = $tag_sort_2;
            $place = 1;
         } else {
            $cat_2 = $tag2tag_manager->getFatherItemID($tag_sort_2);
            $children_id_array = $tag2tag_manager->getChildrenItemIDArray($cat_2);
            $place = 0;
            foreach ($children_id_array as $children_item_id) {
               $place++;
               if ( $children_item_id == $tag_sort_2 ) {
                  break;
               }
            }
            if ( $tag_sort_action == 2 ) {
               $place++;
            }
         }
         $tag2tag_manager->change($cat_1,$cat_2,$place);
      }
      unset($tag2tag_manager);
   } else if($_GET['do'] == 'sort_tag_abc'){
   	$tag_manager = $environment->getTagManager();
      $root_item = $tag_manager->getRootTagItem();
      $tag2tag_manager = $environment->getTag2TagManager();
      $children_id_array = $tag2tag_manager->getRecursiveChildrenItemIDArray($root_item->getItemID());
      $tag2tag_manager->sortRecursiveABC($root_item->getItemID());
      unset($tag2tag_manager);
   } else if($_GET['do'] == 'combine_tag'){
      $tag_combine_1 = '';
      $tag_combine_2 = '';
      $tag_combine_father = '';
      $get_keys = array_keys($_GET);
      foreach($get_keys as $get_key){
         if(stristr($get_key, 'tag_combine_1')){
            $tag_combine_1 = $_GET[$get_key];
         }
         if(stristr($get_key, 'tag_combine_2')){
            $tag_combine_2 = $_GET[$get_key];
         }
         if(stristr($get_key, 'tag_combine_father')){
            $tag_combine_father = $_GET[$get_key];
         }
      }
      $tag2tag_manager = $environment->getTag2TagManager();
      $sel_1 = $tag_combine_1;
      $sel_2 = $tag_combine_2;
      $put = $tag_combine_father;
      $childrenIdArray_1 = $tag2tag_manager->getRecursiveChildrenItemIDArray($sel_1);
      $childrenIdArray_2 = $tag2tag_manager->getRecursiveChildrenItemIDArray($sel_2);
      
      if(   !in_array($put, $childrenIdArray_1) &&
            !in_array($put, $childrenIdArray_2) &&
            $put != $sel_1 &&
            $put != $sel_2) {
         $tag2tag_manager->combine($sel_1, $sel_2, $put);
      }
      unset($tag2tag_manager);
   } else if($_GET['do'] == 'change_tag'){
      $tag_change_value = '';
      $tag_change_id = '';
      $get_keys = array_keys($_GET);
      foreach($get_keys as $get_key){
         if(stristr($get_key, 'tag_change_id')){
            $tag_change_id = $_GET[$get_key];
         }
         if(stristr($get_key, 'tag_change_value')){
            $tag_change_value = $_GET[$get_key];
         }
      }
      $tag_manager = $environment->getTagManager();
      $tag_item = $tag_manager->getItem($tag_change_id);
      if(!empty($tag_item)) {
         $tag_item->setTitle($tag_change_value);
         $tag_item->save();
      }
   } else if($_GET['do'] == 'delete_tag'){
      $tag_delete_id = '';
      $get_keys = array_keys($_GET);
      foreach($get_keys as $get_key){
         if(stristr($get_key, 'tag_delete_id')){
            $tag_delete_id = $_GET[$get_key];
         }
      }
      $tag_manager = $environment->getTagManager();
      $tag_item = $tag_manager->getItem($tag_delete_id);
      if(!empty($tag_item)) {
         $tag_item->delete();
      }
   }
   
   $tag2tag_manager =  $environment->getTag2TagManager();
   $tag2tag_manager->resetCachedChildrenIdArray();
	$tag_manager = $environment->getTagManager();
	$tag_manager->resetCache();
	$root_item = $tag_manager->getRootTagItem();
   
   $values_tree = array();
   $first_sort_tree = array();
   $second_sort_tree = array();
   if ( isset($root_item) ) {
      $temp_array = array();
      $temp_array['value'] = $root_item->getItemID();
      $temp_array['text'] = '*'.$translator->getMessage('TAG_FORM_ROOT_LEVEL');
      $values_tree[] = $temp_array;
      unset($temp_array);
      $first_sort_tree = initFormChildren($root_item,0);
      $values_tree = array_merge($values_tree, $first_sort_tree);
      $second_sort_tree = $values_tree;
   }
   
   $values_html = '';
   foreach($values_tree as $value){
      $values_html .= '<option value="'.$value['value'].'">'.$value['text'].'</option>';
   }
   $values_html = str_ireplace("'", "\'", $values_html);
   $values_html = str_ireplace('"', '\"', $values_html);
   $page->add('values_update', $values_html);
   
   $first_sort_html = '';
   foreach($first_sort_tree as $value){
      $first_sort_html .= '<option value="'.$value['value'].'">'.$value['text'].'</option>';
   }
   $first_sort_html = str_ireplace("'", "\'", $first_sort_html);
   $first_sort_html = str_ireplace('"', '\"', $first_sort_html);
   $page->add('first_sort_update', $first_sort_html);
   
   $second_sort_html = '';
   foreach($second_sort_tree as $value){
      $second_sort_html .= '<option value="'.$value['value'].'">'.$value['text'].'</option>';
   }
   $second_sort_html = str_ireplace("'", "\'", $second_sort_html);
   $second_sort_html = str_ireplace('"', '\"', $second_sort_html);
   $page->add('second_sort_update', $second_sort_html);
   
   $change_html = createFormForChildren($root_item,0);
   $change_html = str_ireplace("'", "\'", $change_html);
   $change_html = str_ireplace('"', '\"', $change_html);
   $page->add('change_update', $change_html);
   
	$selected_id = '';
   $father_id_array = array();
   #$tag_array = $this->_getSelectedTagArray();
   $tag_array = array();
   $count = (count($tag_array));
   if ($count >0){
      $selected_id = $tag_array[0];
      $father_id_array = $tag2tag_manager->getFatherItemIDArray($selected_id);
   }
   $tree_update = getTagContentAsHTMLWithJavascript($root_item,0,$selected_id, $father_id_array,0,true);
   $tree_update = str_ireplace("'", "\'", $tree_update);
   $tree_update = str_ireplace('"', '\"', $tree_update);
   $page->add('tree_update', $tree_update);
}

function getTagContentAsHTMLWithJavascript($item = NULL, $ebene = 0,$selected_id = 0, $father_id_array, $distance = 0, $with_div=false) {
	   global $environment;
	   $display_mode = '';
      // MUSEUM
      $html = '';
      #$params = $environment->getCurrentParameterArray();
      $params = array();
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
            $html .= '<ul>';
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
                  $tag2tag_manager = $environment->getTag2TagManager();
                  $count = count($tag2tag_manager->getFatherItemIDArray($id));
                  $font_size = round(13 - (($count*0.2)+$count));
                  if ($font_size < 8){
                     $font_size = 8;
                  }
                  $font_color = 20 + getTagColorLogarithmic($count);
               }else{
                  if ( in_array($id,$father_id_array) ){
                     $tag2tag_manager = $environment->getTag2TagManager();
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
                     $font_color = 20 + getTagColorLogarithmic($count);
                     $font_weight = 'bold';
                     $font_style = 'normal';
                  }else{
                     $tag2tag_manager = $environment->getTag2TagManager();
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
                     $font_color = 20 + getTagColorLogarithmic($count);
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
               $text_converter = $environment->getTextConverter();
               $title = $text_converter->text_as_html_short($current_item->getTitle());
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
                  if ( $environment->inPrivateRoom()
                       and $environment->getCurrentModule() == CS_MATERIAL_TYPE
                       and $display_mode == 'flash'
                     ) {
                     $html .= '<li id="' . $current_item->getItemID() . '" data="StudyLog: \'' . $current_item->getItemID() . '\'" style="color:#545454; font-style:normal; font-size:9pt; font-weight:normal;">';
                     $html .= '<a href="javascript:callStudyLogSortByTagId('.$current_item->getItemID().')">'.$title.'</a>';
                  } else {
                     $link = curl($environment->getCurrentContextID(),
                                         'entry',
                                         'index',
                                         $params);
                     $html .= '<li id="' . $current_item->getItemID() . '" data="url: \'' . $link . '\'" style="color:#545454; font-style:normal; font-size:9pt; font-weight:normal;">';
                     $html .= ahref_curl($environment->getCurrentContextID(),
                                         'entry',
                                         'index',
                                         $params,
                                         $title,
                                         $title,'','','','','','style="color:#545454; font-size:9pt;"');
                  }
               }else{
                  $params['name'] = $link_name;
                  $link = curl($environment->getCurrentContextID(),
                                         'entry',
                                         'index',
                                         $params);
                  $html .= '<li id="' . $current_item->getItemID() . '" data="url: \'' . $link . '\'" style="color:#000000; font-style:normal; font-size:9pt; font-weight:bold;">';
                  $html .= $title;
               }
               $html .= getTagContentAsHTMLWithJavascript($current_item, $ebene+1, $selected_id, $father_id_array, $distance);
               $current_item = $list->getNext();
               $html.='</li>';
            }
            $html.='</ul>';
            if($with_div){
               $html .= '</div>';
            }
         }
      }
      return $html;
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
   
   function initFormChildren ( $item, $depth ) {
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
               $retour = array_merge($retour,initFormChildren($child,$depth+1));
               unset($child);
               $child = $children_list->getNext();
            }

         }
         unset($children_list);
      }
      return $retour;
   }
   
   function createFormForChildren ( $item, $depth ) {
   	global $environment;
   	$translator = $environment->getTranslationObject();
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
               $html .= '<tr>';
               $html .= '<td class="formfield" style="padding-left:'.$px.'px;">';
               $html .= '<input type="text" id="my_tag_form_change_value-'.$child->getItemID().'" value="'.$child->getTitle().'" maxlength="255" tabindex="49" class="text" style="width:'.$width.'px;"/>';
               $html .= '</td><td>';
               $html .= '<input type="submit" id="my_tag_form_change_button-'.$child->getItemID().'" value="'.$translator->getMessage('BUZZWORDS_CHANGE_BUTTON').'" tabindex="50"/>';
               $html .= '</td><td>';
               $html .= '<input type="submit" id="my_tag_form_delete_button-'.$child->getItemID().'" value="'.$translator->getMessage('COMMON_DELETE_BUTTON').'" tabindex="52"/>';
               $html .= '</td>';
               $html .= '</tr>';                  
               $html .= createFormForChildren($child,$depth+1);
               unset($child);
               $child = $children_list->getNext();
            }
         }
         unset($children_list);
      }
      unset($item);
      return $html;
   }
?>