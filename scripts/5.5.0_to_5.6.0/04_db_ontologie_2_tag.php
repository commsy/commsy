<?php
//
//
// Copyright (c)2002-2007 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

function migrateCategories ( $old_id, $new_id, $context_id ) {
   $sql  = 'SELECT ont_categories.* FROM ont_categories';
   $sql .= ' INNER JOIN ont_parents ON';
   $sql .= ' ont_parents.parent_id="'.$old_id.'"';
   $sql .= ' AND ont_parents.direct_parent="yes"';
   $sql .= ' AND ont_parents.child_id=ont_categories.category_id';

   $result = select($sql);
   while ( $row = mysql_fetch_assoc($result) ) {

      // cat to tag
      $sql = 'INSERT INTO items SET type="tag", context_id="'.$context_id.'", modification_date=now();';
      $new_item_id = insert($sql);

      $sql  = 'INSERT INTO tag SET ';
      $sql .= ' item_id="'.$new_item_id.'",';
      $sql .= ' context_id="'.$context_id.'",';
      $sql .= ' creator_id="99",';
      $sql .= ' modifier_id="99",';
      $sql .= ' creation_date=now(),';
      $sql .= ' modification_date=now(),';
      $sql .= ' title="'.addslashes($row['title']).'";';
      insert($sql);

      // link new tag to father tag
      $sql = 'SELECT count(link_id) FROM tag2tag WHERE from_item_id ="'.$new_id.'";';
      $count2 = array_shift(mysql_fetch_row(select($sql)));
      if ( empty($count2) ) {
         $count2 = 1;
      } else {
         $count2++;
      }
      $sql  = 'INSERT INTO tag2tag SET';
      $sql .= ' from_item_id="'.$new_id.'",';
      $sql .= ' to_item_id="'.$new_item_id.'",';
      $sql .= ' context_id="'.$context_id.'",';
      $sql .= ' creator_id="99",';
      $sql .= ' modifier_id="99",';
      $sql .= ' creation_date=now(),';
      $sql .= ' modification_date=now(),';
      $sql .= ' sorting_place="'.$count2.'";';
      insert($sql);

      // link(s) to item(s)
      $sql = 'SELECT * FROM ont_links WHERE category_id="'.$old_id.'";';
      $result2 = select($sql);
      while ( $row2 = mysql_fetch_assoc($result2) ) {
         $sql = 'SELECT type FROM items WHERE item_id="'.$row2['item_id'].'";';
         $type = array_shift(mysql_fetch_row(select($sql)));
         if ( $type == 'label' ) {
            $sql = 'SELECT type FROM labels WHERE item_id="'.$row2['item_id'].'";';
            $type = array_shift(mysql_fetch_row(select($sql)));
         }

         $sql = 'INSERT INTO items SET type="link_item", context_id="'.$context_id.'", modification_date=now();';
         $new_link_item_id = insert($sql);

         $sql  = 'INSERT INTO link_items SET';
         $sql .= ' item_id="'.$new_link_item_id.'",';
         // wie rum?
         $sql .= ' first_item_id="'.$new_item_id.'",';
         $sql .= ' first_item_type="tag",';
         $sql .= ' second_item_id="'.$row2['item_id'].'",';
         $sql .= ' second_item_type="'.$type.'",';
         // wie rum?
         #$sql .= ' first_item_id="'.$row2['item_id'].'",';
         #$sql .= ' first_item_type="'.$type.'",';
         #$sql .= ' second_item_id="'.$new_item_id.'",';
         #$sql .= ' second_item_type="tag",';
         $sql .= ' creation_date=now(),';
         $sql .= ' modification_date=now(),';
         $sql .= ' creator_id="99",';
         $sql .= ' context_id="'.$context_id.'";';
         insert($sql);
      }

      migrateCategories($row['category_id'],$new_item_id,$context_id);
   }
}

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('../update_functions.php');

// time management for this script
$time_start = getmicrotime();

// add column "has_html" to table "files"
echo ('CommSy database: migrate ontolgies to tags.'."\n");
$success = true;

