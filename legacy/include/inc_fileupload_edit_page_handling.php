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

$file_upload_rubric = $environment->getCurrentModule();

// init vars
$from_multiupload = false;

$translator = $environment->getTranslationObject();

// Upload a file
if ( !empty($_FILES['upload']['tmp_name']) ) {
   $focus_element_onload = 'upload';
   if ( $session->issetValue($file_upload_rubric.'_add_files') ) {
      $file_array = $session->getValue($file_upload_rubric.'_add_files');
   } else {
      $file_array = array();
   }
   $new_file_ids = array();
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
         if ($virus_scanner->isClean($_FILES['upload']['tmp_name'],$_FILES['upload']['name'])) {
            move_uploaded_file($_FILES['upload']['tmp_name'], $_FILES['upload']['tmp_name'].'commsy3');
            $temp_array = array();
            $temp_array['name'] = $_FILES['upload']['name'];
            $temp_array['tmp_name'] = $_FILES['upload']['tmp_name'].'commsy3';
            $temp_array['file_id'] = $temp_array['name'].'_'.getCurrentDateTimeInMySQL();
            $file_array[] = $temp_array;
            $new_file_ids[] = $temp_array['file_id'];
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
         move_uploaded_file($_FILES['upload']['tmp_name'], $_FILES['upload']['tmp_name'].'commsy3');
         $temp_array = array();
         $temp_array['name'] = $_FILES['upload']['name'];
         $temp_array['tmp_name'] = $_FILES['upload']['tmp_name'].'commsy3';
         $temp_array['file_id'] = $temp_array['name'].'_'.getCurrentDateTimeInMySQL();
         $file_array[] = $temp_array;
         $new_file_ids[] = $temp_array['file_id'];
      }
   }
   if ( count($file_array) > 0 ) {
      $session->setValue($file_upload_rubric.'_add_files', $file_array);
   } else {
      $session->unsetValue($file_upload_rubric.'_add_files');
   }
   if ( isset($_POST['filelist']) ) {
      $post_file_ids = $_POST['filelist'];
   } else {
      $post_file_ids = array();
   }
   $post_file_ids = array_merge($post_file_ids, $new_file_ids);
}
?>