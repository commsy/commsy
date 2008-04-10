<?php

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');

function updateCopyOf () {
   global $test, $success, $error;
   $success = select("SELECT copy_of from materials WHERE 1=2");
   echo "<br />adding field 'copy_of' to table 'materials'";
   if($success) {
      //script already performed
      echo "<br />field 'materials.copy_of' already exists";
   } else {
      $alter_query = 'ALTER TABLE materials ADD copy_of INT(11) DEFAULT NULL';
      $success = select($alter_query);
      if(!$success) {
         echo "<br /><b>mysql complains:</b> ".$error;
         return;
      }
   }

   $select_query  = 'SELECT materials.*,links.to_item_id, links.to_version_id FROM materials LEFT JOIN links ON links.from_item_id=materials.item_id AND links.from_version_id=materials.version_id WHERE links.link_type="copy_of"';
   $result = select($select_query);
   $number = mysql_num_rows($result);
   init_progress_bar($number);
   while ( $row = mysql_fetch_array($result)) {
         update_progress_bar($number);
         if (empty($row['to_version_id'])){
            $row['to_version_id']= (int)0;
         }
         $insert_query = 'UPDATE materials SET copy_of="'.$row['to_item_id'].'" WHERE item_id="'.$row['item_id'].'"';
         $success = select($insert_query);
         if(!$success) {
            echo "<br /><b>query failed:</b> $insert_query";
            echo "<br /><b>mysql complains:</b> ".$error;
            return;
         }
         $insert_query = 'DELETE FROM links WHERE from_item_id="'.$row['item_id'].'" AND link_type="copy_of"';
         $success = select($insert_query);
         if(!$success) {
            echo "<br /><b>query failed:</b> $insert_query";
            echo "<br /><b>mysql complains:</b> ".$error;
            return;
         }
         if ($row['room_id']!=0){
            $insert_query2 = 'DELETE FROM links WHERE from_item_id="'.$row['item_id'].'" AND from_version_id="'.$row['version_id'].'" AND (link_type="material_for_topic" OR link_type="material_for_course" OR link_type="material_for_institution")';
            $success = select($insert_query2);
            if(!$success) {
               echo "<br /><b>mysql complains:</b> ".$error;
               return;
            }
         }
   }
}

updateCopyOf();
?>