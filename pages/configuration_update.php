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

# more time for processing
set_time_limit(0);

// Get the current user
$current_user = $environment->getCurrentUserItem();
$translator = $environment->getTranslationObject();
$current_context = $environment->getServerItem();

if (!$current_user->isRoot() and !$current_context->mayEdit($current_user)) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->addWarning($errorbox);
} else {
   //access granted

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }

   // Cancel editing
   if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      redirect($environment->getCurrentContextID(),'configuration','index',array());
   }

   // Show form and/or save item
   else {

      // Initialize the form
      $form = $class_factory->getClass(CONFIGURATION_UPDATE_FORM,array('environment' => $environment));
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
      unset($params);

      // Load form data from postvars
      if ( !empty($_POST) ) {
         $values = $_POST;
         $form->setFormPost($values);
      } else {
         $form->setItem($current_context);
      }

      $form->prepareForm();
      $form->loadValues();

      // run scripts
      if ( !empty($command)
           and ( isOption($command, $translator->getMessage('CONFIGURATION_UPDATE_BUTTON')) )
         ) {
         if ( $form->check() ) {
            $run_array = array();
            $script_array = $form->getScriptArray();
            foreach ( $script_array as $folder => $scripts ) {
               if ( !empty($_POST[str_replace('.','_',$folder)]) ) {
                  $run_array[$folder] = $script_array[$folder];
               } else {
                  foreach ( $scripts as $script ) {
                     if ( !empty($_POST[str_replace('.','_',$folder).'/'.str_replace('.','_',$script)]) ) {
                        $run_array[$folder][] = $script;
                     }
                  }
               }
            }

            // transfer script to view object
            if ( !empty($run_array) ) {
               $page->setFlushModeOn();
               foreach ( $run_array as $folder => $scripts ) {
                  foreach ( $scripts as $script ) {
                     $update_view = $class_factory->getClass(UPDATE_VIEW,array('environment' => $environment));
                     $update_view->setScript($script);
                     $update_view->setFolder($folder);
                     $update_view->setPath($form->getRootScriptPath());
                     $page->add($update_view);
                  }
               }
               $current_version_array = explode('_to_',$folder);
               if ( !empty($current_version_array[1])) {
                  $current_version = $current_version_array[1];
                  $current_code_version = getCommSyVersion();
                  if ( !strstr($current_code_version,'beta')
                       and !strstr($current_code_version,' ')
                       and substr_count($current_code_version,'.') == 2
                     ) {
                     $current_context->setDBVersion($current_version);
                     $current_context->save();
                  }
                  unset($form);
                  $form = $class_factory->getClass(CONFIGURATION_UPDATE_FORM,array('environment' => $environment));
                  $form->setItem($current_context);
                  $form->prepareForm();
                  $form->loadValues();
               }
            }
         }
      }

      // display form
      if (isset($current_context) and !$current_context->mayEditRegular($current_user)) {
         $form_view->warnChanger();
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = true;
         $params['width'] = 500;
         $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
         unset($params);
         $errorbox->setText($translator->getMessage('COMMON_EDIT_AS_MODERATOR'));
         $page->addWarning($errorbox);
      }

      include_once('functions/curl_functions.php');
      $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
      $form_view->setForm($form);
      $page->addForm($form_view);
   }
}
?>