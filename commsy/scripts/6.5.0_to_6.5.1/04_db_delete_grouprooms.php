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
$this->_flushHTML('delete grouprooms if linked group is deleted'.BRLF);

$portal_manager = $this->_environment->getPortalManager();
$portal_manager->setContextLimit($this->_environment->getCurrentContextID());
$portal_manager->select();
$portal_list = $portal_manager->get();
$portal = $portal_list->getFirst();
while ( $portal ) {

   $room_manager = $this->_environment->getGroupRoomManager();
   $room_manager->setContextLimit($portal->getItemID());
   $room_manager->select();
   $room_list = $room_manager->get();

   $count = $room_list->getCount();

   if ( $count > 0 ) {
      $group_manager = $this->_environment->getGroupManager();
      $this->_flushHTML(BRLF);
      $this->_flushHTML($this->_environment->getTextConverter()->text_as_html_short($portal->getTitle()).BRLF);

      $this->_initProgressBar($count);

      $room = $room_list->getFirst();
      while ( $room ) {
         $group_item = $group_manager->getItem($room->getLinkedGroupItemID());
         if ( !isset($group_item)
              or ( !empty($group_item)
                   and $group_item->isDeleted()
                 )
            ) {
            $room->delete();
         }
         $room = $room_list->getNext();
         $this->_updateProgressBar($count);
      }
      $this->_flushHTML(BRLF);
   }

   $portal = $portal_list->getNext();
}
$this->_flushHTML(BRLF);
?>