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
#ie{
   width:expression(document.body.clientWidth < 990 ? "990px": "auto" && document.body.clientWidth > 1200 ? "1200px": "auto");
}
#ie_footer{
   width:expression(document.body.clientWidth < 990 ? "990px": "auto" && document.body.clientWidth > 1200 ? "1200px": "auto");
}
.commsy_footer{border-top:1px solid #B0B0B0; background-color:white;}

/*General Settings */
body {
   margin: 0px;
   padding: 0px;
   min-width:930px;
   max-width:1200px;
   font-family: 'Trebuchet MS','lucida grande',tahoma,'ms sans serif',verdana,arial,sans-serif;
   font-size:80%;
   font-size-adjust:none;
   font-stretch:normal;
   font-style:normal;
   font-variant:normal;
   font-weight:normal;
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

.link_text{
   color: <?php echo($color['hyperlink'])?>;
}

.disabled, .key .infocolor{
   color: <?php echo($color['disabled'])?>;
}

.changed {
   color: <?php echo($color['warning'])?>;
   font-size: 8pt;
}

.infoborder{
    border-top: 1px solid <?php echo($color['disabled'])?>;
    padding-top:10px;
}

.listinfoborder{
    border-top: 1px solid <?php echo($color['disabled'])?>;
    margin:5px 0px;
}

.infoborder_display_content{
    width: 70%;
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
#main{
   padding: 10px 0px 0px 0px;
   width:100%;
}

div.page_header_border{
   padding:0px 20px;
   height: 8px;
   background-color:white;
}


#page_header{
   clear:both;
   min-height:70px;
   padding:0px 10px 5px 10px;
   background-color: white;
   font-size:8pt;
}

#page_header_logo{
   font-size:10pt;
   vertical-align:bottom;
}

#page_header_logo table{
}

#page_header_logo td{
   vertical-align:bottom;
}

#page_header_logo h1{
   font-size:24pt;
   font-weight:bold;
}

div.page_header_personal_area{
   float:right;
   height:70px;
   width: 40%;
   padding:5px 0px 0px 0px;
}


div.content_fader{
    margin:0px;
    padding:5px 10px 0px 10px;
    <?php
    if ($color['schema']=='SCHEMA_OWN'){
       if ($room->getBGImageFilename()){
           if ($room->issetBGImageRepeat()){
              echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture='.$room->getBGImageFilename().') repeat;');
           }else{
              echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture='.$room->getBGImageFilename().') no-repeat;');
           }
       }
    } elseif ( file_exists('htdocs/css/images/bg-'.$color['schema'].'.jpg')
               or ( !empty($color['background_image'])
                    and (file_exists('htdocs/css/images/'.$color['background_image'].'.jpg') or file_exists('htdocs/css/images/'.$color['background_image']))
                  )
             ) {
        if ( !isset($color['background_image'])
             and file_exists('htdocs/css/images/bg-'.$color['schema'].'.jpg')
           ) {
           if (isset($color['repeat_background']) and $color['repeat_background'] == 'xy'){
              echo('background: url(images/bg-'.$color['schema'].'.jpg) repeat;');
           }elseif (isset($color['repeat_background']) and $color['repeat_background'] == 'x'){
              echo('background: url(images/bg-'.$color['schema'].'.jpg) repeat-x;');
           }elseif (isset($color['repeat_background']) and $color['repeat_background'] == 'y'){
              echo('background: url(images/bg-'.$color['schema'].'.jpg) repeat-y;');
           }else{
              echo('background: url(images/bg-'.$color['schema'].'.jpg) no-repeat;');
           }
        } elseif ( !empty($color['background_image'])
                   and (file_exists('htdocs/css/images/'.$color['background_image'].'.jpg') or file_exists('htdocs/css/images/'.$color['background_image']))
                 ) {
           if (isset($color['repeat_background']) and $color['repeat_background'] == 'xy'){
              echo('background: url(images/'.$color['background_image'].') repeat;');
           }elseif (isset($color['repeat_background']) and $color['repeat_background'] == 'x'){
              echo('background: url(images/'.$color['background_image'].') repeat-x;');
           }elseif (isset($color['repeat_background']) and $color['repeat_background'] == 'y'){
              echo('background: url(images/'.$color['background_image'].') repeat-y;');
           }else{
              echo('background: url(images/'.$color['background_image'].') no-repeat;');
           }
        }
    }
    ?>
}


