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

/*General Settings */
body {
   margin: 0px;
   padding: 0px;
   font-family: Arial, "Nimbus Sans L", sans-serif;
   font-size: 10pt;
   background-color: white;
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

a.portal_link, a.portal_link:hover{
   font-weight: bold;
   color: <?php echo($color['myarea_section_title'])?>;
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

.required {
   color: <?php echo($color['warning'])?>;
   font-weight: bold;
}

.normal{
   font-size: 10pt;
}

.desc, .desc_usage {
   font-size: 8pt;
}

.bold{
   font-size: 10pt;
   font-weight: bold;
}

.small_font{
   font-weight:normal;
   font-size:8pt;
}

.portal_system_link{
   color: <?php echo($color['info_color'])?>;
}


/* Portal Layout */
div.portal_tabs_frame {
   position:relative;
   margin:2px 5px 5px 6px;
   padding:0px;
   <?php
   echo('background: url('.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24.png) repeat-x;');
   ?>
   background-color: <?php echo($color['tabs_background'])?>;
}

div.portal-tabs {
   position:relative;
   width: 100%;
   margin:0px;
   padding:4px 0px 3px 0px;
   border-bottom: 1px solid <?php echo($color['tabs_title'])?>;
}

div.portal_content{
    margin:0px;
    padding:0px 3px;
    border-left: 2px solid white;
    border-right: 2px solid white;
}

td.portal_leftviews {
    padding:0px;
    vertical-align: top;
}

td.portal_rightviews {
    padding: 0px 0px 0px 10px;
    vertical-align: top;
}

div.frame_bottom {
    position:relative;
    font-size: 1px;
    border-left: 2px solid #C3C3C3;
    border-right: 2px solid #C3C3C3;
    border-bottom: 2px solid #C3C3C3;
}

div.content_bottom {
    position:relative;
    width: 100%;
}

/* Layout of content areas */
div.welcome_frame {
    position:relative;
    margin:0px;
    padding:0px;
    border: 2px solid #CBD0D6;
}

div.welcome_content {
    position:relative;
    margin:0px;
    padding:0px;
}

div.content_fader{
    position:relative;
    width: 100%;
    margin:0px;
    padding:0px;
    background: url(images/layout/bg-fader_<?php echo($color['schema'])?>.gif) repeat-x;
    background-color: <?php echo($color['content_background'])?>;
}

div.content_without_fader{
    position:relative;
    width: 100%;
    margin:0px;
    padding:0px;
    font-size: 10pt;
}

div.content_background{
    background-color: <?php echo($color['content_background'])?>;
}

div.main{
    padding: 20px 5px 0px 5px;
}

div.content{
    padding:0px;
    margin:0px;
    background-color: <?php echo($color['content_background'])?>;
}

/*Announcements*/
td.anouncement_background, td.anouncement_background a{
    font-weight: bold;
    background-color: <?php echo($color['myarea_title_backround'])?>;
    font-size:10pt;
}

td.anouncement_background a:hover{
    color: <?php echo($color['myarea_section_title'])?>;
    font-size:10pt;
}

/*Search Box*/
div.search_box{
    margin: 0px 5px 0px 0px;
    padding: 10px 5px;
    font-weight: bold;
    border: 2px solid #B0B0B0;background-color: <?php echo($color['myarea_title_backround'])?>;
}

span.search_title{
    font-size:18px;
    font-weight:bold;
    color: <?php echo($color['info_color'])?>;
}

div.search_link {
    padding: 0px 5px; color: <?php echo($color['myarea_section_title'])?>;
    font-size:10pt;
}

div.search_link a{
    color: <?php echo($color['myarea_section_title'])?>;
    font-size:10pt;
}


/*Room List*/
.list {
    border-collapse: collapse;
    width: 100%;
    font-size:10pt;
}

.list td.portal-even {
    background-color: <?php echo($color['list_entry_even'])?>;
}

.list td.portal-odd {
    background-color: <?php echo($color['list_entry_odd'])?>;
}

.list td {
    padding: 2px 3px;
    font-weight:normal;
    text-align:left;
    border-bottom:none;
    background-color: <?php echo($color['tabs_background'])?>;
}

.list td.portal-head {
    font-weight:bold;
    line-height:18px;
    white-space:nowrap;
    color: <?php echo($color['headline_text'])?>;
    <?php
    echo('background: url('.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24.png) repeat-x;');
    ?>
    background-color: <?php echo($color['tabs_background'])?>;
}

.portal_link{
    color: <?php echo($color['headline_text'])?>;
}


.list td.head {
    font-weight:bold;
    line-height:18px;
    white-space:nowrap;
    color: <?php echo($color['headline_text'])?>;
    <?php
    echo('background: url('.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24.png) repeat-x;');
    ?>
    background-color: <?php echo($color['tabs_background'])?>;
}

.list a.head, .list a.head:hover {
    color: <?php echo($color['headline_text'])?>;
}

.list span.head {
    font-weight: bold;
}

.list span.desc {
    font-size: 8pt;
}

div.gauge {
    height:5px;
    margin: 3px 10px 3px 0px;
    border: 1px solid #666; font-size:5px;
    background-color: <?php echo($color['myarea_headline_title'])?>;
}

div.gauge-bar {
    height:5px;
    font-size:5px;
    text-align: right;
    color: black;
    background-color: <?php echo($color['myarea_section_title'])?>;
}


/*Headlines*/
h1{
    margin:0px;
    padding:5px 0px 5px 10px;
    font-size:30px;
}

h1.portal_title{
    margin:0px;
    padding:0px;
    font-size:20px;
    font-weight:bold;
    color:<?php echo($color['welcome_text'])?>;
}

h1.portal_main_title{
    margin:0px;
    padding:0px 10px 0px 0px;
    font-size:25px;
    font-weight:bold;
    color:<?php echo($color['welcome_text'])?>;
}

h1.portal_announcement_title{
    margin:0px;
    padding:0px;
    font-size:18px;
    font-weight:bold;
    color:<?php echo($color['info_color'])?>;
}

h2.pagetitle{
    margin:0px 0px 10px 0px;
    font-size: 16pt;
}

/* Right Boxes Style */
.right_box{
   background-color: <?php echo($color['boxes_background'])?>;
   padding-bottom:0px;
   font-size:10pt;
}

a.right_box_title {
   color:<?php echo($color['headline_text'])?>;
   font-weight:bold;
   font-size: 8pt;
}

div.right_box_title{
   <?php
   echo('background: url('.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24.png) repeat-x;');
   ?>
   background-color:<?php echo($color['tabs_background'])?>;
   height:18px;
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
   <?php
   echo('border-left:1px solid #B0B0B0;');
   echo('border-right:1px solid #B0B0B0;');
   echo('border-bottom:1px solid #B0B0B0;');
   ?>
   padding:3px 3px 3px 5px;
}

div.gauge {
   background-color: <?php echo($color['boxes_background'])?>;
   height:10px;
   margin-left: 10px;
   margin-right: 10px;
   margin-top: 3px;
   margin-bottom: 3px;
   border: 1px solid #666;
   font-size:10px;
}
div.gauge-bar {
   background-color: <?php echo($color['tabs_background'])?>;
   height:10px;
   text-align: right;
   color:<?php echo($color['headline_text'])?>;
   font-size:10px;
}
span.index_system_link{
   color: <?php echo($color['tabs_title'])?>;
}

a.index_system_link{
   color: <?php echo($color['tabs_title'])?>;
}

div.div_line{
   margin:10px 0px;
   border-top:1px solid black;
}

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

/*Special Designs*/
.top_of_page {
    padding:5px 0px 3px 10px;
    font-size: 8pt;
    color: <?php echo($color['info_color'])?>;
}

.top_of_page a{
    color: <?php echo($color['info_color'])?>;
}

span.portal_section_title{
    font-size:18px;
    font-weight:bold;
    color: <?php echo($color['info_color'])?>;
}

span.portal_description{
    font-size:8pt;
    font-weight:normal;
    color: <?php echo($color['info_color'])?>;
}

a.portal_system_link, a.portal_system_link:hover{
    font-weight:bold;
    color: <?php echo($color['info_color'])?>;
}

span.portal_forward_links{
    font-weight:bold;
    color: <?php echo($color['info_color'])?>;
    font-size:10pt;
}

#template_information_box{
   margin-top:5px;
   margin-bottom:0px;
   width:400px;
   padding:5px;
   border: 1px #B0B0B0 dashed;
   background-color:<?php echo($color['boxes_background'])?>;
}

span.template_description{
   font-weight:bold;
   color: <?php echo($color['myarea_section_title'])?>;
   white-space: nowrap;
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
   border: 2px solid #CBD0D6;
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




<?php
// Password Security Check Javascript
?>
#iSM
{margin:0 0 15px 0;padding:0;height:14px;}
#iSM ul
{border:0;margin:4px 0 0 0;padding:0;list-style-type:none;text-align:center;}
#iSM ul li
{display:block;float:left;text-align:center;padding:1px 0 0 0;margin:0;height:14px;}
#iWeak,#iMedium,#iStrong
{width:40px;font-size:.8em;color:#adadad;text-align:center;padding:2px;background-color:#F1F1F1;display:block;}
#iWeak,#iMedium
{border-right:solid 1px #DEDEDE;}
#iMedium
{width:74px;}
#iMedium,#iStrong
{border-left-width:0;}

div.strong #iWeak, div.strong #iMedium, div.strong #iStrong  {
	background: #00CC66;
	color: #00CC66;
}

div.medium #iWeak, div.medium #iMedium {
	background: #FFFF99;
	color: #FFFF99;
}

div.medium #iWeak, div.medium #iMedium {
	background: #FFFF99;
	color: #FFFF99;
}

div.weak #iWeak {
	background: #FF0000;
	color: #FF0000;
}

