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

if ( isset($_GET['cid']) ) {
   $path = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
   $path = str_replace('rss.php','',$path);
   chdir('..');
   include_once('etc/cs_constants.php');
   include_once('etc/cs_config.php');
   include_once('classes/cs_environment.php');
   $environment = new cs_environment();
   $environment->setCurrentContextID($_GET['cid']);
   $hash_manager = $environment->getHashManager();
   $context_item = $environment->getCurrentContextItem();
   $validated = false;
   if ( $context_item->isOpenForGuests() ) {
      $validated = true;
   }

   if ( !$context_item->isPortal()
        and !$context_item->isServer()
        and isset($_GET['hid'])
        and !empty($_GET['hid'])
        and !$validated
        and !$context_item->isLocked()
        and $hash_manager->isRSSHashValid($_GET['hid'],$context_item)
      ) {
      $validated = true;
   }

   if ( !empty($_SERVER['PHP_AUTH_USER'])
        and !empty($_SERVER['PHP_AUTH_PW'])
        and !$validated
      ) {
       $user = $_SERVER['PHP_AUTH_USER'];
       $pass = $_SERVER['PHP_AUTH_PW'];

      //AUTHENTICATION
      $authentication = $environment->getAuthenticationObject();
      if ($authentication->isAccountGranted($user, $pass, '')) {
         $authsourceid = $authentication->getGrantedAuthSourceItemID();
         if ( $context_item->isOpenForGuests() ) {
            $validated = true;
         } elseif ( $context_item->mayEnterByUserID($user, $authsourceid) ) {
            $validated = true;
         }
      }
   }
   $translator = $environment->getTranslationObject();
   if (!$validated) {
      $title = $context_item->getTitle();
      if ( $context_item->isPrivateRoom()) {
         $title = '';
         $current_portal_item = $environment->getCurrentPortalItem();
         if ( isset($current_portal_item) ) {
            $title .= $current_portal_item->getTitle();
         }
         $owner_user_item = $context_item->getOwnerUserItem();
         $owner_fullname = $owner_user_item->getFullName();
         if ( !empty($owner_fullname) ) {
            if ( !empty($title) ) {
               $title .= ': ';
            }
            $title .= $owner_fullname;
         }
         unset($owner_user_item);
         unset($current_portal_item);
      }
      header('WWW-Authenticate: Basic realm="'.$translator->getMessage('RSS_TITLE',$title).'"');
      header('HTTP/1.0 401 Unauthorized');
      die ($translator->getMessage('RSS_NOT_ALLOWED'));
   }

   if ( $validated
        and isset($user)
        and !empty($user)
      ) {
      $user_item = $context_item->getUserByUserID($user, $authsourceid);
   } elseif ( $validated
              and isset($_GET['hid'])
              and !empty($_GET['hid'])
            ) {
      $user_item = $context_item->getUserByRSSHash($_GET['hid']);
   }
   $item_manager = $environment->getItemManager();
   $item_manager->setIntervalLimit(1);
   $item_manager->setDeleteLimit(true);
   $mod_date = $item_manager->_performQuery();
   $date = date('r',strtotime($mod_date['0']['modification_date']));
   if ( $context_item->isPrivateRoom() ) {
      $maintitle = '';
      $current_portal_item = $environment->getCurrentPortalItem();
      if ( isset($current_portal_item) ) {
         $maintitle .= $current_portal_item->getTitle();
      }
      $owner_user_item = $context_item->getOwnerUserItem();
      $owner_fullname = $owner_user_item->getFullName();
      if ( !empty($owner_fullname) ) {
         if ( !empty($maintitle) ) {
            $maintitle .= ': ';
         }
         $maintitle .= $owner_fullname;
      }
      unset($owner_user_item);
      unset($current_portal_item);
   } else {
      $maintitle = $context_item->getTitle();
   }
   $language = $environment->getSelectedLanguage();
   $maintitle = str_replace('&','&amp;',$maintitle);

   $rss = '<?xml version="1.0" encoding="utf-8"?>

   <rss version="2.0">

     <channel>
      <title>'.$translator->getMessage('RSS_TITLE',$maintitle).'</title>
      <link>'.$path.$c_single_entry_point.'</link>
      <ttl>60</ttl>
      <description>'.$translator->getMessage('RSS_DESCRIPTION',$maintitle).'</description>
      <language>'.$context_item->getLanguage().'</language>
      <copyright>-</copyright>
      <pubDate>'.$date.'</pubDate>
      <image>
        <url>'.$path.'images/commsy_logo_transparent.gif</url>
        <title>'.$translator->getMessage('RSS_TITLE',$maintitle).'</title>
        <link>'.$path.$c_single_entry_point.'</link>
      </image>';

   $type_limit_array = array();
   if ( $context_item->withRubric(CS_USER_TYPE) ) {
      $type_limit_array[] = CS_USER_TYPE;
   }
   if ( $context_item->withRubric(CS_DISCUSSION_TYPE) ) {
      $type_limit_array[] = CS_DISCUSSION_TYPE;
      $type_limit_array[] = CS_DISCARTICLE_TYPE;
   }
   if ( $context_item->withRubric(CS_MATERIAL_TYPE) ) {
      $type_limit_array[] = CS_MATERIAL_TYPE;
      $type_limit_array[] = CS_SECTION_TYPE;
   }
   if ( $context_item->withRubric(CS_ANNOUNCEMENT_TYPE) ) {
      $type_limit_array[] = CS_ANNOUNCEMENT_TYPE;
   }
   if ( $context_item->withRubric(CS_DATE_TYPE) ) {
      $type_limit_array[] = CS_DATE_TYPE;
   }
   if ( $context_item->withRubric(CS_TODO_TYPE) ) {
      $type_limit_array[] = CS_TODO_TYPE;
      $type_limit_array[] = CS_STEP_TYPE;
   }
   if ( $context_item->withRubric(CS_GROUP_TYPE)
        or $context_item->withRubric(CS_INSTITUTION_TYPE)
        or $context_item->withRubric(CS_TOPIC_TYPE)
      ) {
      $type_limit_array[] = CS_LABEL_TYPE;
   }
   $type_limit_array[] = CS_ANNOTATION_TYPE;
   if ( !$context_item->isPrivateRoom() ) {
      // RSS File Content
      $item_manager->resetLimits();
      $item_manager->setTypeArrayLimit($type_limit_array);
      $item_manager->setIntervalLimit(20);
      $result = $item_manager->_performQuery();
   } else {
      $project_list = $user_item->getRelatedProjectList();
      $room_array = Array();
      $item = $project_list->getFirst();
      while ($item) {
         $room_array[] = $item->getItemID();
         $item = $project_list->getNext();
      }
      unset($item);
      $community = $user_item->getRelatedCommunityList();
      $item = $community->getFirst();
      while ($item) {
         $room_array[] = $item->getItemID();
         $item = $community->getNext();
      }
      unset($item);
      $grouprooms = $user_item->getRelatedGroupList();
      $item = $grouprooms->getFirst();
      while ($item) {
         $room_array[] = $item->getItemID();
         $item = $grouprooms->getNext();
      }
      unset($item);
      $room_array[] = $context_item->getItemID();
      $item_manager->resetLimits();
      $item_manager->setContextArrayLimit($room_array);
      $item_manager->setTypeArrayLimit($type_limit_array);
      $item_manager->setIntervalLimit(20);
      $result = $item_manager->_performQuery();

   }
   $counter = 0;
   foreach($result as $row) {
      if ( $counter == 10 ) {
         break;
      }

      $type = $row['type'];
      $environment->setCurrentContextID($row['context_id']);
      $curr_context = $environment->getCurrentContextItem();
      $environment->setCurrentContextID($_GET['cid']);
      if ( $context_item->isPrivateRoom() ) {
         $cid = $curr_context->getItemID();
      } else {
         $cid = $_GET['cid'];
      }

      $desc_len = 160;
      switch($type)
      {
         case 'user':
            include_once('classes/cs_user_manager.php');
            $manager = new cs_user_manager($environment);
            $item = $manager->getItem($row['item_id']);
            $fullname = $item->getFullName();
            $email = $item->getEmail();
            if ( $context_item->isCommunityRoom() ) {
               if ( empty($_GET['hid']) and !$item->isVisibleForAll() ) {
                  $fullname = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                  $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
               }
            }
            if ( !$item->isEmailVisible() ) {
               $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
            }
            if ( $item->getCreationDate() == $item->getModificationDate() ) {
               $title = $translator->getMessage('RSS_NEW_PERSON_TITLE',$fullname);
               $description = $translator->getMessage('RSS_NEW_PERSON_DESCRIPTION',$fullname);
            } else {
               $title = $translator->getMessage('RSS_NEW_PERSON_TITLE',$fullname);
               $description = $translator->getMessage('RSS_CHANGE_PERSON_DESCRIPTION',$fullname);
            }
            $date = date('r',strtotime($item->getModificationDate()));
            $author = $email.' ('.$fullname.')';
            unset($email);
            unset($fullname);
            $link = $path.$c_single_entry_point.'?cid='.$cid.'&amp;mod=user&amp;fct=detail&amp;iid='.$row['item_id'];
            unset($manager);
            unset($item);
            break;
         case 'annotation':
            include_once('classes/cs_annotations_manager.php');
            $manager = new cs_annotations_manager($environment);
            $item = $manager->getItem($row['item_id']);
            $linked_item = $item->getLinkedItem();
            if ( isset($linked_item) ) {
               $title = $translator->getMessage('RSS_NEW_ANNOTATION_TITLE',$item->getTitle(),$linked_item->getTitle());
               $description = $item->getDescriptionWithoutHTML();
               if (mb_strlen($description) > $desc_len)
               {
                  $description = chunkText($description,$desc_len);
               }
               $user_item = $item->getModificatorItem();
               $fullname = $user_item->getFullName();
               $email = $user_item->getEmail();
               if ( $context_item->isCommunityRoom() ) {
                  if ( empty($_GET['hid']) and !$user_item->isVisibleForAll() ) {
                     $fullname = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                     $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                  }
               }
               if ( !$user_item->isEmailVisible() ) {
                  $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
               }
               $author = $email.' ('.$fullname.')';
               unset($email);
               unset($fullname);
               $link = $path.$c_single_entry_point.'?cid='.$cid.'&amp;mod='.$linked_item->getItemType().'&amp;fct=detail&amp;iid='.$linked_item->getItemID();
               $date = date('r',strtotime($item->getModificationDate()));
            }
            unset($manager);
            unset($item);
            unset($linked_item);
            unset($user_item);
            break;
         case 'discussion':
            include_once('classes/cs_discussion_manager.php');
            $manager = new cs_discussion_manager($environment);
            $item = $manager->getItem($row['item_id']);
            if ( isset($last_discarticle_item)
                 and $item->getModificationDate() == $last_discarticle_item->getModificationDate()
                 and $item->getModificationDate() != $item->getCreationDate()
               ) {
               unset($last_discarticle_item);
               $title = '';
               $description = '';
               $link = '';
               $author = '';
               $date = '';
            } elseif ( $item->getModificationDate() != $item->getCreationDate() ) {
               $title = '';
               $description = '';
               $link = '';
               $author = '';
               $date = '';
            } else {
               $title = $translator->getMessage('RSS_NEW_DISCUSSION_TITLE',$item->getTitle());
               $description = $translator->getMessage('RSS_NEW_DISCUSSION_DESCRIPTION',$item->getTitle());
               $user_item = $item->getModificatorItem();
               $fullname = $user_item->getFullName();
               $email = $user_item->getEmail();
               if ( $context_item->isCommunityRoom() ) {
                  if ( empty($_GET['hid']) and !$user_item->isVisibleForAll() ) {
                     $fullname = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                     $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                  }
               }
               if ( !$user_item->isEmailVisible() ) {
                  $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
               }
               $author = $email.' ('.$fullname.')';
               unset($email);
               unset($fullname);
               $link = $path.$c_single_entry_point.'?cid='.$cid.'&amp;mod=discussion&amp;fct=detail&amp;iid='.$row['item_id'];
               $date = date('r',strtotime($item->getModificationDate()));
            }
            unset($manager);
            unset($item);
            unset($user_item);
            break;
         case 'discarticle':
            include_once('classes/cs_discussionarticles_manager.php');
            $manager = new cs_discussionarticles_manager($environment);
            $item = $manager->getItem($row['item_id']);
            $linked_item = $item->getLinkedItem();
            if ( !empty($linked_item) ) {
               $title = $translator->getMessage('RSS_NEW_DISCUSSIONARTICLE_TITLE',$item->getTitle(),$linked_item->getTitle());
               $description = $item->getDescriptionWithoutHTML();
               if ( mb_strlen($description) > $desc_len ) {
                  $description = chunkText($item->getDescription(),$desc_len);
               }
               $user_item = $item->getModificatorItem();
               $fullname = $user_item->getFullName();
               $email = $user_item->getEmail();
               if ( $context_item->isCommunityRoom() ) {
                  if ( empty($_GET['hid']) and !$user_item->isVisibleForAll() ) {
                     $fullname = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                     $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                  }
               }
               if ( !$user_item->isEmailVisible() ) {
                  $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
               }
               $author = $email.' ('.$fullname.')';
               unset($email);
               unset($fullname);
               $link = $path.$c_single_entry_point.'?cid='.$cid.'&amp;mod=discussion&amp;fct=detail&amp;iid='.$linked_item->getItemID();
               $date = date('r',strtotime($item->getModificationDate()));
               unset($manager);
               $last_discarticle_item = $item;
               unset($item);
               unset($user_item);
               unset($linked_item);
            }
            break;
      case 'material':
            include_once('classes/cs_material_manager.php');
            $manager = new cs_material_manager($environment);
            $item = $manager->getItem($row['item_id']);
            if ( !isset($item)
                 or $item->isDeleted()
                 or ( isset($last_section_item)
                      and $item->getModificationDate() == $last_section_item->getModificationDate()
                    )
               ) {
               unset($last_section_item);
               $title = '';
               $description = '';
               $link = '';
               $author = '';
               $date = '';
            } else {
               $title = $translator->getMessage('RSS_NEW_MATERIAL_TITLE',$item->getTitle());
               $description = $item->getDescriptionWithoutHTML();
               if ( mb_strlen($description) > $desc_len ) {
                  $description = chunkText($description,$desc_len);
               }
               $user_item = $item->getModificatorItem();
               $fullname = $user_item->getFullName();
               $email = $user_item->getEmail();
               if ( $context_item->isCommunityRoom() ) {
                  if ( empty($_GET['hid']) and !$user_item->isVisibleForAll() ) {
                     $fullname = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                     $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                  }
               }
               if ( !$user_item->isEmailVisible() ) {
                  $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
               }
               $author = $email.' ('.$fullname.')';
               unset($email);
               unset($fullname);
               $link = $path.$c_single_entry_point.'?cid='.$cid.'&amp;mod=material&amp;fct=detail&amp;iid='.$row['item_id'];
               $date = date('r',strtotime($item->getModificationDate()));
            }
            unset($manager);
            unset($item);
            unset($user_item);
            break;
      case 'announcement':
            include_once('classes/cs_announcement_manager.php');
            $manager = new cs_announcement_manager($environment);
            $item = $manager->getItem($row['item_id']);
            $title = $translator->getMessage('RSS_NEW_ANNOUNCEMENT_TITLE',$item->getTitle());
            $description = $item->getDescriptionWithoutHTML();
            if(mb_strlen($description) > $desc_len)
            {
               $description = chunkText($description,$desc_len);
            }
            $user_item = $item->getModificatorItem();
            $fullname = $user_item->getFullName();
            $email = $user_item->getEmail();
            if ( $context_item->isCommunityRoom() ) {
               if ( empty($_GET['hid']) and !$user_item->isVisibleForAll() ) {
                  $fullname = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                  $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
               }
            }
            if ( !$user_item->isEmailVisible() ) {
               $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
            }
            $author = $email.' ('.$fullname.')';
            unset($email);
            unset($fullname);
            $link = $path.$c_single_entry_point.'?cid='.$cid.'&amp;mod=announcement&amp;fct=detail&amp;iid='.$row['item_id'];
            $date = date('r',strtotime($item->getModificationDate()));
            unset($manager);
            unset($item);
            unset($user_item);
            break;
      case 'section':
            include_once('classes/cs_section_manager.php');
            $manager = new cs_section_manager($environment);
            $item = $manager->getItem($row['item_id']);
            $linked_item = $item->getLinkedItem();
            $title = '';
            if ( isset($linked_item) ) {
               $title = $translator->getMessage('RSS_NEW_SECTION_TITLE',$item->getTitle(),$linked_item->getTitle());
            }
            $description = $item->getDescriptionWithoutHTML();
            if ( mb_strlen($description) > $desc_len ) {
               $description = chunkText($description,$desc_len);
            }
            $user_item = $item->getModificatorItem();
            $fullname = $user_item->getFullName();
            $email = $user_item->getEmail();
            if ( $context_item->isCommunityRoom() ) {
               if ( empty($_GET['hid']) and !$user_item->isVisibleForAll() ) {
                  $fullname = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                  $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
               }
            }
            if ( !$user_item->isEmailVisible() ) {
               $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
            }
            $author = $email.' ('.$fullname.')';
            unset($email);
            unset($fullname);
            $link = $path.$c_single_entry_point.'?cid='.$cid.'&amp;mod=material&amp;fct=detail&amp;iid='.$item->getLinkedItemID();
            $date = date('r',strtotime($item->getModificationDate()));
            unset($manager);
            $last_section_item = $item;
            unset($item);
            unset($user_item);
            unset($linked_item);
            break;
      case 'date':
            include_once('classes/cs_dates_manager.php');
            $manager = new cs_dates_manager($environment);
            $item = $manager->getItem($row['item_id']);
            $title = $translator->getMessage('RSS_NEW_DATE_TITLE',$item->getTitle());
            $description = $item->getDescriptionWithoutHTML();
            if(mb_strlen($description) > $desc_len)
            {
               $description = chunkText($description,$desc_len);
            }
            $user_item = $item->getModificatorItem();
            $fullname = $user_item->getFullName();
            $email = $user_item->getEmail();
            if ( $context_item->isCommunityRoom() ) {
               if ( empty($_GET['hid']) and !$user_item->isVisibleForAll() ) {
                  $fullname = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                  $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
               }
            }
            if ( !$user_item->isEmailVisible() ) {
               $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
            }
            $author = $email.' ('.$fullname.')';
            unset($email);
            unset($fullname);
            $link = $path.$c_single_entry_point.'?cid='.$cid.'&amp;mod=date&amp;fct=detail&amp;iid='.$row['item_id'];
            $date = date('r',strtotime($item->getModificationDate()));
            unset($manager);
            unset($item);
            unset($user_item);
            break;
      case 'label':
            include_once('classes/cs_labels_manager.php');
            $manager = new cs_labels_manager($environment);
            $item = $manager->getItem($row['item_id']);
            switch($item->getLabelType()) {
               case 'group':
                  $title = $translator->getMessage('RSS_NEW_GROUP_TITLE',$item->getTitle());
                  $description = $item->getDescriptionWithoutHTML();
                  if(mb_strlen($description) > $desc_len)
                  {
                     $description = chunkText($description,$desc_len);
                  }
                  $user_item = $item->getModificatorItem();
                  $fullname = $user_item->getFullName();
                  $email = $user_item->getEmail();
                  if ( $context_item->isCommunityRoom() ) {
                     if ( empty($_GET['hid']) and !$user_item->isVisibleForAll() ) {
                        $fullname = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                        $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                     }
                  }
                  if ( !$user_item->isEmailVisible() ) {
                     $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                  }
                  $author = $email.' ('.$fullname.')';
                  unset($email);
                  unset($fullname);
                  $link = $path.$c_single_entry_point.'?cid='.$cid.'&amp;mod=group&amp;fct=detail&amp;iid='.$row['item_id'];
               break;
               case 'institution':
                  $title = $translator->getMessage('RSS_NEW_INSTITUTION_TITLE',$item->getTitle());
                  $description = $item->getDescriptionWithoutHTML();
                  if(mb_strlen($description) > $desc_len)
                  {
                     $description = chunkText($description,$desc_len);
                  }
                  $user_item = $item->getModificatorItem();
                  $fullname = $user_item->getFullName();
                  $email = $user_item->getEmail();
                  if ( $context_item->isCommunityRoom() ) {
                     if ( empty($_GET['hid']) and !$user_item->isVisibleForAll() ) {
                        $fullname = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                        $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                     }
                  }
                  if ( !$user_item->isEmailVisible() ) {
                     $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                  }
                  $author = $email.' ('.$fullname.')';
                  unset($email);
                  unset($fullname);
                  $link = $path.$c_single_entry_point.'?cid='.$cid.'&amp;mod=institution&amp;fct=detail&amp;iid='.$row['item_id'];
               break;
               case 'topic':
                  $title = $translator->getMessage('RSS_NEW_TOPIC_TITLE',$item->getTitle());
                  $description = $item->getDescriptionWithoutHTML();
                  if(mb_strlen($description) > $desc_len)
                  {
                     $description = chunkText($description,$desc_len);
                  }
                  $user_item = $item->getModificatorItem();
                  $fullname = $user_item->getFullName();
                  $email = $user_item->getEmail();
                  if ( $context_item->isCommunityRoom() ) {
                     if ( empty($_GET['hid']) and !$user_item->isVisibleForAll() ) {
                        $fullname = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                        $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                     }
                  }
                  if ( !$user_item->isEmailVisible() ) {
                     $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                  }
                  $author = $email.' ('.$fullname.')';
                  unset($email);
                  unset($fullname);
                  $link = $path.$c_single_entry_point.'?cid='.$cid.'&amp;mod=topic&amp;fct=detail&amp;iid='.$row['item_id'];
               break;
            }
            $date = date('r',strtotime($item->getModificationDate()));
            unset($manager);
            unset($item);
            unset($user_item);
            break;
      case 'todo':
            include_once('classes/cs_todos_manager.php');
            $manager = new cs_todos_manager($environment);
            $item = $manager->getItem($row['item_id']);
            $title = $translator->getMessage('RSS_NEW_TODO_TITLE',$item->getTitle(),date('d.m.Y',strtotime($item->getDate())));
            $description = $item->getDescriptionWithoutHTML();
            if(mb_strlen($description) > $desc_len)
            {
               $description = chunkText($description,$desc_len);
            }
            $user_item = $item->getModificatorItem();
            $fullname = $user_item->getFullName();
            $email = $user_item->getEmail();
            if ( $context_item->isCommunityRoom() ) {
               if ( empty($_GET['hid']) and !$user_item->isVisibleForAll() ) {
                  $fullname = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
                  $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
               }
            }
            if ( !$user_item->isEmailVisible() ) {
               $email = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
            }
            $author = $email.' ('.$fullname.')';
            unset($email);
            unset($fullname);
            $link = $path.$c_single_entry_point.'?cid='.$cid.'&amp;mod=todo&amp;fct=detail&amp;iid='.$row['item_id'];
            $date = date('r',strtotime($item->getModificationDate()));
            unset($manager);
            unset($item);
            unset($user_item);
            break;
      default:
            $title = '';
            $description = '';
            $link = '';
            $author = '';
            $date = '';
      }
      if ( $context_item->isPrivateRoom() ) {
         if ( $curr_context->isPrivateRoom() ) {
            $pre_title = $translator->getMessage($curr_context->getTitle()).': ';
         } else {
            $pre_title = $curr_context->getTitle().': ';
         }
         $title = $pre_title.$title;
         unset($pre_title);
      }
      unset($curr_context);
      if ( isset($description)
           and !empty($description)
         ) {
         if ( mb_strlen($description) > $desc_len ) {
            $description = chunkText($description,$desc_len);
         } elseif ($description == '0') {
            $description = '';
         }
      } else {
         $description = '';
      }
      if ( !empty($title) and !empty($link) ) {
         $rss .= '
         <item>
           <title>'.encode(AS_RSS,$title).'</title>
           <description>'.encode(AS_RSS,$description).'</description>
           <link>'.$link.'</link>
           <pubDate>'.encode(AS_RSS,$date).'</pubDate>'.LF;
         $rss .= '           <author>'.encode(AS_RSS,trim($author)).'</author>'.LF;
         $rss .= '           <guid isPermaLink="false">'.$row['item_id'].'</guid>'.LF;
         $rss .= '         </item>';
         $counter++;
         unset($title);
         unset($description);
         unset($link);
         unset($date);
         unset($author);
      }
   }

   $rss .=	'
     </channel>
  </rss>';

   // debugging
   #pr($rss);
   #exit();

   // Wir werden eine XML Datei ausgeben
   header('Content-type: application/rss+xml; charset=UTF-8');

   echo($rss);
   exit();
} else {
   chdir('..');
   include_once('etc/cs_constants.php');
   include_once('etc/cs_config.php');
   include_once('classes/cs_environment.php');
   $environment = new cs_environment();
   $translator = $environment->getTranslationObject();
   die($translator->getMessage('RSS_NO_CONTEXT'));
}
?>