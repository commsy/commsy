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

// Get the translator object
$translator = $environment->getTranslationObject();

// Check access rights
if ( !$current_user->isRoot() ) {
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
   } elseif (isset($_POST['mail_text']) ) {
      $command = $translator->getMessage('COMMON_CHOOSE_BUTTON');
   } else {
      $command = '';
   }

   // Initialize the form
   $form = $class_factory->getClass(CONFIGURATION_EXTRA_FORM,array('environment' => $environment));

   if ( isset($_POST) and !empty($_POST) ) {
      $post_vars = $_POST;
   } else {
      $post_vars = array();
   }

   if ( isOption($command, $translator->getMessage('COMMON_CHOOSE_BUTTON'))
        or ( !empty($_POST['extra']) and !isOption($command,$translator->getMessage('PREFERENCES_SAVE_BUTTON')) )
      ) {
      $translator = $environment->getTranslationObject();
      $languages = $environment->getAvailableLanguageArray();
      if ($_POST['extra'] == -1) {
         $extra = '';
      } elseif ($_POST['extra'] == 'CONFIGURATION_EXTRA_SPONSORING') {
         $extra = $_POST['extra'];
         $values['description'] = $translator->getMessage('CONFIGURATION_EXTRA_SPONSORING_DESC');
      }
      /*
      elseif ($_POST['extra'] == 'CONFIGURATION_EXTRA_GROUPROOM') {
         $extra = $_POST['extra'];
         $values['description'] = $translator->getMessage('CONFIGURATION_EXTRA_GROUPROOM_DESC');
      }
      */
      elseif ($_POST['extra'] == 'CONFIGURATION_EXTRA_LOGARCHIVE') {
         $extra = $_POST['extra'];
         $values['description'] = $translator->getMessage('CONFIGURATION_EXTRA_LOGARCHIVE_DESC');
//       } elseif ($_POST['extra'] == 'CONFIGURATION_EXTRA_LOG_IP') {
//          $extra = $_POST['extra'];
//          $values['description'] = $translator->getMessage('CONFIGURATION_EXTRA_LOG_IP_DESC');
      } elseif ($_POST['extra'] == 'CONFIGURATION_EXTRA_WIKI') {
         $extra = $_POST['extra'];
         $values['description'] = $translator->getMessage('CONFIGURATION_EXTRA_WIKI_DESC');
      } elseif ($_POST['extra'] == 'CONFIGURATION_EXTRA_WORDPRESS') {
         $extra = $_POST['extra'];
         $values['description'] = $translator->getMessage('CONFIGURATION_EXTRA_WORDPRESS_DESC');
      } elseif ($_POST['extra'] == 'CHAT_CONFIGURATION_EXTRA_CHAT') {
         $extra = $_POST['extra'];
         $values['description'] = $translator->getMessage('CHAT_CONFIGURATION_EXTRA_CHAT_DESC');
      } elseif ($_POST['extra'] == 'CONFIGURATION_EXTRA_PDA') {
         $extra = $_POST['extra'];
         $values['description'] = $translator->getMessage('CONFIGURATION_EXTRA_PDA_DESC');
      } elseif ($_POST['extra'] == 'CONFIGURATION_EXTRA_MATERIALIMPORT') {
         $extra = $_POST['extra'];
         $values['description'] = $translator->getMessage('CONFIGURATION_EXTRA_MATERIALIMPORT_DESC');
      } elseif ($_POST['extra'] == 'CONFIGURATION_EXTRA_ACTIVATING_CONTENT') {
         $extra = $_POST['extra'];
         $values['description'] = $translator->getMessage('CONFIGURATION_EXTRA_ACTIVATING_CONTENT_DESC');
      } elseif ($_POST['extra'] == 'CONFIGURATION_EXTRA_WORKFLOW') {
         $extra = $_POST['extra'];
         $values['description'] = $translator->getMessage('CONFIGURATION_EXTRA_WORKFLOW_DESC');
      } else {
         include_once('functions/error_functions.php');trigger_error('choice of extra lost',E_USER_WARNING);
      }
      if ( !empty($values['description']) ) {
         $description_hidden = $values['description'];
      }

      if ( !empty($_POST['portal'])
           and $_POST['portal'] > 99
         ) {
         $portal_manager = $environment->getPortalManager();
         $portal = $portal_manager->getItem($_POST['portal']);
         unset($portal_manager);
         if (
              ( $extra == 'CONFIGURATION_EXTRA_SPONSORING'   and $portal->withAds() ) or
              #( $extra == 'CONFIGURATION_EXTRA_GROUPROOM'    and $portal->withGrouproomFunctions() ) or
//          	  ( $extra == 'CONFIGURATION_EXTRA_LOG_IP'   and $portal->withLogIPCover() ) or
              ( $extra == 'CONFIGURATION_EXTRA_LOGARCHIVE'   and $portal->withLogArchive() ) or
              ( $extra == 'CONFIGURATION_EXTRA_PDA'          and $portal->withPDAView() ) or
              ( $extra == 'CONFIGURATION_EXTRA_WIKI' and $portal->withWikiFunctions() ) or
              ( $extra == 'CONFIGURATION_EXTRA_WORDPRESS' and $portal->withWordpressFunctions() ) or
              ( $extra == 'CHAT_CONFIGURATION_EXTRA_CHAT'    and $portal->withChatLink() ) or
              ( $extra == 'CHAT_CONFIGURATION_EXTRA_MATERIALIMPORT'    and $portal->withMaterialImportLink() ) or
              ( $extra == 'CONFIGURATION_EXTRA_ACTIVATING_CONTENT'    and $portal->withActivatingContent() ) or
              ( $extra == 'CONFIGURATION_EXTRA_WORKFLOW' and $portal->withWorkflowFunctions() )
            ) {
            $values['ROOM_'.$portal->getItemID()] = $portal->getItemID();
            unset($post_vars['ROOM_'.$portal->getItemID()]);
         } else {
            $values['ROOM_'.$portal->getItemID()] = '';
            unset($post_vars['ROOM_'.$portal->getItemID()]);
         }
         $room_list = $portal->getRoomList();
         
         if ( !$room_list->isEmpty() ) {
            $room = $room_list->getFirst();
            while ($room) {
               if (
                   ( $extra == 'CONFIGURATION_EXTRA_SPONSORING'   and $room->withAds() ) or
                    #( $extra == 'CONFIGURATION_EXTRA_GROUPROOM'    and $room->withGrouproomFunctions() ) or
//                		( $extra == 'CONFIGURATION_EXTRA_LOG_IP'   and $room->withLogIPCover() ) or
                    ( $extra == 'CONFIGURATION_EXTRA_LOGARCHIVE'    and $room->withLogArchive() ) or
                    ( $extra == 'CONFIGURATION_EXTRA_PDA'          and $room->withPDAView() ) or
                    ( $extra == 'CONFIGURATION_EXTRA_WIKI' and $room->withWikiFunctions() ) or
                    ( $extra == 'CONFIGURATION_EXTRA_WORDPRESS' and $room->withWordpressFunctions() ) or
                    ( $extra == 'CONFIGURATION_EXTRA_MATERIALIMPORT' and $room->withMaterialImportLink() ) or
                    ( $extra == 'CHAT_CONFIGURATION_EXTRA_CHAT'    and $room->withChatLink() ) or
                    ( $extra == 'CONFIGURATION_EXTRA_ACTIVATING_CONTENT'    and $room->withActivatingContent() ) or
                    ( $extra == 'CONFIGURATION_EXTRA_WORKFLOW' and $room->withWorkflowFunctions() )
               ) {
                  $values['ROOM_'.$room->getItemID()] = $room->getItemID();
                  unset($post_vars['ROOM_'.$room->getItemID()]);
               } else {
                  $values['ROOM_'.$room->getItemID()] = '';
                  unset($post_vars['ROOM_'.$room->getItemID()]);
               }
               $room = $room_list->getNext();
            }
         }
      }
   }

   // Load form data from postvars
   if ( !empty($post_vars) and !empty($values)) {
      $values = array_merge($values,$post_vars);
      if ( !empty($description_hidden) ) {
         $values['description_hidden'] = $description_hidden;
      }
      $form->setFormPost($values);
   } elseif ( !empty($post_vars) ) {
      $form->setFormPost($post_vars);
   }
   $form->prepareForm();
   $form->loadValues();

   // Save item
   if ( !empty($command) and ( isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) or
                               isOption($command, $translator->getMessage('COMMON_CHOOSE_BUTTON')) )
      ) {

      $correct = $form->check();
      if ( $correct and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {

         if (
              $_POST['extra'] == 'CONFIGURATION_EXTRA_SPONSORING' or
              #$_POST['extra'] == 'CONFIGURATION_EXTRA_GROUPROOM' or
//         	  $_POST['extra'] == 'CONFIGURATION_EXTRA_LOG_IP' or
              $_POST['extra'] == 'CONFIGURATION_EXTRA_LOGARCHIVE' or
              $_POST['extra'] == 'CONFIGURATION_EXTRA_PDA' or
              $_POST['extra'] == 'CONFIGURATION_EXTRA_WIKI' or
              $_POST['extra'] == 'CONFIGURATION_EXTRA_WORDPRESS' or
              $_POST['extra'] == 'CONFIGURATION_EXTRA_MATERIALIMPORT' or
              $_POST['extra'] == 'CHAT_CONFIGURATION_EXTRA_CHAT' or
              $_POST['extra'] == 'CONFIGURATION_EXTRA_ACTIVATING_CONTENT' or
              $_POST['extra'] == 'CONFIGURATION_EXTRA_WORKFLOW'
            ) {
            $extra = $_POST['extra'];
         } else {
            include_once('functions/error_functions.php');
            trigger_error('choice of extra lost',E_USER_WARNING);
         }

         // save extra configuration
         if ( !empty($_POST['portal']) ) {
            $portal_manager = $environment->getPortalManager();
            $portal = $portal_manager->getItem($_POST['portal']);
            unset($portal_manager);
            if ( !empty($_POST['ROOM_'.$portal->getItemID()]) ) {
               if ( $extra == 'CONFIGURATION_EXTRA_SPONSORING' ) {
                  $portal->setWithAds();
               }
               /*
               elseif ( $extra == 'CONFIGURATION_EXTRA_GROUPROOM' ) {
                  $portal->setWithGrouproomFunctions();
               }
               */
               elseif ( $extra == 'CONFIGURATION_EXTRA_LOGARCHIVE' ) {
                  $portal->setWithLogArchive();
//                } elseif ( $extra == 'CONFIGURATION_EXTRA_LOG_IP' ) {
//                   	$portal->setWithLogIPCover();
               } elseif ( $extra == 'CONFIGURATION_EXTRA_WIKI' ) {
                  $portal->setWithWikiFunctions();
               } elseif ( $extra == 'CONFIGURATION_EXTRA_WORDPRESS' ) {
                  $portal->setWithWordpressFunctions();
               } elseif ( $extra == 'CHAT_CONFIGURATION_EXTRA_CHAT' ) {
                  $portal->setWithChatLink();
               } elseif ( $extra == 'CONFIGURATION_EXTRA_PDA' ) {
                  $portal->setWithPDAView();
               } elseif ( $extra == 'CONFIGURATION_EXTRA_MATERIALIMPORT' ) {
                  $portal->setWithMaterialImport();
               } elseif ( $extra == 'CONFIGURATION_EXTRA_ACTIVATING_CONTENT' ) {
                  $portal->setWithActivatingContent();
               } elseif ( $extra == 'CONFIGURATION_EXTRA_WORKFLOW' ) {
                  $portal->setWithWorkflowFunctions();
               }
            } else {
               if ( $extra == 'CONFIGURATION_EXTRA_SPONSORING' ) {
                  $portal->setWithoutAds();
               }
               /*
               elseif ( $extra == 'CONFIGURATION_EXTRA_GROUPROOM' ) {
                  $portal->setWithoutGrouproomFunctions();
               }
               */
               elseif ( $extra == 'CONFIGURATION_EXTRA_LOGARCHIVE' ) {
                  $portal->setWithoutLogArchive();
//                } elseif ( $extra == 'CONFIGURATION_EXTRA_LOG_IP' ) {
//                   $portal->setWithoutLogIPCover();
               } elseif ( $extra == 'CONFIGURATION_EXTRA_WIKI' ) {
                  $portal->setWithoutWikiFunctions();
               } elseif ( $extra == 'CONFIGURATION_EXTRA_WORDPRESS' ) {
                  $portal->setWithoutWordpressFunctions();
               } elseif ( $extra == 'CHAT_CONFIGURATION_EXTRA_CHAT' ) {
                  $portal->setWithoutChatLink();
               } elseif ( $extra == 'CONFIGURATION_EXTRA_PDA' ) {
                  $portal->setWithoutPDAView();
               } elseif ( $extra == 'CONFIGURATION_EXTRA_MATERIALIMPORT' ) {
                  $portal->setWithoutMaterialImport();
               } elseif ( $extra == 'CONFIGURATION_EXTRA_ACTIVATING_CONTENT' ) {
                  $portal->setWithoutActivatingContent();
               } elseif ( $extra == 'CONFIGURATION_EXTRA_WORKFLOW' ) {
                  $portal->setWithoutWorkflowFunctions();
               }
            }
            $portal->save();
            $room_list = $portal->getRoomList();


            if ( !$room_list->isEmpty() ) {
               $room = $room_list->getFirst();
               while ($room) {
                  $save_flag = false;
                  if ( !empty($_POST['ROOM_'.$room->getItemID()]) ) {
                     if ( $extra == 'CONFIGURATION_EXTRA_SPONSORING' and !$room->WithAds()) {
                        $room->setWithAds();
                        $save_flag = true;
                     }
                     /*
                     elseif ( $extra == 'CONFIGURATION_EXTRA_GROUPROOM' and !$room->WithGrouproomFunctions()) {
                        $room->setWithGrouproomFunctions();
                        $save_flag = true;
                     }
                     */
                     elseif ( $extra == 'CONFIGURATION_EXTRA_LOGARCHIVE' and !$room->withLogArchive()) {
                        $room->setWithLogArchive();
                        $save_flag = true;
//                      } elseif ( $extra == 'CONFIGURATION_EXTRA_LOG_IP' and !$room->withLogIPCover()) {
//                         $room->setWithLogIPCover();
//                         $save_flag = true;
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_WIKI' and !$room->WithWikiFunctions() ) {
                        $room->setWithWikiFunctions();
                        $save_flag = true;
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_WORDPRESS' and !$room->WithWordpressFunctions() ) {
                        $room->setWithWordpressFunctions();
                        $save_flag = true;
                     } elseif ( $extra == 'CHAT_CONFIGURATION_EXTRA_CHAT' and !$room->WithChatLink() ) {
                        $room->setWithChatLink();
                        $save_flag = true;
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_PDA' and !$room->WithPDAView() ) {
                        $room->setWithPDAView();
                        $save_flag = true;
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_MATERIALIMPORT' and !$room->withMaterialImportLink() ) {
                        $room->setWithMaterialImport();
                        $save_flag = true;
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_ACTIVATING_CONTENT' and !$room->WithActivatingContent() ) {
                        $room->setWithActivatingContent();
                        $save_flag = true;
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_WORKFLOW' and !$room->WithWorkflowFunctions() ) {
                        $room->setWithWorkflowFunctions();
                        $save_flag = true;
                     }
                  } else {
                     if ( $extra == 'CONFIGURATION_EXTRA_SPONSORING' and $room->WithAds() ) {
                        $room->setWithoutAds();
                        $save_flag = true;
                     }
                     /*
                     elseif ( $extra == 'CONFIGURATION_EXTRA_GROUPROOM' and $room->WithGrouproomFunctions() ) {
                        $room->setWithoutGrouproomFunctions();
                        $save_flag = true;
                     }
                     */
                     elseif ( $extra == 'CONFIGURATION_EXTRA_LOGARCHIVE' and $room->withLogArchive()) {
                        $room->setWithoutLogArchive();
                        $save_flag = true;
//                      } elseif ( $extra == 'CONFIGURATION_EXTRA_LOG_IP' and $room->withLogIPCover()) {
//                         $room->setWithoutLogIPCover();
//                         $save_flag = true;
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_WIKI' and $room->WithWikiFunctions() ) {
                        $room->setWithoutWikiFunctions();
                        $save_flag = true;
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_WORDPRESS' and $room->WithWordpressFunctions() ) {
                        $room->setWithoutWordpressFunctions();
                        $save_flag = true;
                     } elseif ( $extra == 'CHAT_CONFIGURATION_EXTRA_CHAT' and $room->WithChatLink() ) {
                        $room->setWithoutChatLink();
                        $save_flag = true;
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_PDA' and $room->WithPDAView() ) {
                        $room->setWithoutPDAView();
                        $save_flag = true;
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_MATERIALIMPORT' and $room->withMaterialImportLink() ) {
                        $save_flag = true;
                        $room->setWithoutMaterialImport();
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_ACTIVATING_CONTENT' and $room->WithActivatingContent() ) {
                        $room->setWithoutActivatingContent();
                        $save_flag = true;
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_WORKFLOW' and $room->WithWorkflowFunctions() ) {
                        $room->setWithoutWorkflowFunctions();
                        $save_flag = true;
                     }
                  }
                  if ( $save_flag ) {
                     $room->save();
                  }
                  $room = $room_list->getNext();
               }
            }
         }

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