<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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
include_once('../update_functions.php');

// time management for this script
$time_start = getmicrotime();

// add column "has_html" to table "files"
echo ('english translation: change XYZ room to XYZ workspace'."\n");
$success = true;

$table = array();
$table[] = 'room';
$table[] = 'portal';
$table[] = 'server';

foreach ( $table as $tbl ) {
   echo("<br/><br/>".$tbl);
   $count = array_shift(mysql_fetch_row(select("SELECT COUNT(*) FROM ".$tbl.";")));
   if ($count < 1) {
      echo "<br />nothing to do.";
   } else {
      init_progress_bar($count);

      $query  = "SELECT item_id, extras FROM ".$tbl.";";
      $result = select($query);
      $row = mysql_fetch_row($result);
      $item_id = $row[0];
      $extra = $row[1];
      while (isset($item_id)) {
         $extra_array = unserialize($extra);

         // project room -> project workspace
         // community room -> community workspace
         // class room -> class workspace
         // school -> school workplace
         // Schule -> Schulraum

         if ( !empty($extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['NOMS'])
              and $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['NOMS'] == 'project room'
            ) {
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['NOMS'] = 'project workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['GENS'] = 'project workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['AKKS'] = 'project workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['DATS'] = 'project workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['NOMPL'] = 'project workspaces';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['GENPL'] = 'project workspaces';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['AKKPL'] = 'project workspaces';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['DATPL'] = 'project workspaces';
         }
         if ( !empty($extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['NOMS'])
              and $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['NOMS'] == 'class room'
            ) {
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['NOMS'] = 'class workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['GENS'] = 'class workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['AKKS'] = 'class workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['DATS'] = 'class workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['NOMPL'] = 'class workspaces';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['GENPL'] = 'class workspaces';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['AKKPL'] = 'class workspaces';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['PROJECT']['EN']['DATPL'] = 'class workspaces';
         }
         if ( !empty($extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['NOMS'])
              and $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['NOMS'] == 'community room'
            ) {
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['NOMS'] = 'community workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['GENS'] = 'community workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['AKKS'] = 'community workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['DATS'] = 'community workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['NOMPL'] = 'community workspaces';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['GENPL'] = 'community workspaces';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['AKKPL'] = 'community workspaces';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['DATPL'] = 'community workspaces';
         }
         if ( !empty($extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['NOMS'])
              and $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['NOMS'] == 'group room'
            ) {
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['NOMS'] = 'group workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['GENS'] = 'group workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['AKKS'] = 'group workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['DATS'] = 'group workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['NOMPL'] = 'group workspaces';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['GENPL'] = 'group workspaces';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['AKKPL'] = 'group workspaces';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['DATPL'] = 'group workspaces';
         }
         if ( !empty($extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['NOMS'])
              and $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['NOMS'] == 'school'
            ) {
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['NOMS'] = 'school workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['GENS'] = 'school workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['AKKS'] = 'school workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['DATS'] = 'school workspace';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['NOMPL'] = 'school workspaces';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['GENPL'] = 'school workspaces';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['AKKPL'] = 'school workspaces';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['DATPL'] = 'school workspaces';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['EN']['GENUS'] = 'M';
         }
         if ( !empty($extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['DE']['NOMS'])
              and $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['DE']['NOMS'] == 'Schule'
            ) {
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['DE']['NOMS'] = 'Schulraum';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['DE']['GENS'] = 'Schulraums';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['DE']['AKKS'] = 'Schulraum';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['DE']['DATS'] = 'Schulraum';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['DE']['NOMPL'] = 'Schulräume';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['DE']['GENPL'] = 'Schulräume';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['DE']['AKKPL'] = 'Schulräume';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['DE']['DATPL'] = 'Schulräumen';
            $extra_array['RUBRIC_TRANSLATION_ARRAY']['COMMUNITY']['DE']['GENUS'] = 'M';
         }

         $update_query = 'UPDATE '.$tbl.' SET extras="'.addslashes(serialize($extra_array)).'" WHERE item_id="'.$item_id.'";';
         $success2 = select($update_query);
         $success = $success and $success2;

         $row = mysql_fetch_row($result);
         $item_id = $row[0];
         $extra = $row[1];
         update_progress_bar($count);
      }
   }

}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>