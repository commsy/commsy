<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Iver Jackewitz
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

// new version of the update mechanism
// -----------------------------------
// the following is part of the method "asHTML"
// from the object cs_update_view.php

set_time_limit(0);

// init $success
$success = true;

// headline
$this->_flushHTML('description: security FCK editor'.BRLF);

$table_array = array();
$table_array[] = 'annotations';
$table_array[] = 'announcement';
$table_array[] = 'dates';
$table_array[] = 'discussionarticles';
$table_array[] = 'labels';
$table_array[] = 'materials';
$table_array[] = 'section';
$table_array[] = 'step';
$table_array[] = 'todos';
$table_array[] = 'user';

include_once('functions/security_functions.php');

foreach ( $table_array as $table ) {
   $counter = 0;
   $this->_flushHTML($table.BRLF);

   // count entries
   $result = $this->_select('SELECT count(item_id) AS count FROM '.$table.' WHERE description LIKE \'%<!-- KFC TEXT %\' and deletion_date IS NULL and deleter_id IS NULL;');
   if ( !empty($result[0]['count']) ) {
      $count = $result[0]['count'];
   } else {
      $count = 0;
   }
   if ($count < 1) {
      // nothing to do
      $this->_flushHTML('nothing to do.'.BRLF);
   } else {
      // something to do
      $this->_initProgressBar($count);
      $interval = 100;
      $i = 0;

      while ( $i<$count ) {
         $sql = 'SELECT item_id, description FROM '.$table.' WHERE description LIKE \'%<!-- KFC TEXT %\' and deletion_date IS NULL and deleter_id IS NULL ORDER BY item_id LIMIT '.$i.','.$interval.';';
         $i = $i + $interval;
         $result = $this->_select($sql);
         if ( !empty($result) ) {
            foreach ( $result as $row ) {
               if ( !empty($row)
                    and !empty($row['item_id'])
                    and !empty($row['description'])
                  ) {
                  $desc = $row['description'];
                  $desc = preg_replace('~<!-- KFC TEXT -->~u','',$desc);
                  $desc = preg_replace('~<!-- KFC TEXT [a-z0-9]* -->~u','',$desc);
                  $fck_text = '<!-- KFC TEXT '.getSecurityHash($desc).' -->';
                  $desc = $fck_text.$desc.$fck_text;
                  $sql = 'UPDATE '.$table.' SET description = "'.addslashes($desc).'" WHERE item_id = "'.$row['item_id'].'";';
                  $success1 = $this->_select($sql);
                  $success = $success and $success1;
                  $counter++;
               }
               $this->_updateProgressBar($count);
            }
         }
      }
      $this->_flushHTML(BRLF);
   }
   $this->_flushHTML('Changed '.$counter.' items.'.BRLF);
   $this->_flushHTML(BRLF);
}
?>