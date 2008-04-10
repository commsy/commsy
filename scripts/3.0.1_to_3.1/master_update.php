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

$test = false;
#$test = true;

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('../update_functions.php');

$scripts[] = 'update_user_organizer_cr';
$scripts[] = 'update_email_texts_pr';
$scripts[] = 'update_email_texts_cr';
$scripts[] = 'update_rubric_translation_cr';
$scripts[] = 'update_rubric_translation_pr';
$scripts[] = 'add_date_mode_field_to_dates';
$scripts[] = 'add_position_field_to_ont_categories';

set_time_limit(0);

// start of execution time
$time_start_all = getmicrotime();

echo('<h2>Master Update Script for CommSy Update 3.0.1 to 3.1</h2>');

$first = true;
foreach ($scripts as $script) {
   $success = FALSE;
   if ($first) {
      $first = false;
   } else {
      echo "<br /><b>---------------------------------</b><br />";
   }
   echo('<h3>'.$script);
   if ($test) {
      echo(' (testing)');
   } else {
      echo(' (executing)');
   }
   echo('</h3>');
   echo '<script language="Javascript">window.scrollTo(1,10000000);</script>';
   flush();

   include_once($script.".php");
   echo '<script language="Javascript">window.scrollTo(1,10000000);</script>';
   flush();

   if ($success == FALSE) {
      echo "<font color='#ff0000'><b> [failed]</b></font>";
      break;
   } else {
      echo "<font color='#00ff00'><b> [done]</b></font>";
   }
   echo('<br />');
   echo '<script language="Javascript">window.scrollTo(1,10000000);</script>';
   flush();
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start_all,3);
echo "<br /><br /><b>".count($scripts)." scripts processed in ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))." hours</b><br /><br /><br />\n";
echo '<script language="Javascript">window.scrollTo(1,10000000);</script>';
flush();
?>