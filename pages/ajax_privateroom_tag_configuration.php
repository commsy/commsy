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
}
?>