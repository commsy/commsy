<?
include_once('../migration.conf.php');
include_once('../db_link.dbi.php');

echo "<br /><br />Adding field 'modifier_id' to annotations-table...<br />";
$select_query = "ALTER TABLE `annotations` ADD `modifier_id` INT( 11 ) NOT NULL";
echo "Query: ".$select_query."<br />";
$result = select($select_query);

if (!$result) {
   $error = mysql_error();
   echo "Failed to add 'modifier_id' to annotations-table: ".$error."<br />";    
} else {
   echo "Success! Addded 'modifier_id' to annotations-table <br />";    
}
echo "";

?>