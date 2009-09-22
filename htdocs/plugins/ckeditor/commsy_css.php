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

header("Content-type: text/css");

// load required classes
chdir('../../..');
include_once('etc/cs_constants.php');
include_once('etc/cs_config.php');
include_once('classes/cs_environment.php');

// create environment of this page
$color = $cs_color['DEFAULT'];

// find out the room we're in
if (!empty($_GET['cid'])) {
   $cid = $_GET['cid'];
   $environment = new cs_environment();
   $environment->setCurrentContextID($cid);
   $room = $environment->getCurrentContextItem();
   $color = $room->getColorArray();
}
?>

div.form_view_detail_formelement .cke_toolgroup {
   margin-right: 5px;
}

table#form tr.textarea #cke_27_text {
   width: 40px;
}

<?php
if ( !empty($environment)
     and strtoupper($environment->getSelectedLanguage()) == 'DE'
   ) {
   echo('div.form_view_detail_formelement #cke_25_text {'."\n");
   echo('   width: 39px;'."\n");
   echo('}'."\n");
   echo('div.form_view_detail_formelement #cke_26_text {'."\n");
   echo('   width: 48px;'."\n");
   echo('}'."\n");
   echo('div.form_view_detail_formelement #cke_27_text {'."\n");
   echo('   width: 33px;'."\n");
   echo('}'."\n");
}
?>