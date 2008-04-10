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
include_once('../../functions/misc_functions.php');

function rubricTranslationArray() {
   global $test;

   $rubric_array = array();
   $rubric_array[] = 'COURSES';
   $rubric_array[] = 'INSTITUTION';
   $rubric_array[] = 'TOPICS';

   echo "<br />Migration der einzelnen Rubric-Translation-Arrays zu einem einzigen.<br /><br />\n\n";

   if (!$test) {
      $result = select('SELECT * FROM campus;');
      while ($row = mysql_fetch_assoc($result)) {
         $extra_array = xml2Array($row['extras']);
         if (!isset($extra_array['RUBRIC_TRANSLATION_ARRAY'])) {
            $translation_array = array();
            foreach ($rubric_array as $rubric) {
               if (isset($extra_array[$rubric.'_ARRAY'])) {
                  $translation_array[$rubric] = $extra_array[$rubric.'_ARRAY'];
                  unset($extra_array[$rubric.'_ARRAY']);
               }
            }
            $extra_array['RUBRIC_TRANSLATION_ARRAY'] = $translation_array;
            if (select('UPDATE campus SET extras="'.addslashes(array2XML($extra_array)).'" WHERE item_id="'.$row['item_id'].'"')) {
               echo('success: Campus - '.$row['title'].' - '.$row['item_id'].'<br />'."\n");
            } else {
               echo('ERROR: Campus - '.$row['title'].' - '.$row['item_id'].'<br />'."\n");
            }
         } else {
            echo('nothing to do: Campus - '.$row['title'].' - '.$row['item_id'].'<br />'."\n");
         }
      }
      echo("<br /><br />done");
   } else {
      echo "<br />testing";
   }
}
rubricTranslationArray();
?>