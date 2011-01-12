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

/**************************************
**** commsy_room_css ******************
**************************************/
/*General Settings */
body {
   margin: 0px;
   padding: 0px;
   font-family: Arial, "Nimbus Sans L", sans-serif;
   font-size: 10pt;
   background-color: white;
}

.fade-out-link{
    font-size:8pt;
    color:black;
}

img {
   border: 0px;
}


/*Hyperlinks*/
a {
   color: <?php echo($color['hyperlink'])?>;
   text-decoration: none;
}

a:hover, a:active {
   text-decoration: underline;
}


/* Font-Styles */
.infocolor{
   color: <?php echo($color['info_color'])?>;
}

.disabled, .key .infocolor{
   color: <?php echo($color['disabled'])?>;
}

.changed {
   color: <?php echo($color['warning'])?>;
   font-size: 8pt;
}

.infoborder{
    border-top: 1px solid <?php echo($color['info_color'])?>;
    padding-top:10px;
}

.infoborder_display_content{
    width: 70%;
    border-top: 1px solid <?php echo($color['info_color'])?>;
    padding-top:10px;
}

.required {
   color: <?php echo($color['warning'])?>;
   font-weight: bold;
}

.normal{
   font-size: 10pt;
}

.handle_width{
    overflow:auto;
    padding-bottom:3px;
}

.handle_width_border{
    overflow:auto;
    padding:3px;
    border: 1px solid <?php echo($color['info_color'])?>;
}

.desc {
   font-size: 8pt;
}

.bold{
   font-size: 10pt;
   font-weight: bold;
}


/* Room Design */
div.main{
   padding: 20px 5px 0px 5px;
}

div.content_fader{
   margin:0px;
   padding: 0px 3px;
   background: url(images/layout/bg-fader_<?php echo($color['schema'])?>.gif) repeat-x;
}

div.content{
   padding:0px;
   margin:0px;
   background-color: <?php echo($color['content_background'])?>;
}

div.content_display_width{
   width:71%;
}

div.frame_bottom {
   position:relative;
   font-size: 1px;
   border-left: 2px solid #C3C3C3;
   border-right: 2px solid #C3C3C3;
   border-bottom: 2px solid #C3C3C3;
}

div.content_bottom {
   position:relative; width: 100%;
}

/*Panel Style*/
#commsy_panels .commsy_panel, #commsy_panel_form .commsy_panel{
   margin:0px;
}

#commsy_panels .panelContent, #commsy_panel_form .panelContent{
   font-size:0.7em;
   padding:0px;
   overflow:hidden;
   position:relative;
}

#commsy_panels .small, #commsy_panel_form .small{
   font-size:8pt;
}

#commsy_panels .panelContent div, #commsy_panel_form .panelContent div{
   position:relative;
}

#commsy_panels .commsy_panel .topBar, #commsy_panel_form .commsy_panel .topBar{
   <?php
   echo('background: url('.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24.png) repeat-x;');
   ?>
   background-color:<?php echo($color['tabs_background'])?>;
   color:<?php echo($color['headline_text'])?>;
   padding: 0px 0px;
   height:20px;
   overflow:hidden;
}

#commsy_panels .commsy_panel .topBar span, #commsy_panel_form .commsy_panel .topBar span{
   line-height:20px;
   vertical-align:baseline;
   color:<?php echo($color['headline_text'])?>;
   font-weight:bold;
   float:left;
   padding-left:5px;
}

#commsy_panels .commsy_panel .topBar img, #commsy_panel_form .commsy_panel .topBar img{
   float:right;
   cursor:pointer;
}

#otherContent{  /* Normal text content */
   float:left;  /* Firefox - to avoid blank white space above panel */
   padding-left:10px;   /* A little space at the left */
}

ul.item_list {
   margin: 3px 0px 2px 2px;
   padding: 0px 0px 3px 15px;
   list-style: circle;
}



