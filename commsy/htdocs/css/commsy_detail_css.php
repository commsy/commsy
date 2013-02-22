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
   color: <?php echo($color['myarea_section_title'])?>;
   white-space: nowrap;
   font-size:10pt;
}

a.select_link, span.select_link{
   font-size:8pt;
   color:<?php echo($color['tabs_focus'])?>;
}
/* background for the search text yellow */
span.searched_text_yellow{
	font-weight:bold;
	/* font-size:9pt; */
	background-color:#FFFF00;
	color:#000000;
	padding:0px;
}
/* background for the search text green */
span.searched_text_green{
	font-weight:bold;
	/* font-size:9pt; */
	background-color:#77FF00;
	color:#000000;
	padding:0px;
}



/*Headlines*/
h3{
  margin:0px;
  font-size: 14pt;
}




/*Layout Detailview*/
table.detail td {
   vertical-align: baseline;
}

table.detail td.key {
   color: <?php echo($color['myarea_section_title'])?>;
   white-space: nowrap;
}

table.detail td.value p {
   margin: 0;
}

.detail_discussion_entries{
   padding-top:20px;
}

.subitemtitle{
   font-size: 12pt;
}
.steptitle{
   color: <?php echo($color['myarea_section_title'])?>;
   font-size: 12pt;
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
}

#annotation_form{
   background-color: #FFFFFF;
   padding:0px;
   margin:0px;
   border: 1px solid <?php echo($color['tabs_background'])?>;
}

#newest_link_box{
   background-color: #FFFFFF;
   padding:0px;
   margin:0px;
   border: 1px solid <?php echo($color['tabs_background'])?>;
}

.annotation_pagetitle{
   padding-top:5px;
   font-size: 16pt;
   font-weight:bold;
}


div.detail_sub_items_title{
   margin-top:10px;
   padding:20px 11px 3px 11px;
}

div.sub_item_main{
  margin:0px;
  padding: 0px 0px;
  background-color: <?php echo($color['content_background'])?>;
}
.detail_annotation_table{
   width:100%;
   padding:5px 5px;
   border-collapse:collapse;
   margin-bottom:20px;
}


#detail_content, .detail_content{
   margin:0px;
   padding:5px 5px;
   background-color: #FFFFFF;
   border: 1px solid <?php echo($color['tabs_background'])?>;
}

#detail_annotations{
   margin:40px 0px 0px 0px;
   padding:0px;
   background-color: #FFFFFF;
   border: 1px solid <?php echo($color['tabs_background'])?>;
   width: 100%;
}

#detail_assessments_headline {
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_32.png) repeat-x;');
   ?>background-color:<?php echo($color['tabs_background'])?>;
   color:<?php echo($color['headline_text'])?>;
   padding:4px 5px 5px 5px;
   border:0px;
}

#detail_assessments {
   margin:40px 0px 0px 0px;
   padding:0px;
   background-color: #FFFFFF;
   border: 1px solid <?php echo($color['tabs_background'])?>;
   width: 100%;
}

#detail_headline{
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_32.png) repeat-x;');
   ?>background-color:<?php echo($color['tabs_background'])?>;
   color:<?php echo($color['headline_text'])?>;
   vertical-align:top;
}

#detail_annotation_headline{
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_32.png) repeat-x;');
   ?>background-color:<?php echo($color['tabs_background'])?>;
   color:<?php echo($color['headline_text'])?>;
   padding:4px 5px 5px 5px;
   border:0px;
}


.contenttitle, .annotationtitle{
   padding:0px;
   margin:0px;
   font-size: 14pt;
}


.detail_creator_information{
   padding:0px;
   background-color: #FFFFFF;
   margin-bottom:0px;
   vertical-align:top;
}


/*Creator Information*/
table.creator_info td {
   vertical-align: middle;
   font-size:10pt;
}

table.creator_info td.key {
   color: <?php echo($color['myarea_section_title'])?>;
}

.ims_key{
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


.no_netnavigation_panel{
   background-color: <?php echo($color['boxes_background'])?>;
   border-left:1px solid #B0B0B0;
   border-right:1px solid #B0B0B0;
   border-bottom:1px solid #B0B0B0;
   margin:0px;
   width:99%;
   font-size:8pt;
}

.no_netnavigation_panel ul{
   margin: 3px 0px 2px 2px;
   padding: 0px 0px 3px 15px;
   list-style: circle;
}

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

.netnavigation {
    font-size:0.7em;
    padding:0px;
    overflow:hidden;
    position:relative;
    clear:both;
}

.netnavigation div{
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

li.detail_list_entry{
   background-color:#EFEFEF;
}

li.path_list{
   background-color: #dddddd;
}

/*List Layout*/
.list {
   width: 100%;
   font-size:10pt;
   border-collapse: collapse;
}

.list tr{
}

table.list {
   width: 100%;
   font-size:10pt;
}

.list td, td.odd {
   background-color: #FFFFFF;
   padding: 2px 3px;
   font-size:10pt;
}
.list td.even  {
   background-color: <?php echo($color['list_entry_even'])?>;
}
.list td.head {
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24.png) repeat-x;');
   ?>background-color:<?php echo($color['tabs_background'])?>;
   color: <?php echo($color['headline_text'])?>;
   border-bottom: none;
   line-height:17px;
   padding: 3px 3px;
   font-weight:bold;
   white-space:nowrap;
}
.list td.head_nav {
   border-bottom: none;
   padding: 3px 3px;
   font-weight:bold;
   text-align: right;
}
.list td.foot_left {
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_32.png) repeat-x;');
   ?>background-color: <?php echo($color['tabs_background'])?>;
   color: <?php echo($color['headline_text'])?>;
   border-bottom: none;
   padding: 2px 2px;
   font-weight:bold;
}
.list td.foot_right {
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_32.png) repeat-x;');
   ?>background-color: <?php echo($color['tabs_background'])?>;
   color: <?php echo($color['headline_text'])?>;
   border-bottom: none;
   padding: 2px 2px;
   font-weight:bold;
   text-align: right;
}
.even{
   background-color: <?php echo($color['list_entry_even'])?>;
}

div.gauge {
   background-color: <?php echo($color['boxes_background'])?>;
   height:12px;
   margin-left: 10px;
   margin-right: 0px;
   margin-top: 0px;
   margin-bottom: 0px;
   border: 1px solid #666;
   font-size:10px;
}
div.gauge-bar {
   background-color: <?php echo($color['tabs_background'])?>;
   height:12px;
   text-align: right;
   color:<?php echo($color['headline_text'])?>;
   font-size:10px;
}

td.form_view_detail_left {
   vertical-align: top;
   padding-right: 5px;
   width: 1px;
}

div.form_view_detail_formelement {
   padding-bottom: 5px;
}

div.form_view_detail_formelement span.titlefield {
   font-size: 12pt;
   font-weight: bold;
}

table.form_view_detail {
   width: 100%;
}
?>