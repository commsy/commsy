<?PHP
//
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

// get portal item and current user
$portal_item = $environment->getCurrentPortalItem();
$current_user = $environment->getCurrentUserItem();
$is_saved = false;

// Get the translator object
$translator = $environment->getTranslationObject();

if(!$current_user->isModerator() || !$environment->inPortal()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
}
// Access granted
else {

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }

   // Initialize the form
   $form = $class_factory->getClass(CONFIGURATION_EXPORT_IMPORT_FORM,array('environment' => $environment));
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);

   // Export item
   if (!empty($command) and (isOption($command, $translator->getMessage('PREFERENCES_EXPORT_IMPORT_EXPORT_BUTTON')))) {
      if ( $form->check() ) {
         if (isset($_POST['room']) && $_POST['room'] != "-1") {
            $room_manager = $environment->getRoomManager();
            $xml = $room_manager->export_item($_POST['room']);
            $dom = new DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml->asXML());
            //el($dom->saveXML());
   
            $filename = '../files/temp/commsy_xml_export_import_'.$_POST['room'].'.xml';
            if ( file_exists($filename) ) {
               unlink($filename);
            }
   
            $xmlfile = fopen($filename, 'a');   
            fputs($xmlfile, $dom->saveXML());
            fclose($xmlfile);
   
            //Location where export is saved
            $zipfile = '../files/temp/commsy_export_import_'.$_POST['room'].'.zip';
            if ( file_exists($zipfile) ) {
               unlink($zipfile);
            }
   
            //Location that will be backuped
            $disc_manager = $environment->getDiscManager();
            $disc_manager->setPortalID($environment->getCurrentPortalID());
   
            $backup_paths = array();
            $backup_paths_files = array();
            $room_item = $room_manager->getItem($_POST['room']);
            
            if ($room_item == NULL) {
               $privateroom_manager = $environment->getPrivateRoomManager();
               $room_item = $privateroom_manager->getItem($_POST['room']);
            }
            
            if ($room_item->getRoomType() == 'community') {
               $disc_manager->setContextID($room_item->getItemId());
               $backup_paths[$room_item->getItemId()] = $disc_manager->getFilePath();
               if (file_exists('../files/templates/individual/styles_'.$room_item->getItemID().'.css')) {
                  $backup_paths_files[] = '../files/templates/individual/styles_'.$room_item->getItemID().'.css';
               }
               $project_list = $room_item->getProjectList();
               $project_item = $project_list->getFirst();
               while ($project_item) {
                  $disc_manager->setContextID($project_item->getItemId());
                  $backup_paths[$project_item->getItemId()] = $disc_manager->getFilePath();
                  if (file_exists('../files/templates/individual/styles_'.$project_item->getItemID().'.css')) {
                     $backup_paths_files[] = '../files/templates/individual/styles_'.$project_item->getItemID().'.css';
                  }
                  $grouproom_list = $project_item->getGroupRoomList();
                  $grouproom_item = $grouproom_list->getFirst();
                  while ($grouproom_item) {
                     $disc_manager->setContextID($grouproom_item->getItemId());
                     $backup_paths[$grouproom_item->getItemId()] = $disc_manager->getFilePath();
                     if (file_exists('../files/templates/individual/styles_'.$grouproom_item->getItemID().'.css')) {
                        $backup_paths_files[] = '../files/templates/individual/styles_'.$grouproom_item->getItemID().'.css';
                     }
                     $grouproom_item = $grouproom_list->getNext();   
                  }
                  $project_item = $project_list->getNext();
               }
            } else if ($room_item->getRoomType() == 'project') {
               $disc_manager->setContextID($room_item->getItemId());
               $backup_paths[$room_item->getItemId()] = $disc_manager->getFilePath();
               if (file_exists('../files/templates/individual/styles_'.$room_item->getItemID().'.css')) {
                  $backup_paths_files[] = '../files/templates/individual/styles_'.$room_item->getItemID().'.css';
               }
               $grouproom_list = $room_item->getGroupRoomList();
               $grouproom_item = $grouproom_list->getFirst();
               while ($grouproom_item) {
                  $disc_manager->setContextID($grouproom_item->getItemId());
                  $backup_paths[$grouproom_item->getItemId()] = $disc_manager->getFilePath();
                  if (file_exists('../files/templates/individual/styles_'.$grouproom_item->getItemID().'.css')) {
                     $backup_paths_files[] = '../files/templates/individual/styles_'.$grouproom_item->getItemID().'.css';
                  }
                  $grouproom_item = $grouproom_list->getNext();   
               }
            } else if ($room_item->getRoomType() == 'privateroom') {
               $disc_manager->setContextID($room_item->getItemId());
               $backup_paths[$room_item->getItemId()] = $disc_manager->getFilePath();
               if (file_exists('../files/templates/individual/styles_'.$room_item->getItemID().'.css')) {
                  $backup_paths_files[] = '../files/templates/individual/styles_'.$room_item->getItemID().'.css';
               }
            }
   
            if ( class_exists('ZipArchive') ) {
               include_once('functions/misc_functions.php');
               $zip = new \ZipArchive();
               $filename_zip = $zipfile;
   
               if ( $zip->open($filename_zip, ZIPARCHIVE::CREATE) !== TRUE ) {
                  include_once('functions/error_functions.php');
                  trigger_error('can not open zip-file '.$filename_zip,E_USER_WARNNG);
               }
               $temp_dir = getcwd();
               foreach ($backup_paths as $item_id => $backup_path) {
                  chdir($backup_path);
                  $zip = addFolderToZip('.',$zip,'files_'.$item_id);
                  chdir($temp_dir);
               }
               foreach ($backup_paths_files as $backup_paths_file) {
                  $backup_paths_file_array = explode('/', $backup_paths_file);
                  $zip->addFile($backup_paths_file, 'styles/'.array_pop($backup_paths_file_array));
               }
   
               $zip->addFile($filename, basename($filename));
               $zip->close();
               unset($zip);
               
               header('Content-disposition: attachment; filename=commsy_export_import_'.$_POST['room'].'.zip');
               header('Content-type: application/zip');
               readfile($zipfile);
               exit;
            } else {
               include_once('functions/error_functions.php');
               trigger_error('can not initiate ZIP class, please contact your system administrator',E_USER_WARNNG);
            }
         }
      }
   } else if (!empty($command) and (isOption($command, $translator->getMessage('PREFERENCES_EXPORT_COMMON_UPLOAD')))) {
      if ( $form->check() ) {
         if ( !empty($_FILES['upload']['tmp_name']) ) {
            $temp_stamp = time();
            //$files = file_get_contents($_FILES['upload']['tmp_name']);
            move_uploaded_file($_FILES['upload']['tmp_name'], '../files/temp/upload_'.$temp_stamp.'.zip');
            $zip = new ZipArchive;
            $res = $zip->open('../files/temp/upload_'.$temp_stamp.'.zip');
            if ($res === TRUE) {
               $zip->extractTo('../files/temp/'.$temp_stamp);
               $zip->close();
               
               $commsy_work_dir = getcwd();
               chdir('../files/temp/'.$temp_stamp);
               foreach (glob("commsy_xml_export_import_*.xml") as $filename) {
                  $xml = simplexml_load_file($filename, null, LIBXML_NOCDATA);
                  $dom = new DOMDocument('1.0');
                  $dom->preserveWhiteSpace = false;
                  $dom->formatOutput = true;
                  $dom->loadXML($xml->asXML());
                  
                  $options = array();
                  chdir($commsy_work_dir);
                  $room_manager = $environment->getRoomManager();
                  $room_item = $room_manager->import_item($xml, null, $options);
                  chdir('../files/temp/'.$temp_stamp);
   
                  $files = scandir('.');
                  foreach($files as $file) {
                     if (strpos($file, 'files') === 0) {
                        $directory_name_array = explode('_', $file);
                        $directory_old_id = $directory_name_array[1];
                        $disc_manager = $environment->getDiscManager();
                        $disc_manager->setPortalID($environment->getCurrentPortalID());
                        if (isset($options[$directory_old_id])) {
                           $directory_new_id = $options[$directory_old_id];
                           if ($directory_new_id != '') {
                              $disc_manager->setContextID($directory_new_id);
                              $new_file_path = $disc_manager->getFilePath();
                              chdir($file);
                              $files_to_copy = glob('./*');
                              foreach($files_to_copy as $file_to_copy){
                                 if (!stristr($file_to_copy, 'default_cs_gradient')) {
                                    $file_to_copy = str_ireplace('./', '', $file_to_copy);
                                    $file_name_array = explode('.', $file_to_copy);
                                    $file_old_id = $file_name_array[0];
                                    if (isset($options[$file_old_id])) {
                                       $file_new_id = $options[$file_old_id];
                                       if ($file_new_id != '') {
                                          $file_to_copy_temp = str_ireplace($file_old_id.'.', $file_new_id.'.', $file_to_copy);
                                          $file_to_copy_temp = './'.$file_to_copy_temp;
                                          $file_to_go = str_replace('./',$commsy_work_dir.'/'.$new_file_path, $file_to_copy_temp);
                                          copy($file_to_copy, $file_to_go);
                                       }
                                    }
                                 }
                                 $logo_matches = array();
                                 preg_match('/(?<=cid)(\d+)(?=_logo)/', $file_to_copy, $logo_matches);
                                 if (!empty($logo_matches)) {
                                     if (isset($options[$logo_matches[0]])) {
                                         $logo_file_to_copy = str_ireplace($logo_matches[0], $options[$logo_matches[0]], $file_to_copy);
                                         $logo_file_to_copy_temp = './'.$logo_file_to_copy;
                                         $logo_file_to_go = str_replace('./',$commsy_work_dir.'/'.$new_file_path, $logo_file_to_copy_temp);
                                         copy($file_to_copy, $logo_file_to_go);
                                     }
                                 }
                              }
                              chdir('..');
                           }
                        }
                     } else if (strpos($file, 'styles') === 0) {
                        chdir($file);
                        $styles_to_copy = glob('./*');
                        foreach($styles_to_copy as $style_to_copy){
                           $style_to_copy = str_ireplace('./', '', $style_to_copy);
                           $style_name_array = explode('.', $style_to_copy);
                           $style_name_array = explode('_', $style_name_array[0]);
                           $style_old_id = $style_name_array[1];
                           if (isset($options[$style_old_id])) {
                              $style_new_id = $options[$style_old_id];
                              if ($style_new_id != '') {
                                 $style_to_copy_temp = str_ireplace($style_old_id.'.', $style_new_id.'.', $style_to_copy);
                                 copy($style_to_copy, $commsy_work_dir.'/../files/templates/individual/'.$style_to_copy_temp);
                              }
                           }
                        }
                        chdir('..');
                     }
                  }
               }
               chdir($commsy_work_dir);
            }
         }
      }
   }

   $form->prepareForm();
   $form->loadValues();

   include_once('functions/curl_functions.php');
   $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
   $form_view->setForm($form);
   if ( $environment->inPortal() or $environment->inServer() ){
      $page->addForm($form_view);
   } else {
      $page->add($form_view);
   }
}
?>