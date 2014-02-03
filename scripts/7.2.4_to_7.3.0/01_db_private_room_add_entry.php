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
$this->_flushHeadline('db: add entries to homeconf of private rooms');
$this->_flushHTML(BRLF);
$success = true;

$old_memory = ini_get("memory_limit");
ini_set("memory_limit","4000M");
set_time_limit(0);

$toggle_c_use_new_private_room = false;
$c_use_new_private_room = $this->_environment->inConfigArray('c_use_new_private_room',$this->_environment->getCurrentContextID());
$current_context_id = $this->_environment->getCurrentContextID();
$current_portal_id = $this->_environment->getCurrentPortalID();
if ($c_use_new_private_room){
   $c_use_new_private_room = false;
   $toggle_c_use_new_private_room = true;
}

$portal_manager = $this->_environment->getPortalManager();
$portal_manager->setContextLimit($this->_environment->getCurrentContextID());
$portal_manager->select();
$portal_list = $portal_manager->get();
$portal = $portal_list->getFirst();
while ( $portal ) {

   $this->_flushHTML($portal->getTitle());
   $this->_flushHTML(BRLF);

   $room_manager = $this->_environment->getPrivateRoomManager();
   $room_manager->setContextLimit($portal->getItemID());
   $room_manager->select();
   $room_list = $room_manager->get();

   $count = $room_list->getCount();
   $this->_initProgressBar($count);

   $room = $room_list->getFirst();
   while ( $room ) {
      unset($home_conf);
      $home_conf = $room->getHomeConf();
      if ( !mb_stristr($home_conf,'entry') ) {
         $home_conf .= ','.CS_ENTRY_TYPE.'_tiny';
         $room->setHomeConf($home_conf);
         $room->save();
      }
      $mycalendar_conf = $room->getMyCalendarDisplayConfig();
      if(empty($mycalendar_conf)){
      	$mycalendar_conf[] = 'mycalendar_dates_portlet';
      	$mycalendar_conf[] = 'mycalendar_todo_portlet';
      	$room->setMyCalendarDisplayConfig($mycalendar_conf);
      	$room->save();
      }
      $this->_updateProgressBar($count);
      $room = $room_list->getNext();
   }

   $this->_flushHTML(BRLF);
   $this->_flushHTML(BRLF);
   $portal = $portal_list->getNext();
}

if ( $toggle_c_use_new_private_room ) {
   $c_use_new_private_room = true;
}
ini_set("memory_limit",$old_memory);

$this->_flushHTML(BRLF);
?>