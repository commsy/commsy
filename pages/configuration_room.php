<?PHP
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

// include all classes and functions needed for this script
include_once('functions/date_functions.php');
include_once('functions/text_functions.php');

// translation - object
$translator = $environment->getTranslationObject();

// this script can be activate with get parameters
if (!empty($_GET['automatic'])) {
   $status = $_GET['automatic'];
   $command = 'automatic';
} else {
   $command = '';
}

if (!empty($_GET['iid'])) {
   $iid = $_GET['iid']; // item id of the room
}

$manager = $environment->getRoomManager();
$room = $manager->getItem($iid);
$archive = false;
if ( !isset($room) ) {
   $manager = $environment->getPrivateRoomManager();
   $room = $manager->getItem($iid);
   unset($manager);
   if ( !isset($room) ) {
      $zzz_manager = $environment->getZzzRoomManager();
      $room = $zzz_manager->getItem($iid);
      unset($zzz_manager);
      $archive = true;
   }
}
$current_user = $environment->getCurrentUserItem();
$context_item = $environment->getCurrentContextItem();

// error if room is already deleted
if (empty($command) and $room->getDeletionDate()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ERROR_ROOM_IS_DELETED'));
   $page->add($errorbox);
   $command = 'error';
}

if ( $current_user->isModerator() or $current_user->isRoot() ) {
   if ($command == 'automatic') {
      // change room status
      if ($status == 'lock') {
         if ($archive) {
            $environment->toggleArchiveMode();
         }
      	$room->lock();
      	if ($archive) {
            $room->save();
      		$environment->toggleArchiveMode();
         }
      } elseif ($status == 'unlock') {
         if ($archive) {
            $environment->toggleArchiveMode();
         }
      	$room->unlock();
         if ($archive) {
            $room->save();
         	$environment->toggleArchiveMode();
         }
      } elseif ($status == 'delete') {
         if ($archive) {
            $environment->toggleArchiveMode();
         }
      	$room->delete();
         if ($archive) {
            $environment->toggleArchiveMode();
         }
      } elseif ($status == 'undelete') {
         if ($archive) {
            $environment->toggleArchiveMode();
         }
      	$room->undelete();
         if ($archive) {
            $environment->toggleArchiveMode();
         }
      }elseif ($status == 'archive') {
         if ( !$room->isTemplate() ) {
            $room->moveToArchive();
         }

         // TBD: close wiki
      
      }elseif ($status == 'open') {
         if ($archive) {
            $room->backFromArchive();
         } else {
            $room->open();

            // Fix: Find Group-Rooms if existing
            if( $room->isGrouproomActive() ) {
               $groupRoomList = $room->getGroupRoomList();

               if( !$groupRoomList->isEmpty() ) {
                  $room_item = $groupRoomList->getFirst();

                  while($room_item) {
                     // All GroupRooms have to be opened too
                     $room_item->open();
                     $room_item->save();

                     $room_item = $groupRoomList->getNext();
                  }
               }
            }
         }
         
         // TBD: open wiki

      } else {
         include_once('functions/error_functions.php');
         trigger_error('automatic mode is not defined, E_USER_ERROR');
      }
      $room->save();

      // back to index pages
      $history = $session->getValue('history');
      $params = $history[0]['parameter'];
      if ($status == 'delete') {
         unset($params['room_id']);
      }
      redirect($history[0]['context'],$history[0]['module'],$history[0]['function'],$params);
   } else {
      include_once('functions/error_functions.php');
      trigger_error('no automatic status set',E_USER_ERROR);
   }
} else {
   include_once('functions/error_functions.php');
   trigger_error('you don\'t have the permission to do that',E_USER_ERROR);
}
?>