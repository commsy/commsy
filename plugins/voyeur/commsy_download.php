<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Dr. Iver Jackewitz
//
// This file is part of the voyeur plugin for CommSy.
//
// This plugin is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This plugin is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You have received a copy of the GNU General Public License
// along with the plugin.

// url to commsy zip
if ( !empty($_GET['iid']) ) {
   $item_manager = $environment->getItemManager();
   $item_type = $item_manager->getItemType($_GET['iid']);
   if ( !empty($item_type) ) {
      global $export_temp_folder;
      if(!isset($export_temp_folder)) {
         $export_temp_folder = 'var/temp/zip_export';
      }
      $voyeur_zip_name = $export_temp_folder.'/'.$item_type.'_'.$_GET['iid'].'_voyeur.zip';
      if ( !file_exists($voyeur_zip_name) ) {
         $zip_name = $export_temp_folder.'/'.$item_type.'_'.$_GET['iid'].'.zip';
         if ( !file_exists($zip_name) ) {
            $url_params = array();
            $url_params['iid'] = $_GET['iid'];
            $url_params['download'] = 'zip';
            $url_params['mode'] = 'print';
            $session_item = $environment->getSessionItem();
            if ( isset($session_item) ) {
               $url_params['SID'] = $session_item->getSessionID();
            }

            global $c_commsy_domain, $c_commsy_url_path;
            $url_to_zip = $c_commsy_domain.$c_commsy_url_path;
            include_once('functions/misc_functions.php');
            $url_to_zip .= '/'._curl(false,$environment->getCurrentContextID(),type2module($item_type),'detail',$url_params);

            // get zip
            $directory_split = explode("/",$export_temp_folder);
            $done_dir = "./";
            foreach($directory_split as $dir) {
               if(!is_dir($done_dir.'/'.$dir)) {
                  mkdir($done_dir.'/'.$dir, 0777);
               }
               $done_dir .= '/'.$dir;
            }
            $directory = './'.$export_temp_folder;

            $file = $directory.'/'.$item_type.'_'.$_GET['iid'].'_temp.zip';
            $file_url = $url_to_zip;
            $out = fopen($file,'wb');
            if ( $out == false ) {
               include_once('functions/error_functions.php');
               trigger_error('can not open destination file. - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
            }
            if ( function_exists('curl_init') ) {
               $ch = curl_init();
               curl_setopt($ch,CURLOPT_FILE,$out);
               curl_setopt($ch,CURLOPT_HEADER,0);
               curl_setopt($ch,CURLOPT_URL,$file_url);
               global $c_proxy_ip;
               global $c_proxy_port;
               if ( !empty($c_proxy_ip) ) {
                  $proxy = $c_proxy_ip;
                  if ( !empty($c_proxy_port) ) {
                     $proxy = $c_proxy_ip.':'.$c_proxy_port;
                  }
                  curl_setopt($ch,CURLOPT_PROXY,$proxy);
               }
               curl_exec($ch);
               $error = curl_error($ch);
               if ( !empty($error) ) {
                  include_once('functions/error_functions.php');
                  trigger_error('curl error: '.$error.' - '.$file_url.' - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
               }
               curl_close($ch);
            } else {
               include_once('functions/error_functions.php');
               trigger_error('curl library php5-curl is not installed - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
            }
            fclose($out);
            if ( file_exists($file) ) {
               unlink($file);
            }
         }

         // change ZIP
         if ( file_exists($zip_name) ) {
            $zip = new ZipArchive();
            if ( $zip->open($zip_name) !== TRUE ) {
               include_once('functions/error_functions.php');
               trigger_error('can not modify zip',E_USER_WARNING);
            } else {
               // extract zip
               $voyeur_dir = $export_temp_folder.'/'.$item_type.'_'.$_GET['iid'].'_voyeur';
               if ( !is_dir($voyeur_dir) ) {
                  mkdir($voyeur_dir, 0777);
                  if ( is_dir($voyeur_dir) ) {
                     $zip->extractTo($voyeur_dir);
                     $zip->close();

                     // delete folder
                     $disc_manager = $environment->getDiscManager();
                     $disc_manager->removeDirectory($voyeur_dir.'/css');
                     $disc_manager->removeDirectory($voyeur_dir.'/images');

                  } else {
                     include_once('functions/error_functions.php');
                     trigger_error('can not make directory ('.$voyeur_dir.')',E_USER_ERROR);
                  }
               }

               // make zip
               $zip = new ZipArchive();

               if ( $zip->open($voyeur_zip_name, ZIPARCHIVE::CREATE) !== TRUE ) {
                   include_once('functions/error_functions.php');
                   trigger_error('can not open zip-file '.$filename_zip,E_USER_WARNNG);
               }
               $temp_dir = getcwd();
               chdir($voyeur_dir);

               $zip = addFolderToZip('.',$zip);
               chdir($temp_dir);

               $zip->close();
               unset($zip);

               $disc_manager = $environment->getDiscManager();
               $disc_manager->removeDirectory($voyeur_dir);
            }
         } else {
            include_once('functions/error_functions.php');
            trigger_error('zip for '.$item_type.' with id ('.$_GET['iid'].')',E_USER_ERROR);
         }
      }

      // to sender
      $voyeur_zip_name = $export_temp_folder.'/'.$item_type.'_'.$_GET['iid'].'_voyeur.zip';
      $downloadfile =  str_replace($export_temp_folder.'/','',$voyeur_zip_name);
      header('Content-type: application/zip');
      header('Content-Disposition: attachment; filename="'.$downloadfile.'"');
      readfile($voyeur_zip_name);
      exit();

   } else {
      include_once('functions/error_functions.php');
      trigger_error('id ('.$_GET['iid'].') as no type',E_USER_WARNING);
   }
} else {
   include_once('functions/error_functions.php');
   trigger_error('no item id given',E_USER_WARNING);
}
?>