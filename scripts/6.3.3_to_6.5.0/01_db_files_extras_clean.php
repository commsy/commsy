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

// init $success
$success = true;

// headline
$this->_flushHTML('files: clean extras'.BRLF);

// count entries
$result = $this->_select('SELECT count(files_id) AS count FROM files WHERE extras LIKE \'%\\\\\\\\"%\';');
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
   $sql = 'SELECT * FROM files WHERE extras LIKE \'%\\\\\\\\"%\';';
   $result = $this->_select($sql);
   foreach ($result as $row) {
      $extra_array = @unserialize($row['extras']);
      if ( empty($extra_array) ) {
         $serial_str = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $row['extras'] );
         $extra_array = @unserialize($serial_str);
      }
      if ( strlen($row['extras']) > 0
           and !is_array($extra_array)
         ) {
         $new_extra = str_replace('\\"','"',$row['extras']);
         while ( strstr($new_extra,'\\"') ) {
            $new_extra = str_replace('\\"','"',$new_extra);
         }
         if ( is_array(mb_unserialize($new_extra))
              or ($new_extra == 's:0:"";')
            ) {
            $sql = 'UPDATE files SET extras="'.addslashes($new_extra).'" WHERE files_id="'.$row['files_id'].'";';
            $success1 = $this->_select($sql);
            $success = $success and $success1;
         }
      }
      $this->_updateProgressBar($count);
   }
}
?>