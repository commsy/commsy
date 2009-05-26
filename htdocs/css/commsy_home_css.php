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

h2.pagetitle{
  margin-bottom:0px;
  margin-top: 0px;
  font-size: 16pt;
}

div.homedate{
   color:<?php echo($color['date_title'])?>;
   padding-top:3px;
   padding-left:5px;
   padding-bottom:3px;
   font-size:12pt;
   font-weight: bold;
}
div.home_extra_tool_headline{
   color:<?php echo($color['date_title'])?>;
   padding-top:5px;
   padding-bottom:1px;
   font-weight: bold;
}
td.leftviews {
   padding-right: 5px;
   vertical-align: top;
}
td.rightviews {
   padding-left: 5px;
   vertical-align: top;
}

.closed {
   color: <?php echo($color['hyperlink'])?>;
   font-size: 8pt; }

.desc {
   font-size: 8pt; }

.desc_usage {
   font-size: 8pt; }

span.home_description{
   font-size:8pt;
   font-weight:normal;
   color: <?php echo($color['info_color'])?>;
}

span.home_forward_links{
   font-weight:bold;
   color: <?php echo($color['info_color'])?>;
}
div.homeheader {
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24.png) repeat-x;');
   ?>
   background-color:<?php echo($color['tabs_background'])?>;
   color: <?php echo($color['headline_text'])?>;
   padding: 3px 3px;
   line-height: 18px;
   vertical-align:middle;
   font-weight:bold;
   white-space:nowrap;
}

div.homerubric {
}


/*List Layout*/
.homelist {
   border-collapse: collapse;
   width: 100%;
   font-size:10pt;
   margin:0px;
   padding:0px;
}

.homelist tr{
}

table.homelist, div.index_flash {
   width: 100%;
   font-size:10pt;
}

.homelist td, td.odd {
   background-color: #FFFFFF;
   padding: 2px 3px;
   font-size:10pt;
}
.homelist td.even  {
   background-color: <?php echo($color['list_entry_even'])?>;
}

.even{
   background-color: <?php echo($color['list_entry_even'])?>;
}
.homelist td.head {
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24.png) repeat-x;');
   ?>
   background-color:<?php echo($color['tabs_background'])?>;
   color: <?php echo($color['headline_text'])?>;
   border-bottom: none;
   line-height:17px;
   padding: 3px 3px;
   font-weight:bold;
   white-space:nowrap;
}
.homelist td.head_nav {
   border-bottom: none;
   padding: 3px 3px;
   font-weight:bold;
   text-align: right;
}
.homelist td.foot_left {
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_32.png) repeat-x;');
   ?>
   background-color: <?php echo($color['tabs_background'])?>;
   color: <?php echo($color['headline_text'])?>;
   border-bottom: none;
   padding: 2px 2px;
   font-weight:bold;
}
.homelist td.foot_right {
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_32.png) repeat-x;');
   ?>background-color: <?php echo($color['tabs_background'])?>;
   color: <?php echo($color['headline_text'])?>;
   border-bottom: none;
   padding: 2px 2px;
   font-weight:bold;
   text-align: right;
}
