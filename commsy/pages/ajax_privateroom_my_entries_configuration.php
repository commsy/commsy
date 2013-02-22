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
		$current_entries = $privateroom_item->getMyEntriesDisplayConfig();
		
		$get_keys = array_keys($_GET);
		$myentries_array = array();
		foreach($get_keys as $get_key){
			if(stristr($get_key, 'myentries')){
				$myentries_array = $_GET[$get_key];
			}
		}

		$new_entries = array();
	   foreach($current_entries as $entry){
	   	if(in_array($entry, $myentries_array)){
            $new_entries[] = $entry;
	   	}
      }
		
		foreach($myentries_array as $myentry){
			if(!in_array($myentry, $current_entries)){
				$new_entries[] = $myentry;
			}
		}
		
		$privateroom_item->setMyEntriesDisplayConfig($new_entries);
      $privateroom_item->save();
	}
}
?>