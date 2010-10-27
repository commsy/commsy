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
		$myroom_array = array();
		foreach($get_keys as $get_key){
			if(stristr($get_key, 'myrooms')){
				$myroom_array = $_GET[$get_key];
			}
		}
      
		if(empty($myroom_array)){
         $myroom_array[] = 'empty';
      }
      $privateroom_item->setMyroomDisplayConfig($myroom_array);
		
	   $myroom_config_array = $privateroom_item->getMyroomConfig();
      foreach($myroom_config_array as $key_top => $column){
         foreach($column as $key => $column_entry){
            if(($column_entry != 'null') && ($column_entry != 'empty')){
               if(!in_array($column_entry, $myroom_array)){
                  unset($myroom_config_array[$key_top][$key]);
               }
            }
         }
      }
      
      $selected_myroom_array = array();
      foreach($myroom_config_array as $column){
         foreach($column as $column_entry){
            if(($column_entry != 'null') && ($column_entry != 'empty')){
               $selected_myroom_array[] = $column_entry;
            }
         }
      }
      
      $add_to_myroom_config_array = array();
      foreach($myroom_array as $temp_room){
         if(!in_array($temp_room, $selected_myroom_array)){
            $add_to_myroom_config_array[] = $temp_room;
         }
      }
      
      foreach($add_to_myroom_config_array as $add_to_myroom_room){
         $smallest = 0;
         $size = sizeof($myroom_config_array[0]);
         foreach($myroom_config_array as $key => $column){
            if((sizeof($column) < $size) and ($column[0] != 'null') and ($column[0] != 'empty')){
               $smallest = $key;
               $size = sizeof($column);
            }
         }
         $myroom_config_array[$smallest][] = $add_to_myroom_room;
      }
         
      $privateroom_item->setMyroomConfig($myroom_config_array);
      
	   $privateroom_item->save();
	}
}
?>