<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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

$item_id = '';
if ( !empty($_POST['item_id'])
     and is_numeric($_POST['item_id'])
     and $_POST['item_id'] > 0
   ) {
   $item_id = $_POST['item_id'];
} elseif ( !empty($_GET['item_id'])
     and is_numeric($_GET['item_id'])
     and $_GET['item_id'] > 0
   ) {
   $item_id = $_GET['item_id'];
} else {
   include_once('functions/error_functions.php');
   trigger_error('item id lost for linking file - please set item_id',E_USER_ERROR);
}
$version = 0;
if ( !empty($_POST['version'])
     and is_numeric($_POST['version'])
     and $_POST['version'] > 0
   ) {
   $version = $_POST['version'];
} elseif ( !empty($_GET['version'])
     and is_numeric($_GET['version'])
     and $_GET['version'] > 0
   ) {
   $version = $_GET['version'];
}

if ( $version_id > 0 ) {
   $manager = $environment->getMaterialManager();
} else {
   $item_manager = $environment->getItemManager();
   $item_type = $item_manager->getItemType($item_id);
   if ( $item_type != CS_MATERIAL_TYPE
        and $item_type != CS_SECTION_TYPE
        and $item_type != CS_ANNOUNCEMENT_TYPE
        and $item_type != CS_DATE_TYPE
        and $item_type != CS_DISCARTICLE_TYPE
        and $item_type != CS_TODO_TYPE
      ) {
      include_once('functions/error_functions.php');
      trigger_error('upload file: can not link file to '.$item_type,E_USER_ERROR);
   } else {
      $manager = $environment->getManager($item_type);
   }
}
$item_files_upload_to = $manager->getItem($item_id);

$file_array = array();
if ( !empty($_FILES)
     and is_array($_FILES)
     and !empty($_FILES['upload']['tmp_name'])
   ) {
   $file_array = $_FILES;
} else {
   include_once('functions/error_functions.php');
   trigger_error('file lost - please post file',E_USER_ERROR);
}

// Upload a file
if ( !empty($_FILES['upload']['tmp_name']) ) {
   $scan = false;
   if ( !empty($_FILES['upload']['tmp_name'])
        and $_FILES['upload']['size'] > 0
      ) {
      if ( isset($c_virus_scan)
           and $c_virus_scan
           and isset($c_virus_scan_cron)
           and !empty($c_virus_scan_cron)
           and !$c_virus_scan_cron
         ) {
         include_once('classes/cs_virus_scan.php');
         $virus_scanner = new cs_virus_scan($environment);
         if ( !$virus_scanner->isClean($_FILES['upload']['tmp_name'],$_FILES['upload']['name']) ) {
            include_once('functions/error_functions.php');
            trigger_error($virus_scanner->getOutput(),E_USER_ERROR);
         } else {
            $scan = true;
         }
      }
   }
   $file_man = $environment->getFileManager();
   $file_item = $file_man->getNewItem();
   $file_item->setPostFile($_FILES['upload']);
   $file_item->save();
   $file_id_array = $item_files_upload_to->getFileIDArray();
   $file_id_array[] = $file_item->getFileID();
   $item_files_upload_to->setFileIDArray($file_id_array);
   $item_files_upload_to->save();
   echo('success');
}
?>