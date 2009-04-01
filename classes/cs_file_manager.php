<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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

/** upper class of the material manager
 */
include_once('classes/cs_manager.php');

/** cs_list is needed for storage of the commsy items
 */
include_once('classes/cs_list.php');

/** cs_file_item is needed to create file items
 */
include_once('classes/cs_file_item.php');

/** date functions are needed for ???
 */
include_once('functions/date_functions.php');

/** text functions are needed for ???
 */
include_once('functions/text_functions.php');

/** file functions are needed for ???
 */
include_once('functions/file_functions.php');

/** class for database connection to the database table "material"
 * this class implements a database manager for the table "material"
 */
class cs_file_manager extends cs_manager {

   //maximal length of a picture side in pixel- if a picture that is showd inline is bigger, there is a thumbnale with this size shown
   var $_MAX_PICTURE_SIDE = 200;

   var $_cache = array();

   var $_mime = array();
   var $_limit_scan = '';
   var $_limit_newer = '';

  /** constructor: cs_file_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
   function cs_file_manager ($environment) {
      $this->cs_manager($environment);
      $this->_db_table = 'files';

      $this->_mime['tex']   = 'application/x-tex';
      $this->_mime['dvi']   = 'application/x-dvi';

      // Text
      $this->_mime['htm']     = 'text/html';
      $this->_mime['html']    = 'text/html';
      $this->_mime['txt']     = 'text/plain';
      $this->_mime['text']    = 'text/plain';
      $this->_mime['xml']     = 'text/xml';
      $this->_mime['css']     = 'text/css';
      $this->_mime['xsl']     = 'text/xml';

      // Pictures
      $this->_mime['jpg']     = 'image/jpeg';
      $this->_mime['jpeg']    = 'image/jpeg';
      $this->_mime['gif']     = 'image/gif';
      $this->_mime['tif']     = 'image/tiff';
      $this->_mime['tiff']    = 'image/tiff';
      $this->_mime['png']     = 'image/png';
      $this->_mime['qt']      = 'image/quicktime';
      $this->_mime['pict']    = 'image/pict';
      $this->_mime['psd']     = 'image/x-photoshop';
      $this->_mime['bmp']     = 'image/bmp';

      // Archives
      $this->_mime['zip']     = 'application/x-zip-compressed';
      $this->_mime['tar']     = 'application/x-tar';
      $this->_mime['gz']      = 'application/x-compressed';
      $this->_mime['tgz']     = 'application/x-compressed';
      $this->_mime['z']       = 'application/x-compress';
      $this->_mime['hqx']     = 'application/mac-binhex40';
      $this->_mime['sit']     = 'application/x-stuffit';

      // Audio
      $this->_mime['au']      = 'audio/basic';
      $this->_mime['wav']     = 'audio/wav';
      $this->_mime['mp3']     = 'audio/mpeg';
      $this->_mime['aif']     = 'audio/x-aiff';
      $this->_mime['aiff']    = 'audio/x-aiff';

      // Video
      $this->_mime['avi']     = 'video/avi';
      $this->_mime['mov']     = 'video/quicktime';
      $this->_mime['moov']    = 'video/quicktime';
      $this->_mime['mpg']     = 'video/mpeg';
      $this->_mime['mpeg']    = 'video/mpeg';
      $this->_mime['dif']     = 'video/x-dv';
      $this->_mime['dv']      = 'video/x-dv';

      // Vendor-specific
      $this->_mime['pdf']     = 'application/pdf';
      $this->_mime['fdf']     = 'application/vnd.fdf';
      $this->_mime['doc']     = 'application/msword';
      $this->_mime['dot']     = 'application/msword';
      $this->_mime['rtf']     = 'application/rtf';

      // open office
      $this->_mime['odf']     = 'application/smath';
      $this->_mime['odg']     = 'application/sdraw';
      $this->_mime['ods']     = 'application/scalc';
      $this->_mime['odp']     = 'application/simpress';
      $this->_mime['odt']     = 'application/swriter';

      // Flash / Shockwave
      $this->_mime['swf']      = 'application/x-shockwave-flash';

      $this->_mime['js'] = 'application/x-javascript';
      $this->_type = 'file';
   }

   /**
    * get empty file item
    *
    * @return cs_file_item
    */
   function getNewItem() {
      $item = new cs_file_item($this->_environment);
      $item->setContextID($this->_environment->getCurrentContextID());
      return $item;
   }

