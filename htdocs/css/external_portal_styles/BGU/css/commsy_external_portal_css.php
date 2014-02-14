<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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
?>



/* $Id$ */

/*** Generic elements */


html {
  height: 100%;
}

.ads{
width:150px;
}


#room_list{
   padding:0px;
   margin-top:20px;
   width:500px;
}

#room_list table{
   border-collapse: collapse;
   border: 0px solid #C6C7CD;
}

.room_list_head{
    height:24px;
}

#room_list .portal_section_title{
   font-size:14pt;
   font-weight:bold;
   xtext-transform:uppercase;
}

#room_list .portal_forward_links{
}

#room_list .portal_description{
   font-weight:normal;
}

#room_list .portal-head{
   background-color: #012F59;
   font-size:10pt;
}

#room_list a.head{
   color: #FFFFFF;
   font-weight:bold;
}

#room_list .portal_link{
   color: #FFFFFF;
   font-weight:bold;
}

#room_list .portal-even, #portal_config_overview td.even{
   background-color: #F1EFE7;
}

#portal_config_overview td.head{
   background: url(menu_bg.gif) repeat-x;
   height:24px;
   color: #606060;
   padding: 2px 5px;
   font-weight:bold;
}

#portal_config_overview table.list{
   background-color: #FFFFFF;
   border-left: 1px solid #C6C7CD;
   border-right: 1px solid #C6C7CD;
   border-bottom: 1px solid #C6C7CD;
   margin:0px;
   padding:0px;
}

#portal_config_overview table.configuration_table{
    border:0px;
}



#room_list .gauge-bar{
   background-color: #012F59;
}

#room_list .gauge{
   border: 1px solid #012F59;
}

#portal_search, #portal_action, #left_box, #portal_news, #portal_news2, #portal_announcements{
   margin:20px;
   padding:0px;
}

#room_actions a{
 /* font-size:8pt;*/
}

#left_box .myarea_content{
	font-size:8pt;
}

#left_box{
	background-color: #F7F7F7;
    border: 1px solid #B0B0B0;
    padding: 10px 5px;
    width: 220px;
}

.sidehead{
	font-weight:bold;
}

#left_box a{
    /*font-size:8pt;*/

}

#left_box a.myarea_content, #portal_news a.myarea_content, #portal_news2 a.myarea_content{
   display: inline;
   font-size:8pt;
}

#portal_news .myarea_section_title, #portal_news2 .myarea_section_title{
   font-size: 10pt;
   color: #606060;
   margin: 0px;
   line-height: 24px;
   padding-top:0px;
   xtext-transform:uppercase;
   border-bottom: 1px solid #C6C7CD;
}

#left_box .myarea_section_title{
   font-size: 12px;
   color: #606060;
   margin: 0px;
   line-height: 24px;
   padding-top:10px;
   xtext-transform:uppercase;
   border-bottom: 1px solid #C6C7CD;
}

#portal_room_config, #portal_config_overview{
   padding:5px;
   margin:30px;
   width: 800px;
}

#portal_room_config div.right_box_title, #portal_config_overview div.right_box_title{
   background: url(menu_bg.gif) repeat-x;
   height:24px;
   color: #606060;
   padding: 2px 5px;
   font-weight:bold;
   font-size: 10pt;
}

#portal_room_config div.right_box, #portal_config_overview div.right_box{
   border-top: 1px solid #C6C7CD;
   border-left: 1px solid #C6C7CD;
   border-right: 1px solid #C6C7CD;
   border-bottom: 1px solid #C6C7CD;
}

#portal_room_config div.right_box_main, #portal_config_overview div.right_box_main{
  border:0px;
}

#portal_config_overview a.index_system_link, #portal_config_overview div.index_forward_links{
   color: #606060;
}

#portal_announcements .info-link-h1{
   background-color: #012F59;
   font-size: 12px;
   color: #606060;
   margin: 0px;
   line-height: 24px;
   padding-left:5px;
   xtext-transform:uppercase;
}

#room_detail_content{
   border: 1px solid #012F59;
   padding:10px;
}


.info-link-h1{
   background-color: #012F59;
   color:#ffffff;
   font-size: 12px;
   margin: 0px;
   line-height: 24px;
   padding-left:5px;
   xtext-transform:uppercase;
}

.info-link-h2{
   font-size: 12px;
   color: #606060;
   margin: 0px;
   line-height: 24px;
   padding-left:5px;
   xtext-transform:uppercase;
}

#portal_search .search_title{
   overflow:hidden;
}

#room_detail{
   width: 500px;
   margin-top:20px;
   background-color: #FFFFFF;
   margin-bottom:20px;
}

#room_detail span.search_title{
   font-size:10pt;
   font-weight:bold;
}

#room_detail_actions{
   width:100%;
   text-align:right;
   font-size:8pt;
}

.anouncement_background{
   border-bottom:1px solid #C6C7CD;
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
   padding:0px
   background-color: #FFFFFF;
   border: 2px solid <?php echo($color['tabs_background'])?>;
}

a.titlelink{
   color:<?php echo($color['headline_text'])?>;
}

