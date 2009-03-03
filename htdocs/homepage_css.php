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
include_once('classes/cs_environment.php');

// create environment of this page
$environment = new cs_environment();

$color_mod = 'normal';
if (isset($_GET['color_mod']) AND $_GET['color_mod'] == 'temp') {
   $color_mod = 'temp';
}

// find out the room we're in
if (!empty($_GET['cid'])) {
   $cid = $_GET['cid'];
} else {
   $cid = 0;
}

// load settings for a room
$environment->setCurrentContextID($cid);
$room = $environment->getCurrentContextItem();

if (!isset($room)) {
   // do nothing
   // so colours will be used out of configfile cs_config.php
} else {
   $color = $room->getColorArray();
   if (!empty($color)) {
      $cs_color['background'] = $color['content_background'];
   }
   if (!empty($color)) {
      $cs_color['text'] = '#000000';
   }
   if (!empty($color)) {
      $cs_color['hyperlink'] = $color['hyperlink'];
   }
   if (!empty($color)) {
      $cs_color['table_head'] = $color['tabs_background'];
   }
   $background = $cs_color['background'];
}

?>
body {
   margin: 0px;
   font-family: Arial, Helvetica, "Nimbus Sans L", sans-serif;
   font-size: 10pt;
   background-color: white }

table {
   border-collapse: collapse;
}

td {
   font-family: Arial, Helvetica, "Nimbus Sans L", sans-serif;
   font-size: 10pt; }

a {
   color: <?php echo($cs_color['hyperlink'])?>;
   text-decoration: none; }

a:hover {
   text-decoration: underline; }

a:visited {
   color: <?php echo($cs_color['hyperlink'])?>; }

a:active {
   color: <?php echo($cs_color['hyperlink'])?>;
   text-decoration: underline; }

img {
   border: 0px; }

.bold {
   font-weight: bold;
}

.disabled {
   color: #666;
}

div.main {
   border: 2px solid <?php echo($cs_color['table_head'])?>;
   background-color: #FFFFFF;
   position: relative;
   top: -2px;
   left: -2px;
}

td.header {
   font-weight: bold;
   font-size: xx-large;
   padding-top: 10px;
   padding-left: 10px;
   background-color: <?php echo($background)?>;
}

hr.content {
   width: 90%;
   border: 1px dashed <?php echo($cs_color['table_head'])?>;
}

img.logo {
   vertical-align: middle;
}

span.header {
   vertical-align: middle;
}

div.navigation {
   margin-top: 20px;
   padding: 5px;
   border-left: 2px dashed <?php echo($cs_color['table_head'])?>;
   margin-left: 20px;
}

div.navigation_link {
   margin-top: 4px;
}

div.navigation_login {
   margin-top: 20px;
   padding: 5px;
   border-left: 2px dashed <?php echo($cs_color['table_head'])?>;
   margin-left: 20px;
}
div.navigation_login_box {
	width: 11.0em;
	background-color: #F5F5F5;
	border: 2px solid rgb(213, 213, 213);
	padding: 0px;
	margin: 0px;
}
img.navigation_login_box {
	border-width: 0px;
	margin-left: 5px;
	margin-top: 1px;
}

td.navigation_login_box {
	padding: 5px 5px 10px 5px;
	vertical-align: middle;
}

div.navigation_link_second {
   font-size: 8pt;
}

td.navigation {
   vertical-align: top;
   text-align: left;
   width: 20%;
   background-color: <?php echo($background)?>;
}

td.content {
   vertical-align: top;
   width: 80%;
   background-color: <?php echo($background)?>;
}
div.content {
   padding-left: 10px;
}

table.homepage {
   width: 100%;
}

input.form_title {
   text-weight: bold;
   font-size: x-large;
}
h2 {
   font-size: x-large;
}

ul.detail {
	margin: 2px 10px 10px 10px;
	padding: 2px 10px 10px 10px;
}

/* footer */
div.footer {
   font-size: 8pt;
}
td.footer {
   padding-top: 10px;
   text-align: center;
   background-color: <?php echo($background)?>;
}
hr.footer {
   width: 50%;
   border: 1px dashed <?php echo($cs_color['table_head'])?>;
}
td.empty {
   background-color: <?php echo($background)?>;
}


/* Action-links in index and detail views */
div.actions {
   position:relative;
   float:right;
   padding: 4px 2px;
   margin-top: 0px;
   border-left: 1px solid #666;
   border-bottom: 1px solid #666;
   font-size: 8pt;
}

div.actions a {
   padding: 2px;
}

div.actions .disabled {
   padding: 2px;
}

div.shadow {
   background-color: #8e8e8e;
   border-right: 1px solid #959595;
   border-bottom: 1px solid #959595;
   padding: 0px;
   margin: 0px;
   }
div.shadow2 {
   background-color: #aaa;
   border-right: 1px solid #bebebe;
   border-bottom: 1px solid #bebebe;
   padding: 0px;
   margin: 0px;
   }
div.shadow3 {
   background-color: #d1d1d1;
   border-right: 1px solid #e1e1e1;
   border-bottom: 1px solid #e1e1e1;
   padding: 0px;
   margin: 0px;
   }
div.shadow4 {
   background-color: #ededed;
   border-right: 1px solid #f6f6f6;
   border-bottom: 1px solid #f6f6f6;
   padding: 0px;
   margin: 0px;
   }
div.shadow5 {
   background-color: #fcfcfc;
   border-right: 1px solid #ffffff;
   border-bottom: 1px solid #ffffff;
   margin-left: 20px;
   margin-top: 20px;
   width: 70em;
}

div.site_footer {
   margin-left: 20px;
   font-size: 8pt;
}

div.project-gauge {
   width: 100%;
   margin: 5px 0px;
   border: 0px;
}

div.project-gauge-bar {
   background-color: <?php echo($cs_color['table_head'])?>;
#   background-color: grey;
   text-align: right;
   font-size: 4pt;
   color: black;
}


<?php
// Improve layout for Safari
if ( mb_strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') === false ) {
   echo('/* All input fields have the same font setting */'.LF);
   echo('input, textarea {'.LF);
   echo('   font-family: Arial, Helvetica, "Nimbus Sans L", sans-serif;'.LF);
   echo('   font-size: 10pt; }'.LF);
}
?>