$count = array_shift(mysql_fetch_row(select('SELECT COUNT(item_id) FROM ontologies WHERE deletion_date IS NULL AND deleter_id IS NULL;')));
if ($count < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count);

   $cs_tag_root_array = array();

   $query  = "SELECT * FROM ontologies WHERE deletion_date IS NULL AND deleter_id IS NULL;";
   $result = select($query);
   while ( $row = mysql_fetch_assoc($result) ) {

      if ( empty($cs_tag_root_array[$row['context_id']]) ) {
         $sql = 'SELECT item_id FROM tag WHERE title="CS_TAG_ROOT" AND context_id="'.$row['context_id'].'"';
         $result2 = select($sql);
         $row2 = mysql_fetch_assoc($result2);
         if ( empty($row2['item_id']) ) {
            $sql = 'INSERT INTO items SET type="tag", context_id="'.$row['context_id'].'", modification_date=now();';
            $cs_tag_root_array[$row['context_id']] = insert($sql);

            $sql  = 'INSERT INTO tag SET ';
            $sql .= ' item_id="'.$cs_tag_root_array[$row['context_id']].'",';
            $sql .= ' context_id="'.$row['context_id'].'",';
            $sql .= ' creator_id="99",';
            $sql .= ' modifier_id="99",';
            $sql .= ' creation_date=now(),';
            $sql .= ' modification_date=now(),';
            $sql .= ' title="CS_TAG_ROOT";';
            insert($sql);
         } else {
            $cs_tag_root_array[$row['context_id']] = $row2['item_id'];
         }
      }

      // make ontologie to tag
      $sql  = 'INSERT INTO tag SET';
      $sql .= ' item_id="'.$row['item_id'].'",';
      $sql .= ' context_id="'.$row['context_id'].'",';
      $sql .= ' creator_id="'.$row['creator_id'].'",';
      $sql .= ' modifier_id="'.$row['modificator_id'].'",';
      $sql .= ' creation_date="'.$row['creation_date'].'",';
      $sql .= ' modification_date="'.$row['modification_date'].'",';
      $sql .= ' title="'.addslashes($row['title']).'";';
      insert($sql);

      $sql = 'UPDATE items SET type = "tag" WHERE item_id = "'.$row['item_id'].'";';
      select($sql);

      $sql = 'SELECT count(link_id) FROM tag2tag WHERE from_item_id ="'.$cs_tag_root_array[$row['context_id']].'";';
      $count2 = array_shift(mysql_fetch_row(select($sql)));
      if ( empty($count2) ) {
         $count2 = 1;
      } else {
         $count2++;
      }
      $sql  = 'INSERT INTO tag2tag SET';
      $sql .= ' from_item_id="'.$cs_tag_root_array[$row['context_id']].'",';
      $sql .= ' to_item_id="'.$row['item_id'].'",';
      $sql .= ' context_id="'.$row['context_id'].'",';
      $sql .= ' creator_id="'.$row['creator_id'].'",';
      $sql .= ' modifier_id="'.$row['modificator_id'].'",';
      $sql .= ' creation_date="'.$row['creation_date'].'",';
      $sql .= ' modification_date="'.$row['modification_date'].'",';
      $sql .= ' sorting_place="'.$count2.'";';
      insert($sql);

      // link(s) to item(s)
      $sql = 'UPDATE link_items SET first_item_type="tag" WHERE first_item_id="'.$row['item_id'].'";';
      select($sql);
      $sql = 'UPDATE link_items SET second_item_type="tag" WHERE second_item_id="'.$row['item_id'].'";';
      select($sql);

      $sql = 'SELECT category_id FROM ont_categories WHERE ontology_id="'.$row['item_id'].'" AND title="CS_ROOT_CATEGORY";';
      $result3 = select($sql);
      $row3 = mysql_fetch_assoc($result3);
      if ( !empty($row3['category_id']) ) {
         migrateCategories($row3['category_id'],$row['item_id'],$row['context_id']);
      }

      update_progress_bar($count);
   }
}

// delete tables and data in items and links_items
$sql = 'DROP TABLE ontologies;';
select($sql);
$sql = 'DROP TABLE ont_parents;';
select($sql);
$sql = 'DROP TABLE ont_links;';
select($sql);
$sql = 'DROP TABLE ont_categories;';
select($sql);

$sql = 'DELETE FROM link_items WHERE first_item_type="ontology" OR second_item_type="ontology";';
select($sql);
$sql = 'DELETE FROM items WHERE type="ontology";';
select($sql);

if ($success) {
   echo('[ <font color="#00ff00">done</font> ]<br/>'."\n");
} else {
   echo('[ <font color="#ff0000">failed</font> ]<br/>'."\n");
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>