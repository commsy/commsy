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
chdir('..');
include_once('etc/cs_constants.php');
include_once('etc/cs_config.php');

// create environment of this page
$color = $cs_color['DEFAULT'];

// find out the room we're in
if (!empty($_GET['cid'])) {
   $cid = $_GET['cid'];
   include_once('classes/cs_environment.php');
   $environment = new cs_environment();
   $environment->setCurrentContextID($cid);
   $room = $environment->getCurrentContextItem();
   if ( isset($room) ) {
      $color = $room->getColorArray();
   }
}
?>

div.myarea_frame {
   position:relative;
   width: 13.5em;
   margin:0px 0px 0px 5px;
   padding:0px; font-size: 10pt;
   border: 2px solid #C5C5C5;
}
div.myarea_headline {
   position:relative;
   width: 100%;
   margin:0px;
   padding:4px 0px 3px 0px;
   font-size: 10pt;
   font-weight: bold;
   background:url(images/layout/tab_menu_fader_aktiv_myarea.gif) repeat-x;
   background-color: #A2A2A2;
}

div.myarea_headline_title {
   padding: 0px 5px;
   color: #FFFFFF;
}

div.myarea_title {
   margin: 0px;
   padding: 3px 5px;
   font-style: italic;
   font-weight: bold;
   background-color: <?php echo($color['myarea_title_backround'])?>;
   border-bottom: 1px solid <?php echo($color['myarea_headline_background'])?>;
}

div.myarea_section_title {
   margin: 0px;
   padding: 3px 5px;
   color:black;
   background-color: <?php echo($color['myarea_title_backround'])?>;
   border-bottom: 1px solid <?php echo($color['myarea_headline_background'])?>;
}

div.myarea_content {
   margin: 0px;
   padding: 5px 5px 20px 5px;
   font-size: 9pt;
   background-color: #FCFCFC;
}

div.myarea_frame_bottom {
   position:relative;
   width: 13.5em;
   margin:0px 0px 0px 5px;
   padding:0px;
   font-size:1px;
   border-left: 2px solid #CBD0D6;
   border-right: 2px solid #CBD0D6;
   border-bottom: 2px solid #CBD0D6;
}

div.myarea_content_bottom {
   position:relative;
   width: 100%;
   margin:0px;
   padding:0px;
   background-color: <?php echo($color['myarea_content_backround'])?>;
}