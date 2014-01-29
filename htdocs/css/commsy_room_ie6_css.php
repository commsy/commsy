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

#right_box_page_numbers{
   float:left;
   white-space:nowrap;
}
#information_box_title{
   float:left;
   white-space:nowrap;
}

div.right_box_main{
   margin-top:0px;
   font-size:10pt;
}

#detail_annotations .annotation_creator_information{
   font-size: 10pt;
   width: 100%;
}

#detail_annotations a{
    font-size: 10pt;
}

#creator_information_read_text{
   font-size: 10pt;
}

#list_info_table{
    font-size: 10pt;
}
#list_info_table2{
    font-size: 10pt;
}
#room_information_activity_description{
    font-size: 10pt;
}

#detail_content td.key, #detail_content td.value{
   font-size:10pt;
}

#commsy_panels span.infocolor{
   font-size:10pt;
}

#discussionSummary td{
    font-size:10pt;
}

a.discarticleCreatorInformation{
   font-size:10pt;
}

#configuration_form{
   width:100%;
   font-size:10pt;
   margin-top:5px;
   padding-top:0px;
   vertical-align:bottom;
}

.handle_width{
   font-size:10pt;
}
