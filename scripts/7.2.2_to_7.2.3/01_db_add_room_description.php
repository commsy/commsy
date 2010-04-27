<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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

// headline
$this->_flushHeadline('db: add room_description to room table');

$success = true;

if ( !$this->_existsField('room','room_description') ) {
   $sql = "ALTER TABLE `room` ADD `room_description` VARCHAR( 2000 ) NULL , ADD INDEX ( `room_description` )";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsField('zzz_room','room_description') ) {
   $sql = "ALTER TABLE `zzz_room` ADD `room_description` VARCHAR( 2000 ) NULL , ADD INDEX ( `room_description` )";
   $success = $success AND $this->_select($sql);
}

$old_memory = ini_get("memory_limit");
ini_set("memory_limit","1000M");
set_time_limit(600);
$portal_manager = $this->_environment->getPortalManager();
$portal_manager->setContextLimit($this->_environment->getCurrentContextID());
$portal_manager->select();
$portal_list = $portal_manager->get();
$portal = $portal_list->getFirst();
while ( $portal ) {
   $room_manager = $this->_environment->getRoomManager();
   $room_manager->setContextLimit($portal->getItemID());
   $room_manager->select();
   $room_list = $room_manager->get();
   $room = $room_list->getFirst();
   while ( $room ) {
   	$description_new = $room->getDescription();
   	if(empty($description_new)){
	      $description_array = $room->getDescriptionArray();
	      $language = $room->getLanguage();
	      $description_text = '';
	      if(isset($description_array[strtoupper($language)]) and !empty($description_array[strtoupper($language)])){
	      	$description_text = $description_array[strtoupper($language)];
	      } else {
	      	foreach($description_array as $language_key => $description){
	      		if($language_key != $language){
	      			if(isset($description_array[strtoupper($language_key)]) and !empty($description_array[strtoupper($language_key)])){
	      				$description_text = $description_array[strtoupper($language_key)];
	      			}
	      		}
	      	}
	      }
	      
	      $values = array();
	      preg_match('~<!-- KFC TEXT ([a-z0-9]*) -->~u',$description_text,$values);
	      if ( !empty($values[1]) ) {
	         $hash = $values[1];
	         $description_text = str_replace('<!-- KFC TEXT '.$hash.' -->','',$description_text);
	         
	         if(mb_strlen($description_text) > 1000){
	            $description_text = mb_substr($description_text, 0, 1000);
	         }
	         
	         $description_text = '<!-- KFC TEXT '.$hash.' -->'.$description_text.'<!-- KFC TEXT '.$hash.' -->';
	      } else {
	         if(mb_strlen($description_text) > 1000){
	      	  $description_text = mb_substr($description_text, 0, 1000);
	         }
	      }
	
	      $room->setDescription($description_text);
	      $room->save();
   	}
   	$room = $room_list->getNext();
   }
   
   // Backup-Tabellen
   $zzz_room_manager = $this->_environment->getZzzRoomManager();
   $zzz_room_manager->setContextLimit($portal->getItemID());
   $zzz_room_manager->select();
   $room_list = $zzz_room_manager->get();
   $room = $room_list->getFirst();
   while ( $room ) {
      $description_new = $room->getDescription();
      if(empty($description_new)){
         $description_array = $room->getDescriptionArray();
         $language = $room->getLanguage();
         $description_text = '';
         if(isset($description_array[strtoupper($language)]) and !empty($description_array[strtoupper($language)])){
            $description_text = $description_array[strtoupper($language)];
         } else {
            foreach($description_array as $language_key => $description){
               if($language_key != $language){
                  if(isset($description_array[strtoupper($language_key)]) and !empty($description_array[strtoupper($language_key)])){
                     $description_text = $description_array[strtoupper($language_key)];
                  }
               }
            }
         }
         
         $values = array();
         preg_match('~<!-- KFC TEXT ([a-z0-9]*) -->~u',$description_text,$values);
         if ( !empty($values[1]) ) {
            $hash = $values[1];
            $description_text = str_replace('<!-- KFC TEXT '.$hash.' -->','',$description_text);
            
            if(mb_strlen($description_text) > 1000){
               $description_text = mb_substr($description_text, 0, 1000);
            }
            
            $description_text = '<!-- KFC TEXT '.$hash.' -->'.$description_text.'<!-- KFC TEXT '.$hash.' -->';
         } else {
            if(mb_strlen($description_text) > 1000){
              $description_text = mb_substr($description_text, 0, 1000);
            }
         }

         $room->setDescription($description_text);
         $room->save();
      }
      $room = $room_list->getNext();
   }
   
   $portal = $portal_list->getNext();
}


ini_set("memory_limit",$old_memory);

$this->_flushHTML(BRLF);
?>