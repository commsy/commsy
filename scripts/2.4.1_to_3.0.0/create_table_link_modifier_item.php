<?
include_once('../migration.conf.php');
include_once('../db_link.dbi.php');

function createLinkModifierItemTable () {
   global $test, $success, $error;
   echo "<br />creating table 'link_modifier_item'";
   $select_query  = "CREATE TABLE link_modifier_item (
                    item_id int(11) NOT NULL default '0',
                    modifier_id int(11) NOT NULL default '0',
                    PRIMARY KEY  (item_id,modifier_id)) TYPE=MyISAM;";
   $success = select("SELECT * FROM link_modifier_item WHERE 1=2");
   if(!$success) {
      $success = select($select_query);
      if(!$success) {
         echo "<br /><b>mysql complains:</b> ".$error;
         return;
      }
   } else {
      echo "<br />table 'link_modifier_item' already exists";
   }
}

function putModifiersAndCreatorsInTable() {
   global $test, $success, $error, $errno;
   // items
$items["announcements"] = "item";
$items["courses"] = "item";
$items["materials"] = "item";
$items["news"] = "item";
$items["dates"] = "item";
$items["discussions"] = "item";
   // label items
$items["topic"] = "label";
$items["institution"] = "label";
$items["group"] = "label";
   echo "<br />adding creators and modifiers to table 'link_modifier_item'";
   foreach($items as $name => $type) {
      echo "<br />updating ".$name;
      $query_select = 'SELECT item_id, modifier_id,creator_id FROM ';
      $query_select .= ($type == "item") ? $name : 'labels WHERE type = "'.$name.'"';
      $result = select($query_select);
      $number = mysql_num_rows($result);
      init_progress_bar($number);
      while($row = mysql_fetch_assoc($result)) {
         update_progress_bar($number);
         if ($row['creator_id'] != NULL) {
            $query_update = 'INSERT INTO link_modifier_item SET '.
                            'item_id = "'.$row['item_id'].'", '.
                            'modifier_id = "'.$row['creator_id'].'"';
            $success = select($query_update);
            if(!$success) {
                  if($errno != 1062) { // 1062: entry exists, we can ignore this one
                     echo "<br /><b>mysql complains:</b> ".$error;
                     echo "<br /><b>about query:</b>'".$query_update."'";
                     return;
                  } else {
                     $success = TRUE;
                  }
            }
         } else {
            echo "<br /><b>Warning: No creator id found for item ".$row['item_id']."</b><br />";
         }
         //if item has been modified by another user after creation, add modifier
         if ($row['modifier_id'] != NULL and $row['modifier_id'] != $row['creator_id']) {
            $query_update = 'INSERT INTO link_modifier_item SET '.
                            'item_id = "'.$row['item_id'].'", '.
                            'modifier_id = "'.$row['modifier_id'].'"';
            $success = select($query_update);
            if(!$success) {
                  if($errno != 1062) { // 1062: entry exists, we can ignore this one
                     echo "<br /><b>mysql complains:</b> ".$error;
                     echo "<br /><b>about query:</b>'".$query_update."'";
                     return;
                  } else {
                     $success = TRUE;
                  }
            }
         }
      }
   }
}

createLinkModifierItemTable();
putModifiersAndCreatorsInTable();
?>