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
if ( !$current_user->isRoot() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText(getMessage('LOGIN_NOT_ALLOWED'));
   $page->add($errorbox);
}

// Access granted
else {

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } elseif (isset($_POST['mail_text']) ) {
      $command = getMessage('COMMON_CHOOSE_BUTTON');
   } else {
      $command = '';
   }

      // Initialize the form
      include_once('classes/cs_configuration_extra_form.php');
      $form = new cs_configuration_extra_form($environment);

      if ( isset($_POST) and !empty($_POST) ) {
         $post_vars = $_POST;
      } else {
         $post_vars = array();
      }

      if ( isOption($command, getMessage('COMMON_CHOOSE_BUTTON'))
           or ( !empty($_POST['extra']) and !isOption($command,getMessage('PREFERENCES_SAVE_BUTTON')) )
         ) {
         $translator = $environment->getTranslationObject();
         $languages = $environment->getAvailableLanguageArray();
         if ($_POST['extra'] == -1) {
            $extra = '';
         } elseif ($_POST['extra'] == 'CONFIGURATION_EXTRA_SPONSORING') {
            $extra = $_POST['extra'];
            $values['description'] = $translator->getMessage('CONFIGURATION_EXTRA_SPONSORING_DESC');
         } elseif ($_POST['extra'] == 'CONFIGURATION_EXTRA_GROUPROOM') {
            $extra = $_POST['extra'];
            $values['description'] = $translator->getMessage('CONFIGURATION_EXTRA_GROUPROOM_DESC');
         } elseif ($_POST['extra'] == 'CONFIGURATION_EXTRA_LOGARCHIVE') {
            $extra = $_POST['extra'];
            $values['description'] = $translator->getMessage('CONFIGURATION_EXTRA_LOGARCHIVE_DESC');
         } elseif ($_POST['extra'] == 'HOMEPAGE_CONFIGURATION_EXTRA_HOMEPAGE') {
            $extra = $_POST['extra'];
            $values['description'] = $translator->getMessage('HOMEPAGE_CONFIGURATION_EXTRA_HOMEPAGE_DESC');
         } elseif ($_POST['extra'] == 'CONFIGURATION_EXTRA_WIKI') {
            $extra = $_POST['extra'];
            $values['description'] = $translator->getMessage('CONFIGURATION_EXTRA_WIKI_DESC');
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
         } else {
            include_once('functions/error_functions.php');trigger_error('choice of extra lost',E_USER_WARNING);
         }
   if ( !empty($values['description']) ) {
      $description_hidden = $values['description'];
   }

         $server_item = $environment->getServerItem();
         $portal_list = $server_item->getPortalList();
         if ( !$portal_list->isEmpty() ) {
            $portal = $portal_list->getFirst();
            while ($portal) {
               if (
                    ( $extra == 'CONFIGURATION_EXTRA_SPONSORING'   and $portal->withAds() ) or
                    ( $extra == 'CONFIGURATION_EXTRA_GROUPROOM'    and $portal->withGrouproomFunctions() ) or
                    ( $extra == 'CONFIGURATION_EXTRA_LOGARCHIVE'   and $portal->withLogArchive() ) or
                    ( $extra == 'CONFIGURATION_EXTRA_PDA'          and $portal->withPDAView() ) or
                    ( $extra == 'HOMEPAGE_CONFIGURATION_EXTRA_HOMEPAGE' and $portal->withHomepageLink() ) or
                    ( $extra == 'CONFIGURATION_EXTRA_WIKI' and $portal->withWikiFunctions() ) or
                    ( $extra == 'CHAT_CONFIGURATION_EXTRA_CHAT'    and $portal->withChatLink() ) or
                    ( $extra == 'CHAT_CONFIGURATION_EXTRA_MATERIALIMPORT'    and $portal->withMaterialImportLink() ) or
                    ( $extra == 'CONFIGURATION_EXTRA_ACTIVATING_CONTENT'    and $portal->withActivatingContent() )
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
                          ( $extra == 'CONFIGURATION_EXTRA_GROUPROOM'    and $room->withGrouproomFunctions() ) or
                          ( $extra == 'CONFIGURATION_EXTRA_LOGARCHIVE'    and $room->withLogArchive() ) or
                          ( $extra == 'CONFIGURATION_EXTRA_PDA'          and $room->withPDAView() ) or
                          ( $extra == 'HOMEPAGE_CONFIGURATION_EXTRA_HOMEPAGE' and $room->withHomepageLink() ) or
                          ( $extra == 'CONFIGURATION_EXTRA_WIKI' and $room->withWikiFunctions() ) or
                          ( $extra == 'CONFIGURATION_EXTRA_MATERIALIMPORT' and $room->withMaterialImportLink() ) or
                          ( $extra == 'CHAT_CONFIGURATION_EXTRA_CHAT'    and $room->withChatLink() ) or
                          ( $extra == 'CONFIGURATION_EXTRA_ACTIVATING_CONTENT'    and $room->withActivatingContent() )
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
               $portal = $portal_list->getNext();
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
      if ( !empty($command) and ( isOption($command, getMessage('PREFERENCES_SAVE_BUTTON')) or
                                  isOption($command, getMessage('COMMON_CHOOSE_BUTTON')) )
         ) {

         $correct = $form->check();
         if ( $correct and isOption($command, getMessage('PREFERENCES_SAVE_BUTTON')) ) {

            if (
                 $_POST['extra'] == 'CONFIGURATION_EXTRA_SPONSORING' or
                 $_POST['extra'] == 'CONFIGURATION_EXTRA_GROUPROOM' or
                 $_POST['extra'] == 'CONFIGURATION_EXTRA_LOGARCHIVE' or
                 $_POST['extra'] == 'CONFIGURATION_EXTRA_PDA' or
                 $_POST['extra'] == 'HOMEPAGE_CONFIGURATION_EXTRA_HOMEPAGE' or
                 $_POST['extra'] == 'CONFIGURATION_EXTRA_WIKI' or
                 $_POST['extra'] == 'CONFIGURATION_EXTRA_MATERIALIMPORT' or
                 $_POST['extra'] == 'CHAT_CONFIGURATION_EXTRA_CHAT' or
                 $_POST['extra'] == 'CONFIGURATION_EXTRA_ACTIVATING_CONTENT'
               ) {
               $extra = $_POST['extra'];
            } else {
               include_once('functions/error_functions.php');
               trigger_error('choice of extra lost',E_USER_WARNING);
            }

            // save extra configuration
            $server_item = $environment->getServerItem();
            $portal_list = $server_item->getPortalList();
            if ( !$portal_list->isEmpty() ) {
               $portal = $portal_list->getFirst();
               while ($portal) {
                  if ( !empty($_POST['ROOM_'.$portal->getItemID()]) ) {
                     if ( $extra == 'CONFIGURATION_EXTRA_SPONSORING' ) {
                        $portal->setWithAds();
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_GROUPROOM' ) {
                        $portal->setWithGrouproomFunctions();
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_LOGARCHIVE' ) {
                        $portal->setWithLogArchive();
                     } elseif ( $extra == 'HOMEPAGE_CONFIGURATION_EXTRA_HOMEPAGE' ) {
                        $portal->setWithHomepageLink();
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_WIKI' ) {
                        $portal->setWithWikiFunctions();
                     } elseif ( $extra == 'CHAT_CONFIGURATION_EXTRA_CHAT' ) {
                        $portal->setWithChatLink();
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_PDA' ) {
                        $portal->setWithPDAView();
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_MATERIALIMPORT' ) {
                        $portal->setWithMaterialImport();
                     }elseif ( $extra == 'CONFIGURATION_EXTRA_ACTIVATING_CONTENT' ) {
                        $portal->setWithActivatingContent();
                     }
                  } else {
                     if ( $extra == 'CONFIGURATION_EXTRA_SPONSORING' ) {
                        $portal->setWithoutAds();
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_GROUPROOM' ) {
                        $portal->setWithoutGrouproomFunctions();
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_LOGARCHIVE' ) {
                        $portal->setWithoutLogArchive();
                     } elseif ( $extra == 'HOMEPAGE_CONFIGURATION_EXTRA_HOMEPAGE' ) {
                        $portal->setWithoutHomepageLink();
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_WIKI' ) {
                        $portal->setWithoutWikiFunctions();
                     } elseif ( $extra == 'CHAT_CONFIGURATION_EXTRA_CHAT' ) {
                        $portal->setWithoutChatLink();
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_PDA' ) {
                        $portal->setWithoutPDAView();
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_MATERIALIMPORT' ) {
                        $portal->setWithoutMaterialImport();
                     } elseif ( $extra == 'CONFIGURATION_EXTRA_ACTIVATING_CONTENT' ) {
                        $portal->setWithoutActivatingContent();
                     }
                  }
                  $portal->save();
                  $room_list = $portal->getRoomList();


                  if ( !$portal_list->isEmpty() ) {
                     $room = $room_list->getFirst();
                     while ($room) {
                        $save_flag = false;
                        if ( !empty($_POST['ROOM_'.$room->getItemID()]) ) {
                           if ( $extra == 'CONFIGURATION_EXTRA_SPONSORING' and !$room->WithAds()) {
                              $room->setWithAds();
                              $save_flag = true;
                           } elseif ( $extra == 'CONFIGURATION_EXTRA_GROUPROOM' and !$room->WithGrouproomFunctions()) {
                              $room->setWithGrouproomFunctions();
                              $save_flag = true;
                           } elseif ( $extra == 'CONFIGURATION_EXTRA_LOGARCHIVE' and !$room->withLogArchive()) {
                              $room->setWithLogArchive();
                              $save_flag = true;
                           } elseif ( $extra == 'HOMEPAGE_CONFIGURATION_EXTRA_HOMEPAGE' and !$room->WithHomepageLink() ) {
                              $room->setWithHomepageLink();
                              $save_flag = true;
                           } elseif ( $extra == 'CONFIGURATION_EXTRA_WIKI' and !$room->WithWikiFunctions() ) {
                              $room->setWithWikiFunctions();
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
                           }
                        } else {
                           if ( $extra == 'CONFIGURATION_EXTRA_SPONSORING' and $room->WithAds() ) {
                              $room->setWithoutAds();
                              $save_flag = true;
                           } elseif ( $extra == 'CONFIGURATION_EXTRA_GROUPROOM' and $room->WithGrouproomFunctions() ) {
                              $room->setWithoutGrouproomFunctions();
                              $save_flag = true;
                           } elseif ( $extra == 'CONFIGURATION_EXTRA_LOGARCHIVE' and $room->withLogArchive()) {
                              $room->setWithoutLogArchive();
                              $save_flag = true;
                           } elseif ( $extra == 'HOMEPAGE_CONFIGURATION_EXTRA_HOMEPAGE' and $room->WithHomepageLink() ) {
                              $room->setWithoutHomepageLink();
                              $save_flag = true;
                           } elseif ( $extra == 'CONFIGURATION_EXTRA_WIKI' and $room->WithWikiFunctions() ) {
                              $room->setWithoutWikiFunctions();
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
                           }
                        }
                        if ( $save_flag ) {
                           $room->save();
                        }
                        $room = $room_list->getNext();
                     }
                  }
                  $portal = $portal_list->getNext();
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