   function getMime($file) {
      $extension = mb_strtolower(mb_substr(strrchr($file,"."),1), 'UTF-8');
      return empty($this->_mime[$extension]) ? 'application/octetstream' : $this->_mime[$extension];
   }

   function getItem( $file_id ) {
      $file = NULL;
      $query  = 'SELECT * FROM files';
      $query .= ' WHERE 1';
      if ($this->_delete_limit == true) {
        $query .= ' AND files.deleter_id IS NULL';
      }
      $query .= ' AND files.files_id="'.encode(AS_DB,$file_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems get file entry ['.$file_id.'].',E_USER_WARNING);
         $file = array();
      } elseif ( !empty($result[0]) ) {
         $query_result = $result[0];
         $file = $this->_buildItem($query_result);
      }
      return $file;
   }

   function updateHasHTML($file_item) {
      $saved = false;
      $current_user = $this->_environment->getCurrentUser();
        $query = 'UPDATE '.$this->_db_table.' SET'.
                ' has_html="'.encode(AS_DB,$file_item->getHasHTML()).'"'.
                    ' WHERE files_id = "'.encode(AS_DB,$file_item->getFileID()).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
          include_once('functions/error_functions.php');
          trigger_error("Filemanager: Problem creating file entry: ".$query, E_USER_ERROR);
      } else {
         $saved = true;
      }
       unset($file_item);
       return $saved;
    }

    function updateExtras($file_item) {
      $saved = false;
      $current_user = $this->_environment->getCurrentUser();
        $query = 'UPDATE '.$this->_db_table.' SET'.
                ' extras="'.encode(AS_DB,serialize($file_item->getExtraInformation())).'"'.
                    ' WHERE files_id = "'.encode(AS_DB,$file_item->getFileID()).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
          include_once('functions/error_functions.php');
          trigger_error("Filemanager: Problem creating file entry: ".$query, E_USER_ERROR);
      } else {
         $saved = true;
      }
       unset($file_item);
       return $saved;
    }

   function saveItem($file_item) {
      $saved = false;
      $current_user = $this->_environment->getCurrentUser();
      $query =  'INSERT INTO '.$this->_db_table.' SET'.
                ' context_id="'.encode(AS_DB,$file_item->getContextID()).'",'.
                ' creation_date="'.getCurrentDateTimeInMySQL().'", '.
                ' creator_id="'.encode(AS_DB,$current_user->getItemID()).'", '.
                ' filename="'.encode(AS_DB,$file_item->getFileName()).'", ' .
                ' scan="'.encode(AS_DB,$file_item->getScanValue()).'", ';
      $has_html = $file_item->getHasHTML();
      if ( !empty($has_html) ) {
         $query .= ' has_html="'.encode(AS_DB,$has_html).'", ';
      }
      $query .= ' extras="'.encode(AS_DB,serialize($file_item->getExtraInformation())).'"';
      unset($current_user);
      $result = $this->_db_connector->performQuery($query);
      if ( isset($result) ) {
         $file_item->setFileID($result);
         $saved = $this->_saveOnDisk($file_item);
         if ($saved) {
            $query = 'UPDATE '.$this->_db_table.' SET'.
                     ' size="'.encode(AS_DB,filesize($file_item->getDiskFileName())).'"'.
                     ' WHERE files_id="'.encode(AS_DB,$file_item->getFileID()).'"';
            $result = $this->_db_connector->performQuery($query);
         }
       } else {
          include_once('functions/error_functions.php');
          trigger_error("Filemanager: Problem creating file entry: ".$query, E_USER_ERROR);
       }
       unset($file_item);
       return $saved;
    }

   function _saveOnDisk($file_item) {
      $success = false;
      $tempname = $file_item->_getTempName();
      if ( !empty($tempname) ) {
         $disc_manager = $this->_environment->getDiscManager();
         $disc_manager->setContextID($file_item->getContextID());
         $portal_id = $file_item->getPortalID();
         if ( isset($portal_id) and !empty($portal_id) ) {
            $disc_manager->setPortalID($portal_id);
         }
         // Currently, the file manager does not unlink a file here, because it is also used for copying files when copying material between rooms.
         $success = $disc_manager->copyFile($tempname, $file_item->getDiskFileNameWithoutFolder(),false);
         if (!$success) {
            // Fehlerbehandlung jetzt in RUBRIK_clipboard_index.php
            // include_once('functions/error_functions.php');
            // trigger_error('Filemanager: Could not save (temporary) file: "'.$file_item->_getTempName().'" to disk as: "'.$file_item->getDiskFileName().'"', E_USER_ERROR);
         } else {
            if (function_exists('gd_info')) {
               $size_info = @getImageSize($file_item->getDiskFileName());
               if (is_array($size_info)) {
                  if ($size_info[0] > $this->_MAX_PICTURE_SIDE OR $size_info[1] > $this->_MAX_PICTURE_SIDE) {
                     //create Filename: origname.xxx -> origname_thumb.png
                     $destination = $this->_create_thumb_name_from_image_name($file_item->getDiskFileNameWithoutFolder());
                     $this->_miniatur($file_item->getDiskFileName(),$destination);
                  }
               }
            }
         }
         $disc_manager->setContextID($this->_environment->getCurrentContextID());
      }
      unset($file_item);
      unset($disc_manager);
      return $success;
   }


   function setScanLimit () {
      $this->_limit_scan = 1;
   }

   function setNotScanLimit () {
      $this->_limit_scan = -1;
   }

   function setNewerLimit ( $datetime ) {
      $this->_limit_newer = $datetime;
   }

   function resetLimits () {
      $this->_limit_scan = '';
      $this->_limit_newer = '';
   }

   function get() {
      return $this->_data;
   }

   function _performQuery ($count = false) {
      $query  = 'SELECT  files.files_id, files.creator_id, files.deleter_id, files.creation_date, files.modification_date, files.deletion_date, files.filename, files.context_id, files.size, files.has_html, files.scan, files.extras';
      $query .= ' FROM '.$this->_db_table;
      $query .= ' WHERE 1';

      if ($this->_delete_limit == true) {
         $query .= ' AND files.deleter_id IS NULL';
         $query .= ' AND files.deletion_date IS NULL';
      }

      if (isset($this->_id_array_limit)) {
         $id_string = implode(', ', $this->_id_array_limit);
         if ($id_string == '') {
            $query .= ' AND 1=0';
         } else {
            $query .= ' AND files.files_id IN ('.encode(AS_DB,$id_string).')';
         }
      }

      if ( !empty($this->_limit_scan) ) {
         $query .= ' AND '.$this->_db_table.'.scan="'.encode(AS_DB,$this->_limit_scan).'"';
      }
      if ( !empty($this->_room_limit) ) {
         $query .= ' AND '.$this->_db_table.'.context_id="'.encode(AS_DB,$this->_room_limit).'"';
      }
      if ( !empty($this->_limit_newer) ) {
          $query .= ' AND '.$this->_db_table.'.creation_date>"'.encode(AS_DB,$this->_limit_newer).'"';
      }

      if (isset($this->_order)) {
         $query .= ' ORDER BY '.$this->_order;
      } else {
         $query .= ' ORDER BY filename DESC';
      }

      $cache_exists = false;
      if (!empty($this->_cache)){
          if (isset($this->_id_array_limit)) {
             $cache_exists = true;
             foreach ($this->_id_array_limit as $id) {
                if (!array_key_exists($id, $this->_cache)) {
                   $cache_exists = false;
                } else {
                   $result[] = $this->_cache[$id];
                }
             }
          }
          if (!$cache_exists){
             $result = array();
          }
      }
      if (!$cache_exists){
         // perform query
         $r = $this->_db_connector->performQuery($query);
         if (!isset($r)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems with links: "'.$this->_dberror.'" from query: "'.$query.'"',E_USER_WARNING);
         } else {
            if ( $this->_cache_on ) {
               foreach ($r as $res) {
                  $this->_cache[$res['files_id']] = $res;
               }
            }
            $result = $r;
         }
      }
      return $result;
   }

  /**  delete a file "item"
   *
   * @param cs_file_item the file "item" to be deleted
   *
   * @access public
   */
   function delete ($item_id) {
      $current_datetime = getCurrentDateTimeInMySQL();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_id = $current_user->getItemID();
      $query = 'UPDATE '.$this->_db_table.' SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE files_id="'.encode(AS_DB,$item_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems deleting files from query: "'.$query.'"',E_USER_WARNING);
      } else {
         $link_manager = $this->_environment->getLinkItemFileManager();
         $link_manager->deleteByFileID($item_id);
         unset($link_manager);
      }
   }

   function deleteReally ($file_item) {
      $query = 'DELETE FROM '.$this->_db_table.
              ' WHERE files_id="'.encode(AS_DB,$file_item->getFileID()).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems deleting files from query: "'.$query.'"',E_USER_WARNING);
      } else {
         $disc_manager = $this->_environment->getDiscManager();
         $disc_manager->unlinkFile($file_item->getDiskFileNameWithoutFolder());
         unset($disc_manager);

         $link_manager = $this->_environment->getLinkItemFileManager();
         $link_manager->deleteByFileReally($file_item->getFileID());
         unset($link_manager);
      }
      unset($file_item);
   }

   private function _deleteReallyByFileIDOnlyDB ($file_id) {
      $query = 'DELETE FROM '.$this->_db_table.
               ' WHERE files_id="'.encode(AS_DB,$file_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems deleting links of a file item from query: "'.$query.'"',E_USER_WARNING);
      }
   }

   function _miniatur($pict, $dest_pict){
      $image_in_info = GetImageSize ($pict);
      $x_orig= $image_in_info[0];
      $y_orig= $image_in_info[1];
      $file_type = $image_in_info[2];

      //Depending of image format, use the corrct function to read the image
      switch ($file_type) {
         case 1: //Gif
            $image_in = ImageCreateFromGIF ($pict);
            break;
         case 2: //Jpeg
            $image_in = ImageCreateFromJPEG ($pict);
            break;
         case 3: //Png
            $image_in = ImageCreateFromPNG ($pict);
      }

      if (isset($image_in)) {

           //scale the image- the longest side is _MAX_PICTURE_SIDE px long
         $scale = $this->_MAX_PICTURE_SIDE/$x_orig;
         if ($x_orig < $y_orig) {
            $scale = $this->_MAX_PICTURE_SIDE/$y_orig;
         }

         $horizontal=round($x_orig*$scale);
         $vertikal=round($y_orig*$scale);

         $x0=0;
         $y0=0;
         $xw=$horizontal;
         $yw=$vertikal;

         //create pitput picture
         if ($file_type != 1) { //all but gif
            $image_out = imagecreatetruecolor($horizontal, $vertikal);
         } else {
            $image_out = imagecreate($horizontal, $vertikal);
         }
         $color = imagecolorallocate( $image_out, 255, 128, 255); //magenta
         imagefill($image_out, 0, 0, $color);
         imagecolortransparent ( $image_out , $color);
         imagecopyresampled ($image_out, $image_in, $x0, $y0, 0, 0, $xw, $yw, $x_orig, $y_orig);
          $disc_manager = $this->_environment->getDiscManager();
         ImagePNG($image_out, $disc_manager->getFilePath().$dest_pict);
         imagedestroy($image_in);
         imagedestroy($image_out);
      }
   }

   //create Filename: origname.xxx -> origname_thumb.png
   function _create_thumb_name_from_image_name($name) {
      //$thumb_name = $name;
      //$point_position = mb_strrpos($thumb_name,'.');
      //$thumb_name = substr_replace ( $thumb_name, '_thumb.png', $point_position , mb_strlen($thumb_name));
      //$thumb_name = substr($thumb_name, 0, $point_position).'_thumb.png'.substr($thumb_name, $point_position+mb_strlen($thumb_name));
      $thumb_name = $name . '_thumb';
      return $thumb_name;
   }

   function copyDataFromRoomToRoom ($old_id, $new_id, $user_id='') {
      $retour = array();
      $current_date = getCurrentDateTimeInMySQL();

      $query  = '';
      $query .= 'SELECT * FROM '.$this->_db_table.' WHERE context_id="'.encode(AS_DB,$old_id).'" AND deleter_id IS NULL AND deletion_date IS NULL';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems getting data "'.$this->_db_table.'" from query: "'.$query.'"',E_USER_WARNING);
      } else {
         foreach ($result as $query_result) {
            $insert_query  = '';
            $insert_query .= 'INSERT INTO '.$this->_db_table.' SET';
            $first = true;
            $old_item_id = '';
            foreach ($query_result as $key => $value) {
               $value = encode(FROM_DB,$value);
               if ( $key == 'files_id' ) {
                  $old_item_id = $value;
               } elseif ($key == 'context_id') {
                  $after = $key.'="'.$new_id.'"';
               } elseif ( $key == 'modification_date'
                          or $key == 'creation_date'
                        ) {
                  $after = $key.'="'.$current_date.'"';
               } elseif ( !empty($user_id)
                          and ( $key == 'creator_id'
                                or $key == 'modifier_id' )
                        ) {
                  $after = $key.'="'.$user_id.'"';
               } elseif ( $key == 'deletion_date'
                          or $key == 'deleter_id'
                          or $key == 'material_id'
                          or $key == 'material_vid'
                        ) {
                  // do nothing
               } elseif ( $key == 'has_html'
                          and empty($value)
                        ) {
                  // do nothing
               } else {
                  $after = $key.'="'.encode(AS_DB,$value).'"';
               }

               if (!empty($after)) {
                  if ($first) {
                     $first = false;
                     $before = ' ';
                  } else {
                     $before = ',';
                  }
                  $insert_query .= $before.$after;
                  unset($after);
               }
            }
            $result_insert = $this->_db_connector->performQuery($insert_query);
            if ( !isset($result_insert) ) {
               include_once('functions/error_functions.php');
               trigger_error( 'Problem creating item from query: "'.$insert_query.'"',E_USER_ERROR);
            } else {
               $new_item_id = $result_insert;
               if (!empty($old_item_id)) {
                  $retour[CS_FILE_TYPE.$old_item_id] = $new_item_id;
               } else {
                  include_once('functions/error_functions.php');
                  trigger_error('lost old item id at copying data',E_USER_ERROR);
               }
            }
         }
      }
      $disc_manager = $this->_environment->getDiscManager();
      $disc_manager->setPortalID($this->_environment->getCurrentPortalID());

      // copy files
      foreach ($retour as $old_file_id => $new_file_id) {
         $real_old_file_id = str_replace(CS_FILE_TYPE,'',$old_file_id);
         $file_item = $this->getItem($real_old_file_id);
         if (!empty($file_item)) {
            $result = $disc_manager->copyFileFromRoomToRoom($old_id,$real_old_file_id,$file_item->getFileName(),$new_id,$new_file_id);
            if (!$result) {
               //include_once('functions/error_functions.php');
               //trigger_error('can not copy file on disc',E_USER_ERROR);
            }
         } else {
            include_once('functions/error_functions.php');
            trigger_error('can not get old file item',E_USER_ERROR);
         }
      }
      unset($disc_manager);
      return $retour;
   }

   function deleteReallyOlderThan ($days) {
      $disc_manager = $this->_environment->getDiscManager();
      $retour = true;
      $timestamp = getCurrentDateTimeMinusDaysInMySQL($days);

      $query = 'SELECT '.$this->_db_table.'.files_id, '.$this->_db_table.'.context_id, '.$this->_db_table.'.filename FROM '.$this->_db_table.' WHERE deletion_date IS NOT NULL and deletion_date < "'.$timestamp.'";';

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problem selecting items from query: "'.$query.'"',E_USER_ERROR);
         $retour = false;
      } else {
         $retour = $retour and parent::deleteReallyOlderThan($days);
         foreach ($result as $query_result) {
            $query2 = 'SELECT context_id as portal_id FROM room WHERE item_id="'.$query_result['context_id'].'"';
            $result2 = $this->_db_connector->performQuery($query2);
            if ( !isset($result2) ) {
               include_once('functions/error_functions.php');
               trigger_error('Problem selecting items from query: "'.$query.'"',E_USER_ERROR);
               $retour = false;
            } elseif ( !empty($result2[0]) ) {
               $query_result2 = $result2[0];
               if (!empty($query_result2['portal_id'])) {
                  $filename = 'cid'.$query_result['context_id'].'_'.$query_result['files_id'].'_'.$query_result['filename'];
                  $disc_manager->setPortalID($query_result2['portal_id']);
                  $disc_manager->setContextID($query_result['context_id']);
                  if ($disc_manager->existsFile($filename)) {
                     $retour = $retour and $disc_manager->unlinkFile($filename);
                  }
               }
            }
         }
      }
      return $retour;
   }

   function deleteUnneededFiles ( $context_id ) {
      if ( !isset($context_id) or empty($context_id) ) {
         include_once('functions/error_functions.php');
         trigger_error('deleteUnneededFiles: no context_id given',E_USER_ERROR);
         $retour = false;
      } else {
         $retour = true;
         $sql = 'SELECT '.$this->_db_table.'.files_id, '.$this->_db_table.'.context_id, '.$this->_db_table.'.filename FROM '.$this->_db_table.' LEFT JOIN item_link_file ON item_link_file.file_id='.$this->_db_table.'.files_id WHERE '.$this->_db_table.'.context_id="'.$context_id.'" AND item_link_file.file_id IS NULL;';
         $result = $this->_db_connector->performQuery($sql);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problem selecting items from query: "'.$sql.'"',E_USER_ERROR);
            $retour = false;
         } else {
            $disc_manager = $this->_environment->getDiscManager();
            foreach ($result as $query_result) {
               $sql = 'DELETE FROM '.$this->_db_table.' WHERE files_id="'.$query_result['files_id'].'";';
               $result_delete = $this->_db_connector->performQuery($sql);

               $query2 = 'SELECT context_id as portal_id FROM room WHERE item_id="'.$query_result['context_id'].'"';
               $result2 = $this->_db_connector->performQuery($query2);
               if ( !isset($result2) ) {
                  include_once('functions/error_functions.php');
                  trigger_error('Problem selecting items from query: "'.$query2.'"',E_USER_ERROR);
                  $retour = false;
               } elseif ( !empty($result2[0]) ) {
                  $query_result2 = $result2[0];
                  if (!empty($query_result2['portal_id'])) {
                     $filename = 'cid'.$query_result['context_id'].'_'.$query_result['files_id'].'_'.$query_result['filename'];
                     $disc_manager->setPortalID($query_result2['portal_id']);
                     $disc_manager->setContextID($query_result['context_id']);
                     if ($disc_manager->existsFile($filename)) {
                        $retour = $retour and $disc_manager->unlinkFile($filename);
                     }
                  }
               }
            }
         }
         unset($disc_manager);
      }
      return $retour;
   }

   public function updateScanned ($file_item) {
      $saved = false;
      $query = 'UPDATE '.$this->_db_table.' SET'.
               ' scan="'.encode(AS_DB,$file_item->getScanValue()).'"'.
               ' WHERE files_id = "'.encode(AS_DB,$file_item->getFileID()).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error("Filemanager: Problem updating file entry: ".$query, E_USER_ERROR);
      } else {
         $saved = true;
      }
      unset($file_item);
      return $saved;
   }

   /** Prepares the db_array for the item
    *
    * @param $db_array Contains the data from the database
    *
    * @return array Contains prepared data ( textfunctions applied etc. )
    */
   function _buildItem($db_array) {
      include_once('functions/text_functions.php');
      $db_array['extras'] = mb_unserialize($db_array['extras']);
      return parent::_buildItem($db_array);
   }

   public function getFileIDForTempKey ( $temp_key ) {
      $retour = '';
      $sql = 'SELECT files_id FROM '.$this->_db_table.' WHERE context_id="'.$this->_room_limit.'" AND extras LIKE "%'.$temp_key.'%";';
      $result = $this->_db_connector->performQuery($sql);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error("Filemanager: Problem creating file entry: ".$sql, E_USER_ERROR);
      } elseif ( count($result) == 1
                 and !empty($result[0]['files_id'])
               ) {
         $retour = $result[0]['files_id'];
      }
      return $retour;
   }
}
?>