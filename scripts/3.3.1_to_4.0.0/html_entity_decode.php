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
set_time_limit(0);

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('../../functions/misc_functions.php');

function decode ($string) {
   while (stristr($string,'&amp;') or stristr($string,'&quot;') or stristr($string,'&#039;') or stristr($string,'&lt;') or stristr($string,'&gt;')) {
      $string = html_entity_decode($string);
      // html_entity_decode doesn't decode "&#039;", so ...
      $string = str_replace("&#039;","'",$string);
   }
   return $string;
}

function extras_decode ($string) {
   $xml_array = XML2Array($string);
   $xml_array = xml_decode($xml_array);
   $string = array2XML($xml_array);
   return $string;
}

function xml_decode ($array) {
   $xml_array = array();
   if (!empty($array) and is_array($array)) {
      $keys = array_keys($array);
      foreach ($keys as $key) {
         if (is_array($array[$key])) {
            if (count($array[$key]) > 0) {
               $data = xml_decode($array[$key]);
            } else {
               $data = '';
            }
         } else {
			   $data = decode_xml_chars($array[$key]);
            //$data = htmlspecialchars(decode($array[$key]));
         }
         $xml_array[$key] = $data;
      }
   }
   return $xml_array;
}

function selectRubric ($rubric) {
   return select('SELECT * FROM '.$rubric.';');
}

function countEntries ($rubric, $id_name) {
   $count = array_shift(mysql_fetch_row(select("SELECT COUNT(".$rubric.".".$id_name.") FROM ".$rubric.";")));
   return $count;
}

function updateRubricEntry ($rubric, $iid, $vid, $change_values,$id_name) {
   global $test;
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
      $query .= ' WHERE '.$id_name.'="'.$iid.'"';
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
				$_success = false;
         }
      } else {
         echo ('<br />'.$query."\n");
      }
   }
}

function entity_decode () {
   echo ('This script cleans up the formatting of text in the DB.'."\n");
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
   $rubric_array[] = 'rooms';
   $rubric_array[] = 'section';
   $rubric_array[] = 'user';
   $rubric_array[] = 'todos';
   $rubric_array[] = 'ontologies';
   $rubric_array[] = 'ont_categories';


   foreach ($rubric_array as $rubric) {
      echo('<br /><br />'."\n");
      echo('Rubric: '.strtoupper($rubric).'<br />'."\n");

		$id_name = 'item_id';
		if ($rubric == 'ont_categories') {
		   $id_name =  'category_id';
		}

      $count = countEntries($rubric, $id_name);
      init_progress_bar($count);

      $result = selectRubric($rubric);
      while ($row = mysql_fetch_assoc($result)) {
         $update_array = array();
         foreach ($row as $key => $value) {
            if ($key == 'extras') {
               $update_array[$key] = addslashes(extras_decode($value));
            } elseif (stristr($value,'&amp;') or stristr($value,'&quot;') or stristr($value,'&#039;') or stristr($value,'&lt;') or stristr($value,'&gt;')) {
               $update_array[$key] = addslashes(decode($value));
            }
         }
         if (count($update_array) > 0) {
            $vid = '';
            if (empty($row['version_id']) and $row['version_id'] == "0") {
               $vid = 'NULL';
            } elseif (!empty($row['version_id'])) {
               $vid = $row['version_id'];
            }
            updateRubricEntry($rubric,$row[$id_name],$vid,$update_array,$id_name);
         }
         update_progress_bar($count);
      }
   }
}

function decode_xml_chars($text) {
   $data = $text;
   if ( strstr($text,"&lt;") ) {
      $data = ereg_replace("&lt;", "<", $data);
   }
   if ( strstr($data,"&gt;") ) {
      $data = ereg_replace("&gt;", ">", $data);
   }
   if ( strstr($data,"%CS_AND;") ) {
      $data = ereg_replace("%CS_AND;", "&", $data);
   }
	/*if ( strstr($data,"&quot;") ) {
      $data = ereg_replace("&quot;", '"', $data);
   }*/
	return $data;
}

$_success = TRUE;
// time management for this script
$time_start = getmicrotime();
entity_decode();
if ($_success) {
   echo('[ <font color="#00ff00">done</font> ]<br/>'."\n");
} else {
   echo('[ <font color="#ff0000">failed</font> ]<br/>'."\n");
}
$success = $_success;
// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";

?>