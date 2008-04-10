<?
//TBD: test

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');

function updateMaterialForCourse () {
   global $test, $success, $error;

   $select_query  = "SELECT DISTINCT links.*,
                     courses.item_id, courses.creator_id, courses.creation_date,
                     materials.item_id FROM links LEFT JOIN courses ON links.to_item_id=courses.item_id
                     LEFT JOIN materials ON links.from_item_id = materials.item_id
                     WHERE links.link_type = 'material_for_course' AND links.from_version_id = materials.version_id AND links.deleter_id IS NULL
                     GROUP BY links.from_item_id, links.to_item_id";
   $result = select($select_query);
   $number = mysql_num_rows($result);
   while ( $row = mysql_fetch_array($result)) {
        $insert_query =
        'INSERT INTO items ( item_id , room_id , campus_id , type , deleter_id , deletion_date , modification_date )
                      VALUES ("", '.$row['room_id'].' , '.$row['campus_id'].', "link_item", NULL , NULL, NULL)';
        $success = select($insert_query);
         if(!$success) {
            echo "<br /><b>mysql complains:</b> ".$error;
            return;
         }
        $select_query = "SELECT MAX(items.item_id) AS IID FROM items WHERE items.type = 'link_item'";
        $result2 = select($select_query);
        $item = mysql_fetch_array($result2);
        $iid = $item['IID'];
        $insert_query = 'INSERT INTO link_items ( item_id , room_id , campus_id , creator_id , deleter_id ,
                                  creation_date , modification_date , deletion_date , first_item_id , first_item_type , second_item_id ,
                                  second_item_type )
                          VALUES ('.$iid.', '.$row['room_id'].','.$row['campus_id'].', '.$row['creator_id'].', NULL , "'
                              .$row['creation_date'].'", NULL, NULL , '.$row['from_item_id'].', "material", '.$row['to_item_id'].', "course")';

        $success = select($insert_query);
         if(!$success) {
            echo "<br /><b>mysql complains:</b> ".$error;
            return;
         }

   }
   $insert_query = 'DELETE FROM links WHERE links.link_type="material_for_course"';
   $success = select($insert_query);
   if(!$success) {
      echo "<br /><b>mysql complains:</b> ".$error;
      return;
   }
}

updateMaterialForCourse();

?>