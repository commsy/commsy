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


.small_font {8pt;}

/**************************************
**** commsy_right_boxes_css ***********
**************************************/
/*Panel Style*/
#commsy_panels .commsy_panel, #commsy_panel_form .commsy_panel{
   margin:0px;
}

#commsy_panels .panelContent, #commsy_panel_form .panelContent{
   padding:0px;
   overflow:hidden;
   position:relative;
}

.panelContent{
   <?php
   $current_browser = mb_strtolower($environment->getCurrentBrowser(), 'UTF-8');
   $current_browser_version = $environment->getCurrentBrowserVersion();
   if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.')) or (strstr($current_browser_version,'7.'))) ){
      echo('margin:-2px 0px 2px 0px;');
   }
   ?>
   font-size:8pt;
}

#commsy_panels .small, #commsy_panel_form .small{
   font-size:8pt;
}

#commsy_panels .panelContent div, #commsy_panel_form .panelContent div{
   position:relative;
   margin:0px;
   font-size:8pt;
}

#commsy_panels .commsy_panel .topBar, #commsy_panel_form .commsy_panel .topBar{
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24.png) repeat-x;');
   ?>
   background-color:<?php echo($color['tabs_background'])?>;
   color:<?php echo($color['tabs_title'])?>;
   padding: 0px 0px;
   margin:0px;
   height:20px;
   overflow:hidden;
}

#commsy_panels .commsy_panel .topBar span, #commsy_panel_form .commsy_panel .topBar span{
   vertical-align:baseline;
   color:<?php echo($color['tabs_title'])?>;
   font-weight:bold;
   float:left;
   padding-left:5px;
}
.topBar{
   font-size:10pt;
}

#commsy_panels .commsy_panel .topBar img, #commsy_panel_form .commsy_panel .topBar img{
   float:right;
   cursor:pointer;
}

#otherContent{  /* Normal text content */
   float:left;  /* Firefox - to avoid blank white space above panel */
   padding-left:10px;   /* A little space at the left */
}


#right_boxes_area{
   width:28%;
   float:right;
   padding-top:5px;
   padding-left:10px;
   vertical-align:top;
   text-align:left;
}


/* Right Boxes Style */
.right_box{
   background-color: <?php echo($color['boxes_background'])?>;
   margin:0px;
   padding-bottom:0px;
   font-size:10pt;
}

div.usage_info{
   border: 1px solid <?php echo($color['tabs_background'])?>;
   background-color: <?php echo($color['boxes_background'])?>;
   padding:5px 5px 10px 5px;
   font-size:8pt;
}


a.right_box_title {
   color:<?php echo($color['headline_text'])?>;
   font-weight:bold;
   font-size: 8pt;
   margin:0px;
}

div.right_box_title{
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24.png) repeat-x;');
   ?>
   background-color:<?php echo($color['tabs_background'])?>;
   line-height:18px;
   vertical-align:bottom;
   color:<?php echo($color['headline_text'])?>;
   padding: 2px 5px;
   font-weight:bold;
   font-size: 10pt;
}

div.index_forward_links{
   width:100%;
   text-align:center;
   font-weight:bold;
   color: <?php echo($color['tabs_title'])?>;
}

div.right_box_main{
   border: 1px solid <?php echo($color['tabs_background'])?>;
   <?php
   $current_browser = mb_strtolower($environment->getCurrentBrowser(), 'UTF-8');
   $current_browser_version = $environment->getCurrentBrowserVersion();
   if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.')) or (strstr($current_browser_version,'7.'))) ){
      echo('margin:-2px 0px 0px 0px;');
   }else{
      echo('margin:0px;');
   }
   ?>
   padding:3px 3px 3px 5px;
}

