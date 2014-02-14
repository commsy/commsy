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

ini_set("memory_limit","4000M");
set_time_limit(0);

// init $success
$success = true;

$len_array = array();

// headline
$this->_flushHeadline('DB: clean description from ms word'.LF);
$this->_flushHTML(BRLF);

$table_array = array();
$column_array = array();
$length_array = array();

$this->_flushHTML('get tabels'.LF);
$this->_flushHTML(BRLF);

$sql = 'SHOW TABLES;';
$result = $this->_select($sql);
if ( !empty($result) ) {
   foreach ( $result as $table ) {
      if ( !empty($table) ) {
         $table_array[] = array_pop($table);
      }
   }
} else {
   include_once('functions/error_functions.php');
   trigger_error('can get tables with query: '.$sql,E_USER_NOTICE);
}

if ( !empty($table_array) ) {
   $this->_flushHTML('get description columns'.LF);
   $this->_flushHTML(BRLF);
   foreach ( $table_array as $table ) {
      $sql = 'SHOW COLUMNS FROM '.$table.';';
      $result = $this->_select($sql);
      if ( !empty($result) ) {
         foreach ($result as $column ) {
            if ( !empty($column['Type'])
                 and stristr($column['Type'],'text')
                 and stristr($column['Field'],'desc')
               ) {
               $column_array[$table][] = $column['Field'];
            }
         }
      }
   }
} else {
   $this->_flushHTML('no tabels found'.LF);
   $this->_flushHTML(BRLF);
}

if ( !empty($column_array) ) {
   $this->_flushHTML('start cleaning'.LF);
   $this->_flushHTML(BRLF);
   foreach ( $column_array as $table => $columns) {
      if ( !empty($columns) ) {
         foreach ($columns as $column) {
            if ( stristr($table,'section')
                 or stristr($table,'material')
               ) {
               $sql = 'SELECT item_id,version_id,'.$column.' FROM '.$table.' WHERE '.$column.' LIKE "%<w:WordDocument>%" OR '.$column.' LIKE "%class=\"Mso%";';
            } else {
               $sql = 'SELECT item_id,'.$column.' FROM '.$table.' WHERE '.$column.' LIKE "%<w:WordDocument>%" OR '.$column.' LIKE "%class=\"Mso%";';
            }
            $result = $this->_select($sql);
            if ( !empty($result) ) {
               $count_rows = count($result);
               $this->_flushHTML($table);
               $this->_initProgressBar($count_rows);
               foreach ( $result as $row ) {
                  if ( !empty($row['item_id']) ) {
                     $item_id = $row['item_id'];
                     $version_id = 0;
                     if ( !empty($row['version_id']) ) {
                        $version_id = $row['version_id'];
                     }
                     $data = $row[$column];
                     $data = $this->_text_converter->cleanTextFromWord($data);
                     $sql = 'UPDATE '.$table.' SET '.$column.'="'.mysql_real_escape_string($data).'" WHERE item_id="'.$item_id.'"';
                     if ( !empty($version_id) ) {
                        $sql .= ' AND version_id="'.$version_id.'"';
                     }
                     $sql .= ';';
                     $result = $this->_select($sql);
                     if ( !$result ) {
                        include_once('functions/error_functions.php');
                        trigger_error('can not save cleaned data ('.$column.') for '.$item_id.' ('.$table.')',E_USER_NOTICE);
                        $success = false;
                     }
                  }
                  $this->_updateProgressBar($count_rows);
               }
               $this->_flushHTML(BRLF.BRLF);
            } elseif ( isset($result) ) {
               $this->_flushHTML($table.': nothing to do'.BRLF);
            }
         }
      }
   }
   $this->_flushHTML(BRLF);
} else {
   $this->_flushHTML('no description columns found'.LF);
   $this->_flushHTML(BRLF);
}
?>