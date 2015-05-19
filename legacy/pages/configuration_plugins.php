<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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

// get room item and current user
set_time_limit(0);

$current_user = $environment->getCurrentUserItem();
$is_saved = false;

// Check access rights
if ( !$current_user->isRoot()
     and !$current_user->isModerator()
   ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
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
   $form = $class_factory->getClass(CONFIGURATION_PLUGINS_FORM,array('environment' => $environment));

   if ( isset($_POST) and !empty($_POST) ) {
      $post_vars = $_POST;
   } else {
      $post_vars = array();
   }

   // Load form data from postvars
   if ( !empty($post_vars) ) {
      $form->setFormPost($post_vars);
   }
   $form->prepareForm();
   $form->loadValues();

   // Save item
   if ( !empty($command)
        and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON'))
      ) {
      $correct = $form->check();
      if ( $correct ) {
         $current_context_item = $environment->getCurrentContextItem();

         global $symfonyContainer;
         $c_wordpress = $symfonyContainer->getParameter('commsy.wordpress.enabled');
         
         if ( isset($c_wordpress) and  $c_wordpress ) {
         	if($environment->inPortal()) {
         		$wp_url = $_POST['wp_url'];
         		$wp_activate = $_POST['wp'];
         		$current_context_item->setWordpressUrl($wp_url);
         		if($wp_activate != '-1'){
         			$current_context_item->setWordpressPortalActive(true);
         		} else {
         			$current_context_item->setWordpressPortalActive(false);
         		}
         		
         		#pr($_POST);
         	}
         }
         
         global $c_plugin_array;
         if (isset($c_plugin_array) and !empty($c_plugin_array)) {
            foreach ($c_plugin_array as $plugin) {
               $plugin_class = $environment->getPluginClass($plugin);
               if ( method_exists($plugin_class,'isConfigurableInPortal') ) {
                  if ( ( $environment->inServer()
                         and $plugin_class->isConfigurableInServer()
                       )
                       or
                  	  ( $environment->inPortal()
                         and $plugin_class->isConfigurableInPortal()
                       )
                       or
                       ( !$environment->inServer()
                         and $plugin_class->isConfigurableInRoom($current_context_item->getItemType())
                       )
                     ) {
                     if ( !empty($_POST[$plugin])
                          and $_POST[$plugin] == 1
                        ) {
                        $current_context_item->setPluginOn($plugin);
                     } else {
                        $current_context_item->setPluginOff($plugin);
                     }
                     $values = $_POST;
                     $values['current_context_item'] = $current_context_item;
                     if ( $environment->inServer()
                         and method_exists($plugin_class,'configurationAtServer')
                        ) {
                        $plugin_class->configurationAtServer('save_config',$values);
                     } elseif ( $environment->inPortal()
                         and method_exists($plugin_class,'configurationAtPortal')
                        ) {
                        $plugin_class->configurationAtPortal('save_config',$values);
                     } elseif ( !$environment->inServer()
                                and method_exists($plugin_class,'configurationAtRoom')
                               ) {
                        $plugin_class->configurationAtRoom('save_config',$values);
                     }
                  }
               }
            }
         }
         $current_context_item->save();
         $is_saved = true;
      }
   }

   // Display form
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);
   $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
   $form_view->setForm($form);
   if ($is_saved) {
      $form_view->setItemIsSaved();
   }
   if ( $environment->inPortal() or $environment->inServer() ) {
      $page->addForm($form_view);
   } else {
      $page->add($form_view);
   }
}
?>