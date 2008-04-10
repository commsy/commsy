<?
// add_public_fields_to_rubrics.php
// Legt public-Felder für die Kennzeichnung öffentlich
// editierbarer Beiträge in den Rubriken an

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');


function add_public_fields () {
   global $test, $success, $error;

   $tables[] = "announcements";
   $tables[] = "courses";
   $tables[] = "materials";
   $tables[] = "labels";
   $tables[] = "news";
   $tables[] = "dates";
   $tables[] = "discussions";
         
   foreach($tables as $table) {
      $select_query  = 'SHOW COLUMNS FROM '.$table.' LIKE "public"';
      $result = select($select_query);   
      $result_row = mysql_fetch_array($result);
      if(empty($result_row)) {
         $alter_query = "ALTER TABLE $table ADD public TINYINT(11) DEFAULT 0 NOT NULL";
         if(!$test) {
            $success = select($alter_query);
            if (!$success) {
               echo "<br /><b>mysql complains at line ".__LINE__.":</b> ".$error;
               return;
            }
         }
      } else {
         $success = TRUE;
      }
   }
}

add_public_fields();
?>