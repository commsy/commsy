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
		$json_return_array = array();
	   $get_keys = array_keys($_GET);
      $matrix_array = array();
      $matrix_id_array = array();
      $matrix_text_array = array();
      $change_array = array();
      $new_matrix_column = '';
      $new_matrix_row = '';
      foreach($get_keys as $get_key){
         if(stristr($get_key, 'current_matrix')){
            $matrix_array = $_GET[$get_key];
         } else if(stristr($get_key, 'new_matrix_column')){
            $new_matrix_column = $_GET[$get_key];
         } else if(stristr($get_key, 'new_matrix_row')){
            $new_matrix_row = $_GET[$get_key];
         }
      }
      
      foreach($matrix_array as $matrix_entry){
      	$matrix_id_array[] = $matrix_entry[0];
      	$matrix_text_array[$matrix_entry[0]] = $matrix_entry[1];
      }
      
		$matrix_manager = $environment->getMatrixManager();
	   $matrix_manager->resetLimits();
	   $matrix_manager->setContextLimit($environment->getCurrentContextID());
	   $matrix_manager->setColumnLimit();
	   $matrix_manager->select();
	   $matrix_column_list = $matrix_manager->get();
	   $matrix_item = $matrix_column_list->getFirst();
	   while($matrix_item){
	      $id = $matrix_item->getItemID();
	      if(!in_array($id, $matrix_id_array)){
	      	$matrix_item->delete();
	      } else {
	      	$matrix_item->setName($matrix_text_array[$id]);
	      	$matrix_item->save();
	      	$change_array[] = array($id, $matrix_text_array[$id]);
	      }
	      $matrix_item = $matrix_column_list->getNext();
	   }
	   if (($new_matrix_column != '') and ($new_matrix_column != $translator->getMessage('PRIVATEROOM_MATRIX_NEW_COLUMN_ENTRY'))){	
	      $matrix_item = $matrix_manager->getNewItem();
	      $matrix_item->setLabelType('matrix');
	      $matrix_item->setName($new_matrix_column);
	      $matrix_item->setIsColumn();
	      $matrix_item->setContextID($environment->getCurrentContextID());
	      $user = $environment->getCurrentUserItem();
	      $matrix_item->setCreatorItem($user);
	      $matrix_item->setCreationDate(getCurrentDateTimeInMySQL());
	      $matrix_item->save();
	      $json_return_array['new_column'] = $matrix_item->getItemID();
	      $json_return_array['new_column_name'] = $new_matrix_column;
	   }
	   $matrix_manager->resetLimits();
	   $matrix_manager->setContextLimit($environment->getCurrentContextID());
	   $matrix_manager->setRowLimit();
	   $matrix_manager->select();
	   $matrix_row_list = $matrix_manager->get();
	   $matrix_item = $matrix_row_list->getFirst();
	   while($matrix_item){
	      $id = $matrix_item->getItemID();
	      if(!in_array($id, $matrix_id_array)){
            $matrix_item->delete();
         } else {
            $matrix_item->setName($matrix_text_array[$id]);
            $matrix_item->save();
            $change_array[] = array($id, $matrix_text_array[$id]);
         }
	      $matrix_item = $matrix_row_list->getNext();
	   }
	   if (($new_matrix_row != '') and ($new_matrix_row != $translator->getMessage('PRIVATEROOM_MATRIX_NEW_ROW_ENTRY'))){
	      $matrix_item = $matrix_manager->getNewItem();
	      $matrix_item->setLabelType('matrix');
	      $matrix_item->setName($new_matrix_row);
	      $matrix_item->setIsRow();
	      $matrix_item->setContextID($environment->getCurrentContextID());
	      $user = $environment->getCurrentUserItem();
	      $matrix_item->setCreatorItem($user);
	      $matrix_item->setCreationDate(getCurrentDateTimeInMySQL());
	      $matrix_item->save();
	      $json_return_array['new_row'] = $matrix_item->getItemID();
	      $json_return_array['new_row_name'] = $new_matrix_row;
	   }
	   
	   if(!empty($json_return_array['new_column'])){
	      $page->add('new_column', $json_return_array['new_column']);
	   }
	   if(!empty($json_return_array['new_column_name'])){
         $page->add('new_column_name', $json_return_array['new_column_name']);
      }
      if(!empty($json_return_array['new_row'])){
	      $page->add('new_row', $json_return_array['new_row']);
	   }
      if(!empty($json_return_array['new_row_name'])){
	      $page->add('new_row_name', $json_return_array['new_row_name']);
	   }
	   foreach($change_array as $change){
	      $page->add($change[0], $change[1]);
	   }
	}
}
?>