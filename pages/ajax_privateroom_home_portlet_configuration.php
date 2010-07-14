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

		if($channel != ''){
			$privateroom_item->setPortletYouTubeAccount($channel);
		}
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
	}
}
?>