<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2006 Iver Jackewitz
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

class cs_disc_manager {

   var $_first_id = NULL;
   var $_second_id = NULL;
   var $_file_path_basic = 'var/';
   private $_last_saved_filename = '';

   function cs_disc_manager () {
   }

   function _setFirstID ($value) {
      $this->_first_id = $value;
   }

   function _setSecondID ($value) {
      $this->_second_id = $value;
   }

   function setServerID ($value) {
      $this->_setFirstID($value);
      $this->_setSecondID($value);
   }

   function setPortalID ($value) {
      $this->_setFirstID($value);
   }

   function setContextID ($value) {
      $this->_setSecondID($value);
   }

   function _getFilePath () {
      $retour  = '';
      $retour .= $this->_file_path_basic;
      if (!empty($this->_first_id)) {
         $retour .= $this->_first_id.'/';
      } else {
         include_once('functions/error_functions.php');
         trigger_error('first_id is not set',E_USER_ERROR);
      }
      if (!empty($this->_second_id)) {
         $retour .= $this->_second_id.'/';
      } else {
         include_once('functions/error_functions.php');
         trigger_error('second_id is not set',E_USER_ERROR);
      }
      return $retour;
   }

   function getFilePath () {
      return $this->_getFilePath();
   }

   function existsFile ($filename) {
      $retour = false;
      if ( !empty($filename)
           and file_exists($this->_getFilePath().$filename)
         ) {
         $retour = true;
      }
      return $retour;
   }

   function unlinkFile ($filename) {
      $retour = false;
      if (!empty($filename)
           and $this->existsFile($filename)
         ) {
         $retour = unlink($this->_getFilePath().$filename);
      }
      return $retour;
   }

   function copyFile ($source_file, $dest_filename, $delete_source) {
      $retour = false;
      $first_folder_string = $this->_file_path_basic.$this->_first_id;
      $first_folder = @opendir($first_folder_string);
      if (!$first_folder) {
         mkdir($first_folder_string);
      }
      $second_folder_string = $first_folder_string.'/'.$this->_second_id;
      $second_folder = @opendir($second_folder_string);
      if (!$second_folder) {
         mkdir($second_folder_string);
      }
      if ( file_exists($source_file) ) {
         $retour = copy($source_file, $this->_getFilePath().$dest_filename);
      }
      if ($retour and $delete_source) {
         unlink($source_file);
      }
      return $retour;
   }

   function copyFileFromRoomToRoom ($old_room_id, $old_file_id, $filename, $new_room_id, $new_file_id) {
      $retour = false;
      if ( empty($old_room_id) ) {
         include_once('functions/error_functions.php');
         trigger_error('old_room_id is not set',E_USER_ERROR);
      }
      $this->_makeFolder($this->_first_id, $new_room_id);
      $source_file = $this->_file_path_basic;
      if (!empty($this->_first_id)) {
         $source_file .= $this->_first_id.'/';
      } else {
         include_once('functions/error_functions.php');
         trigger_error('first_id is not set',E_USER_ERROR);
      }
      $source_file .= $old_room_id.'/'.$old_file_id.'.'.cs_strtolower(mb_substr(strrchr($filename,'.'),1));

      $target_file = $this->_file_path_basic;
      if (!empty($this->_first_id)) {
         $target_file .= $this->_first_id.'/';
      } else {
         include_once('functions/error_functions.php');
         trigger_error('first_id is not set',E_USER_ERROR);
      }
      $target_file .= $new_room_id.'/'.$new_file_id.'.'.cs_strtolower(mb_substr(strrchr($filename,'.'),1));

      if ( file_exists($source_file) ) {
         $retour = copy($source_file,$target_file);
      } else {
         $retour = true;
      }
      return $retour;
   }

   function copyImageFromRoomToRoom ($picture_name, $new_room_id) {
      $retour = false;
      $this->_makeFolder($this->_first_id, $new_room_id);

      $value_array = explode('_',$picture_name);
      $old_room_id = $value_array[0];
      $old_room_id = str_replace('cid','',$old_room_id);
      $value_array[0] = 'cid'.$new_room_id;

      $new_picture_name = implode('_',$value_array);

      // source file
      $source_file = $this->_file_path_basic;
      if (!empty($this->_first_id)) {
         $source_file .= $this->_first_id.'/';
      } else {
         include_once('functions/error_functions.php');
         trigger_error('first_id is not set',E_USER_ERROR);
      }
      $source_file .= $old_room_id.'/'.$picture_name;

      // target file
      $target_file = $this->_file_path_basic;
      if (!empty($this->_first_id)) {
         $target_file .= $this->_first_id.'/';
      } else {
         include_once('functions/error_functions.php');
         trigger_error('first_id is not set',E_USER_ERROR);
      }
      $target_file .= $new_room_id.'/'.$new_picture_name;

      // copy
      if ( file_exists($source_file) ) {
         if ($source_file != $target_file){
            $retour = copy($source_file,$target_file);
            if ( $retour ) {
               $this->_last_saved_filename = $new_picture_name;
            }
         } else {
            $retour = true;
         }
      } else {
         $retour = true;
      }
      return $retour;
   }

