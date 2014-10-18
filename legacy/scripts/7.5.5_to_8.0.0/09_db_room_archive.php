<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2013 Dr. Iver Jackewitz
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
$this->_flushHeadline('db: archive rooms');

$success = true;

$this->_flushHTML('open room templates'.LF);
$sql = "UPDATE room SET status=1 WHERE status=2 and template=1 and deletion_date IS NULL;";
$success = $success AND $this->_select($sql);
$this->_flushHTML(BRLF);

$this->_flushHTML('archive closed rooms'.LF);
$server_item = $this->_environment->getServerItem();
$portal_list = $server_item->getPortalList();

if ( !empty($portal_list)
     and $portal_list->isNotEmpty()
   ) {
	set_time_limit(0);
	
	$portal_item = $portal_list->getFirst();
	while ( $portal_item ) {
		
		$this->_flushHTML($portal_item->getTitle().LF);
		$this->_flushHTML(BRLF);
		
		// project rooms
		$room_manager = $this->_environment->getProjectManager();
		$room_manager->setContextLimit($portal_item->getItemID());
		$room_manager->setStatusLimit(CS_ROOM_CLOSED);
		$room_manager->select();
		$room_list = $room_manager->get();
		if ( !empty($room_list)
		     and $room_list->isNotEmpty() 
		   ) {
		   $this->_flushHTML('project rooms'.LF);
			
		   $count = $room_list->getCount();
			$this->_initProgressBar($count);
			
			$room_item = $room_list->getFirst();
			while ( $room_item ) {
				
				$success = $success and $room_item->moveToArchive();
				
            $this->_updateProgressBar($count);
				unset($room_item);
				$room_item = $room_list->getNext();
			}
			unset($user_manager);
         $this->_flushHTML(BRLF);
			$this->_flushHTML(BRLF);
		}
				
		// community rooms
		$room_manager = $this->_environment->getCommunityManager();
		$room_manager->setContextLimit($portal_item->getItemID());
		$room_manager->setStatusLimit(CS_ROOM_CLOSED);
		$room_manager->select();
		$room_list = $room_manager->get();
		if ( !empty($room_list)
		     and $room_list->isNotEmpty()
		   ) {
			$this->_flushHTML('community rooms'.LF);
		
			$count = $room_list->getCount();
			$this->_initProgressBar($count);
		
			$room_item = $room_list->getFirst();
			while ( $room_item ) {
		
				$success = $success and $room_item->moveToArchive();
		
				$this->_updateProgressBar($count);
				unset($room_item);
				$room_item = $room_list->getNext();
			}
			unset($user_manager);
			$this->_flushHTML(BRLF);
			$this->_flushHTML(BRLF);
		}
		
		unset($portal_item);
		$portal_item = $portal_list->getNext();
		$this->_flushHTML(BRLF);
	}
}
unset($portal_list);
unset($server_item);

$this->_flushHTML(BRLF);
?>