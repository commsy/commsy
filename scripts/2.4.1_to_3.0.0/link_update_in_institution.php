<?
include_once('../migration.conf.php');
include_once('../db_link.dbi.php');

function updateInInstitution () {
   global $test, $success, $error;

   $select_query  = "SELECT links.*,
                     courses.item_id, courses.creator_id, courses.creation_date,
                     labels.item_id FROM links LEFT JOIN courses ON links.from_item_id=courses.item_id
                     LEFT JOIN labels ON links.to_item_id = labels.item_id
                     WHERE links.link_type = 'in_institution' AND links.deleter_id IS NULL";
   $result = select($select_query);
   $number = mysql_num_rows($result);
   if($number == 0) {
      echo "<br />all entries up to date";
      $success = TRUE;
      return;
   }
   init_progress_bar($number);
   while ( $row = mysql_fetch_array($result)) {
         update_progress_bar($number);
        $insert_query =  'INSERT INTO items ( item_id , room_id , campus_id , type , deleter_id , deletion_date , modification_date )
                      VALUES ("", '.$row['room_id'].' , '.$row['campus_id'].', "link_item", NULL , NULL, NULL)';
         if(!$test) {
            $success = select($insert_query);
            if(!$success) {
               echo "<br /><font color='#ff0000'> mysql complains at ".__LINE__."</font>: ".$error;
               return;
            }
         }
        $select_query = "SELECT MAX(items.item_id) AS IID FROM items WHERE items.type = 'link_item'";
        $result2 = select($select_query);
        $item = mysql_fetch_array($result2);
        $iid = $item['IID'];
        $insert_query = 'INSERT INTO link_items ( item_id , room_id , campus_id , creator_id , deleter_id ,
                                  creation_date , modification_date , deletion_date , first_item_id ,
                                  first_item_type , second_item_id ,
                                  second_item_type )
                          VALUES ('.$iid.', '.$row['room_id'].','.$row['campus_id'].', '.$row['creator_id'].', NULL , "'
                              .$row['creation_date'].'", NULL, NULL , '.$row['from_item_id'].', "course", '.$row['to_item_id'].' , "institution")';
         if(!$test) {
            $success = select($insert_query);
            if(!$success) {
               echo "<br /><font color='#ff0000'> mysql complains at ".__LINE__."</font>: ".$error;
               return;
            }
         }
   }
   $insert_query = 'DELETE FROM links WHERE links.link_type="in_institution"';
   if(!$test) {
      $success = select($insert_query);
      if(!$success) {
         echo "<br /><font color='#ff0000'> mysql complains in ".__FILE__."</font>: ".$error;
         return;
      }
   }
}

updateInInstitution();
?>