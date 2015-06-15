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
chdir('../legacy/');
include_once('etc/cs_constants.php');
include_once('etc/cs_config.php');
include_once('classes/cs_environment.php');

// create environment of this page
$color = $cs_color['DEFAULT'];

// find out the room we're in
if (!empty($_GET['cid'])) {
   $cid = $_GET['cid'];

   global $symfonyContainer;
   $symfonyContainer = $container;
   var_dump($symfonyContainer);exit;
   $environment = $symfonyContainer->get('commsy_legacy.environment')->getEnvironment();
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
    <?php
       if ( file_exists('htdocs/images/layout/bg-fader_'.$color['schema'].'.gif') ) {
           echo('    background:url(images/layout/bg-fader_'.$color['schema'].'.gif) repeat-x;');
       }
    ?>
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
    line-height: 22px;
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