div.content{
    margin:0px;
    height:100%;
    <?php
    if ($color['schema']=='SCHEMA_OWN'){
       if ($room->getBGImageFilename()){
           if ($room->issetBGImageRepeat()){
              echo('background: '.$color['content_background'].' url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture='.$room->getBGImageFilename().') repeat;');
           }else{
              echo('background: '.$color['content_background'].' url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture='.$room->getBGImageFilename().') no-repeat;');
           }
       }else{
          echo('background-color: '.$color['content_background'].';');
       }
    } elseif ( file_exists('htdocs/css/images/bg-'.$color['schema'].'.jpg')
               or ( !empty($color['background_image'])
                    and file_exists('htdocs/css/images/'.$color['background_image'].'.jpg')
                  )
             ) {
        if ( !isset($color['background_image'])
             and file_exists('htdocs/css/images/bg-'.$color['schema'].'.jpg')
           ) {
           if (isset($color['repeat_background']) and $color['repeat_background'] == 'xy'){
              echo('background: '.$color['content_background'].' url(images/bg-'.$color['schema'].'.jpg) repeat;');
           }elseif (isset($color['repeat_background']) and $color['repeat_background'] == 'x'){
              echo('background: '.$color['content_background'].' url(images/bg-'.$color['schema'].'.jpg) repeat-x;');
           }elseif (isset($color['repeat_background']) and $color['repeat_background'] == 'y'){
              echo('background: '.$color['content_background'].' url(images/bg-'.$color['schema'].'.jpg) repeat-y;');
           }else{
              echo('background: '.$color['content_background'].' url(images/bg-'.$color['schema'].'.jpg) no-repeat;');
           }
        } elseif ( !empty($color['background_image'])
                   and file_exists('htdocs/css/images/'.$color['background_image'].'.jpg')
                 ) {
           if (isset($color['repeat_background']) and $color['repeat_background'] == 'xy'){
              echo('background: '.$color['content_background'].' url(images/'.$color['background_image'].') repeat;');
           }elseif (isset($color['repeat_background']) and $color['repeat_background'] == 'x'){
              echo('background: '.$color['content_background'].' url(images/'.$color['background_image'].') repeat-x;');
           }elseif (isset($color['repeat_background']) and $color['repeat_background'] == 'y'){
              echo('background: '.$color['content_background'].' url(images/'.$color['background_image'].') repeat-y;');
           }else{
              echo('background: '.$color['content_background'].' url(images/'.$color['background_image'].') no-repeat;');
           }
        } else {
           echo('background-color: '.$color['content_background'].';');
        }
    } else {
       echo('background-color: '.$color['content_background'].';');
    }
    ?>
    border-right: 1px solid #C3C3C3;
}

div.content_display_width{
    width:70%;
}

div.index_content_display_width{
    width:70%;
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

ul.item_list {
   margin: 3px 0px 2px 2px;
   padding: 0px 0px 3px 15px;
   list-style: circle;
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



/* Tab Style */
#tabs_frame {
   position:relative;
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24.png) repeat-x;');
   ?>
   background-color: <?php echo($color['tabs_background'])?>;
   padding:0px;
   margin:0px;
   font-weight: bold;
}

#tablist{
   margin:0px;
   padding:0px 10px;
   white-space:nowrap;
}

#tabs {
   position:relative;
   width: 100%;
   border-bottom: 1px solid <?php echo($color['tabs_dash'])?>;
   padding:4px 0px 3px 0px;
   margin:0px;
   font-weight: bold;
   font-size: 10pt;
}

div.tabs_bottom {
   position:relative;
   width: 100%;
   border-top: 1px solid <?php echo($color['tabs_title'])?>;
   padding:4px 0px 3px 0px;
   margin:0px;
   font-weight: bold;
   font-size: 10pt;
}

a.titlelink{
   color:<?php echo($color['headline_text'])?>;
}

span.navlist{
   color:<?php echo($color['headline_text'])?>;
}
a.navlist{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 6px;
   border-right:1px solid <?php echo($color['tabs_separators'])?>;
   text-decoration:none;
   font-size: 10pt;
}

