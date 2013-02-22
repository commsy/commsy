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
		// room_manager -> config speichern
		$get_keys = array_keys($_GET);
		$column_array = array();
		foreach($get_keys as $get_key){
			if(stristr($get_key, 'column')){
				$column_array[] = $_GET[$get_key];
			}
		}
	   // remove null-entries from jQuery
      foreach($column_array as $key_top => $column){
         foreach($column as $key => $column_entry){
            if(($column_entry != 'null') && ($column_entry != null)){ //&& ($column_entry != 'empty')
            } else {
            	unset($column_array[$key_top][$key]);
            }
         }
         if(empty($column_array[$key_top])){
         	$column_array[$key_top] = array('empty');
         }
      }
		
      $privateroom_item = $environment->getCurrentContextItem();
      $privateroom_item->setHomeConfig($column_array);
      $privateroom_item->updateHomeConfiguration($column_array);
      $privateroom_item->save();
	} elseif($_GET['do'] == 'get_config'){
      #$privateroom_item = $environment->getCurrentContextItem();
      #$column_array = $privateroom_item->getHomeConfig();
      #debugToFile($column_array);
   } else {

   }
}
?>