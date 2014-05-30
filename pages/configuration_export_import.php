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
   if ( !empty($command)
        and (isOption($command, $translator->getMessage('PREFERENCES_EXPORT_IMPORT_EXPORT_BUTTON')))
      ) {
      if ( $form->check() ) {
         $room_manager = $environment->getRoomManager();
         $xml = $room_manager->export_item($_POST['room']);
         $dom = new DOMDocument('1.0');
         $dom->preserveWhiteSpace = false;
         $dom->formatOutput = true;
         $dom->loadXML($xml->asXML());

         $filename = 'var/temp/commsy_xml_export_import_'.$_POST['room'].'.xml';
         if ( file_exists($filename) ) {
            unlink($filename);
         }

         $xmlfile = fopen($filename, 'a');   
         fputs($xmlfile, $dom->saveXML());
         fclose($xmlfile);

         //Location where export is saved
         $zipfile = 'var/temp/commsy_export_import_'.$_POST['room'].'.zip';
         if ( file_exists($zipfile) ) {
            unlink($zipfile);
         }

         //Location, that will be backuped
         $disc_manager = $environment->getDiscManager();
         $disc_manager->setPortalID($environment->getCurrentPortalID());
         $disc_manager->setContextID($_POST['room']);
         $backuppath = $disc_manager->getFilePath();
         $disc_manager->setContextID($environment->getCurrentContextID());
         unset($disc_manager);

         if ( class_exists('ZipArchive') ) {
            include_once('functions/misc_functions.php');
            $zip = new ZipArchive();
            $filename_zip = $zipfile;

            if ( $zip->open($filename_zip, ZIPARCHIVE::CREATE) !== TRUE ) {
               include_once('functions/error_functions.php');
               trigger_error('can not open zip-file '.$filename_zip,E_USER_WARNNG);
            }
            $temp_dir = getcwd();
            chdir($backuppath);

            $zip = addFolderToZip('.',$zip,'files');
            chdir($temp_dir);

            $zip->addFile($filename, basename($filename));
            $zip->close();
            unset($zip);
            
            header('Content-disposition: attachment; filename=commsy_export_import_'.$_POST['room'].'.zip');
            header('Content-type: application/zip');
            readfile($zipfile);
         } else {
            include_once('functions/error_functions.php');
            trigger_error('can not initiate ZIP class, please contact your system administrator',E_USER_WARNNG);
         }
       
       
            /*$params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = true;
            $link = $class_factory->getClass(TEXT_VIEW,$params);
            unset($params);
            $link->setText('<a href="../'.$zipfile.'">Download</a> ('.getFilesize($zipfile).')');
            $page->addForm($link);*/
         
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