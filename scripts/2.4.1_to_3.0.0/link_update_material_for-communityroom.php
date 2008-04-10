<?
//TBD: test

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');

function updateMaterialForCommunityRoom () {
   global $test;
   $select_query  = "SELECT DISTINCT links.*,
                     items.item_id, items.type,
                     materials.item_id FROM links LEFT JOIN items ON links.to_item_id=items.item_id
                     LEFT JOIN materials ON links.from_item_id = materials.item_id
                     WHERE links.link_type = 'material_for' AND links.from_version_id = materials.version_id AND links.deleter_id IS NULL
                     AND (links.room_id='0' OR links.room_id IS NULL)
                     GROUP BY links.from_item_id, links.to_item_id";
   echo "<br />Hole alle Items:";
   echo "<br />Select_Query: ".$select_query;
   $result = select($select_query);
   $number = mysql_num_rows($result);
   echo "<br />Anzahl: ".$number;
   while ( $row = mysql_fetch_array($result)) {
        if ($row['type'] =='announcements'){
           $select_query  = "SELECT announcements.* FROM announcements
                     WHERE announcements.item_id = '".$row['item_id']."'";
           echo("<br />Select_Query: ".$select_query);
           var_dump($result_temp);
           $result_temp = select($select_query);
           $second_item = mysql_fetch_array($result_temp);
        }else{
           echo($result_temp);
           $select_query  = "SELECT section.* FROM section
                     WHERE section.item_id = '".$row['item_id']."' AND section.version_id = '".$row['to_version_id']."'";
           echo("<br />Select_Query: ".$select_query);
           $result_temp = select($select_query);
           $second_item = mysql_fetch_array($result_temp);
        }
        echo('Füge neues Item in der Item-Tabelle ein<br />');
        $insert_query =
        'INSERT INTO items ( item_id , room_id , campus_id , type , deleter_id , deletion_date , modification_date )
                      VALUES ("", '.$row['room_id'].' , '.$row['campus_id'].', "link_item", NULL , NULL, NULL)';
        echo($insert_query.'<br /><br />');
        select($insert_query);
        echo('Hole mir das aktuelle Item');
        $select_query = "SELECT MAX(items.item_id) AS IID FROM items WHERE items.type = 'link_item'";
        echo($select_query.'<br /><br />');
        $result2 = select($select_query);
        $item = mysql_fetch_array($result2);
        $iid = $item['IID'];
        echo($iid.'<br /><br />');
        if ($row['type'] =='announcements'){
           $insert_query = 'INSERT INTO link_items ( item_id , room_id , campus_id , creator_id , deleter_id ,
                                  creation_date , modification_date , deletion_date , first_item_id ,
                                  first_version_id , first_item_type , second_item_id , second_version_id ,
                                  second_item_type )
                          VALUES ('.$iid.', '.$row['room_id'].','.$row['campus_id'].', '.$second_item['creator_id'].', NULL , "'
                              .$second_item['creation_date'].'", NULL, NULL , '.$row['from_item_id'].', '
                              .$row['from_version_id'].', "material", '.$row['to_item_id'].', '
                              .$row['to_version_id'].', "announcement")';
        }else{
           $insert_query = 'INSERT INTO link_items ( item_id , room_id , campus_id , creator_id , deleter_id ,
                                  creation_date , modification_date , deletion_date , first_item_id ,
                                  first_version_id , first_item_type , second_item_id , second_version_id ,
                                  second_item_type )
                          VALUES ('.$iid.', '.$row['room_id'].','.$row['campus_id'].', '.$second_item['creator_id'].', NULL , "'
                              .$second_item['creation_date'].'", NULL, NULL , '.$row['from_item_id'].', '
                              .$row['from_version_id'].', "material", '.$row['to_item_id'].', '
                              .$row['to_version_id'].', "section")';
        }
        echo($insert_query.'<br />');
           select($insert_query);
     }
   echo('Die Linktabelleneintrag löschen:<br />');
   $insert_query = 'DELETE FROM links WHERE links.link_type="material_for" AND (links.room_id="0" OR links.room_id IS NULL)';
   echo($insert_query.'<br /><br />');
   select($insert_query);
}

updateMaterialForCommunityRoom();

?>