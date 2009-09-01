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

function _rm_swishe_index($dirname) {
   if ( $dirHandle = opendir($dirname) ) {
      $old_cwd = getcwd();
      chdir($dirname);

      while ($file = readdir($dirHandle)){
         if ($file == '.' or $file == '..' or $file == 'CVS') continue;
         if ( is_dir($file) ) {
            if ( !_rm_swishe_index($file) ) {
               chdir($old_cwd);
               return false;
            }
         } elseif ( is_file($file)
                    and ( $file == 'ft.index'
                          or $file == 'ft.index.prop'
                          or $file == 'ft_idx.log'
                        )
                  ) {
            if ( !@unlink($file) ) {
               chdir($old_cwd);
               return false;
            }
         }
      }

      closedir($dirHandle);
      chdir($old_cwd);
      return true;
   }
}

set_time_limit(0);

// init $success
$success = true;

// headline
$this->_flushHTML('file: delete swish-e index'.BRLF);
$success = _rm_swishe_index('var/');
?>