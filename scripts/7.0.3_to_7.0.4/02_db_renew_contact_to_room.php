<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Iver Jackewitz
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

// new version of the update mechanism
// -----------------------------------
// the following is part of the method "asHTML"
// from the object cs_update_view.php

// init $success
$success = true;

// headline
$this->_flushHTML('copy contacts to room item'.BRLF);

$old_memory = ini_get("memory_limit");
ini_set("memory_limit","1000M");
set_time_limit(600);
$portal_manager = $this->_environment->getPortalManager();
$portal_manager->setContextLimit($this->_environment->getCurrentContextID());
$portal_manager->select();
$portal_list = $portal_manager->get();
$portal = $portal_list->getFirst();
while ( $portal ) {

   $this->_flushHTML(BRLF);
   $this->_flushHTML($this->_environment->getTextConverter()->text_as_html_short($portal->getTitle()).BRLF);

   $room_manager = $this->_environment->getRoomManager();
   $room_manager->setContextLimit($portal->getItemID());
   $room_manager->select();
   $room_list = $room_manager->get();

   $count = $room_list->getCount();
   $this->_initProgressBar($count);

   $room = $room_list->getFirst();
   while ( $room ) {
      $room->renewContactPersonString();
      $room = $room_list->getNext();
      $this->_updateProgressBar($count);
   }
   $portal = $portal_list->getNext();
   $this->_flushHTML(BRLF);
}
$this->_flushHTML(BRLF);
?>