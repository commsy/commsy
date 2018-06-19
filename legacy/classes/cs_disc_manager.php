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
   var $_file_path_basic = '../files/';
   private $_folder_temp = 'temp';
   private $_last_saved_filename = '';

   function __construct() {
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

   function _getSecondFolder ( $second_folder ) {
      $second_folder = (string)$second_folder;
      if ( !empty($second_folder) ) {
         $array = array();
         $retour = '';
         for ( $i=0; $i<strlen($second_folder);$i++) {
            if ( $i > 0 and $i%4 == 0 ) {
               $retour .= '/';
            }
            $retour .= $second_folder[$i];
         }
         $retour .= '_';
      } else {
         include_once('functions/date_functions.php');
         $retour = md5(getCurrentDateTimeInMySQL());
      }
      return $retour;
   }

   function _getFilePath ( $first_id = '', $second_id = '') {
      $retour  = '';
      $retour .= $this->_file_path_basic;
      if (!empty($first_id)) {
         $retour .= $first_id.'/';
      } elseif (!empty($this->_first_id)) {
         $retour .= $this->_first_id.'/';
      } else {
         include_once('functions/error_functions.php');
         trigger_error('first_id is not set',E_USER_WARNING);
      }

      if (!empty($second_id)) {
         $retour_old = $retour.$second_id.'/';
         $retour .= $this->_getSecondFolder($second_id).'/';
         if ( !is_dir($retour) and is_dir($retour_old) ) {
            $retour = $retour_old;
         }
      } elseif (!empty($this->_second_id)) {
         $retour_old = $retour.$this->_second_id.'/';
         $retour .= $this->_getSecondFolder($this->_second_id).'/';
         if ( !is_dir($retour) and is_dir($retour_old) ) {
            $retour = $retour_old;
         }
      } else {
         include_once('functions/error_functions.php');
         trigger_error('second_id is not set',E_USER_WARNING);
      }
      return $retour;
   }

   function getFilePath ($first_id = '', $second_id = '') {
      return $this->_getFilePath($first_id,$second_id);
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

   function existsTempFile ($filename) {
      $retour = false;
      if ( !empty($filename)
           and file_exists($this->getTempFolder().'/'.$filename)
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
      $this->_makeFolder($this->_first_id, $this->_second_id);
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
      $source_file = str_replace('//','/',$this->_getFilePath('',$old_room_id).'/'.$old_file_id.'.'.cs_strtolower(mb_substr(strrchr($filename,'.'),1)));
      $target_file = str_replace('//','/',$this->_getFilePath('',$new_room_id).'/'.$new_file_id.'.'.cs_strtolower(mb_substr(strrchr($filename,'.'),1)));

      // copy
      if ( file_exists($source_file) ) {
         $retour = copy($source_file,$target_file);
      } else {
         $retour = true;
      }
      return $retour;
   }

   function copyImageFromRoomToRoom ($picture_name, $new_room_id) {
      if ( !empty($picture_name)
           and !empty($new_room_id)
         ) {
         $retour = false;
         $this->_makeFolder($this->_first_id, $new_room_id);

         $value_array = explode('_',$picture_name);
         $old_room_id = $value_array[0];
         $old_room_id = str_replace('cid','',$old_room_id);
         $value_array[0] = 'cid'.$new_room_id;

         $new_picture_name = implode('_',$value_array);

         // source file
         $source_file = str_replace('//','/',$this->_getFilePath('',$old_room_id).'/'.$picture_name);
         $target_file = str_replace('//','/',$this->_getFilePath('',$new_room_id).'/'.$new_picture_name);

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
      } else {
         $retour = true;
      }
      return $retour;
   }

   function _makeFolder ($first_id, $second_id) {
      return $this->makeDirectoryR($this->_getFilePath($first_id,$second_id));
   }

   public function makeFolder ($first_id, $second_id) {
      if ( !empty($first_id) and !empty($second_id) ) {
         $this->_makeFolder($first_id,$second_id);
      } else {
         include_once('functions/error_functions.php');
         trigger_error('first and second folder can not be empty - abort executing',E_USER_ERROR);
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
      $this->_makeFolder($new_first_folder,$second_folder);
      $folder_new = $this->_getFilePath($new_first_folder,$second_folder);
      $folder_old = $this->_getFilePath($old_first_folder,$second_folder);
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

   function moveFilesR ( $quelle, $ziel  ) {
      if ( is_dir($quelle) ) {
         if ( !$this->makeDirectoryR($ziel) ) {
            include_once('functions/error_functions.php');
            trigger_error('<br/>kann '.$ziel.' nicht anlegen',E_USER_ERROR);
         }

         if ( $dirHandle = opendir($quelle) ) {
            while ($file = readdir($dirHandle)){
               if ($file == '.' || $file == '..') continue;
               if ( is_dir($quelle.'/'.$file) ) {
                  if ( !$this->moveFilesR($quelle.'/'.$file,$ziel.'/'.$file) ) {
                     return false;
                  }
               } else {
                  if ( !$this->_moveFile($quelle.'/'.$file,$ziel.'/'.$file) ) {
                     return false;
                  }
               }
            }

            closedir($dirHandle);
            if (!rmdir($quelle)) return false;
            return true;
         } else {
            return false;
         }
      }
   }

    private function _full_rmdir($dirname)
    {
        if (is_dir($dirname)) {
            if ($dirHandle = opendir($dirname)) {
                $old_cwd = getcwd();
                chdir($dirname);

                while ($file = readdir($dirHandle)) {
                    if ($file == '.' || $file == '..') continue;
                    if (is_dir($file)) {
                        if (!$this->_full_rmdir($file)) {
                            chdir($old_cwd);
                            return false;
                        }
                    } else {
                        if (!@unlink($file)) {
                            chdir($old_cwd);
                            return false;
                        }
                    }
                }

                closedir($dirHandle);
                chdir($old_cwd);
                if (!rmdir($dirname)) return false;
                return true;
            }
        }

        return false;
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

   public function moveUploadedFileToTempFolder ($tempFile) {
      $retour = false;
      $this->_makeTempFolder();
      if ( move_uploaded_file($tempFile, $this->getTempFolder().'/'.basename($tempFile)) ) {
         $retour = $this->getTempFolder().'/'.basename($tempFile);
      }
      return $retour;
   }

   public function getTempFolder () {
      $retour = $this->_file_path_basic.$this->_folder_temp;
      return $retour;
   }

   function _makeTempFolder () {
      $first_folder_string = $this->getTempFolder();
      $first_folder = @opendir($first_folder_string);
      if (!$first_folder) {
         mkdir($first_folder_string);
      }
   }

   public function saveFileFromBase64 ($file, $base64_data) {
      $data = base64_decode($base64_data);
      $this->_makeTempFolder();
      $retour = file_put_contents($this->getTempFolder().'/'.$file,$data);
      if ( $retour ) {
         $retour = $this->getTempFolder().'/'.$file;
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

   public function getFilePathBasic ( $full = false) {
      $retour = $this->_file_path_basic;
      if ( $full ) {
         $retour = getcwd().'/'.$retour;
      }
      return $retour;
   }

    public function removeRoomDir($first_id, $second_id)
    {
        $dir = $this->_getFilePath($first_id, $second_id);
        $this->_full_rmdir($dir);
    }

   public function saveURL2Temp ( $url, $filename ) {
      $out = fopen($this->getTempFolder().'/'.$filename,'wb');
      if ( $out == false ) {
         include_once('functions/error_functions.php');
         trigger_error('can not open destination file: '.$this->getTempFolder().'/'.$filename.' - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
      }
      if ( function_exists('curl_init') ) {
         $ch = curl_init();
         curl_setopt($ch,CURLOPT_FILE,$out);
         curl_setopt($ch,CURLOPT_HEADER,0);
         curl_setopt($ch,CURLOPT_URL,$url);
         curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
         curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);

         global $symfonyContainer;
         $c_proxy_ip = $symfonyContainer->getParameter('commsy.settings.proxy_ip');
         $c_proxy_port = $symfonyContainer->getParameter('commsy.settings.proxy_port');

         if ( !empty($c_proxy_ip) ) {
            $proxy = $c_proxy_ip;
            if ( !empty($c_proxy_port) ) {
               $proxy .= ':'.$c_proxy_port;
            }
            curl_setopt($ch,CURLOPT_PROXY,$proxy);
         }
         curl_exec($ch);
         $error = curl_error($ch);
         if ( !empty($error) ) {
            include_once('functions/error_functions.php');
            trigger_error('curl error: '.$error.' - '.$url.' - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
         }
         curl_close($ch);
      } else {
         include_once('functions/error_functions.php');
         trigger_error('curl library php5-curl is not installed - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
      }
      fclose($out);
      return file_exists($this->getTempFolder().'/'.$filename);
   }
}
?>