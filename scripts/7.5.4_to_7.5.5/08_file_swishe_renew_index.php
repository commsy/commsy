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

function _rm_swishe_index($dirname) {
   if ( $dirHandle = opendir($dirname) ) {
      $old_cwd = getcwd();
      chdir($dirname);

      while ($file = readdir($dirHandle)){
         if ($file == '.' or $file == '..' or $file == 'CVS') continue;
         if ( is_dir($file) ) {
            if ( !_rm_swishe_index($file) ) {
               chdir($old_cwd);
               return false;
            }
         } elseif ( is_file($file)
                    and ( $file == 'ft.index'
                          or $file == 'ft.index.prop'
                          or $file == 'ft_idx.log'
                        )
                  ) {
            if ( !@unlink($file) ) {
               chdir($old_cwd);
               return false;
            }
         }
      }

      closedir($dirHandle);
      chdir($old_cwd);
      return true;
   }
}

$c_indexing = $this->_environment->getConfiguration('c_indexing');
if ( isset($c_indexing) 
     and !empty($c_indexing)
     and $c_indexing
   ) {
	
	set_time_limit(0);
	
	// init $success
	$success = true;
	
	// headline
	$this->_flushHTML('file: renew swish-e index'.BRLF);
	#$success = _rm_swishe_index('var/');
	
	$server_item = $this->_environment->getServerItem();
	$portal_list = $server_item->getPortalList();
	
	if ( !empty($portal_list)
	   and $portal_list->isNotEmpty()
	   ) {
		$portal_item = $portal_list->getFirst();
		while ( $portal_item ) {
	
			$this->_flushHTML($portal_item->getTitle().LF);
			$this->_flushHTML(BRLF);
	
			// project rooms
			$room_manager = $this->_environment->getProjectManager();
			$room_manager->setContextLimit($portal_item->getItemID());
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
	
					$success = $success and $room_item->renewFileIndex();
	
					$this->_updateProgressBar($count);
					unset($room_item);
					$room_item = $room_list->getNext();
				}
				$this->_flushHTML(BRLF);
				$this->_flushHTML(BRLF);
			}
	
			// group rooms
			$room_manager = $this->_environment->getGroupRoomManager();
			$room_manager->setContextLimit($portal_item->getItemID());
			$room_manager->select();
			$room_list = $room_manager->get();
			if ( !empty($room_list)
	 		     and $room_list->isNotEmpty()
			   ) {
				$this->_flushHTML('group rooms'.LF);
	
				$count = $room_list->getCount();
				$this->_initProgressBar($count);
	
				$room_item = $room_list->getFirst();
				while ( $room_item ) {
	
					$success = $success and $room_item->renewFileIndex();
	
					$this->_updateProgressBar($count);
					unset($room_item);
					$room_item = $room_list->getNext();
				}
				$this->_flushHTML(BRLF);
				$this->_flushHTML(BRLF);
			}
	
			// community rooms
			$room_manager = $this->_environment->getCommunityManager();
			$room_manager->setContextLimit($portal_item->getItemID());
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
	
					$success = $success and $room_item->renewFileIndex();
	
					$this->_updateProgressBar($count);
					unset($room_item);
					$room_item = $room_list->getNext();
				}
				$this->_flushHTML(BRLF);
				$this->_flushHTML(BRLF);
			}
	
			// private rooms
			$room_manager = $this->_environment->getPrivateRoomManager();
			$room_manager->setContextLimit($portal_item->getItemID());
			$room_manager->select();
			$room_list = $room_manager->get();
			if ( !empty($room_list)
			     and $room_list->isNotEmpty()
			   ) {
				$this->_flushHTML('private rooms'.LF);
	
				$count = $room_list->getCount();
				$this->_initProgressBar($count);
	
				$room_item = $room_list->getFirst();
				while ( $room_item ) {
	
					$success = $success and $room_item->renewFileIndex();
	
					$this->_updateProgressBar($count);
					unset($room_item);
					$room_item = $room_list->getNext();
				}
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
}	
?>