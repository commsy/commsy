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

if(isset($_GET['portlet'])){
	if($_GET['portlet'] == 'youtube'){
		$privateroom_item = $environment->getCurrentContextItem();
		$channel = '';
		
		$get_keys = array_keys($_GET);
		$column_array = array();
		foreach($get_keys as $get_key){
			if(stristr($get_key, 'youtube_channel')){
				if(!empty($_GET[$get_key])){
					$channel = $_GET[$get_key];
				}
			}
		}

		#if($channel != ''){
			$privateroom_item->setPortletYouTubeAccount($channel);
		#}
      $privateroom_item->save();
	} elseif($_GET['portlet'] == 'flickr'){
		$privateroom_item = $environment->getCurrentContextItem();
      $id = '';
      
      $get_keys = array_keys($_GET);
      $column_array = array();
      foreach($get_keys as $get_key){
         if(stristr($get_key, 'flickr_id')){
            if(!empty($_GET[$get_key])){
               $id = $_GET[$get_key];
            }
         }
      }
      
      if($id != ''){
         $privateroom_item->setPortletFlickrID($id);
      }
      $privateroom_item->save();
	} elseif($_GET['portlet'] == 'twitter'){
      $privateroom_item = $environment->getCurrentContextItem();
      $id = '';
      
      $get_keys = array_keys($_GET);
      $column_array = array();
      foreach($get_keys as $get_key){
         if(stristr($get_key, 'twitter_channel_id')){
            if(!empty($_GET[$get_key])){
               $id = $_GET[$get_key];
            }
         }
      }
      
      if($id != ''){
         $privateroom_item->setPortletTwitterAccount($id);
      }
      $privateroom_item->save();
   } elseif($_GET['portlet'] == 'rss_add'){
      $privateroom_item = $environment->getCurrentContextItem();
      $current_rss_array = $privateroom_item->getPortletRSSArray();
      $temp_rss_array = array();
      
      $get_keys = array_keys($_GET);
      $column_array = array();
      foreach($get_keys as $get_key){
         if(stristr($get_key, 'rss_add_titel')){
            if(!empty($_GET[$get_key])){
               $temp_rss_array['title'] = $_GET[$get_key];
            }
         } elseif(stristr($get_key, 'rss_add_adress')){
            if(!empty($_GET[$get_key])){
               $temp_rss_array['adress'] = $_GET[$get_key];
            }
         }
      }
      $temp_rss_array['display'] = '1';
      $current_rss_array[] = $temp_rss_array;

      $privateroom_item->setPortletRSSArray($current_rss_array);
      $privateroom_item->save();
   } elseif($_GET['portlet'] == 'rss_save'){
      $privateroom_item = $environment->getCurrentContextItem();
      $current_rss_array = $privateroom_item->getPortletRSSArray();
      $temp_rss_array = array();
      $checked_array = array();
      
      $get_keys = array_keys($_GET);
      $column_array = array();
      foreach($get_keys as $get_key){
         if(stristr($get_key, 'rss_add_titel')){
            if(!empty($_GET[$get_key])){
               $temp_rss_array['title'] = $_GET[$get_key];
            }
         } elseif(stristr($get_key, 'rss_add_adress')){
            if(!empty($_GET[$get_key])){
               $temp_rss_array['adress'] = $_GET[$get_key];
            }
         } elseif(stristr($get_key, 'rss_checked')){
            if(!empty($_GET[$get_key])){
               $checked_array = $_GET[$get_key];
            }
         }
      }
      
      $new_rss_array = array();
      foreach($current_rss_array as $current_rss){
         if(in_array($current_rss['title'], $checked_array)){
         	$new_rss_array[] = $current_rss;
         }
      }
      
      if(isset($temp_rss_array['title'])){
         $temp_rss_array['display'] = '1';
         $new_rss_array[] = $temp_rss_array;
      }
      
      $privateroom_item->setPortletRSSArray($new_rss_array);
      $privateroom_item->save();
   } elseif($_GET['portlet'] == 'new_entries'){
      $privateroom_item = $environment->getCurrentContextItem();
      $count = '';
      $user = '';
      
      $get_keys = array_keys($_GET);
      $column_array = array();
      foreach($get_keys as $get_key){
         if(stristr($get_key, 'new_entries_count')){
            if(!empty($_GET[$get_key])){
               $count = $_GET[$get_key];
            }
         }
         if(stristr($get_key, 'new_entries_show_user')){
            if(!empty($_GET[$get_key])){
               $user = $_GET[$get_key];
            }
         }
      }
      
      #if($count != '' and $user != ''){
      #   $privateroom_item->setPortletNewEntryListCount($count);
      #   $privateroom_item->setPortletNewEntryListShowUser($user);
      #}
      
      if($count != ''){
         $privateroom_item->setPortletNewEntryListCount($count);
      }
      
      $privateroom_item->save();
   } elseif($_GET['portlet'] == 'note'){
   	$privateroom_item = $environment->getCurrentContextItem();
   	
      $get_keys = array_keys($_GET);
      $text = '';

      foreach($get_keys as $get_key){
         if(stristr($get_key, 'portlet_note_content')){
            if(!empty($_GET[$get_key])){
               $text = $_GET[$get_key];
            }
         }
      }
      
      $text_converter = $environment->getTextConverter();
      $text_html = str_ireplace('COMMSY_BR', "\n\r", $text);
      $text_html = str_ireplace('COMMSY_DOUBLE_QUOTE', '"', $text_html);
      $text_html = str_ireplace('COMMSY_SINGLE_QUOTE', "'", $text_html);
      #$text_html = $text_converter->text_as_html_long($text_converter->cleanDataFromTextArea($text_html));
      $text_html = $text_converter->textFullHTMLFormatting($text_converter->cleanDataFromTextArea($text_html));
      $text_html = str_ireplace("\n\r", '', $text_html);
      $text_html = str_ireplace('"', '\"', $text_html);
      $text_html = str_ireplace("'", "\'", $text_html);
      
      //if($text != ''){
         $privateroom_item->setPortletNoteContent($text);
      //}
      $privateroom_item->save();
      
   	$page->add('content', $text);
   	$page->add('content_html', $text_html);
   }
}
?>