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
      
      $page->add('tag_created', '1');
	}
	
	$tag_manager = $environment->getTagManager();
   $root_item = $tag_manager->getRootTagItem();
	$selected_id = '';
   $father_id_array = array();
   #$tag_array = $this->_getSelectedTagArray();
   $tag_array = array();
   $count = (count($tag_array));
   if ($count >0){
      $selected_id = $tag_array[0];
      $tag2tag_manager =  $environment->getTag2TagManager();
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
                  $html .= '<div id="tag_tree" name="tag_tree_detail">';
               } else {
                  $html .= '<div id="tag_tree">';
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
?>