<?php
//
// Release $Name$
//
// Copyright (c)2002-2003 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
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

if ( !empty($_GET['iid']) ) {
   $file_manager = $environment->getFileManager();
   $file = $file_manager->getItem($_GET['iid']);

   // is zip unpacked?
   $disc_manager = $environment->getDiscManager();
   $disc_manager->setPortalID($environment->getCurrentPortalID());
   $disc_manager->setContextID($environment->getCurrentContextID());
   $path_to_file = $disc_manager->getFilePath();
   unset($disc_manager);
   $dir = './'.$path_to_file.'html_'.$file->getDiskFileNameWithoutFolder();
   if ( !is_dir($dir) ) {
      include_once('pages/html_upload.php');
   }

   if ( file_exists('./'.$path_to_file.'html_'.$file->getDiskFileNameWithoutFolder().'/index.htm')
        and empty($_GET['file'])
      ) {
      $filename = 'index.htm';
   } elseif((file_exists('./'.$path_to_file.'html_'.$file->getDiskFileNameWithoutFolder().'/index.html')) and empty($_GET['file'])) {
      $filename = 'index.html';
   } elseif ( !empty($_GET['file']) ) {
      $filename = $_GET['file'];
   } else {
      include_once('functions/error_functions.php');
      trigger_error('material_showzip: lost file, please close window and try again',E_USER_ERROR);
   }
   if (file_exists('./'.$path_to_file.'html_'.$file->getDiskFileNameWithoutFolder().'/'.$filename)) {
      $filecontent = file_get_contents('./'.$path_to_file.'html_'.$file->getDiskFileNameWithoutFolder().'/'.$filename);
      echo $filecontent;
      exit;
   } else {
      include_once('functions/error_functions.php');
      trigger_error("material_showzip: File not found!", E_USER_ERROR);
      trigger_error("material_showzip: File (./".$path_to_file."html_".$file->getDiskFileNameWithoutFolder()."/".$filename.") not found!", E_USER_ERROR);
   }
   unset($iid);
} else {
   include_once('functions/error_functions.php');
   trigger_error("material_showzip: Have no valid Item ID", E_USER_ERROR);
}

?>