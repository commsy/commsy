<?PHP
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

// Get the translator object
$translator = $environment->getTranslationObject();

class cNode extends SimpleXMLElement {
   function getName() {
      return dom_import_simplexml($this)->nodeName;
   }

   function getType() {
      return dom_import_simplexml($this)->nodeType;
   }
}

function display_xml_error($error, $xml) {
   $retour  = $xml[$error->line - 1].BRLF;
   $retour .= str_repeat('-', $error->column).BRLF;

   switch ($error->level) {
      case LIBXML_ERR_WARNING:
         $retour .= 'Warning '.$error->code.': ';
         break;
      case LIBXML_ERR_ERROR:
         $retour .= 'Error '.$error->code.': ';
         break;
      case LIBXML_ERR_FATAL:
         $retour .= 'Fatal Error '.$error->code.': ';
         break;
   }

   $retour .= trim($error->message).
              BRLF.'Line: '.$error->line.
              BRLF.'Column: '.$error->column;

   if ($error->file) {
      $retour .= BRLF.'File: '.$error->file;
   }

   return $retour.BRLF.BRLF.'--------------------------------------------'.BRLF.BRLF;
}

set_time_limit(0);

// get room item and current user
$room_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();
$is_saved = false;

// Check access rights
if ($current_user->isGuest()) {
   redirect($room_item->getItemID(),'home','index','');
} elseif ( $room_item->isPortal() and !$room_item->isOpen() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $room_item->getTitle()));
   $page->add($errorbox);
} elseif ( ($room_item->isPortal() and !$current_user->isModerator())
           or ($room_item->isServer() and !$current_user->isRoot())
         ) {
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
   $form = $class_factory->getClass(CONFIGURATION_BACKUP_FORM,array('environment' => $environment));
   // Display form
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);

   // Load form data from postvars
   if ( !empty($_POST) ) {
      $form->setFormPost($_POST);
   } else {
      $form->setItem($room_item);
   }
   $form->prepareForm();
   $form->loadValues();

   if ( !empty($command) and ( isOption($command, $translator->getMessage('PREFERENCES_BACKUP_BUTTON')) ) ) {
      if ( $form->check() ) {
         if ( !empty($_FILES['upload']['tmp_name'])
              and $_FILES['upload']['size'] > 0 ) {
            // scanning virus
      if (isset($c_virus_scan) and $c_virus_scan) {
         include_once('classes/cs_virus_scan.php');
         $virus_scanner = new cs_virus_scan($environment);
         if ($virus_scanner->isClean($_FILES['upload']['tmp_name'],$_FILES['upload']['name'])) {
                  // no error
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
      }

            // import
            if ( !isset($error_on_upload) or !$error_on_upload ) {
               $do_it = false;
               $ext = strrchr($_FILES['upload']['name'],'.');
               if ($ext == '.xml') {
                  $xml = file_get_contents($_FILES['upload']['tmp_name']);
                  unlink($_FILES['upload']['tmp_name']);
                  $xml = utf8_encode($xml);
                  $test = mb_substr($xml,0,mb_strpos($xml,'>')+1);
                  if ( $test == '<commsy_export>') {
                     $do_it = true;
                  }
               }
               if ($do_it) {
                  libxml_use_internal_errors(true);
                  $xml_object = simplexml_load_string($xml,'cNode');
                  if (!$xml_object) {
                     $errors = libxml_get_errors();
                     foreach ($errors as $error) {
                        echo(display_xml_error($error,$xml));
                     }
                     libxml_clear_errors();
                     exit();
                  }

                  $commsy_version = utf8_decode((string)$xml_object->version);

                  $current_commsy_version = getCommSyVersion();
                  if ( $current_commsy_version != $commsy_version ) {
                     $params = array();
                     $params['environment'] = $environment;
                     $params['with_modifying_actions'] = true;
                     $params['width'] = 500;
                     $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
                     unset($params);
                     $errorbox->setText($translator->getMessage('PREFERENCES_BACKUP_WARNING_VERSION',$current_commsy_version,$commsy_version));
                     $page->add($errorbox);
                  }
                  foreach ($xml_object->data->children() as $list) {
                     $name = str_replace('_list','',$list->getName());
                     $manager = $environment->getManager(DBTable2Type($name));
                     $success = false;
                     $success = $manager->backupDataFromXMLObject($list);
                     if (!isset($success) or !$success) {
                        $params = array();
                        $params['environment'] = $environment;
                        $params['with_modifying_actions'] = true;
                        $params['width'] = 500;
                        $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
                        unset($params);
                        $errorbox->setText($translator->getMessage('PREFERENCES_BACKUP_ERROR_MODULE',$name));
                        $page->add($errorbox);
                     } else {
                        $form_view->setItemIsSaved();
                        $is_saved = true;
                     }
                  }
               } else {
                  $params = array();
                  $params['environment'] = $environment;
                  $params['with_modifying_actions'] = true;
                  $params['width'] = 500;
                  $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
                  unset($params);
                  $errorbox->setText($translator->getMessage('PREFERENCES_BACKUP_ERROR_FILE'));
                  $page->add($errorbox);
               }
            }
         }
      }
   }

   $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
   $form_view->setForm($form);
   if ( $environment->inPortal() or $environment->inServer() ){
      $page->addForm($form_view);
   } else {
      $page->add($form_view);
   }
}
?>