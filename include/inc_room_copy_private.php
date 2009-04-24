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
$old_room = $room_manager->getItem($old_room_id);
$new_room = $context_item;
$current_user_item = $environment->getCurrentUserItem();
$creator_id = $current_user_item->getItemID();

// copy room settings
include_once('include/inc_room_copy_config.php');

// save new room
$new_room->save();

// copy data
include_once('include/inc_room_copy_data.php');
?>