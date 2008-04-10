<?php

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('xml_functions.php');

function rubricTranslationArray () {
   global $test, $success, $error;
   $rubric_array = array();
   $rubric_array[] = 'TOPICS';
   if (!$test) {
      $result = select('SELECT * FROM rooms;');
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
            $success = select('UPDATE rooms SET extras="'.addslashes(array2XML($extra_array)).'" WHERE item_id="'.$row['item_id'].'"');
            if (!$success) {
               echo "<br /><b>mysql complains:</b> ".$error;
               return;
            }
         } else {
            $success = TRUE;
         }
      }
   }
}

rubricTranslationArray ()
?>