   function _makeFolder ($first_id, $second_id) {
      $first_folder_string = $this->_file_path_basic.$first_id;
      $first_folder = @opendir($first_folder_string);
      if (!$first_folder) {
         @mkdir($first_folder_string);
         $first_folder = @opendir($first_folder_string);
         if (!$first_folder) {
            include_once('functions/error_functions.php');
            trigger_error('can not make directory '.$first_folder_string.' - abort executing',E_USER_ERROR);
         }
      }
      $second_folder_string = $first_folder_string.'/'.$second_id;
      $second_folder = @opendir($second_folder_string);
      if (!$second_folder) {
         mkdir($second_folder_string);
      }
   }

   public function makeDirectory ( $dir ) {
      $retour = true;
      $folder = @opendir($dir);
      if ( !$folder ) {
         @mkdir($dir);
         $folder = @opendir($dir);
         if ( !$folder ) {
            include_once('functions/error_functions.php');
            trigger_error('can not make directory '.$dir,E_USER_WARNING);
            $retour = false;
         }
      }
      return $retour;
   }

   public function makeDirectoryR ( $dir ) {
      $retour = true;
      $directory_split = explode("/",$dir);
      $done_dir = "./";
      foreach($directory_split as $dir) {
         if ( !is_dir($done_dir.'/'.$dir) ) {
            $success_dir = $this->makeDirectory($done_dir.'/'.$dir);
            $retour = $retour and $success_dir;
         }
         $done_dir .= '/'.$dir;
      }
      return $retour;
   }

   function moveFiles ($second_folder, $old_first_folder, $new_first_folder) {
      $retour = true;
      $folder_new = $this->_file_path_basic.$new_first_folder.'/'.$second_folder;
      $directory_handle = @opendir($folder_new);
      if (!$directory_handle) {
         $this->_makeFolder($new_first_folder,$second_folder);
      } else {
         closedir($directory_handle);
      }

      $folder_old = $this->_file_path_basic.$old_first_folder.'/'.$second_folder;
      $directory_handle = @opendir($folder_old);
      if ($directory_handle) {
         while ( false !== ( $entry = readdir($directory_handle) ) ) {
            if (!is_dir($folder_old.'/'.$entry)) {
               $retour = $retour and $this->_moveFile($folder_old.'/'.$entry,$folder_new.'/'.$entry);
            }
         }
         $retour = $retour and $this->_full_rmdir($folder_old);
      }
      return $retour;
   }

   private function _full_rmdir($dirname) {
      if ( $dirHandle = opendir($dirname) ) {
         $old_cwd = getcwd();
         chdir($dirname);

         while ($file = readdir($dirHandle)){
            if ($file == '.' || $file == '..') continue;
            if ( is_dir($file) ) {
               if ( !$this->_full_rmdir($file) ) {
                  chdir($old_cwd);
                  return false;
               }
            } else {
               if ( !@unlink($file) ) {
                  chdir($old_cwd);
                  return false;
               }
            }
         }

         closedir($dirHandle);
         chdir($old_cwd);
         if (!rmdir($dirname)) return false;
         return true;
      } else {
         return false;
      }
   }

   public function removeDirectory ( $dir ) {
      return $this->_full_rmdir($dir);
   }

   function _moveFile ($source, $dest) {
      $retour = false;
      if ( file_exists($source) ) {
         $success = copy($source,$dest);
         if ($success) {
            $retour = unlink($source);
         }
      }
      return $retour;
   }

   public function getFileAsString ($file) {
      $retour = '';
      if ( file_exists($file) ) {
         $retour .= file_get_contents($file);
      }
      return $retour;
   }

   public function getFileAsBase64 ($file) {
      $retour = '';
      if ( file_exists($file) ) {
         $retour .= file_get_contents($file);
      }
      $retour = base64_encode($retour);
      return $retour;
   }

   function _makeTempFolder () {
      $first_folder_string = $this->_file_path_basic.'temp';
      $first_folder = @opendir($first_folder_string);
      if (!$first_folder) {
         mkdir($first_folder_string);
      }
   }

   public function saveFileFromBase64 ($file, $base64_data) {
      $data = base64_decode($base64_data);
      $this->_makeTempFolder();
      $retour = file_put_contents($this->_file_path_basic.'temp/'.$file,$data);
      if ( $retour ) {
         $retour = $this->_file_path_basic.'temp/'.$file;
      }
      return $retour;
   }

   public function getLastSavedFileName () {
      return $this->_last_saved_filename;
   }

   public function getCurrentFileName ($context_id, $file_id, $file_name, $file_ext) {
      $retour = $file_id.'.'.$file_ext;
      return $retour;
   }
}
?>