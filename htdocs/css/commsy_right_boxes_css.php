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


/* Right Boxes Style */
.right_box{
   background-color: <?php echo($color['boxes_background'])?>;
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
}

div.right_box_title{
   background:url(images/tab_fader_<?php echo($color['schema'])?>.gif) repeat-x;
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
   border: 1px solid <?php echo($color['tabs_background'])?>;
   padding:3px 3px 3px 5px;
}

div.gauge {
   background-color: <?php echo($color['boxes_background'])?>;
   height:14px;
   margin-left: 10px;
   margin-right: 10px;
   margin-top: 0px;
   margin-bottom: 0px;
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