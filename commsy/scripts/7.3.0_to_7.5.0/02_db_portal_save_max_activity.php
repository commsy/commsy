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
$this->_flushHeadline('db: save max activity in portals');

$success = true;

$server_item = $this->_environment->getServerItem();
$portal_list = $server_item->getPortalList();

if ( !empty($portal_list)
     and $portal_list->isNotEmpty()
   ) {
   $count = $portal_list->getCount();
   $this->_initProgressBar($count);

   $room_manager = $this->_environment->getRoomManager();

   $portal_item = $portal_list->getFirst();
   while ( $portal_item ) {
      $room_manager->setContextLimit($portal_item->getItemID());
      $portal_item->setMaxRoomActivityPoints($room_manager->getMaxActivityPoints());
      $portal_item->saveWithoutChangingModificationInformation();

      $this->_updateProgressBar($count);
      unset($portal_item);
      $portal_item = $portal_list->getNext();
   }
}

$this->_flushHTML(BRLF);
?>