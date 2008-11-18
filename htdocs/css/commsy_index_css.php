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


/*Font and Hyperlinks*/
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
   background:url(images/tab_fader_<?php echo($color['schema'])?>.gif) repeat-x;
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
   background-image:url(images/tab_fader_<?php echo($color['schema'])?>.gif) repeat-x;
   background-color: <?php echo($color['tabs_background'])?>;
   color: <?php echo($color['headline_text'])?>;
   border-bottom: none;
   padding: 2px 2px;
   font-weight:bold;
}
.list td.foot_right {
   background-image:url(images/tab_fader_<?php echo($color['schema'])?>.gif) repeat-x;
   background-color: <?php echo($color['tabs_background'])?>;
   color: <?php echo($color['headline_text'])?>;
   border-bottom: none;
   padding: 2px 2px;
   font-weight:bold;
   text-align: right;
}