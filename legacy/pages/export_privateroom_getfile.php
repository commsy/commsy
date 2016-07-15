<?php
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

$currentUserItem = $environment->getCurrentUserItem();
$privateroom_manager = $environment->getPrivateRoomManager();
$privateroom_item = $privateroom_manager->getRelatedOwnRoomForUser($currentUserItem, $environment->getCurrentPortalID());
header('Content-disposition: attachment; filename=commsy_export_import_'.$privateroom_item->getItemID().'.zip');
header('Content-type: application/zip');

$zipfile = 'var/temp/commsy_export_import_'.$privateroom_item->getItemID().'.zip';
readfile($zipfile);
exit;