a.navlist_current{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 6px;
   border-right:1px solid <?php echo($color['tabs_separators'])?>;
   text-decoration:none;
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24_focus.png) repeat-x;');
   ?>
   background-color:<?php echo($color['tabs_focus'])?>;
}

a.navlist_current:hover, a.navlist_current:active, a.navlist:hover{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 6px;
   text-decoration:none;
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24_focus.png) repeat-x;');
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


/* Tablist rightbox*/
#tabs_frame_right_box {
   position:relative;
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24.png) repeat-x;');
   ?>
   background-color: <?php echo($color['tabs_background'])?>;
   padding:0px;
   margin:0px;
   font-weight: bold;
}
#tabs_frame_todo_right_box {
   position:relative;
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24.png) repeat-x;');
   ?>
   background-color: <?php echo($color['tabs_background'])?>;
   padding:0px;
   margin:0px;
   font-weight: bold;
}

#tablist_right_box, #tablist_todo_right_box{
   margin:0px;
   padding:0px 0px;
   white-space:nowrap;
}

#tabs_right_box, #tabs_todo_right_box {
   position:relative;
   width: 100%;
   padding:4px 0px 3px 0px;
   margin:0px 0px 0px 0px;
   font-weight: bold;
   font-size: 10pt;
}

#tablist_right_box a.navlist{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 6px;
   border-right:1px solid <?php echo($color['headline_text'])?>;
   text-decoration:none;
   font-size: 10pt;
}

#tablist_right_box a.navlist_current{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 6px;
   border-right:1px solid <?php echo($color['headline_text'])?>;
   text-decoration:none;
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24_focus.png) repeat-x;');
   ?>
   background-color:<?php echo($color['tabs_focus'])?>;
}

#tablist_todo_right_box a.navlist{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 6px;
   border-right:1px solid <?php echo($color['headline_text'])?>;
   text-decoration:none;
   font-size: 10pt;
}

#tablist_todo_right_box a.navlist_current{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 6px;
   border-right:1px solid <?php echo($color['headline_text'])?>;
   text-decoration:none;
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_24_focus.png) repeat-x;');
   ?>
   background-color:<?php echo($color['tabs_focus'])?>;
}


/*Headlines*/
h1{
   margin:0px;
   padding:0px 0px 0px 10px;
   font-size:30px;
}

.pagetitle{
   margin:0px;
   padding-top:0px;
   font-size: 16pt;
   font-weight:bold;
   color: <?php echo($color['page_title'])?>;
}


/*Special Designs*/
.top_of_page {
   padding:5px 0px;
   font-size: 8pt;
   color: <?php echo($color['info_color'])?>;
}

.top_of_page a{
   color: <?php echo($color['info_color'])?>;
}

#form_formatting_box{
   margin-top:5px;
   margin-bottom:0px;
   width:93%;
   padding:5px;
   border: 1px #666 dashed;
   background-color:#F0F0F0;
}
.form_formatting_checkbox_box{
   margin-top:0px;
   margin-bottom:0px;
   width:93%;
   padding:5px 10px 5px 10px;
   border: 1px #666 dashed;
   background-color:#F0F0F0;
}

#template_information_box{
   margin-top:5px;
   margin-bottom:0px;
   padding:5px;
   border: 1px #666 dashed;
   background-color:#F0F0F0;
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

.search_overlay {
   background:url(images/detail_fader_<?php echo($color['schema'])?>.gif) center repeat-x;
   background-color:<?php echo($color['tabs_background'])?>;
   color:<?php echo($color['headline_text'])?>;
   margin: 5px;
   padding: 2px;
   -moz-border-radius: 5px 5px 0px 0px;
   font-weight: bold;
}

#search_overlay_result_message {
   margin: 5px;
}

.search_overlay table {
	background-color: #FFFFFF;
	color: #000000;
	width: 100%;
	font-weight: normal;
}

.search_overlay td.even {
	background-color: <?php echo($color['list_entry_even'])?>;
	padding: 0px 0px 0px 3px;
}

.search_overlay td.odd {
	background-color: <?php echo($color['list_entry_odd'])?>;
	padding: 0px 0px 0px 3px;
}

.search_overlay_config_label {
	background-color: #FFFFFF;
	color: #000000;
	font-weight: normal;
	float: left;
	width: 95%;
	margin-top: 2px;
	padding-left: 2px;
}