div.strong #iStrong, div.medium #iMedium, div.weak #iWeak {
	color:#000;
}

/* My Area */

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

/* Special Styles */
div.indexdate{
   color:<?php echo($color['date_title'])?>;
   font-weight: bold;
}
div.restriction {
   padding: 1px 1px;
   font-weight:normal;
   font-size:10pt;
}
div.gauge {
   background-color: <?php echo($color['boxes_background'])?>;
   height:5px;
   margin-left: 10px;
   margin-right: 10px;
   margin-top: 3px;
   margin-bottom: 3px;
   border: 1px solid #666;
   font-size:10px;
}
div.gauge-bar {
   background-color: <?php echo($color['tabs_background'])?>;
   height:5px;
   text-align: right;
   color:<?php echo($color['headline_text'])?>;
   font-size:10px;
}


/* Font and Hyperlinks */
.closed {
   color: <?php echo($color['hyperlink'])?>;
   font-size: 8pt;
}
.list span.desc, .desc, .desc_usage {
   font-size: 8pt;
}
span.small_font{
   font-weight:normal;
   font-size:8pt;
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
span.index_system_link, a.index_system_link{
   color: <?php echo($color['tabs_title'])?>;
}
a.select_link{
   font-size:8pt;
   color: <?php echo($color['headline_text'])?>;
   font-weight:bold;
}
span.select_link{
   font-size:8pt;
   color: <?php echo($color['headline_text'])?>;
}



/* List Layout */
.list {
   border-collapse: collapse;
   width: 100%;
   font-size:10pt;
}
.list td, td.odd {
   background-color: <?php echo($color['list_entry_odd'])?>;
   padding: 2px 3px;
   font-size:10pt;
}
.list td.even  {
   background-color: <?php echo($color['list_entry_even'])?>;
}
.list td.head {
   <?php
   echo('background: url('.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24.png) repeat-x;');
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
   echo('background: url('.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_32.png) repeat-x;');
   ?>
   background-color: <?php echo($color['tabs_background'])?>;
   color: <?php echo($color['headline_text'])?>;
   border-bottom: none;
   padding: 2px 2px;
   font-weight:bold;
}
.list td.foot_right {
   <?php
   echo('background: url('.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_32.png) repeat-x;');
   ?>
   background-color: <?php echo($color['tabs_background'])?>;
   color: <?php echo($color['headline_text'])?>;
   border-bottom: none;
   padding: 2px 2px;
   font-weight:bold;
   text-align: right;
}

/* Home */

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


.list {
   border-collapse: collapse;
   width: 100%; }

.list td.even  {
   background-color: <?php echo($color['list_entry_even'])?>;
   padding: 2px 3px; }

.list td.odd  {
   background-color: <?php echo($color['list_entry_odd'])?>;
   padding: 2px 3px; }

.list td {
   background-color: <?php echo($color['list_entry_odd'])?>;
   padding: 2px 3px;
   font-size:10pt;
   }

.list td.head {
   background-color: <?php echo($color['tabs_background'])?>;
   color: <?php echo($color['headline_text'])?>;
   border-bottom: none;
   padding: 3px 3px;
   white-space:nowrap; }

div.head {
   background-color: <?php echo($color['tabs_background'])?>;
   color: <?php echo($color['headline_text'])?>;
   border-bottom: none;
   padding: 3px 3px;
   white-space:nowrap; }

.list td.count {
   border-bottom: none;
   padding: 3px 3px; }

.list td.head_nav {
   border-bottom: none;
   padding: 3px 3px;
   text-align: right; }

.list td.short_head_nav {
   border-bottom: none;
   padding: 3px 3px;
   text-align: right;
   background-color: <?php echo($color['tabs_background'])?>; }

.list td.foot_left {
   border-bottom: none;
   padding: 2px 3px 3px 3px;
   background-color: <?php echo($color['tabs_background'])?>; }

.list td.foot_right {
   border-bottom: none;
   padding: 2px 3px 3px 3px;
   text-align: right;
   background-color: <?php echo($color['tabs_background'])?>; }

.list td.noline {
   border-bottom: none;
   padding-bottom: 0px; }

.list span.head {
   font-weight: bold;
}

a.head{
   color: <?php echo($color['headline_text'])?>;
}

a.head:hover{
   color: <?php echo($color['headline_text'])?>;
}

.list span.desc {
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
   background-color: <?php echo($color['tabs_background'])?>;
   color: <?php echo($color['headline_text'])?>;
   padding: 3px 3px;
   font-weight:bold;
   white-space:nowrap;
}

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
div.gauge {
   background-color: <?php echo($color['boxes_background'])?>;
   height:5px;
   margin-left: 10px;
   margin-right: 10px;
   margin-top: 3px;
   margin-bottom: 3px;
   border: 1px solid #666;
   font-size:10px;
}
div.gauge-bar {
   background-color: <?php echo($color['tabs_background'])?>;
   height:5px;
   text-align: right;
   color:<?php echo($color['headline_text'])?>;
   font-size:10px;
}


/* Calender View */
tr.calendar_head{
   background-color: <?php echo($color['tabs_background'])?>;
   color:<?php echo($color['headline_text'])?>;
   font-weight:bold;
}
td.calendar_head{
   background-image:url(images/layout/tab_fader_<?php echo($color['schema'])?>.gif);
   background-repeat:repeat-x;
   background-color: <?php echo($color['tabs_background'])?>;
   line-height:20px;
   color:<?php echo($color['headline_text'])?>;
   border-right: 1px solid <?php echo($color['tabs_title'])?>;
   font-weight:bold;
}
td.calendar_head_first{
   background-image:url(images/layout/tab_fader_<?php echo($color['schema'])?>.gif);
   background-repeat:repeat-x;
   background-color: <?php echo($color['tabs_background'])?>;
   line-height:20px;
   color:<?php echo($color['headline_text'])?>;
   border-right: 1px solid <?php echo($color['tabs_title'])?>;
   border-left: 1px solid <?php echo($color['tabs_background'])?>;
   font-weight:bold;
}
td.calendar_head_all_first{
   background-image:url(images/layout/tab_fader_<?php echo($color['schema'])?>.gif);
   background-repeat:repeat-x;
   background-color:<?php echo($color['tabs_background'])?>;
   color: <?php echo($color['headline_text'])?>;
   border-bottom: none;
   line-height:17px;
   background-color: <?php echo($color['tabs_background'])?>;
   color:<?php echo($color['headline_text'])?>;
   border-left: 1px solid <?php echo($color['tabs_background'])?>;
   font-weight:bold;
}
td.calendar_head_all{
   background-image:url(images/layout/tab_fader_<?php echo($color['schema'])?>.gif);
   background-repeat:repeat-x;
   background-color:<?php echo($color['tabs_background'])?>;
   color: <?php echo($color['headline_text'])?>;
   border-bottom: none;
   line-height:17px;
   background-color: <?php echo($color['tabs_background'])?>;
   color:<?php echo($color['headline_text'])?>;
   font-weight:bold;
}
a.calendar_head_all{
   color:<?php echo($color['headline_text'])?>;
}
td.calendar_content{
   background-color: #FDFDFD;
   color:black;
   border: 1px solid <?php echo($color['info_color'])?>;
   font-weight:normal;
   font-size:8pt;
}
td.calendar_content_without_time{
   background-color: #EFEFEF;
   color:black;
   border: 1px solid <?php echo($color['info_color'])?>;
   font-weight:normal;
   font-size:8pt;
}
td.calendar_content_with_entry{
   background-color: #FFFF80;
   color:black;
   border: 1px solid <?php echo($color['info_color'])?>;
   font-weight:normal;
   font-size:8pt;
}
td.calendar_content_weekend{
   background-color: #FAFAFA;
   color:black;
   border: 1px solid <?php echo($color['info_color'])?>;
   font-weight:normal;
   font-size:8pt;
}
td.calendar_content_focus{
   background-color: <?php echo($color['boxes_background'])?>;
   color:black;
   border: 2px solid <?php echo($color['tabs_background'])?>;
   font-weight:normal;
   font-size:8pt;
}
td.calendar_content_week_view_focus{
   background-color: <?php echo($color['boxes_background'])?>;
   color:black;
   border: 1px solid <?php echo($color['info_color'])?>;
   font-weight:normal;
   font-size:8pt;
}
.infoborderyear{
   color: <?php echo($color['hyperlink'])?>;
   padding-top:5px;
}
.infoborderweek{
   padding-top:5px;
}

/* Form */
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


h2.pagetitle{
  margin-bottom:0px;
  margin-top: 0px;
  font-size: 16pt;
  font-family: verdana, arial, sans-serif;
}

div.form_title{
   color:<?php echo($color['tabs_focus'])?>;
   padding-top:10px;
   padding-bottom:3px;
   font-weight: bold;
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
   background-color: <?php echo($color['myarea_title_backround'])?>;
   padding-bottom:2px;
   padding-top:3px;
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

.form tr.textarea td {
   position:relative;
   vertical-align: top; }

.form tr.textarea td.key {
   position:relative;
   padding-top: 13px; }

.form tr.textarea td.example {
   position:relative;
   padding-top: 13px; }

.form tr.radio td {
   position:relative;
   vertical-align: top; }

.form tr.radio td.key {
   position:relative;
   padding-top: 13px; }

.form tr.radio td.example {
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
   /* border: 1px solid #666; */
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
}

#MySortableRoom{
	 list-style: none;
    padding-left:0px;
    margin-left:0px;
    width:400px;
}

li.form_checkbox_dhtml{
   margin-top:2px;
   border: 1px dotted <?php echo($color['tabs_background'])?>;
   background-color: <?php echo($color['boxes_background'])?>;
   cursor: move;
   width:400px;
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