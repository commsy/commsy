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
$this->_flushHTML('extras: clean HTML-Tags'.BRLF);

$table_array = array();
$table_array[] = 'room';
$table_array[] = 'room_privat';
$table_array[] = 'portal';
$table_array[] = 'server';
$table_array[] = 'materials';
foreach ( $table_array as $table ) {
   $counter = 0;
   $this->_flushHTML($table.BRLF);
   if ( $table == 'materials' ) {
      $result = $this->_select('SELECT count(*) AS count FROM '.$table.' WHERE deletion_date IS NULL and deleter_id IS NULL;');
   } else {
      $result = $this->_select('SELECT count(item_id) AS count FROM '.$table.' WHERE deletion_date IS NULL and deleter_id IS NULL;');
   }
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
         if ( $table == 'materials' ) {
            $sql = 'SELECT item_id, version_id, extras FROM '.$table.' WHERE deletion_date IS NULL and deleter_id IS NULL ORDER BY item_id LIMIT '.$i.','.$interval.';';
         } else {
            $sql = 'SELECT item_id, extras FROM '.$table.' WHERE deletion_date IS NULL and deleter_id IS NULL ORDER BY item_id LIMIT '.$i.','.$interval.';';
         }
         $i = $i + $interval;
         $result = $this->_select($sql);
         if ( !empty($result) ) {
            foreach ( $result as $row ) {
               $changed = false;
               if ( !empty($row)
                    and !empty($row['item_id'])
                    and !empty($row['extras'])
                  ) {
                  $extra_array = mb_unserialize($row['extras']);
                  $key_array = array();
                  $key_array[] = 'DESCRIPTION';
                  $key_array[] = 'AGBTEXTARRAY';
                  $key_array[] = 'USAGE_INFO_TEXT';
                  $key_array[] = 'USAGE_INFO_FORM';
                  $key_array[] = 'BIBLIOGRAPHIC';
                  $key_array[] = 'OUTOFSERVICE';
                  $key_array[] = 'SERVER_NEWS';
                  foreach ($key_array as $field) {
                     if ( !empty($extra_array[$field])
                          and is_array($extra_array[$field])
                        ) {
                        foreach ( $extra_array[$field] as $key => $value ) {
                           $kfc_text = strstr($value,'<!-- KFC TEXT');
                           if ( empty($kfc_text)
                                and strlen($value) != strlen(strip_tags($value))
                                and ( $field != 'SERVER_NEWS'
                                      or $key == 'TEXT'
                                    )
                              ) {
                              $value = preg_replace('~<!-- KFC TEXT -->~u','',$value);
                              $value = preg_replace('~<!-- KFC TEXT [a-z0-9]* -->~u','',$value);
                              $fck_text = '<!-- KFC TEXT '.getSecurityHash($value).' -->';
                              $value = $fck_text.$value.$fck_text;
                              $extra_array[$field][$key] = $value;
                              $changed = true;
                           }
                        }
                     } elseif ( !empty($extra_array[$field]) ) {
                        $value = $extra_array[$field];
                        $kfc_text = strstr($value,'<!-- KFC TEXT');
                        if ( empty($kfc_text)
                             and strlen($value) != strlen(strip_tags($value))
                           ) {
                           $value = preg_replace('~<!-- KFC TEXT -->~u','',$value);
                           $value = preg_replace('~<!-- KFC TEXT [a-z0-9]* -->~u','',$value);
                           $fck_text = '<!-- KFC TEXT '.getSecurityHash($value).' -->';
                           $value = $fck_text.$value.$fck_text;
                           $extra_array[$field] = $value;
                           $changed = true;
                        }
                     }
                  }
                  if ($changed) {
                     $extras = serialize($extra_array);
                     $sql  = 'UPDATE '.$table.' SET extras = "'.addslashes($extras).'" WHERE item_id = "'.$row['item_id'].'"';
                     if ( isset($row['version_id']) ) {
                        $version_id = $row['version_id'];
                        if ( empty($version_id) ) {
                           $version_id = '0';
                        }
                        $sql .= ' AND version_id="'.$version_id.'"';
                     }
                     $sql .= ';';
                     $success1 = $this->_select($sql);
                     $success = $success and $success1;
                     $counter++;
                  }
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