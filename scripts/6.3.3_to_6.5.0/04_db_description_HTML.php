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
$this->_flushHTML('description: clean HTML-Tags'.BRLF);

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

foreach ( $table_array as $table ) {
   $counter = 0;
   $this->_flushHTML($table.BRLF);

   // count entries
   $result = $this->_select('SELECT count(item_id) AS count FROM '.$table.' WHERE description NOT LIKE \'%<!-- KFC TEXT -->%\' and description NOT LIKE \'%<iframe%\' and description != "" and description IS NOT NULL and deletion_date IS NULL and deleter_id IS NULL;');
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
      for ( $i=0; $i<$count; $i++ ) {
         $sql = 'SELECT item_id, description FROM '.$table.' WHERE description NOT LIKE \'%<!-- KFC TEXT -->%\' and description NOT LIKE \'%<iframe%\' and description != "" and description IS NOT NULL and deletion_date IS NULL and deleter_id IS NULL ORDER BY item_id LIMIT '.$i.',1;';
         $result = $this->_select($sql);
         if ( !empty($result[0]['description'])
              and strlen($result[0]['description']) != strlen(strip_tags($result[0]['description']))
            ) {
            $sql = 'UPDATE '.$table.' SET description = "'.addslashes('<!-- KFC TEXT -->'.$result[0]['description'].'<!-- KFC TEXT -->').'" WHERE item_id = "'.$result[0]['item_id'].'";';
            $success1 = $this->_select($sql);
            $success = $success and $success1;
            $counter++;
         }
         $this->_updateProgressBar($count);
      }
      $this->_flushHTML(BRLF);
   }
   $this->_flushHTML('Changed '.$counter.' items.'.BRLF);
   $this->_flushHTML(BRLF);
}
?>