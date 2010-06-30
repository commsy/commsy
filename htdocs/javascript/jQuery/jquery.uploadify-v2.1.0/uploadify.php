<?php
/*
Uploadify v2.1.0
Release Date: August 24, 2009

Copyright (c) 2009 Ronnie Garcia, Travis Nickels

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

/*
$file_upload_rubric = $environment->getCurrentModule();
$translator = $environment->getTranslationObject();
*/

chdir('../../../..');

ob_start();

/*
 * What about handling more files simultanious?
 */

print_r($_REQUEST);

if(!empty($_FILES)) {
   require_once('classes/cs_environment.php');
   require_once('classes/cs_session_item.php');
   require_once('classes/cs_session_manager.php');
   $environment = new cs_environment();
   
   $tempFile = $_FILES['Filedata']['tmp_name'];
   //$targetPath = $_SERVER['DOCUMENT_ROOT'] . $_REQUEST['folder'] . '/';
   //$targetFile =  str_replace('//','/',$targetPath) . $_FILES['Filedata']['name'];
   
   $file_upload_rubric = $_REQUEST['file_upload_rubric'];
   $focus_element_onload = 'Filedata';
//   $session = $environment->getSessionItem();
   // init session item
   $session_manager = $environment->getSessionManager();
   $session = $session_manager->get($_REQUEST['session_id']);
   
   if($session->issetValue($file_upload_rubric . '_add_files')) {
      $file_array = $session->getValue($file_upload_rubric . '_add_files');
   } else {
      $file_array = array();
   }
   $new_file_ids = array();
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
         // do not use virus scanner
         require_once('functions/date_functions.php');
         move_uploaded_file($tempFile, $tempFile . 'commsy3');
         $temp_array = array();
         $temp_array['name'] = $_FILES['Filedata']['name'];
         $temp_array['tmp_name'] = $tempFile . 'commsy3';
         $temp_array['file_id'] = $temp_array['name'] . '_' . getCurrentDateTimeInMySQL();
         $file_array[] = $temp_array;
         $new_file_ids[] = $temp_array['file_id'];
      }
   }
   if(count($file_array) > 0) {
      $session->setValue($file_upload_rubric . '_add_files', $file_array);
   } else {
      $session->unsetValue($file_upload_rubric . '_add_files');
   }
   /*
   if ( isset($_POST['filelist']) ) {
      $post_file_ids = $_POST['filelist'];
   } else {
      $post_file_ids = array();
   }
   $post_file_ids = array_merge($post_file_ids, $new_file_ids);
   */
   
   ////////////////////////////////////////////
   ////////////////////////////////////////////
   ////////////////////////////////////////////
   $post_file_ids = $new_file_ids;
   ////////////////////////////////////////////
   ////////////////////////////////////////////
   ////////////////////////////////////////////
}

echo $file_upload_rubric;
print_r($post_file_ids);

$handle = fopen('output.txt', 'w');
fwrite($handle, ob_get_flush());
fclose($handle);

?>