div.gauge {
   background-color: <?php echo($color['boxes_background'])?>;
   height:14px;
   margin: 0px;
   border: 1px solid #666;
   font-size:10px;
}
div.gauge-bar {
   background-color: <?php echo($color['tabs_background'])?>;
   height:14px;
   text-align: right;
   color:<?php echo($color['headline_text'])?>;
   font-size:10px;
}
span.index_system_link, a.index_system_link{
   color: <?php echo($color['tabs_title'])?>;
}
div.div_line{
   margin:10px 0px;
   border-top:1px solid black;
}

/**************************************
**** commsy_form_css ******************
**************************************/
td {
}

td.leftviews {
   padding-right: 5px;
   vertical-align: top;
}
td.rightviews {
   padding-left: 5px;
   vertical-align: top;
}

td.formfield{
   padding-bottom:5px;
   padding-top:10px;
}

#form_title{
   background:url(images/detail_fader_<?php echo($color['schema'])?>.gif) center repeat-x;
   background-color:<?php echo($color['tabs_background'])?>;
   color:<?php echo($color['tabs_title'])?>;
   vertical-align:top;
   font-weight:bold;
   font-size:14pt;
   white-space:nowrap;
   width:100%;
}

.form_title_field{
  font-size: 20px;
  font-family: Arial, Nimbus Sans L, sans-serif;
}


td.key{
   position:relative;
   padding-bottom:10px;
   padding-top:10px;
   vertical-align: baseline;
}

.list span.desc, .desc, .desc_usage {
   font-size: 8pt;
}

span.small_font{
   font-weight:normal;
   font-size:8pt;
}

a.select_link{
   font-size:8pt;
   color:<?php echo($color['tabs_focus'])?>;
}

span.select_link{
   font-size:8pt;
   color:<?php echo($color['tabs_focus'])?>;
}

td.buttonbar{
   /*background-color: none;*/
   padding-bottom:2px;
   padding-top:0px;
   white-space:nowrap;
}


div.netnavigation_title{
   padding:0px 3px;
   font-size:8pt;
   font-weight:bold;
   border-bottom:1px solid #B0B0B0; vertical-align:top;
   color: <?php echo($color['myarea_section_title'])?>;
   background-color: <?php echo($color['myarea_title_backround'])?>;
}

div.netnavigation_box{
   border:1px solid <?php echo($color['tabs_background'])?>;
   background-color: <?php echo($color['boxes_background'])?>;
}

div.netnavigation_list{
   padding:2px 3px;
   font-size:8pt;
   font-weight:normal;
}

div.netnavigation_list ul {
   margin-top: 0px;
   margin-left: 0px;
   margin-right: 0px;
   margin-bottom: 10px;
   padding-top: 0px;
   padding-bottom: 0px;
   padding-right: 0px;
   padding-left: 15px;
   list-style: circle; }

div.netnavigation_list li {
   margin-top: 2px;
}

.text{
   font-family: Arial, Nimbus Sans L, sans-serif;
   font-size:10pt;
}


td.key {
   color: <?php echo($color['myarea_section_title'])?>;
   font-weight:bold;
   white-space: nowrap;
}
span.key {
   color: <?php echo($color['myarea_section_title'])?>;
   font-weight:bold;
   white-space: nowrap;
}

#form{
   font-size:10pt;
   width:100%;
   margin:0px;
   padding:5px 5px;
   background-color: #FFFFFF;
   border: 1px solid <?php echo($color['tabs_background'])?>;
}

#form table{
   border-collapse:collapse;
}

#form tr.textarea, #form td {
   position:relative;
   vertical-align: top;
}

#form tr.textarea, #form td.key {
   position:relative;
   padding-top: 13px; }

#form tr.textarea, #form td.example {
   position:relative;
   padding-top: 13px; }

#form tr.radio, #form td {
   position:relative;
   vertical-align: top; }

#form tr.radio, #form td.key {
   position:relative;
   padding-top: 13px; }

#form tr.radio, #form td.example {
   position:relative;
   padding-top: 13px; }

.form tr.checkboxgroup td {
   position:relative;
   vertical-align: top; }

