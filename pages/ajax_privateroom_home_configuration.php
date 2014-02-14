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
	if($_GET['do'] == 'save_config'){
		$privateroom_item = $environment->getCurrentContextItem();

		$get_keys = array_keys($_GET);
		$column_array = array();
		foreach($get_keys as $get_key){
			if(stristr($get_key, 'portlets')){
				$column_array = $_GET[$get_key];
			} elseif(stristr($get_key, 'column_count')){
				$column_count = $_GET[$get_key];
			}
		}

      // add entries
      if(in_array('cs_privateroom_home_new_entries_view', $column_array)){
         $privateroom_item->setPortletShowNewEntryList();
      } else {
      	$privateroom_item->unsetPortletShowNewEntryList();
      }

      if(in_array('cs_privateroom_home_room_view', $column_array)){
         $privateroom_item->setPortletShowActiveRoomList();
      } else {
         $privateroom_item->unsetPortletShowActiveRoomList();
      }

      if(in_array('cs_privateroom_home_search_view', $column_array)){
         $privateroom_item->setPortletShowSearchBox();
      } else {
         $privateroom_item->unsetPortletShowSearchBox();
      }

      if(in_array('cs_privateroom_home_roomwide_search_view', $column_array)){
         $privateroom_item->setPortletShowRoomWideSearchBox();
      } else {
         $privateroom_item->unsetPortletShowRoomWideSearchBox();
      }

      if(in_array('cs_privateroom_home_dokuverser_view', $column_array)){
         $privateroom_item->setPortletShowDokuverserBox();
      } else {
         $privateroom_item->unsetPortletShowDokuverserBox();
      }

      if(in_array('cs_privateroom_home_buzzword_view', $column_array)){
         $privateroom_item->setPortletShowBuzzwordBox();
      } else {
         $privateroom_item->unsetPortletShowBuzzwordBox();
      }

      if(in_array('cs_privateroom_home_configuration_view', $column_array)){
         $privateroom_item->setPortletShowConfigurationBox();
      } else {
         $privateroom_item->unsetPortletShowConfigurationBox();
      }

      if(in_array('cs_privateroom_home_new_item_view', $column_array)){
         $privateroom_item->setPortletShowNewItemBox();
      } else {
         $privateroom_item->unsetPortletShowNewItemBox();
      }

      if(in_array('cs_privateroom_home_weather_view', $column_array)){
         $privateroom_item->setPortletShowWeatherBox();
      } else {
         $privateroom_item->unsetPortletShowWeatherBox();
      }

      if(in_array('cs_privateroom_home_clock_view', $column_array)){
         $privateroom_item->setPortletShowClockBox();
      } else {
         $privateroom_item->unsetPortletShowClockBox();
      }

      if(in_array('cs_privateroom_home_twitter_view', $column_array)){
         $privateroom_item->setPortletShowTwitter();
      } else {
         $privateroom_item->unsetPortletShowTwitter();
      }

      if(in_array('cs_privateroom_home_youtube_view', $column_array)){
         $privateroom_item->setPortletShowYouTube();
      } else {
         $privateroom_item->unsetPortletShowYouTube();
      }

      if(in_array('cs_privateroom_home_flickr_view', $column_array)){
         $privateroom_item->setPortletShowFlickr();
      } else {
         $privateroom_item->unsetPortletShowFlickr();
      }

      if(in_array('cs_privateroom_home_rss_ticker_view', $column_array)){
         $privateroom_item->setPortletShowRSS();
      } else {
         $privateroom_item->unsetPortletShowRSS();
      }

	   if(in_array('cs_privateroom_home_note_view', $column_array)){
         $privateroom_item->setPortletShowNoteBox();
      } else {
         $privateroom_item->unsetPortletShowNoteBox();
      }
      
	   if(in_array('cs_privateroom_home_released_entries_view', $column_array)){
         $privateroom_item->setPortletShowReleasedEntriesBox();
      } else {
         $privateroom_item->unsetPortletShowReleasedEntriesBox();
      }
      
	   if(in_array('cs_privateroom_home_tag_view', $column_array)){
         $privateroom_item->setPortletShowTagBox();
      } else {
         $privateroom_item->unsetPortletShowTagBox();
      }
      
      $home_config_array = $privateroom_item->getHomeConfig();

      if(empty($home_config_array) or sizeof($home_config_array) < $column_count){
      	for ($i = sizeof($home_config_array); $i < $column_count; $i++) {
      		$home_config_array[] = array();
      	}
      }
      
	   // remove unset portlets
      foreach($home_config_array as $key_top => $column){
         foreach($column as $key => $column_entry){
            if(($column_entry != 'null') && ($column_entry != null)){ // && ($column_entry != 'empty')
               if(!in_array($column_entry, $column_array)){
                  unset($home_config_array[$key_top][$key]);
               }
            } else {
            	unset($home_config_array[$key_top][$key]);
            }
         }
      }
      // get new portlets
	   $portlet_array = array();
      foreach($home_config_array as $column){
         foreach($column as $column_entry){
            if(($column_entry != 'null') && ($column_entry != 'empty')){
               $portlet_array[] = $column_entry;
            }
         }
      }
      $add_to_home_config_array = array();
      foreach($column_array as $portlet){
         if(!in_array($portlet, $portlet_array)){
            $add_to_home_config_array[] = $portlet;
         }
      }

      if($column_count == sizeof($home_config_array)){
      } elseif($column_count < sizeof($home_config_array)){
      	// 3 -> 2
         $last_column = $home_config_array[sizeof($home_config_array)-1];
         unset($home_config_array[sizeof($home_config_array)-1]);
         foreach($last_column as $switch_column_portlet){
            $smallest = 0;
            $size = sizeof($home_config_array[0]);
            foreach($home_config_array as $key => $column){
               if((sizeof($column) < $size) and ($column[0] != 'null') and ($column[0] != 'empty')){
                  $smallest = $key;
                  $size = sizeof($column);
               }
            }
            $home_config_array[$smallest][] = $switch_column_portlet;
         }
      } elseif($column_count > sizeof($home_config_array)){
      	// 2 -> 3
         $home_config_array[] = array('empty');
      }

      foreach($add_to_home_config_array as $add_to_home_portlet){
         $smallest = 0;
         $size = sizeof($home_config_array[0]);
         foreach($home_config_array as $key => $column){
            if((sizeof($column) < $size) and ($column[0] != 'null') and ($column[0] != 'empty')){
               $smallest = $key;
               $size = sizeof($column);
               }
            }
         $home_config_array[$smallest][] = $add_to_home_portlet;
      }
	   foreach($home_config_array as $key_top => $column){
         if(empty($home_config_array[$key_top])){
         	$home_config_array[$key_top] = array('empty');
         }
      }
      $privateroom_item->setHomeConfig($home_config_array);
      $privateroom_item->setPortletColumnCount($_GET['column_count']);
      $privateroom_item->save();
	}
}
?>