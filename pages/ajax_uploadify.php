<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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

include_once('functions/development_functions.php');

if(!empty($_FILES)) {
   $post_file_ids = array();
   $tempFile = $_FILES['Filedata']['tmp_name'];
   
   $file_upload_rubric = $_REQUEST['file_upload_rubric'];
   $focus_element_onload = 'Filedata';
   
   if($session->issetValue($file_upload_rubric . '_add_files')) {
      $file_array = $session->getValue($file_upload_rubric . '_add_files');
   } else {
      $file_array = array();
   }
   
//   $new_file_ids = array();
   if(   !empty($tempFile) &&
         $_FILES['Filedata']['size'] > 0) {
      if(   isset($_REQUEST['c_virus_scan']) &&
            $_REQUEST['c_virus_scan'] &&
            isset($_REQUEST['c_virus_scan_cron']) &&
            !empty($_REQUEST['c_virus_scan_cron']) &&
            !$_REQUEST['c_virus_scan_crom']) {
         // use virus scanner
         require_once('classes/cs_virus_scan.php');
         $virus_scanner = new cs_virus_scan($environment);
         if ($virus_scanner->isClean($tempFile,$tempFile)) {
            move_uploaded_file($tempFile, $tempFile . 'commsy3');
            $temp_array = array();
            $temp_array['name'] = $_FILES['Filedata']['name'];
            $temp_array['tmp_name'] = $tempFile. 'commsy3';
            $temp_array['file_id'] = $temp_array['name'].'_' . getCurrentDateTimeInMySQL();
            $file_array[] = $temp_array;
//            $new_file_ids[] = $temp_array['file_id'];
         } else {
            $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = true;
            $params['width'] = 500;
            $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
            unset($params);
            $errorbox->setText($virus_scanner->getOutput());
            $page->add($errorbox);
            $focus_element_onload = '';
            $error_on_upload = true;
         }
      } else {
         // do not use virus scanner
         require_once('functions/date_functions.php');
         move_uploaded_file($tempFile, $tempFile . 'commsy3');
         $temp_array = array();
         $temp_array['name'] = $_FILES['Filedata']['name'];
         $temp_array['tmp_name'] = $tempFile . 'commsy3';
         $temp_array['file_id'] = $temp_array['name'] . '_' . getCurrentDateTimeInMySQL();
         $file_array[] = $temp_array;
//         $new_file_ids[] = $temp_array['file_id'];
      }
   }
   if(count($file_array) > 0) {
      $session->setValue($file_upload_rubric . '_add_files', $file_array);
   } else {
      $session->unsetValue($file_upload_rubric . '_add_files');
   }
   
   echo $temp_array['file_id'];
}

$environment->getSessionManager()->save($session);
exit;
?>