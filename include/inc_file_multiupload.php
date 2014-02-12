<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, JosÃ© Manuel GonzÃ¡lez VÃ¡zquez
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

// Configuration ---------------------------------------------------------------
$failure = false;
$session_item = $environment->getSessionItem();
$session_id = $session_item->getSessionID();

// Whether or not to allow the upload of specific files
$allow_or_deny = true;
// If the above is true, then this states whether the array of files is a list of
// extensions to ALLOW, or DENY
$allow_or_deny_method = "deny"; // "allow" or "deny"
$file_extension_list = array("php","asp","pl");
// -----------------------------------------------------------------------------
if ( $allow_or_deny ) {
   if ( ($allow_or_deny_method == "allow" and !in_array(mb_strtolower(array_pop(explode('.', $_FILES['userfile']['name'])), 'UTF-8'), $file_extension_list))
        or ($allow_or_deny_method == "deny" and in_array(mb_strtolower(array_pop(explode('.', $_FILES['userfile']['name'])), 'UTF-8'), $file_extension_list))){
      // Atempt to upload a file with a specific extension when NOT allowed.
      // 403 error
      header("HTTP/1.1 403 Forbidden");
      echo "POSTLET REPLY\r\n";
      echo "POSTLET:NO\r\n";
      echo "POSTLET:FILE TYPE NOT ALLOWED";
      echo "POSTLET:ABORT THIS\r\n"; // Postlet should NOT send this file again.
      echo "END POSTLET REPLY\r\n";
      exit;
   }
}

if ( isset($c_virus_scan)
    and $c_virus_scan
    and isset($c_virus_scan_cron)
    and !empty($c_virus_scan_cron)
    and !$c_virus_scan_cron
   ) {
   include_once('classes/cs_virus_scan.php');
   $virus_scanner = new cs_virus_scan($environment);
   if ($virus_scanner->isClean($_FILES['userfile']['tmp_name'],$_FILES['userfile']['name'])) {
      if ( move_uploaded_file($_FILES['userfile']['tmp_name'],$_FILES['userfile']['tmp_name'].'commsy3') ) {
         $temp_array = array();
         $temp_array['name'] = utf8_encode($_FILES['userfile']['name']);
         $temp_array['tmp_name'] = $_FILES['userfile']['tmp_name'].'commsy3';
         $temp_array['file_id'] = $temp_array['name'].'_'.getCurrentDateTimeInMySQL();

         // set flag for page: RUBRIC_edit
         if ( !$session_item->issetValue($environment->getCurrentModule().'_add_files_multi') ) {
            $session_item->setValue($environment->getCurrentModule().'_add_files_multi','true');
            $session_manager = $environment->getSessionManager();
            $session_manager->save($session_item);
         }

         // store information about file in DB
         // can not use session because of overlapping read and save actions
         $file_multi_upload_manager = $environment->getFileMultiUploadManager();
         $file_multi_upload_manager->addFileArray($session_item->getSessionID(),$temp_array);

         // All replies MUST start with "POSTLET REPLY", if they don't, then Postlet will
         // not read the reply and will assume the file uploaded successfully.
         echo "POSTLET REPLY\r\n";
         // "YES" tells Postlet that this file was successfully uploaded.
         echo "POSTLET:YES - ".$_FILES['userfile']['name']."\r\n";
         // End the Postlet reply
         echo "END POSTLET REPLY\r\n";
         exit;
      } else {
         $failure = true;
      }
   } else {
      if ( $session_item->issetValue($environment->getCurrentModule().'_add_files_multi_error') ) {
         $file_error_array = $session_item->getValue($environment->getCurrentModule().'_add_files_multi_error');
      } else {
         $file_error_array = array();
      }
      $file_error_array[] = $virus_scanner->getOutput();
      $session_item->setValue($environment->getCurrentModule().'_add_files_multi_error',$file_error_array);
      $session_manager = $environment->getSessionManager();
      $session_manager->save($session_item);

      // All replies MUST start with "POSTLET REPLY", if they don't, then Postlet will
      // not read the reply and will assume the file uploaded successfully.
      echo "POSTLET REPLY\r\n";
      echo "POSTLET:NO - ".$_FILES['userfile']['name']."\r\n";
      echo "POSTLET:VIRUS INSIDE\r\n";
      echo "POSTLET:ABORT THIS\r\n"; // Postlet should NOT send this file again.
      echo "END POSTLET REPLY\r\n";
      exit;
   }
} elseif (  move_uploaded_file($_FILES['userfile']['tmp_name'], $_FILES['userfile']['tmp_name'].'commsy3') ) {
   $temp_array = array();
   $temp_array['name'] = utf8_encode($_FILES['userfile']['name']);
   $temp_array['tmp_name'] = $_FILES['userfile']['tmp_name'].'commsy3';
   $temp_array['file_id'] = $temp_array['name'].'_'.getCurrentDateTimeInMySQL();

   // set flag for page: material_edit
   if ( !$session_item->issetValue($environment->getCurrentModule().'_add_files_multi') ) {
      $session_item->setValue($environment->getCurrentModule().'_add_files_multi','true');
      $session_manager = $environment->getSessionManager();
      $session_manager->save($session_item);
   }

   // store information about file in DB
   // can not use session because of overlapping read and save actions
   $file_multi_upload_manager = $environment->getFileMultiUploadManager();
   $file_multi_upload_manager->addFileArray($session_item->getSessionID(),$temp_array,$environment->getCurrentContextID());

   // All replies MUST start with "POSTLET REPLY", if they don't, then Postlet will
   // not read the reply and will assume the file uploaded successfully.
   echo "POSTLET REPLY\r\n";
   // "YES" tells Postlet that this file was successfully uploaded.
   echo "POSTLET:YES - ".$_FILES['userfile']['name']."\r\n";
   // End the Postlet reply
   echo "END POSTLET REPLY\r\n";
   exit;
} else {
   $failure = true;
}

