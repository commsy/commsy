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
	if($_GET['do'] == 'save_new_buzzword'){
      $new_buzzword = '';
      $get_keys = array_keys($_GET);
      foreach($get_keys as $get_key){
         if(stristr($get_key, 'new_buzzword')){
            $new_buzzword = $_GET[$get_key];
         }
      }
      
	   $buzzword_manager = $environment->getLabelManager();
      $buzzword_item = $buzzword_manager->getNewItem();
      $buzzword_item->setLabelType('buzzword');
      $buzzword_item->setName($new_buzzword);
      $buzzword_item->setContextID($environment->getCurrentContextID());
      $user = $environment->getCurrentUserItem();
      $buzzword_item->setCreatorItem($user);
      $buzzword_item->setCreationDate(getCurrentDateTimeInMySQL());
      $buzzword_item->save();
	   
	   $page->add('new_buzzword_id', $buzzword_item->getItemID());
	   $page->add('new_buzzword_name', $new_buzzword);
      #$page->add('new_column_name', $json_return_array['new_column_name']);
	   #$page->add('new_row', $json_return_array['new_row']);
	   #$page->add('new_row_name', $json_return_array['new_row_name']);
	} elseif($_GET['do'] == 'delete_buzzword'){
	   $delete_buzzword = '';
      $get_keys = array_keys($_GET);
      foreach($get_keys as $get_key){
         if(stristr($get_key, 'buzzword_delete')){
            $delete_buzzword = $_GET[$get_key];
         }
      }
      $buzzword_manager = $environment->getLabelManager();
      $buzzword_item = $buzzword_manager->getItem($delete_buzzword);
      $page->add('delete_buzzword_id', $buzzword_item->getItemID());
      $page->add('delete_buzzword_name', $buzzword_item->getName());
      $buzzword_item->delete();
	} elseif($_GET['do'] == 'change_buzzword'){
      $change_buzzword_id = '';
      $change_buzzword_name = '';
      $get_keys = array_keys($_GET);
      foreach($get_keys as $get_key){
         if(stristr($get_key, 'buzzword_change_id')){
            $change_buzzword_id = $_GET[$get_key];
         } elseif(stristr($get_key, 'buzzword_change_name')){
            $change_buzzword_name = $_GET[$get_key];
         }
      }
      $buzzword_manager = $environment->getLabelManager();
      $buzzword_item = $buzzword_manager->getItem($change_buzzword_id);
      $buzzword_item->setName($change_buzzword_name);
      $page->add('change_buzzword_id', $buzzword_item->getItemID());
      $page->add('change_buzzword_name', $buzzword_item->getName());
      $buzzword_item->save();
   } elseif($_GET['do'] == 'combine_buzzwords'){
      $buzzword_combine_first_id = '';
      $buzzword_combine_second_id = '';
      $get_keys = array_keys($_GET);
      foreach($get_keys as $get_key){
         if(stristr($get_key, 'buzzword_combine_first')){
            $buzzword_combine_first_id = $_GET[$get_key];
         } elseif(stristr($get_key, 'buzzword_combine_second')){
            $buzzword_combine_second_id = $_GET[$get_key];
         }
      }
      $link_manager = $environment->getLinkManager();
      $link_manager->combineBuzzwords($buzzword_combine_first_id,$buzzword_combine_second_id);
      $buzzword_manager = $environment->getLabelManager();
      $buzzword_item1 = $buzzword_manager->getItem($buzzword_combine_first_id);
      $buzzword_item2 = $buzzword_manager->getItem($buzzword_combine_second_id);
      $buzzword_item1->setName($buzzword_item1->getName().'/'.$buzzword_item2->getName());
      $buzzword_item1->setModificationDate(getCurrentDateTimeInMySQL());
      $buzzword_item1->save();
      $buzzword_item2->delete();
      $page->add('combine_first_id', $buzzword_combine_first_id);
      $page->add('combine_second_id', $buzzword_combine_second_id);
      $page->add('combine_name', $buzzword_item1->getName());
   }
}
?>