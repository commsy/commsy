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
body {
   margin: 0px;
   padding: 0px;
   font-family: Arial, Helvetica, sans-serif;
   font-size: 8pt;
   background-color: white;
}


td {
   xborder: 2px solid red;
   font-family: Arial, Helvetica, sans-serif;
   font-size: 8pt; }

a {
   color: <?php echo($color['hyperlink'])?>;
   text-decoration: none; }

a:hover {
   text-decoration: underline; }

a:active {
   color: <?php echo($color['hyperlink'])?>;
   text-decoration: underline; }

img {
   border: 0px; }


/* General purpose styles */

.infocolor{
   color: <?php echo($color['info_color'])?>;
}

.infoborder{
   border-top: 1px solid <?php echo($color['info_color'])?>;
}
td.infoborderbottom{
   border-top: 1px solid <?php echo($color['info_color'])?>;
}

table.detail td {
   vertical-align: baseline; }

table.detail td.key {
   color: <?php echo($color['disabled'])?>;
   white-space: nowrap; }

.disabled, .inactive, .key {
   color: <?php echo($color['disabled'])?>; }

.changed {
   color: #7E7E7E;
   font-size: 6pt; }

.required {
   color: #7E7E7E;
   font-weight: bold; }

div.main{
  margin-top:0px;
  margin-bottom:0px;
  padding-left: 5px;
  padding-right: 5px;
}

div.content_fader{
  width: 100%;
  margin-top:0px;
  xbackground-image:url(images/layout/bg-fader_<?php echo($color['schema'])?>.gif);
  xbackground-repeat:repeat-x;
  xbackground-color: <?php echo($color['content_background'])?>;
  padding-left:5px;
  padding-right:5px;"
}

div.content{
  padding:0px;
  margin:0px;
  xbackground-color: <?php echo($color['content_background'])?>;
}


h1{
   margin-top:0px;
   margin-bottom:0px;
   padding-left:10px;
   padding-top:0px;
   padding-bottom:0px;
   font-size:20px;
}

h2.pagetitle{
  margin-bottom:0px;
  margin-top: 0px;
  font-size: 12pt;
  font-family: verdana, arial, sans-serif;
}

div.pagetitle{
  margin-bottom:0px;
  margin-top: 0px;
  font-size: 10pt;
  font-family: verdana, arial, sans-serif;
}

div.div_line{
   border-top:1px solid black;
   margin-top:10px;
   margin-bottom:10px;"
}
.normal{
   font-size: 8pt;
}
.desc {
   font-size: 6pt; }

.bold{
   font-size: 8pt;
   font-weight: bold;
}

div.frame_bottom {
   position:relative;
   padding-top:0px;
   padding-bottom:0px;
   padding-left:3px;
   padding-right:3px;
   margin:0px;
   font-size: 1px;
   font-weight: bold;
   border-left: 2px solid #CBD0D6;
   border-right: 2px solid #CBD0D6;
   border-bottom: 2px solid #CBD0D6;
}
div.content_bottom {
   position:relative;
   width: 100%;
   padding-top:0px;
   padding-bottom:3px;
   padding-left:0px;
   padding-right:0px;
   margin:0px;
   font-weight: bold;
}

div.top_of_page{
   padding-left:3px; padding-bottom:10px;
   color: <?php echo($color['info_color'])?>;
}

div.top_of_page span{
   font-size: 6pt;
   font-weight:normal;
   font-style:normal;
   color: <?php echo($color['info_color'])?>;
}
div.top_of_page a{
   font-weight:bold;
   font-style:italic;
   color: <?php echo($color['info_color'])?>;
}

.list {
   border-collapse: collapse;
   width: 100%; }

.list td.even  {
   background-color: #E5E5E5;
   padding: 2px 3px; }

.list td.odd  {
   xbackground-color: <?php echo($color['list_entry_odd'])?>;
   padding: 2px 3px; }

.list td {
   xbackground-color: <?php echo($color['list_entry_odd'])?>;
   padding: 2px 3px; }

.list td.head {
   background-color: #7E7E7E;
   color: white;
   border-bottom: none;
   padding: 3px 3px;
   font-weight:bold;
   white-space:nowrap; }


div.detail_sub_items_title{
   padding:3px 11px;
   border-bottom:1px solid #B0B0B0; vertical-align:top;
   background-color: <?php echo($color['myarea_title_backround'])?>;
}

h2.pagetitle{
  margin-bottom:0px;
  margin-top: 0px;
  font-size: 12pt;
  font-family: verdana, arial, sans-serif;
}

span.sub_item_pagetitle{
  margin-bottom:0px;
  margin-top: 0px;
  font-size: 12pt;
  font-weight:bold;
  font-family: verdana, arial, sans-serif;
  color: <?php echo($color['myarea_section_title'])?>;
}

div.gauge {
   xbackground-color: <?php echo($color['boxes_background'])?>;
   height:5px;
   margin-left: 10px;
   margin-right: 10px;
   margin-top: 3px;
   margin-bottom: 3px;
   border: 1px solid #666;
   font-size:8px;
}
div.gauge-bar {
   background-color: #7E7E7E;
   height:5px;
   text-align: right;
   color:<?php echo($color['headline_text'])?>;
   font-size:8px;
}