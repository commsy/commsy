<?php
include_once('../migration.conf.php');
include_once('../db_link.dbi.php');

function add_modifier_id () {
   global $test, $success, $error;

   $rubric_array = array();
   $rubric_array[] = 'announcements';
   $rubric_array[] = 'annotations';
   $rubric_array[] = 'dates';
   $rubric_array[] = 'discussions';
   $rubric_array[] = 'discussionarticles';
   $rubric_array[] = 'labels';
   $rubric_array[] = 'news';
   $rubric_array[] = 'user';
   $rubric_array[] = 'section';

   $query = 'ALTER TABLE materials CHANGE modificator modifier_id INT( 11 ) DEFAULT NULL';

   $success = select("SELECT modifier_id from materials WHERE 1=2");
   if($success) {
      echo '<br />field "modifier_id" already exists in table materials';
   } else {
        if (!$test) {
         $success = select($query);
         if ($success) {
               echo('<br />changed field "modificator" to "modifier_id" in table materials'."\n");
         } else {
            echo "<br /><b>mysql complains at line ".__LINE__.":</b> ".$error;
            return;
         }
      }
   }
   foreach ($rubric_array as $rubric) {
      $success = select("SELECT modifier_id from ".$rubric." WHERE 1=2");
      if($success) {
         echo '<br />field "modifier_id" already exists in table '.$rubric;
      } else {
         $query = 'ALTER TABLE '.$rubric.' ADD modifier_id INT( 11 ) DEFAULT NULL AFTER creator_id';
         if (!$test) {
            $success = select($query);
            if ($success) {
               echo('<br />added field "modifier_id" to table '.$rubric."\n");
            } else {
               echo "<br /><b>mysql complains at line ".__LINE__.":</b> ".$error;
               return;
            }
         } else {
            echo ('<br />'.$query."\n");
         }
      }
   }
}
add_modifier_id();
?>