.search_overlay_config_form {
	float: right;
}

#profile_content{
   margin-bottom:20px;
   padding:0px;
   background-color: #FFFFFF;
   border: 2px solid <?php echo($color['tabs_background'])?>;
}

#mail_content{
   margin:0px 0px 20px 0px;
   padding:10px;
   background-color: #FFFFFF;
   border: 2px solid <?php echo($color['tabs_background'])?>;
}
#mail_headline{
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_32.png) repeat-x;');
   ?>background-color:<?php echo($color['tabs_background'])?>;
   color:<?php echo($color['headline_text'])?>;
   vertical-align:top;
   height:30px;
}

#copy_title, .copy_title{
   background:url(images/detail_fader_<?php echo($color['schema'])?>.gif) center repeat-x;
   background-color:<?php echo($color['tabs_background'])?>;
   color:<?php echo($color['headline_text'])?>;
   vertical-align:top;
   margin:0px;
   padding:2px 10px 5px 10px;
   font-size: 14pt;
}

#action_box{
   float:right;
   margin-top:3px;
   padding:2px;
   background:url(images/action_fader.png) repeat-x;
}


#copy_content{
   margin-bottom:20px;
   padding:0px;
   background-color: #FFFFFF;
   border: 1px solid <?php echo($color['tabs_background'])?>;
}

.config_headline{
   <?php
   echo('background: url(../'.$c_single_entry_point.'?cid='.$cid.'&mod=picture&fct=getfile&picture=' . $color['schema'] . '_cs_gradient_32.png) repeat-x;');
   ?>
   background-color:<?php echo($color['tabs_background'])?>;
   color:<?php echo($color['headline_text'])?>;
   vertical-align:top;
   padding:2px 5px;
   font-size: 12pt;
   font-weight:bold;
   margin-top:10px;
}


table.configuration_table{
   background-color: #FFFFFF;
   border: 1px solid <?php echo($color['tabs_background'])?>;
   margin-bottom:20px;
}

#cs_portal_announcements {
   width:180px;
   border-collapse: collapse;
   border: 0px;
   padding:0px;
   margin-left:5px;
   font-weight:normal;
}

#cs_portal_announcements .first-column{
   vertical-align: middle; width: 10%;
   padding-top: 0px;
   padding-bottom: 0px;
}
#cs_portal_announcements .second-column{
   vertical-align: middle;
   width: 70%;
   padding-top: 0px;
   padding-bottom: 0px;
   text-align:left;
}
#cs_portal_announcements .one-column{
   vertical-align: middle;
   width: 80%;
   padding-top: 3px;
   padding-bottom:3px;
   padding-left:3px;
   text-align:left;
}
#cs_portal_announcements .third-column{
   width:20%;
   text-align:right;
}
#cs_portal_announcements .second-row{
    font-size:8pt;
    padding-bottom:3px;
}

#cs_portal_announcements td.second-row a{
    font-weight: bold;
    background-color: <?php echo($color['myarea_title_backround'])?>;
    font-size:10pt;
}

#cs_portal_announcements td.second-row a:hover{
    color: <?php echo($color['myarea_section_title'])?>;
    font-size:10pt;
}

#configuration_form{
   width:70%;
   font-size:10pt;
   margin-top:5px;
   padding-top:0px;
   vertical-align:bottom;
}

.cs-box-content{
   border: 1px solid <?php echo($color['tabs_background'])?>;
   background-color:#FFFFFF;
   margin:0px 0px 20px 0px;
   padding:5px;
}

.cs-box-header {
    margin: 0em;
    background-color:<?php echo($color['tabs_background'])?>;
    color:<?php echo($color['headline_text'])?>;
    padding: 4px 6px;
    -moz-border-radius-topleft: 5px;
    -webkit-border-top-left-radius: 5px;
    -khtml-border-radius-topleft:5px;
    -moz-border-radius-topright: 5px;
    -webkit-border-top-right-radius: 5px;
    -khtml-border-radius-topright:5px;
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
{width:44px;font-size:.8em;color:#adadad;text-align:center;padding:2px;background-color:#F1F1F1;display:block;}
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

#my_tag_content_div{
   overflow:hidden
}
