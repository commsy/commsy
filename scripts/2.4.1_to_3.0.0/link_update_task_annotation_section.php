<?
//TBD: test

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');

function updateTasksItem () {
   global $test, $success, $error;

   $select_query  = "SELECT tasks.*,links.to_item_id FROM tasks LEFT JOIN links ON links.from_item_id=tasks.item_id";
   $result = select($select_query);
   $number = mysql_num_rows($result);
   $success = select("SELECT COUNT(linked_item_id) from tasks");
   if($success) { // script already done
      $num_entries = mysql_fetch_row($success);
      if($number == $num_entries[0])  {
         echo "<br /><font color='#00ff00'>tasks already done.</font>";
         return;
      }
   } else {
      $success = select('ALTER TABLE tasks ADD linked_item_id INT(11) DEFAULT "0" NOT NULL');
   }
     if(!$success) {
      echo "<br /><font color='#ff0000'> mysql complains at line ".__LINE__."</font>: ".$error;
      return;
   }
   init_progress_bar($number);
   while ( $row = mysql_fetch_array($result)) {
         update_progress_bar($number);
        $insert_query = 'UPDATE tasks SET linked_item_id="'.$row['to_item_id'].'" WHERE item_id="'.$row['item_id'].'"';
        $success = select($insert_query);
         if(!$success) {
            echo "<br /><font color='#ff0000'> mysql complains at line ".__LINE__."</font>: ".$error;
            return;
         }
        $insert_query = 'DELETE FROM links WHERE from_item_id="'.$row['item_id'].'"';
        $success = select($insert_query);
         if(!$success) {
            echo "<br /><font color='#ff0000'> mysql complains at line ".__LINE__."</font>: ".$error;
            return;
         }
   }
   
   echo "<br /><font color='#00ff00'> tasks updated</font>";
}

function updateSectionItem () {
   global $test, $success, $error;

   $select_query  = "SELECT section.*,links.to_item_id, links.to_version_id FROM section LEFT JOIN links ON links.from_item_id=section.item_id";
   $result = select($select_query);
   $number = mysql_num_rows($result);
   $success = select("SELECT COUNT(material_item_id) from section");
   if($success) { // script already done
      $num_entries = mysql_fetch_row($success);
      if($number == $num_entries[0])  {
         echo "<br /><font color='#00ff00'>sections already done.</font>";
         return;
      }
   } else {
      $success = select('ALTER TABLE section ADD material_item_id INT(11) DEFAULT "0" NOT NULL');
   }
   if(!$success) {
      echo "<br /><font color='#ff0000'> mysql complains at line ".__LINE__."</font>: ".$error;
      return;
   }
   init_progress_bar($number);
   while ( $row = mysql_fetch_array($result)) {
      update_progress_bar($number);
        if (empty($row['to_version_id'])){
           $row['to_version_id']= (int)0;
        }
        $insert_query = 'UPDATE section SET material_item_id="'.$row['to_item_id'].'" WHERE item_id="'.$row['item_id'].'" AND version_id="'.$row['to_version_id'].'"';
        $success = select($insert_query);
         if(!$success) {
            echo "<br /><font color='#ff0000'> mysql complains at line ".__LINE__."</font>: ".$error;
            return;
         }
        $insert_query = 'DELETE FROM links WHERE from_item_id="'.$row['item_id'].'" AND link_type="section_for"';
        $success = select($insert_query);
         if(!$success) {
            echo "<br /><font color='#ff0000'> mysql complains at line ".__LINE__."</font>: ".$error;
            return;
         }
   }
   echo "<br /><font color='#00ff00'> sections updated</font>";
}


function updateAnnotationItem () {
   global $test, $success, $error;

   $select_query  = "SELECT annotations.*,links.to_item_id, links.to_version_id FROM annotations LEFT JOIN links ON links.from_item_id=annotations.item_id";
   $result = select($select_query);
   $number = mysql_num_rows($result);
   $success = select("SELECT COUNT(linked_item_id) from annotations");
   if($success) { // script already done
      $num_entries = mysql_fetch_row($success);
      if($number == $num_entries[0])  {
         echo "<br /><font color='#00ff00'>annotations already done.</font>";
         return;
      }
   } else {
      $success = select('ALTER TABLE annotations ADD linked_item_id INT(11) DEFAULT "0" NOT NULL');
      if(!$success) {
         echo "<br /><font color='#ff0000'> mysql complains at line ".__LINE__."</font>: ".$error;
         return;
      }
      $success = select('ALTER TABLE annotations ADD linked_version_id INT(11) DEFAULT "0" NOT NULL');
   }
   if(!$success) {
      echo "<br /><font color='#ff0000'> mysql complains at line ".__LINE__."</font>: ".$error;
      return;
   }
   init_progress_bar($number);
   while ( $row = mysql_fetch_array($result)) {
         update_progress_bar($number);
        if (empty($row['to_version_id'])){
           $row['to_version_id']= (int)0;
        }

        $insert_query = 'UPDATE annotations SET linked_item_id="'.$row['to_item_id'].'" WHERE item_id="'.$row['item_id'].'"';
        $success = select($insert_query);
         if(!$success) {
            echo "<br /><font color='#ff0000'> mysql complains at line ".__LINE__."</font>: ".$error;
            return;
         }
        $insert_query = 'UPDATE annotations SET linked_version_id="'.$row['to_version_id'].'" WHERE item_id="'.$row['item_id'].'"';
        $success = select($insert_query);
         if(!$success) {
            echo "<br /><font color='#ff0000'> mysql complains at line ".__LINE__."</font>: ".$error;
            return;
         }
        $insert_query = 'DELETE FROM links WHERE from_item_id="'.$row['item_id'].'" AND link_type="annotation_of"';
        $success = select($insert_query);
         if(!$success) {
            echo "<br /><font color='#ff0000'> mysql complains at line ".__LINE__."</font>: ".$error;
            return;
         }
   }         
         echo "<br /><font color='#00ff00'> annotations updated</font>";
}

updateTasksItem();
updateSectionItem();
updateAnnotationItem();
?>