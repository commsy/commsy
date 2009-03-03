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


/*Calender View*/
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