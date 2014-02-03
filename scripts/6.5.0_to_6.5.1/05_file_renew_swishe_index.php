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

function _get_folder_array ($dirname) {
   $retour = array();
   if ( $dirHandle = opendir($dirname) ) {
      $old_cwd = getcwd();
      chdir($dirname);

      while ($file = readdir($dirHandle)){
         if ($file == '.' || $file == '..') continue;
         if ( is_dir($file)
              and is_numeric($file)
            ) {
            $retour[$file] = _get_folder_array($file);
         } elseif ( is_file($file)
                    and $file == 'ft.index'
                  ) {
            $retour = 'done';
         }
      }

      closedir($dirHandle);
      chdir($old_cwd);
      if ( empty($retour) ) {
         $retour = '';
      }
      return $retour;
   }
}

set_time_limit(0);

// init $success
$success = true;

$this->_flushHTML('file: create swish-e index'.BRLF);

global $c_indexing;
if ( isset($c_indexing) and $c_indexing ) {
   $folder_array = _get_folder_array('var/');
   $folder_array2 = array();
   foreach ( $folder_array as $portal_id => $room_id_array ) {
      foreach ( $room_id_array as $room_id => $value ) {
         if ( empty($value) ) {
            $folder_array2[] = 'var/'.$portal_id.'/'.$room_id.'/';
         }
      }
   }
   $count = count($folder_array2);
   if ( $count < 1 ) {
      // nothing to do
      $this->_flushHTML('nothing to do.'.BRLF);
   } else {
      // something to do
      $this->_initProgressBar($count);
      $ft_manager = $this->_environment->getFTSearchManager();
      foreach ($folder_array2 as $folder) {
         $ft_manager->buildFTIndexForIndexBase($folder);
         $this->_updateProgressBar($count);
      }
   }
} else {
   $this->_flushHTML('swish-e not enabled'.BRLF);
}
?>