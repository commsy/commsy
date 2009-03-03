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

/* Links and Text Styles*/
.detail_system_link{
   color: <?php echo($color['tabs_title'])?>;
}

a.head, a.head:hover{
   color: <?php echo($color['headline_text'])?>;
}

a.detail_link, a.detail_link:hover{
   color: <?php echo($color['index_td_head_title'])?>;
}

.list span.desc, .desc_usage, span.small_font {
   font-weight:normal;
   font-size: 8pt;
}

span.index_description{
   font-size:8pt;
   font-weight:normal;
   color: <?php echo($color['myarea_section_title'])?>;
}

span.detail_forward_links, span.creator_information_key, .multiupload_discussion_detail{
   font-weight:bold;
   color: <?php echo($color['myarea_section_title'])?>;
   white-space: nowrap;
}

a.select_link, span.select_link{
   font-size:8pt;
   color:<?php echo($color['tabs_focus'])?>;
}


/*Headlines*/
h3{
  margin:0px;
  font-size: 14pt;
  font-family: arial, Nimbus Sans L, sans-serif;
}



/*Layout Detailview*/
table.detail td {
   vertical-align: baseline;
}

table.detail td.key {
   color: <?php echo($color['myarea_section_title'])?>;
   font-weight:bold;
   white-space: nowrap;
}

table.detail td.value p {
   margin: 0;
}

img.portrait {
   border: 1px solid black;
   float: right;
   width: 150px;
}
img.portrait2 {
   border: 1px solid black;
   float: right;
}

td.key {
   color: <?php echo($color['myarea_section_title'])?>;
   font-weight:bold;
   white-space: nowrap;
}

span.formcounter{
   color: <?php echo($color['myarea_section_title'])?>;
}

.formcounterfield{
   border:0;
   background-color: <?php echo($color['content_background'])?>;
   color: <?php echo($color['myarea_section_title'])?>;
}


/*SubItems*/
span.sub_item_pagetitle, span.sub_item_description{
  margin:0px;
  font-size: 14pt;
  font-weight:bold;
  font-family: arial, Nimbus Sans L, sans-serif;
  color: <?php echo($color['myarea_section_title'])?>;
}

div.detail_sub_items_title{
   margin-top:10px;
   padding:3px 11px;
   background-color: <?php echo($color['myarea_title_backround'])?>;
   border-bottom: 1px solid <?php echo($color['myarea_headline_background'])?>;
}

div.sub_item_main{
  margin:0px;
  padding: 0px 8px;
  background-color: <?php echo($color['content_background'])?>;
}


/*Creator Information*/
table.creator_info td {
   vertical-align: middle;
}

table.creator_info td.key {
   font-weight:bold;
   color: <?php echo($color['myarea_section_title'])?>;
}

.ims_key{
   font-weight:bold;
	color: <?php echo($color['myarea_section_title'])?>;
}

.gauge-wrapper {
   width: 100%;
}

.gauge-wrapper td {
   white-space: nowrap;
   color: <?php echo($color['myarea_section_title'])?>;
}

/* Netnavigation */

.netnavigation{
   background-color: <?php echo($color['boxes_background'])?>;
}

.netnavigation .netnavigation_panel, .netnavigation_panel_top{
   background-color: <?php echo($color['boxes_background'])?>;
   border-left:1px solid #B0B0B0;
   border-right:1px solid #B0B0B0;
   border-bottom:1px solid #B0B0B0;
    margin:0px;
   width:99%;
   font-size:8pt;
}

.netnavigation .panelContent{
   font-size:0.7em;
    padding:0px;
    overflow:hidden;
    position:relative;
    clear:both;
}

.netnavigation .panelContent div{
   position:relative;
   border-top:1px solid #B0B0B0;
}

.netnavigation .netnavigation_panel .tpBar{
   background-color:<?php echo($color['myarea_title_backround'])?>;
   color:<?php echo($color['myarea_section_title'])?>;
   vertical-align:top;
   padding: 0px 0px;
   height:16px;
    padding-right:1px;
    overflow:hidden;
}

.netnavigation .netnavigation_panel ul, .netnavigation_panel_top ul {
   margin: 3px 0px 2px 2px;
   padding: 0px 0px 3px 15px;
   list-style: circle;
}

.netnavigation .netnavigations_panel li {
   margin-top: 2px;
}

.netnavigation .netnavigation_panel .tpBar span{
   line-height:16px;
   font-size:8pt;
    vertical-align:baseline;
   color:<?php echo($color['myarea_section_title'])?>;
    float:left;
    padding-left:5px;
}

.netnavigation .netnavigation_panel .tpBar img{
    float:right;
    cursor:pointer;
}

li.path_list{
   background-color: #dddddd;
}

.detail_annotations{
	background-color: #FFFFFF;
   border: 1px solid <?php echo($color['tabs_background'])?>;
}
