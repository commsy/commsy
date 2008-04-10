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

function encode ($string) {
   $string = htmlspecialchars($string);
   return $string;
}

function selectRubric ($rubric) {
   return select('SELECT * FROM '.$rubric.';');
}

function updateRubricEntry ($rubric, $iid, $vid, $change_values) {
   global $test, $success, $error;
   if (count($change_values) > 0) {
      $first = true;
      $query = 'UPDATE '.$rubric.' SET ';
      foreach ($change_values as $key => $value) {
         if ($first) {
            $first = false;
         } else {
            $query .= ',';
         }
         $query .= $key.'="'.$value.'"';
      }
      $query .= ' WHERE item_id="'.$iid.'"';
      if (!empty($vid) AND $vid == 'NULL') {
         $query .= ' AND version_id="0"';
      } elseif (!empty($vid)) {
         $query .= ' AND version_id="'.$vid.'"';
      }
      $query .= ';';
      if (!$test) {
         $success = select($query);
         if (!$success) {
            echo('<br />FAILED item_id: '.$iid."\n");
            echo('<br />'.$query.'<br />'."\n");
            $success = FALSE;
         }
      }
   }
}

function entity_encode () {
   global $test, $success, $error;
   $rubric_array = array();
   $rubric_array[] = 'annotations';
   $rubric_array[] = 'announcements';
   $rubric_array[] = 'campus';
   $rubric_array[] = 'courses';
   $rubric_array[] = 'dates';
   $rubric_array[] = 'discussionarticles';
   $rubric_array[] = 'discussions';
   $rubric_array[] = 'labels';
   $rubric_array[] = 'materials';
   $rubric_array[] = 'news';
   $rubric_array[] = 'rooms';
   $rubric_array[] = 'section';
   $rubric_array[] = 'user';

   foreach ($rubric_array as $rubric) {
      echo "<br />updating ".$rubric;
      $result = selectRubric($rubric);
      $number = mysql_num_rows($result);
      init_progress_bar($number);
      while ($row = mysql_fetch_assoc($result)) {
      update_progress_bar($number);
         $update_array = array();
         foreach ($row as $key => $value) {
            if ($key != 'campus_id' and
                $key != 'room_id' and
                $key != 'item_id' and
                $key != 'files_id' and
                $key != 'creation_date' and
                $key != 'modification_date' and
                $key != 'deletion_date' and
                $key != 'creator_id' and
                $key != 'modificator_id' and
                $key != 'modificator' and
                $key != 'deleter_id' and
                $key != 'new_hack' and
                $key != 'copy_of' and
                $key != 'public' and
                $key != 'linked_item_id' and
                $key != 'linked_version_id' and
                $key != 'enddate' and
                $key != 'status' and
                $key != 'room_item_id' and
                $key != 'datetime_start' and
                $key != 'datetime_end' and
                $key != 'discussion_id' and
                $key != 'latest_article_item_id' and
                $key != 'latest_article_modification_date' and
                $key != 'type' and
                $key != 'material_item_id' and
                $key != 'number' and
                $key != 'lastlogin' and
                $key != 'extras'
               ) {
               $update_array[$key] = addslashes(encode($value));
            }
         }
         if (count($update_array) > 0) {
            $vid = '';
            if (isset($row['version_id']) and $row['version_id'] == "0") {
               $vid = 'NULL';
            } elseif (isset($row['version_id']) and !empty($row['version_id'])) {
               $vid = $row['version_id'];
            }
            updateRubricEntry($rubric,$row['item_id'],$vid,$update_array);
         }
      }
   }
}

entity_encode();

?>