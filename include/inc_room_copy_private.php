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

################################################################
# copy a room (private rooms only)
# like a template (config and data but no user and rooms)
# into a new private room or existing room
################################################################

// initialisation
$old_room_id = $_POST['template_select'];
$room_manager = $environment->getPrivateRoomManager();
if ( $old_room_id > 99 ) {
   $old_room = $room_manager->getItem($old_room_id);
} elseif ( $old_room_id == -1 ) {
   $old_room = $room_manager->getNewItem();
   $old_room->setItemID(-1);
} else {
   include_once('functions/error_functions.php');
   trigger_error('template room id is not valid',E_USER_ERROR);
}
if ( isset($context_item) ) {
   $new_room = $context_item;
} elseif ( isset($item) ) {
   $new_room = $item;
} elseif ( isset($room_item) ) {
   $new_room = $room_item;
} else {
   include_once('functions/error_functions.php');
   trigger_error('current room is not valid',E_USER_ERROR);
}
$current_user_item = $environment->getCurrentUserItem();
$creator_id = $current_user_item->getItemID();

// copy room settings
include_once('include/inc_room_copy_config.php');

// save new room
$new_room->save();

// copy data
if ( $old_room->getItemID() > 99 ) {
   include_once('include/inc_room_copy_data.php');
}
?>