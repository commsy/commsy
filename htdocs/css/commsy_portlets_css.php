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
chdir('../..');
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

/*Portlets*/
.column {
	float: left;
	padding-bottom: 20px;
}

.portlet {
	margin: 0 20px 20px 0;
    -moz-border-radius-topleft: 5px;
    -webkit-border-top-left-radius: 5px;
	-khtml-border-radius-topleft:5px;
    -moz-border-radius-topright: 5px;
    -webkit-border-top-right-radius: 5px;
	-khtml-border-radius-topright:5px;
}

.portlet-configuration {
   margin: 0 20px 0 0;
    -moz-border-radius-topleft: 5px;
    -webkit-border-top-left-radius: 5px;
   -khtml-border-radius-topleft:5px;
    -moz-border-radius-topright: 5px;
    -webkit-border-top-right-radius: 5px;
   -khtml-border-radius-topright:5px;
}

.portlet-header {
	margin: 0em;
	padding: 4px 6px;
    -moz-border-radius-topleft: 5px;
    -webkit-border-top-left-radius: 5px;
	-khtml-border-radius-topleft:5px;
    -moz-border-radius-topright: 5px;
    -webkit-border-top-right-radius: 5px;
	-khtml-border-radius-topright:5px;
	cursor: move;
}

.portlet-header-configuration {
   margin: 0em;
   padding: 4px 6px;
    -moz-border-radius-topleft: 5px;
    -webkit-border-top-left-radius: 5px;
   -khtml-border-radius-topleft:5px;
    -moz-border-radius-topright: 5px;
    -webkit-border-top-right-radius: 5px;
   -khtml-border-radius-topright:5px;
}

.ui-widget-header {
    <?php
    echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24.png) repeat-x;');
    ?>
    background-color: <?php echo($color['tabs_background'])?>;
    color:<?php echo($color['headline_text'])?>;
    border:0px;
}

.portlet-header .ui-icon {
	float: right;
	cursor: auto;
}
.portlet-content { padding: 0.4em; }
.ui-sortable-placeholder { border: 1px dotted black; visibility: visible !important; height: 50px !important; }
.ui-sortable-placeholder * { visibility: hidden; }


.droppable_item_hover{
  background-color: #dddddd;
}

.droppable_list{
   line-height: 20px;
   padding-left:5px;
   margin:0px;
   vertical-align:center;
}

.droppable_list_newest_entries{
   line-height: 20px;
   padding-left:5px;
   margin:0px;
   vertical-align:center;
}

.description-background{
	background-color:#EEEEEE;
	margin: 0px 0px 5px 0px;
	padding:3px;
}


