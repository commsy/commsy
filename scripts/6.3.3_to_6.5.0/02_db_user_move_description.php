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
$this->_flushHTML('user: move description'.BRLF);

if ( !$this->_existsField('user','description') ) {
   $sql = 'ALTER TABLE user ADD description TEXT NULL;';
   $success = $this->_select($sql);
}

// count entries
$result = $this->_select('SELECT count(item_id) AS count FROM user WHERE extras LIKE \'%USERDESCRIPTION%\';');
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
   $sql = 'SELECT item_id,extras FROM user WHERE extras LIKE \'%USERDESCRIPTION%\';';
   $result = $this->_select($sql);
   foreach ($result as $row) {
      $extra_array = @unserialize($row['extras']);
      if ( empty($extra_array) ) {
         $serial_str = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $row['extras'] );
         $extra_array = @unserialize($serial_str);
      }
      if ( isset($extra_array['USERDESCRIPTION']) ) {
         $desc = $extra_array['USERDESCRIPTION'];
         unset($extra_array['USERDESCRIPTION']);
         $extra_string = serialize($extra_array);
         $sql = 'UPDATE user SET description="'.addslashes($desc).'", extras="'.addslashes($extra_string).'" WHERE item_id="'.$row['item_id'].'";';
         $success1 = $this->_select($sql);
         $success = $success and $success1;
      } elseif ( strstr($row['extras'],'USERDESCRIPTION') ) {
         $row['extras'] = preg_replace('/s:15:"USERDESCRIPTION";s:[0-9]*:"[\s\S]*";/','',$row['extras']);
         $temp_array = explode(':',$row['extras']);
         $temp_array[1] = $temp_array[1]-1;
         $extra_string = implode(':',$temp_array);
         $sql = 'UPDATE user SET extras="'.addslashes($extra_string).'" WHERE item_id="'.$row['item_id'].'";';
         $success1 = $this->_select($sql);
         $success = $success and $success1;
      }
      $this->_updateProgressBar($count);
   }
}
?>