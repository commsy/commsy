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

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');

$scripts[] = 'create_table_link_items';
$scripts[] = 'link_update_copy_of';
$scripts[] = 'link_update_task_annotation_section';
$scripts[] = 'link_update_in_institution';
$scripts[] = 'link_update_in_topic';
$scripts[] = 'link_update_material_at_course';
$scripts[] = 'link_update_material_for_course';
$scripts[] = 'link_update_material_for_institution';
$scripts[] = 'link_update_material_for_topic';
$scripts[] = 'add_room_config_topic';
$scripts[] = 'room_update_for_rename_topics';
$scripts[] = 'change_rubric_translation_array';
$scripts[] = 'rubric_translation_array2';
$scripts[] = 'add_public_field_to_rubrics';
$scripts[] = 'html_entity_encode';
$scripts[] = 'add_modifier_id';
$scripts[] = 'link_update_material_for';
$scripts[] = 'link_update_relevant_for';
$scripts[] = 'link_update_member_of';
$scripts[] = 'create_table_link_modifier_item';
$scripts[] = 'alter_table_session';

set_time_limit(0);

// start of execution time
$time_start = getmicrotime();

foreach($scripts as $script) {

   $success = FALSE;
   echo "<br /><b>---------------------------------</b><br />".($test == TRUE ? "testing" : "executing")." script <b>$script...</b>";
   echo '<script language="Javascript">window.scrollTo(1,10000000);</script>';
   flush();
   include_once($script.".php");
   echo '<script language="Javascript">window.scrollTo(1,10000000);</script>';
   flush();
   if($success == FALSE) {
      echo "<font color='#ff0000'><b> [failed]</b></font>";
      break;
   } else {
      echo "<font color='#00ff00'><b> [done]</b></font>";
   }
   echo '<script language="Javascript">window.scrollTo(1,10000000);</script>';
   flush();
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br /><br /><br /><b>".count($scripts)." scripts processed in ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))." hours</b><br /><br /><br />\n";
echo '<script language="Javascript">window.scrollTo(1,10000000);</script>';
flush();

function init_progress_bar($count) {
   echo "<br />total entries to be processed: ".$count;
#   echo "<br />|----------------------------------------------------------------------------------------------------|100%";
   echo "<br />&nbsp;";
   echo '<script language="Javascript">window.scrollTo(1,10000000);</script>';
   flush();
}


function update_progress_bar($total) {
   static $i = 0;
   static $percent = 0;
   $i++;
   $cur_percent = (int)(($i*100)/($total) );
   if($percent < $cur_percent) {
      $add = $cur_percent-$percent;
      while($add>0) {
         $add--;
          echo ".";
      }
      $percent = $cur_percent;
      flush();
   }
      if($i==$total) {
      $i = 0;
      $percent = 0;
   }
}

function getmicrotime() {
   list($usec, $sec) = explode(' ', microtime());
   return ((float)$usec + (float)$sec);
}


/*
include_once('create_table_link_items.php');
include_once('link_update_copy_of.php');
include_once('link_update_task_annotation_section.php');
include_once('link_update_in_institution.php');
include_once('link_update_in_topic.php');
include_once('link_update_material_at_course.php');
include_once('link_update_material_for_course.php');
include_once('link_update_material_for_institution.php');
include_once('link_update_material_for_topic.php');
include_once('add_room_config_topic.php');
include_once('room_update_for_rename_topics.php');
include_once('change_rubric_translation_array.php');
include_once('add_public_field_to_rubrics.php');
include_once('html_entity_encode.php');
include_once('add_modifier_id.php');
include_once('link_update_material_for.php');
include_once('link_update_relevant_for.php');
include_once('link_update_member_of.php');
include_once('create_table_link_modifier_item.php');
include_once('annotation_add_modifier.php');
include_once('sections_discarticle_add_modifier.php');
*/
?>