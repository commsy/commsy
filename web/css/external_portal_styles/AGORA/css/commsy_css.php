<?php
// $Id: commsy_room_css.php,v 1.28 2009/03/03 14:26:06 jschultze Exp $
//
// Release $Name:  $
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

header("Content-type: text/css");
// load required classes
chdir('../../../../..');
include_once('etc/cs_constants.php');
include_once('etc/cs_config.php');
include_once('classes/cs_environment.php');
include_once('functions/curl_functions.php');

// create environment of this page
$color = $cs_color['DEFAULT'];

// find out the room we're in
if (!empty($_GET['cid'])) {
   $cid = $_GET['cid'];
   $environment = new cs_environment();
   $environment->setCurrentContextID($cid);
   $room = $environment->getCurrentContextItem();
   $portal = $environment->getCurrentPortalItem();
   $color = $room->getColorArray();
}
?>

body {
    background: #99a; padding: 10px;

}

.commsy_body {
   border: 1px #889 solid;
   min-width: 756px;
   max-width: 84em;
   margin:auto;
}

.commsy_footer{
   background: #99a;
}