.form tr.checkboxgroup td.key {
   position:relative;
   padding-top: 13px; }

.form tr.checkboxgroup td.example {
   position:relative;
   padding-top: 13px; }


.form tr.button td {
   position:relative;
   padding: 1px 3px;
   background-color: <?php echo($color['table_background'])?>; }

.form tr.button td.example {
  position:relative;
  text-align: right; }

.form table.multiselect {
   position:relative;
   empty-cells: show;
   border-style: none;
   border-spacing: 0px; }

.form table.multiselect td {
   position:relative;
   border-style: none;
   margin: 0px;
   padding-top: 0px;
   padding-left: 0px;
   padding-bottom: 0px;
   padding-right: 10px; }

div.formdate{
   color:<?php echo($color['date_title'])?>;
   padding-top:7px;
   padding-left:2px;
   padding-bottom:2px;
   font-weight: bold;
}

div.formdate{
   color:<?php echo($color['date_title'])?>;
   padding-top:7px;
   padding-left:2px;
   padding-bottom:2px;
   font-weight: bold;
}

span.formcounter{
   color: <?php echo($color['myarea_section_title'])?>;
}

.formcounterfield{
   border:0;
   background-color: <?php echo($color['myarea_title_backround'])?>;
   color: <?php echo($color['myarea_section_title'])?>;
}

#MySortable{
    list-style: none;
    padding-left:0px;
    margin-left:0px;
    width:400px;
    z-index:1005;
}

#MySortableRoom{
    list-style: none;
    padding-left:0px;
    margin-left:0px;
    width:400px;
    z-index:1005;
}

li.form_checkbox_dhtml{
   margin-top:2px;
   border: 1px dotted <?php echo($color['tabs_background'])?>;
   background-color: <?php echo($color['boxes_background'])?>;
   cursor: move;
   width:400px;
   z-index:1005;
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

#otherContent{  /* Normal text content */
    float:left; /* Firefox - to avoid blank white space above panel */
    padding-left:10px;  /* A little space at the left */
}

/*List Layout*/
.list {
   border-collapse: collapse;
   width: 100%;
   font-size:10pt;
}

.list tr{
}
table.list, div.index_flash {
   width: 100%;
   font-size:10pt;
   border: 1px solid <?php echo($color['tabs_background'])?>;
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
   ?>
   background-color:<?php echo($color['tabs_background'])?>;
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

/**************************************
**** commsy_index_css *****************
**************************************/
/*Special Styles*/
div.indexdate{
   color:<?php echo($color['date_title'])?>;
   font-weight: bold;
}
div.restriction {
   padding: 1px 1px;
   font-weight:normal;
   font-size:10pt;
}

/*Font and Hyperlinks*/
.closed {
   color: <?php echo($color['hyperlink'])?>;
   font-size: 8pt;
}
a.head, a.head:hover{
   color: <?php echo($color['headline_text'])?>;
   font-weight:bold;
}
span.index_description{
   font-size:10pt;
   font-weight:normal;
}
a.index_link, a.index_link:hover{
   color: <?php echo($color['index_td_head_title'])?>;
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

/**************************************
**** commsy_home_css ******************
**************************************/
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
.homelist td {
   background-color: #FFFFFF;
   padding: 2px 3px;
   font-size:10pt;
}
.homelist td.even  {
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
table.homelist {
   width: 100%;
   font-size:10pt;
}

/**************************************
**** commsy_myarea_css ****************
**************************************/
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

/**************************************
**** commsy_scorm_css *****************
**************************************/

div.scorm_navigation_block {
  margin-top: 5px;
  margin-left: 10px;
}

/**************************************
**** fast search results **************
**************************************/
div.search_fast_results {
	background: #FFFFFF;
	border: 1px solid black;
	margin-left: -10px;
	margin-top: 5px;
	position: absolute;
	width: 17%;
	z-index: 50;
}



background-color:<?php echo($color['tabs_background'])?>;