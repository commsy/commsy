<?php
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

$test = false;
#$test = true;

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('../update_functions.php');

$scripts[] = 'move_news_to_announcements';
$scripts[] = 'config_to_db_extra_awareness';
$scripts[] = 'config_to_db_extra_teamcalendar';
$scripts[] = 'db_campus_contactmoderator';
$scripts[] = 'db_campus_mail_moderator';
$scripts[] = 'db_links_course_organizer';
$scripts[] = 'db_rooms_add_activity';
$scripts[] = 'db_rooms_news_to_announcements';
$scripts[] = 'db_user_editor_to_moderator';
$scripts[] = 'db_rooms_fix_server_item';
$scripts[] = 'html_entity_decode';
$scripts[] = 'db_add_table_portal';
$scripts[] = 'db_drop_campus_id';
$scripts[] = 'db_add_table_server';
$scripts[] = 'var_files_rename';
$scripts[] = 'db_copy_user_from_community_to_portal';
$scripts[] = 'db_clean_homeconf';
$scripts[] = 'config_to_db_extra_ontology';
$scripts[] = 'config_to_db_extra_todo';
$scripts[] = 'db_clean_links';
$scripts[] = 'db_user_fix_root_item';
$scripts[] = 'db_links_course_projects';
$scripts[] = 'config_to_db_extra_rubric_names';
$scripts[] = 'db_portalwellcometext_2_description';
$scripts[] = 'db_clean_items';
$scripts[] = 'db_add_language_2_rooms';
$scripts[] = 'db_rename_table_courses';
$scripts[] = 'db_rename_table_announcements';
$scripts[] = 'db_update_visibility';
$scripts[] = 'db_merge_project_community_to_room';
$scripts[] = 'db_change_rid_to_cid';
$scripts[] = 'db_room_context';
$scripts[] = 'db_open_for_guests';
$scripts[] = 'db_portal_check_new_member';
$scripts[] = 'db_user_language_at_portal';
$scripts[] = 'db_user_fix_root_item2';
$scripts[] = 'db_change_homeconf_course';
$scripts[] = 'db_links_room_contact';
$scripts[] = 'db_rename_logarchive_mod_2_module';
$scripts[] = 'db_community_merge_contact_moderator';
$scripts[] = 'db_rooms_files_rename_sponsoring';
$scripts[] = 'db_room_page_impression';
$scripts[] = 'db_rooms_add_public';
$scripts[] = 'db_room_copy_desc_from_course';
$scripts[] = 'db_rooms_files_rename_logo';
$scripts[] = 'db_user_files_rename_picture';
$scripts[] = 'db_label_files_rename_picture';

set_time_limit(0);

// start of execution time
$time_start_all = getmicrotime();

$title = 'Master Update Script for CommSy Update 3.3.1 to 4.0.0';

echo('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">'."\n");
echo('<html>'."\n");
echo('<head>'."\n");
echo('<title>'.$title.'</title>'."\n");
echo('</head>'."\n");
echo('<body>'."\n");
echo('<h2>'.$title.'</h2>'."\n");

$first = true;
foreach ($scripts as $script) {
   $success = FALSE;
   if ($first) {
      $first = false;
   } else {
      echo "<br/><b>---------------------------------</b><br/>"."\n";
   }
   echo('<h3>'.$script);
   if ($test) {
      echo(' (testing)');
   } else {
      echo(' (executing)');
   }
   echo('</h3>'."\n");
   echo '<script type="text/javascript">window.scrollTo(1,10000000);</script>'."\n";
   flush();

   include_once($script.".php");
   echo '<script type="text/javascript">window.scrollTo(1,10000000);</script>'."\n";
   flush();

   if ($success == FALSE) {
      echo "<font color='#ff0000'><b> [failed]</b></font>"."\n";
      break;
   } else {
      echo "<font color='#00ff00'><b> [done]</b></font>"."\n";
   }
   echo('<br>');
   echo '<script type="text/javascript">window.scrollTo(1,10000000);</script>'."\n";
   flush();

   // um mysql eine verschnaufpause zwischen jedem script zu gönnen
   sleep(5);
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start_all,3);
echo "<br/><br/><b>".count($scripts)." scripts processed in ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))." hours</b><br><br><br>\n";
echo '<script type="text/javascript">window.scrollTo(1,10000000);</script>';
echo('</body>'."\n");
echo('</html>'."\n");
flush();
?>