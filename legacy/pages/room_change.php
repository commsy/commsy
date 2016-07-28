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

// Verify parameters for this page
if (!empty($_POST['room_id'])) {
   if ($_POST['room_id'] != -1) {
      $context_id = $_POST['room_id'];	
	} else {
	   //An empty row is selected- return to last position
	   $session_item = $environment->getSessionItem();
	   $history = $session_item->getValue('history');	
      redirect($history[0]['context'],$history[0]['module'],$history[0]['function'],$history[0]['parameter']);	
	}
} else {
   $context_id = $environment->getServerID();
}

header('Location: room/'.$context_id);
header('HTTP/1.0 302 Found');
exit();