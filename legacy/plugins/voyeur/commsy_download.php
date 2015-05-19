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

// send zip
if ( !empty($_GET['filename']) ) {
   global $symfonyContainer;
   $export_temp_folder = $symfonyContainer->getParameter('commsy.settings.export_temp_folder');
   if ( !isset($export_temp_folder) ) {
      $export_temp_folder = 'var/temp/zip_export';
   }
   if ( file_exists($export_temp_folder.'/'.$_GET['filename']) ) {
      header('Content-type: application/zip');
      header('Content-Disposition: attachment; filename="'.$_GET['filename'].'"');
      readfile($export_temp_folder.'/'.$_GET['filename']);
      exit();
   }
}

// url to commsy zip
elseif ( !empty($_GET['iid']) ) {
   $item_manager = $environment->getItemManager();
   $item_type = $item_manager->getItemType($_GET['iid']);
   if ( !empty($item_type) ) {
      global $symfonyContainer;
      $export_temp_folder = $symfonyContainer->getParameter('commsy.settings.export_temp_folder');
      if(!isset($export_temp_folder)) {
         $export_temp_folder = 'var/temp/zip_export';
      }
      $voyeur_zip_name = $export_temp_folder.'/'.$item_type.'_'.$_GET['iid'].'_voyeur.zip';
      if ( !file_exists($voyeur_zip_name) ) {
         $zip_name = $export_temp_folder.'/'.$item_type.'_'.$_GET['iid'].'.zip';
         if ( !file_exists($zip_name) ) {
            $item2ZIP = $class_factory->getClass(MISC_ITEM2ZIP,array('environment' => $environment));
            $item2ZIP->setItemID($_GET['iid']);
            $item2ZIP->execute();
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
      if ( !isset($goto)
           or empty($goto)
           or !$goto
         ) {
         $voyeur_zip_name = $export_temp_folder.'/'.$item_type.'_'.$_GET['iid'].'_voyeur.zip';
         $downloadfile =  str_replace($export_temp_folder.'/','',$voyeur_zip_name);
         header('Content-type: application/zip');
         header('Content-Disposition: attachment; filename="'.$downloadfile.'"');
         readfile($voyeur_zip_name);
         exit();
      } else {
         $voyeur = $environment->getPluginClass('voyeur');
         $url = $voyeur->getVoyeurURL();
         header('Location: '.$url);
         header('HTTP/1.0 302 Found');
         exit();
      }

   } else {
      include_once('functions/error_functions.php');
      trigger_error('id ('.$_GET['iid'].') as no type',E_USER_WARNING);
   }
} else {
   include_once('functions/error_functions.php');
   trigger_error('no item id given',E_USER_WARNING);
}
?>