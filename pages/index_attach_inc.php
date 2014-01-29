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

   // I know, this is ugly, but eventually pages will be classes, too,
   // and then we can remove this include file. For now, it is better
   // than copying this code to all index pages, mj.

   // Assumes global $attach_type is set!
   $infix = '_'.$attach_type;
   
   // Get the translator object
   $translator = $environment->getTranslationObject();

   // Setup
   // Get originally attached items and access rights
   if ( !$session->issetValue($ref_iid.$infix.'_new_attach_ids') ) {
      if ( $mode == 'formattach' ) {
         if ( $session->issetValue($ref_iid.$infix.'_attach_ids') ) {
            $new_attach_ids = $session->getValue($ref_iid.$infix.'_attach_ids');
         } else {
            $new_attach_ids = array();
         }
         $dontedit_attach_ids = array();
      } else {

         // Get referer item and current user
         $item_manager = $environment->getItemManager();
         $ref_item_type = $item_manager->getItemType($ref_iid);
         $ref_item_manager = $environment->getManager($ref_item_type);
         $ref_item = $ref_item_manager->getItem($ref_iid);
         $ref_item_type = $ref_item->getItemType();   // Get precise item type (for labels)
         $user = $environment->getCurrentUser();

         $new_attach_ids = array();
         $dontedit_attach_ids = array();
         $link_list = $ref_item->getLinkItemList($attach_type);
         $link_item = $link_list->getFirst();
         while ( $link_item ) {
            $linked_item = $link_item->getLinkedItem($ref_item);
            $new_attach_ids[] = $linked_item->getItemID();
            if ( $linked_item->getItemType() == CS_GROUP_TYPE
                 and $linked_item->isSystemLabel()
                 and $ref_item->isA(CS_USER_TYPE)
               ) {
               $dontedit_attach_ids[] = $linked_item->getItemID();
            }
            $link_item = $link_list->getNext();
         }
      }
      // Save data in session
      $session->setValue($ref_iid.$infix.'_dontedit_attach_ids', $dontedit_attach_ids);
      $session->setValue($ref_iid.$infix.'_old_attach_ids', $new_attach_ids);
      $session->setValue($ref_iid.$infix.'_new_attach_ids', $new_attach_ids);
   }

   // In Progress
   // Load last attached items and access rights from session
   else {
      $new_attach_ids = $session->getValue($ref_iid.$infix.'_new_attach_ids');
      $dontedit_attach_ids = $session->getValue($ref_iid.$infix.'_dontedit_attach_ids');

      // Update attached items from cookie (requires JavaScript in browser)
      if ( isset($_COOKIE['attach']) ) {
         foreach ( $_COOKIE['attach'] as $key => $val ) {
            setcookie ('attach['.$key.']', '', time()-3600);
            if ( $val == '1' ) {
               if ( !in_array($key, $new_attach_ids) ) {
                  $new_attach_ids[] = $key;
               }
            } else {
               $idx = array_search($key, $new_attach_ids);
               if ( $idx !== false ) {
                  unset($new_attach_ids[$idx]);
               }
            }
         }
      }

      // Update attached items from form post (works always)
      if ( isset($_POST['shown']) ) {
         foreach ( $_POST['shown'] as $shown_key => $shown_val ) {
            if ( isset($_POST['attach']) ) {
               if ( array_key_exists($shown_key, $_POST['attach']) ) {
                  if ( !in_array($shown_key, $new_attach_ids) ) {
                     $new_attach_ids[] = $shown_key;
                  }
               } else {
                  $idx = array_search($shown_key, $new_attach_ids);
                  if ( $idx !== false
                       and !in_array($shown_key, $dontedit_attach_ids) ) {
                     unset($new_attach_ids[$idx]);
                  }
               }
            } else {
               $idx = array_search($shown_key, $new_attach_ids);
               if ( $idx !== false and !in_array($shown_key, $dontedit_attach_ids) ) {
                  unset($new_attach_ids[$idx]);
               }
            }
         }
      }

      // Save changes to session
      $session->setValue($ref_iid.$infix.'_new_attach_ids', $new_attach_ids);

      // Form post: save or cancel form
      if ( isset($_POST['option']) ) {

         // Save if attach button was pressed and mode is detailattach
         if ( isOption($_POST['option'], $translator->getMessage('COMMON_ATTACH_BUTTON')) ) {
            if ( $mode == 'formattach' ) {
               $session->setValue($ref_iid.$infix.'_attach_ids', $new_attach_ids);
            } else {
               // Get referer item
               $item_manager = $environment->getItemManager();
               $ref_item_type = $item_manager->getItemType($ref_iid);
               $ref_item_manager = $environment->getManager($ref_item_type);
               $ref_item = $ref_item_manager->getItem($ref_iid);
               $ref_item_type = $ref_item->getItemType();   // Get precise item type (for labels)
               // Set modificator and modification date
               $user = $environment->getCurrentUserItem();
               $ref_item->setModificatorItem($user);
               $ref_item->setModificationDate(getCurrentDateTimeInMySQL());
               // Set new item list
               $ref_item->setLinkedItemsByID($attach_type, $new_attach_ids);
               $ref_item->save();
            }
         }

         // Clean up session
         $session->unsetValue($ref_iid.$infix.'_dontedit_attach_ids');
         $session->unsetValue($ref_iid.$infix.'_old_attach_ids');
         $session->unsetValue($ref_iid.$infix.'_new_attach_ids');

         // Return to referer
         if ( $session->issetValue($ref_iid.$infix.'_back_module') ) {
            $back_module = $session->getValue($ref_iid.$infix.'_back_module');
         } else {
            if ( empty($ref_item_type) ) {
               if ( $ref_iid == 'NEW' ) {
                  $history_array = $session->getValue('history');
                  foreach ( $history_array as $history_step ) {
                     if ( $history_step['function'] == 'edit' ) {
                        $ref_item_type = $history_step['module'];
                        break;
                     }
                  }
               } else {
                  $item_manager = $environment->getItemManager();
                  $ref_item_type = $item_manager->getItemType($ref_iid);
                  $ref_item_manager = $environment->getManager($ref_item_type);
                  $ref_item = $ref_item_manager->getItem($ref_iid);
                  $ref_item_type = $ref_item->getItemType();   // Get precise item type (for labels)
               }
            }
            if ( $environment->inPrivateRoom()
                 and ( $ref_item_type == CS_PROJECT_TYPE
                       or $ref_item_type == CS_COMMUNITY_TYPE
                     )
               ) {
               $back_module = CS_MYROOM_TYPE;
            } else {
               $back_module = type2Module($ref_item_type);
            }
         }
         if ( $session->issetValue($ref_iid.$infix.'_back_function') ) {
            $back_function = $session->getValue($ref_iid.$infix.'_back_function');
         } else {
            if ( $mode == 'formattach' ) {
               $back_function = 'edit';
            } else {
               $back_function = 'detail';
            }
         }
         if ( $session->issetValue($ref_iid.$infix.'_back_iid') ) {
            $back_iid = $session->getValue($ref_iid.$infix.'_back_iid');
         } else {
            $back_iid = $ref_iid;
         }
         if ( $session->issetValue($ref_iid.$infix.'_back_tool') ) {
            $back_tool = $session->getValue($ref_iid.$infix.'_back_tool');
         } else {
            $back_tool = '';
         }
         if ( $mode == 'formattach' ) {
            $backfrom = '&backfrom='.$attach_type;
         } else {
            $backfrom = '';
         }
         $params = array();
         $params['iid'] = $back_iid;
         if ( !empty($backfrom) ) {
            $params['backfrom'] = $attach_type;
         }
         unset($ref_iid);
         unset($ref_user);
         $environment->setSessionItem($session);

            redirect($environment->getCurrentContextID(),
                     $back_module,
                     $back_function,
                     $params,
                     $attach_type,
          '',
          $back_tool);
      }
   }
?>