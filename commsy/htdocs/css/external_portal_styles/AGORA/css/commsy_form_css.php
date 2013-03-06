<?php
// $Id: commsy_form_css.php,v 1.8 2009/03/03 14:26:06 jschultze Exp $
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
   background-color:#F7F7F7;
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

.list span.desc {
   font-size: 8pt; }

.desc {
   font-size: 8pt; }

.desc_usage {
   font-size: 8pt; }

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
   background-color: none;
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
   background-color: #F7F7F7;
}

div.netnavigation_box{
   border:1px solid #C6C7CD;
   background-color: white;
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
   border: 1px solid #C6C7CD;
}

#form table{
	border-collapse:collapse;
}

#form tr.textarea, td {
   position:relative;
   vertical-align: top;
}

#form tr.textarea, td.key {
   position:relative;
   padding-top: 13px; }

#form tr.textarea, td.example {
   position:relative;
   padding-top: 13px; }

#form tr.radio, td {
   position:relative;
   vertical-align: top; }

#form tr.radio, td.key {
   position:relative;
   padding-top: 13px; }

#form tr.radio, td.example {
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
   background-color: #F7F7F7; }

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
   background-color: #C6C7CD;
   color: <?php echo($color['myarea_section_title'])?>;
}

#MySortable{
	 list-style: none;
    padding-left:0px;
    margin-left:0px;
    width:400px;
    z-index:1005;
}

li.form_checkbox_dhtml{
   margin-top:2px;
   border: 1px dotted #C6C7CD;
   background-color: #F7F7F7;
   cursor: move;
   width:400px;
   z-index:1005;
}


/* Netnavigation */
.netnavigation{
   background-color: #F7F7F7;
}

.netnavigation .netnavigation_panel, .netnavigation_panel_top{
   background-color: #F7F7F7;
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
   background-color:#F7F7F7;
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

table.list {
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
   background-color:#F7F7F7;
   color: #666666;
   border-bottom: none;
   line-height:17px;
   padding: 3px 3px;
   font-weight:bold;
   white-space:nowrap;
}
.list td.head_nav {
   color: #666666;
   border-bottom: none;
   padding: 3px 3px;
   font-weight:bold;
   text-align: right;
}
.list td.foot_left {
   background-color: #F7F7F7;
   color: #666666;
   border-bottom: none;
   padding: 2px 2px;
   font-weight:bold;
}
.list td.foot_right {
   background-color: #F7F7F7;
   color: #666666;
   border-bottom: none;
   padding: 2px 2px;
   font-weight:bold;
   text-align: right;
}