if ( $failure ) {
   // If the file can not be uploaded (most likely due to size), then output the
   // correct error code
   // If $_FILES is EMPTY, or $_FILES['userfile']['error']==1 then TOO LARGE
   if (count($_FILES)==0 or $_FILES['userfile']['error']==1) {
      // All replies MUST start with "POSTLET REPLY", if they don't, then Postlet will
      // not read the reply and will assume the file uploaded successfully.
      header("HTTP/1.1 413 Request Entity Too Large");
      echo "POSTLET REPLY\r\n";
      echo "POSTLET:NO\r\n";
      echo "POSTLET:TOO LARGE\r\n";
      echo "POSTLET:ABORT THIS\r\n"; // Postlet should NOT send this file again.
      echo "END POSTLET REPLY\r\n";
      exit;
   }

   // Unable to write the file to the server ALL WILL FAIL
   elseif ($_FILES['userfile']['error']==6 || $_FILES['userfile']['error']==7){
      // All replies MUST start with "POSTLET REPLY", if they don't, then Postlet will
      // not read the reply and will assume the file uploaded successfully.
      header("HTTP/1.1 500 Internal Server Error");
      echo "POSTLET REPLY\r\n";
      echo "POSTLET:NO\r\n";
      echo "POSTLET:SERVER ERROR\r\n";
      echo "POSTLET:ABORT ALL\r\n"; // Postlet should NOT send any more files
      echo "END POSTLET REPLY\r\n";
      exit;
   }

   // Unsure of the error here (leaves 2,3,4, which means try again)
   else {
      // All replies MUST start with "POSTLET REPLY", if they don't, then Postlet will
      // not read the reply and will assume the file uploaded successfully.
      header("HTTP/1.1 500 Internal Server Error");
      echo "POSTLET REPLY\r\n";
      echo "POSTLET:NO\r\n";
      echo "POSTLET:UNKNOWN ERROR\r\n";
      echo "POSTLET:RETRY\r\n";
      print_r($_REQUEST); // Possible usefull for debugging
      echo "END POSTLET REPLY\r\n";
      exit;
   }
} else {
   header("HTTP/1.1 500 Internal Server Error");
   echo "POSTLET REPLY\r\n";
   echo "POSTLET:NO\r\n";
   echo "POSTLET:UNKNOWN ERROR\r\n";
   echo "POSTLET:RETRY\r\n";
   print_r($_REQUEST); // Possible usefull for debugging
   echo "END POSTLET REPLY\r\n";
   exit;
}
?>