/* Tab Style */
div.tabs_frame {
   position:relative;
   <?php
   echo('background: url('.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24.png) repeat-x;');
   ?>
   background-color: <?php echo($color['tabs_background'])?>;
   padding:0px;
   margin:0px;
   font-weight: bold;
   border-left: 2px solid #CBD0D6;
   border-right: 2px solid #CBD0D6;
   border-top: 2px solid #CBD0D6;
}

div.tabs {
   position:relative;
   width: 100%;
   border-bottom: 1px solid <?php echo($color['tabs_title'])?>;
   padding:4px 0px 3px 0px;
   margin:0px;
   font-weight: bold;
   font-size: 10pt;
}

span.navlist{
   color:<?php echo($color['headline_text'])?>;
}
a.navlist{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 6px;
   border-right:1px solid <?php echo($color['headline_text'])?>;
   text-decoration:none;
   font-size: 10pt;
}

a.navlist_current{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 6px;
   border-right:1px solid <?php echo($color['headline_text'])?>;
   text-decoration:none;
   <?php
   echo('background: url('.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24_focus.png) repeat-x;');
   ?>
   background-color:<?php echo($color['tabs_focus'])?>;
}

a.navlist_current:hover, a.navlist_current:active, a.navlist:hover{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 6px;
   text-decoration:none;
   <?php
   echo('background: url('.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24_focus.png) repeat-x;');
   ?>
   background-color:<?php echo($color['tabs_focus'])?>;
}

a.navlist:active{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 6px;
   text-decoration:none;
}

a.navlist_help, a.navlist_help:hover, a.navlist_help:active{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 3px;
   text-decoration:none;
}

/*Headlines*/
h1{
   margin:0px;
   padding:0px 0px 0px 10px;
   font-size:30px;
}

.pagetitle{
   margin:0px;
   font-size: 16pt;
   font-family: verdana, arial, sans-serif;
}


/*Special Designs*/
.top_of_page {
   padding:5px 0px 3px 10px;
   font-size: 8pt;
   color: <?php echo($color['info_color'])?>;
}

.top_of_page a{
   color: <?php echo($color['info_color'])?>;
}

#form_formatting_box{
   margin-top:5px;
   margin-bottom:0px;
   width:400px;
   padding:5px;
   border: 1px #B0B0B0 dashed;
   background-color:<?php echo($color['boxes_background'])?>;
}
.form_formatting_checkbox_box{
   margin-top:0px;
   margin-bottom:0px;
   width:300px;
   padding:5px 10px 5px 10px;
}

#template_information_box{
   margin-top:5px;
   margin-bottom:0px;
   padding:5px;
   border: 1px #B0B0B0 dashed;
   background-color:<?php echo($color['boxes_background'])?>;
}

/* Profile Tab Style */

#profile_tabs_frame {
   position:relative;
   padding:3px 10px;
   margin:0px 0px 0px 0px;
   background-color: #EEEEEE;
   border-bottom:1px solid <?php echo($color['tabs_background'])?>;
}

#profile_tablist{
    margin:0px;
    white-space:nowrap;
    display:inline;
}

.profile_tab{
    border-right:1px solid <?php echo($color['tabs_background'])?>;
    padding:3px 10px;
    display:inline;
}

.profile_tab_current{
    border-right:1px solid <?php echo($color['tabs_background'])?>;
    padding:3px 10px;
    display:inline;
    font-weight:bold;
}

#profile_title, .profile_title{
   background:url(images/detail_fader_<?php echo($color['schema'])?>.gif) center repeat-x;
   background-color:<?php echo($color['tabs_background'])?>;
   color:<?php echo($color['headline_text'])?>;
   vertical-align:top;
   margin:0px;
   padding:5px 10px;
   font-size: 14pt;
}

#profile_content{
   margin-bottom:20px;
   padding:0px;
   background-color: #FFFFFF;
   border: 2px solid <?php echo($color['tabs_background'])?>;
}

a.titlelink{
   color:<?php echo($color['headline_text'])?>;
}