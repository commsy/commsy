<?
// set_del_in_items_script.php
// Setzt in der items-Tabelle deletion_date und deleter_id,
// falls in zugehörigen items aus den anderen Tabellen deleter_id gesetzt ist


include_once('migration.conf.php');
include_once('db_link.dbi.php');

function deleteInItems ($tabelle) {
   global $test;

   $select_query  = "SELECT ".$tabelle.".* FROM ".$tabelle." INNER JOIN ";
   $select_query .= "items AS I1 ON I1.item_id=".$tabelle.".item_id ";
   $select_query .= "WHERE ".$tabelle.".deleter_id IS NOT NULL ";
   $select_query .= "AND I1.deleter_id IS NULL ";

   echo "<br />Hole alle gelöschten items aus Tabelle ".$tabelle.": ";
   echo "<br />Select_Query: ".$select_query;
   $result = select($select_query);
   $number = mysql_num_rows($result);
   echo "<br />Anzahl: ".$number;

   while ( $row = mysql_fetch_array($result)) {
        $item_id = $row['item_id'];
        $deleter_id = $row['deleter_id'];
        $deletion_date = $row['deletion_date'];

        echo "<hr>";
        echo "<br /> Item_id: ".$item_id." Deleter_id: ".$deleter_id;

        $insert_query  = "UPDATE items SET deleter_id=".$deleter_id.", ";
        $insert_query .= " deletion_date='".$deletion_date."'";
        $insert_query .= " WHERE item_id=".$item_id." AND deleter_id is NULL";

        echo "<br /> Setze deleter und deletion_date in der items tabelle für Item:".$item_id;
        echo "<br /> Insert_Query: ".$insert_query;
        if (!$test) {
           insert($insert_query);
        }
        $check_query = "SELECT item_id, deleter_id, deletion_date FROM items";
        $check_query .= " WHERE item_id=".$item_id;

        echo "<br />Überprüfe, ob item gelöscht in items: ";
        echo "<br />Check_Query: ".$check_query;
        $check_result = select($check_query);
        while ( $check_row = mysql_fetch_array($check_result) ) {
           $item_id = $check_row['item_id'];
           $deleter_id = $check_row['deleter_id'];
           $deletion_date = $check_row['deletion_date'];

           echo "<hr>";
           echo "<br />Item_id: ".$item_id." Deleter: ".$deleter_id." Deletion_date: ".$deletion_date;
        }
   }
   $row = "";
   $check_row = "";   

}

deleteInItems ('annotations');
echo "<HR><hr>";
deleteInItems ('announcements');
echo "<HR><hr>";
deleteInItems ('campus');
echo "<HR><hr>";
deleteInItems ('courses');
echo "<HR><hr>";
deleteInItems ('dates');
echo "<HR><hr>";
deleteInItems ('discussionarticles');
echo "<HR><hr>";
deleteInItems ('discussions');
//echo "<HR><hr>";
//deleteInItems ('dossiers');
echo "<HR><hr>";
deleteInItems ('labels');
echo "<HR><hr>";
deleteInItems ('materials');
echo "<HR><hr>";
deleteInItems ('news');
echo "<HR><hr>";
deleteInItems ('rooms');
echo "<HR><hr>";
deleteInItems ('section');
echo "<HR><hr>";
deleteInItems ('tasks');
echo "<HR><hr>";
deleteInItems ('user');



?>