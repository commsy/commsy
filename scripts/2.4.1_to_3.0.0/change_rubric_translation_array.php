<?php

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('xml_functions.php');

function changeRubricTranslationArray() {
   global $test, $success, $error;

#   echo "<br />Umbenennung der Translation-Array:<br />COURSES -> COURSE<br />TOPICS -> TOPIC<br /><br />\n\n";

   // community room
   $result = select('SELECT * FROM campus;');
   while ($row = mysql_fetch_assoc($result)) {
      $extra_array = xml2Array($row['extras']);
      if (isset($extra_array['RUBRIC_TRANSLATION_ARRAY'])) {
         $translation_array = $extra_array['RUBRIC_TRANSLATION_ARRAY'];
         $result_array = $translation_array;
         foreach ($translation_array as $rubric) {
            if ($rubric['NAME'] == 'courses') {
               $result_array['COURSE'] = $rubric;
               unset($result_array['COURSES']);
            }
            if ($rubric['NAME'] == 'topics') {
               $result_array['TOPIC'] = $rubric;
               unset($result_array['TOPICS']);
            }
         }
         $extra_array['RUBRIC_TRANSLATION_ARRAY'] = $result_array;
         $update_query = 'UPDATE campus SET extras="'.addslashes(array2XML($extra_array)).'" WHERE item_id="'.$row['item_id'].'"';
         if(!$test) {
            $success = select($update_query);
            if (!$success) {
               echo "<br /><b>mysql complains at line ".__LINE__.":</b> ".$error;
               return;
            }
         }
      } else {
         $success = TRUE;
      }
   }

   // project room
   $result = select('SELECT * FROM rooms;');
   while ($row = mysql_fetch_assoc($result)) {
      $extra_array = xml2Array($row['extras']);
      if (isset($extra_array['RUBRIC_TRANSLATION_ARRAY'])) {
         $translation_array = $extra_array['RUBRIC_TRANSLATION_ARRAY'];
         $result_array = $translation_array;
         if(is_array($translation_array)) {
            foreach ($translation_array as $rubric) {
               if ($rubric['NAME'] == 'topics') {
                  $result_array['TOPIC'] = $rubric;
                  unset($result_array['TOPICS']);
               }
            }
         }
         $extra_array['RUBRIC_TRANSLATION_ARRAY'] = $result_array;
         $update_query = 'UPDATE rooms SET extras="'.addslashes(array2XML($extra_array)).'" WHERE item_id="'.$row['item_id'].'"';
         if(!$test) {
            $success = select($update_query);
            if (!$success) {
               echo "<br /><b>mysql complains at line ".__LINE__.":</b> ".$error;
               return;
            }
         }
      }
   }
}

changeRubricTranslationArray();

?>