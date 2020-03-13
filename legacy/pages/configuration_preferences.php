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

// Function used for redirecting to connected rubrics
function attach_redirect ($rubric_type, $current_iid) {
   global $session, $environment;
   $infix = '_'.$rubric_type;
   if ( !empty($_FILES['upload']['tmp_name']) ) {
      $new_temp_name = $_FILES['logo']['tmp_name'].'_TEMP_'.$_FILES['logo']['name'];
      move_uploaded_file($_FILES['logo']['tmp_name'],$new_temp_name);
      $_FILES['logo']['tmp_name'] = $new_temp_name;
      $_POST = array_merge($_POST,$_FILES);
   }
   if ( !empty($_FILES['upload']['tmp_name']) ) {
      $new_temp_name = $_FILES['picture']['tmp_name'].'_TEMP_'.$_FILES['picture']['name'];
      move_uploaded_file($_FILES['picture']['tmp_name'],$new_temp_name);
      $_FILES['picture']['tmp_name'] = $new_temp_name;
      $_POST = array_merge($_POST,$_FILES);
   }
   $session->setValue($current_iid.'_post_vars', $_POST);
   if ( isset($_POST[$rubric_type]) ) {
      $session->setValue($current_iid.$infix.'_attach_ids', $_POST[$rubric_type]);
   } else {
      $session->setValue($current_iid.$infix.'_attach_ids', array());
   }
   if ( $environment->getCurrentModule() == CS_MYROOM_TYPE ) {
      $session->setValue($current_iid.$infix.'_back_module', CS_MYROOM_TYPE);
   } elseif ( $environment->getCurrentModule() == CS_MYROOM_TYPE ) {
      $session->setValue($current_iid.$infix.'_back_module', CS_PROJECT_TYPE);
   }
   $params = array();
   $params['ref_iid'] = $current_iid;
   $params['mode'] = 'formattach';
   redirect($environment->getCurrentContextID(), type2Module($rubric_type), 'index', $params);
}

function attach_return ($rubric_type, $current_iid) {
   global $session;
   $infix = '_'.$rubric_type;
   $attach_ids = $session->getValue($current_iid.$infix.'_attach_ids');
   $session->unsetValue($current_iid.'_post_vars');
   $session->unsetValue($current_iid.$infix.'_attach_ids');
   $session->unsetValue($current_iid.$infix.'_back_module');
   return $attach_ids;
}

// Function used for cleaning up the session. This function
// deletes ALL session variables this page writes.
function cleanup_session ($current_iid) {
   global $session;
   $session->unsetValue($current_iid.'_post_vars');
   $session->unsetValue($current_iid.'_add_community_rooms');
   $session->unsetValue($current_iid.'_material_attach_ids');
   $session->unsetValue($current_iid.'_group_attach_ids');
   $session->unsetValue($current_iid.'_topic_attach_ids');
   $session->unsetValue($current_iid.'_material_back_module');
   $session->unsetValue($current_iid.'_group_back_module');
   $session->unsetValue($current_iid.'_topic_back_module');
   $session->unsetValue('project_attachments_user');
}

// function for page edit
// - to check files for virus
if (isset($c_virus_scan) and $c_virus_scan) {
   include_once('functions/page_edit_functions.php');
}

// Coming back from attaching something
if ( !empty($_GET['backfrom']) ) {
   $backfrom = $_GET['backfrom'];
} else {
   $backfrom = false;
}

// Get the current user
$current_user = $environment->getCurrentUserItem();
$translator = $environment->getTranslationObject();
$is_saved = false;
if ( !empty($_GET['reload']) ) {
   $is_saved = true;
}

// Check access rights
$room_item = $environment->getCurrentContextItem();

// get iid
if ( empty($current_iid) ) {
   if ( !empty($_GET['iid']) ) {
      $current_iid = $_GET['iid'];
   } elseif ( !empty($_POST['iid']) ) {
      $current_iid = $_POST['iid'];
   } else {
      $current_iid = $environment->getCurrentContextID();
   }
}


// Find out what to do
if ( isset($_POST['delete_option']) ) {
   $delete_command = $_POST['delete_option'];
}elseif ( isset($_GET['delete_option']) ) {
   $delete_command = $_GET['delete_option'];
} else {
   $delete_command = '';
}




if ($current_user->isGuest()) {
   if (!$room_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
      $params['cid'] = $room_item->getItemId();
      redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
} else {
   //access granted

   // Get item to be edited
   if ($current_iid != 'NEW') {
      if ( $current_iid != $environment->getCurrentContextID() ) {
         $room_manager = $environment->getRoomManager();
         $item = $room_manager->getItem($current_iid);
      } else {
         $item = $environment->getCurrentContextItem();
      }
   } else {
      if ($environment->getCurrentModule() == CS_PROJECT_TYPE) {
         $page->setPageName($translator->getMessage('COMMON_NEW_PROJECT'));
      } elseif ($environment->getCurrentModule() == CS_COMMUNITY_TYPE) {
         $page->setPageName($translator->getMessage('COMMON_NEW_COMMUNITY'));
      } elseif ($environment->inServer()) {
         $page->setPageName($translator->getMessage('PORTAL_ENTER_NEW'));
      } else {
         $page->setPageName($translator->getMessage('PORTAL_ENTER_ROOM'));
      }
      unset($item);
   }



   if ( isOption($delete_command, $translator->getMessage('COMMON_DELETE_BUTTON')) ) {
     $is_grouproom = false;
     if ( $item->isGroupRoom() ) {
        $is_grouproom = true;
        $group_item_id = $item->getLinkedGroupItemID();
        $project_room_id = $item->getLinkedProjectItemID();
     }
     $item->delete();
     if ($environment->getCurrentModule() == CS_PROJECT_TYPE) {
        redirect( $environment->getCurrentContextID(),CS_PROJECT_TYPE,'index','');
     } elseif ( $environment->getCurrentModule() == CS_MYROOM_TYPE ) {
        redirect( $environment->getCurrentContextID(),CS_MYROOM_TYPE,'index','');
     } elseif ( $is_grouproom and !empty($group_item_id) and !empty($project_room_id) ) {
        $group_item = $item->getLinkedGroupItem();
        $project_room_item = $item->getLinkedProjectItem();
        if ( isset($group_item)
           and !empty($group_item)
           and isset($project_room_item)
           and !empty($project_room_item)
           ) {
           $group_item->unsetGroupRoomItemID();
           $group_item->unsetGroupRoomActive();
           $group_item->save();
           redirect($project_room_id,CS_GROUP_TYPE,'detail',array('iid'=> $group_item_id));
        } else {
           redirect($environment->getCurrentPortalID(),'home','index','');
        }
     } else {
        redirect($environment->getCurrentPortalID(),'home','index','');
     }
   }elseif( isOption($delete_command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      if ( $environment->getCurrentModule() == CS_PROJECT_TYPE
           and $environment->inCommunityRoom()
         ) {
         $params = array();
         if (isset($item)) {
            $params['iid'] = $item->getItemID();
            redirect($environment->getCurrentContextID(),CS_PROJECT_TYPE,'detail',$params);
            unset($params);
         } else {
            redirect($environment->getCurrentContextID(),CS_PROJECT_TYPE,'index','');
         }
      } elseif ($environment->getCurrentModule() == CS_MYROOM_TYPE) {
         $params = array();
         if (isset($item)) {
            $params['iid'] = $item->getItemID();
            redirect($environment->getCurrentContextID(),CS_MYROOM_TYPE,'detail',$params);
            unset($params);
         } else {
            redirect($environment->getCurrentContextID(),CS_MYROOM_TYPE,'index','');
         }
      } else {
         $session = $environment->getSessionItem();
         $history = $session->getValue('history');
         redirect($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),'');
      }
   } elseif ( isOption($delete_command, $translator->getMessage('ROOM_ARCHIV_BUTTON')) ) {
      if ( !$item->isTemplate() ) {
      	// TBD: close wiki
      	$item->moveToArchive();
	      if ( $environment->getCurrentModule() == CS_PROJECT_TYPE
	           and $environment->inCommunityRoom()
	         ) {
	         $params = array();
	         if (isset($item)) {
	            $params['iid'] = $item->getItemID();
	            redirect($environment->getCurrentContextID(),CS_PROJECT_TYPE,'detail',$params);
	            unset($params);
	         } else {
	            redirect($environment->getCurrentContextID(),CS_PROJECT_TYPE,'index','');
	         }
	      } elseif ($environment->getCurrentModule() == CS_MYROOM_TYPE) {
	         $params = array();
	         if (isset($item)) {
	            $params['iid'] = $item->getItemID();
	            redirect($environment->getCurrentContextID(),CS_MYROOM_TYPE,'detail',$params);
	            unset($params);
	         } else {
	            redirect($environment->getCurrentContextID(),CS_MYROOM_TYPE,'index','');
	         }
	      } else {
	         $session = $environment->getSessionItem();
	         $history = $session->getValue('history');
	         redirect($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),'');
	      }
      } else {
         $session = $environment->getSessionItem();
         $history = $session->getValue('history');
         redirect($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),'');
      }
   }



   $with_anchor = false;
   if ( !empty($_GET['mode']) ) {
      $private_date = true;
   } elseif ( !empty($_POST['mode']) ) {
      $private_date = true;
   } else {
      $private_date = false;
   }

   // Check access rights
   if ( isset($item) and !$item->mayEdit($current_user) ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
      $page->addLeft($errorbox);
   }
   // Access granted
   else {
      // Find out what to do
      if ( isset($_POST['option']) ) {
         $command = $_POST['option'];
      } else {
         $command = '';
      }

      // delete item
      if ( isOption($command, $translator->getMessage('ROOM_DELETE_BUTTON')) ) {
         $params = $environment->getCurrentParameterArray();
         $page->addDeleteBox(curl($environment->getCurrentContextID(),module2type($environment->getCurrentModule()),$environment->getCurrentFunction(),$params));
      }

      if (isOption($command, $translator->getMessage('PORTAL_DELETE_BUTTON'))) {
          $params = $environment->getCurrentParameterArray();
          $page->addDeleteBox(curl($environment->getCurrentContextID(),module2type($environment->getCurrentModule()),$environment->getCurrentFunction(),$params));
      }

      // Cancel editing
      if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
         if ( $environment->getCurrentModule() == CS_PROJECT_TYPE
              and $environment->inCommunityRoom()
            ) {
            $params = array();
            if (isset($item)) {
               $params['iid'] = $item->getItemID();
               redirect($environment->getCurrentContextID(),CS_PROJECT_TYPE,'detail',$params);
               unset($params);
            } else {
               redirect($environment->getCurrentContextID(),CS_PROJECT_TYPE,'index','');
            }
         } elseif ($environment->getCurrentModule() == CS_MYROOM_TYPE) {
            $params = array();
            if (isset($item)) {
               $params['iid'] = $item->getItemID();
               redirect($environment->getCurrentContextID(),CS_MYROOM_TYPE,'detail',$params);
               unset($params);
            } else {
               redirect($environment->getCurrentContextID(),CS_MYROOM_TYPE,'index','');
            }
         } else {
            $session = $environment->getSessionItem();
            $history = $session->getValue('history');
            if ($history[1]['function'] != 'preferences') {
               redirect($history[1]['context'],$history[1]['module'],$history[1]['function'],$history[1]['parameter']);
            } else {
               redirect($history[2]['context'],$history[2]['module'],$history[2]['function'],$history[2]['parameter']);
            }
         }
      }


      // Show form and/or save item
      else {
         // Initialize the form
         $form = $class_factory->getClass(CONFIGURATION_PREFERENCES_FORM,array('environment' => $environment));

         // Add a community_room
         if ( isOption($command, $translator->getMessage('PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON')) ) {
            $focus_element_onload = 'communityrooms';
            $post_community_room_ids = array();
            $new_community_room_ids = array();
            $new_buzzword_ids = array();
            if ( isset($_POST['communityroomlist']) ) {
               $post_community_room_ids = $_POST['communityroomlist'];
            }

            if ( $session->issetValue($current_iid.'_add_community_rooms') ) {
               $community_room_array = $session->getValue($current_iid.'_add_community_rooms');
            } else {
               $community_room_array = array();
            }
            if ( !empty($_POST['communityrooms']) and $_POST['communityrooms']!=-1 and $_POST['communityrooms']!='disabled' and !in_array($_POST['communityrooms'],$post_community_room_ids) ) {
               $temp_array = array();
               $community_manager = $environment->getCommunityManager();
               $community_manager->reset();
               $community_item = $community_manager->getItem($_POST['communityrooms']);
               
               $temp_array['name'] = $community_item->getTitle();
               
               $temp_array['id'] = $community_item->getItemID();
               $community_room_array[] = $temp_array;
               $new_community_room_ids[] = $temp_array['id'];
            }

            if ( count($community_room_array) > 0 ) {
               $session->setValue($current_iid.'_add_community_rooms', $community_room_array);
            } else {
               $session->unsetValue($current_iid.'_add_community_rooms');
            }
            $post_community_room_ids = array_merge($post_community_room_ids, $new_community_room_ids);
         }

         // Create data for a new material
         elseif ( $current_iid == 'NEW' and !$backfrom ) {
            cleanup_session($current_iid);
         }

         // display form  Outside rooms
         if ($environment->inPortal() or $environment->inServer()) {
            if ( (isset($_GET['iid']) and $_GET['iid'] == 'NEW')
               or (isset($_POST['iid']) and $_POST['iid'] == 'NEW')
            ) {
            $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = true;
            $form_view = $class_factory->getClass(FORM_VIEW,$params);
            unset($params);
            } else {
               $current_context = $environment->getCurrentContextItem();
               $params = array();
               $params['environment'] = $environment;
               $params['with_modifying_actions'] = $current_context->isOpen();
               $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
               unset($params);
            }

         // display form
         } else {
            // In den Listen der Gemeinschaftsräume und persönlichen Räume
            if ( ($environment->inCommunityRoom() and $environment->getCurrentModule() == CS_PROJECT_TYPE)
                  or ($environment->inPrivateRoom() and $environment->getCurrentModule() == CS_MYROOM_TYPE)
               ) {
               $params = array();
               $params['environment'] = $environment;
               $params['with_modifying_actions'] = true;
               $form_view = $class_factory->getClass(FORM_VIEW,$params);
               unset($params);
               $current_context_item = $environment->getCurrentContextItem();

            // im Konfigurationsbereich eines Raumes
            } else {
               $current_context = $environment->getCurrentContextItem();
               $params = array();
               $params['environment'] = $environment;
               $params['with_modifying_actions'] = $current_context->isOpen();
               $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
               unset($params);
            }
         }

         //Wofür wird das gebraucht, nach dem Code von davor????
         if ( ($environment->inCommunityRoom() and $environment->getCurrentModule() == CS_PROJECT_TYPE)
            or ($environment->inPrivateRoom() and $environment->getCurrentModule() == CS_MYROOM_TYPE)
         ) {
            $context_item = $environment->getCurrentContextItem();
         // Redirect to attach material
            if ( isOption($command, $translator->getMessage('RUBRIC_DO_ATTACH_MATERIAL_BUTTON')) ) {
               attach_redirect(CS_MATERIAL_TYPE, $current_iid);
            }
      // Redirect to attach TODO
      if ( isOption($command, $translator->getMessage('RUBRIC_DO_ATTACH_TODO_BUTTON')) ) {
         attach_redirect(CS_TODO_TYPE, $current_iid);
      }

      // Redirect to attach DATE
      if ( isOption($command, $translator->getMessage('RUBRIC_DO_ATTACH_DATE_BUTTON')) ) {
         attach_redirect(CS_DATE_TYPE, $current_iid);
      }

      // Redirect to attach ANNOUNCEMENT
      if ( isOption($command, $translator->getMessage('RUBRIC_DO_ATTACH_ANNOUNCEMENT_BUTTON')) ) {
         attach_redirect(CS_ANNOUNCEMENT_TYPE, $current_iid);
      }

      // Redirect to attach DISCUSSION
      if ( isOption($command, $translator->getMessage('RUBRIC_DO_ATTACH_DISCUSSION_BUTTON')) ) {
         attach_redirect(CS_DISCUSSION_TYPE, $current_iid);
      }

      // Redirect to attach PROJECT
      if ( isOption($command, $translator->getMessage('RUBRIC_DO_ATTACH_PROJECT_BUTTON')) ) {
         attach_redirect(CS_PROJECT_TYPE, $current_iid);
      }


            // Redirect to attach topics
            if ( isOption($command, $translator->getMessage('RUBRIC_DO_ATTACH_TOPIC_BUTTON')) ) {
               attach_redirect(CS_TOPIC_TYPE, $current_iid);
            }
         }


         // Load form data from postvars
         if ( !empty($_POST) ) {
            if ( !empty($_FILES) ) {
               if ( !empty($_FILES['logo']['tmp_name']) ) {
                  $new_temp_name = $_FILES['logo']['tmp_name'].'_TEMP_'.$_FILES['logo']['name'];
                  move_uploaded_file($_FILES['logo']['tmp_name'],$new_temp_name);
                  $_FILES['logo']['tmp_name'] = $new_temp_name;
                  $session_item = $environment->getSessionItem();
                  if (!isset($room_iid) or empty($room_iid)){
                     $room_iid = $environment->getCurrentContextID();
                  }
                  if ( isset($session_item) ) {
                     $session_item->setValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_logo_temp_name',$new_temp_name);
                     $session_item->setValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_logo_name',$_FILES['logo']['name']);
                  }
               }
               if ( !empty($_FILES['picture']['tmp_name']) ) {
                  $new_temp_name = $_FILES['picture']['tmp_name'].'_TEMP_'.$_FILES['picture']['name'];
                  move_uploaded_file($_FILES['picture']['tmp_name'],$new_temp_name);
                  $_FILES['picture']['tmp_name'] = $new_temp_name;
                  $session_item = $environment->getSessionItem();
                  if ( isset($session_item) ) {
                     $session_item->setValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_picture_temp_name',$new_temp_name);
                     $session_item->setValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_picture_name',$_FILES['picture']['name']);
                  }
               }
               $values = array_merge($_POST,$_FILES);
            } else {
               $values = $_POST;
            }
            if ( isset($post_community_room_ids) AND !empty($post_community_room_ids) ) {
               $values['communityroomlist'] = $post_community_room_ids;
            }

            $languages = $environment->getAvailableLanguageArray();
            foreach ($languages as $language) {
               if ( !empty($_POST['wellcome1_'.$language.'_reset']) ) {
                  $values['wellcome1_'.$language] = $translator->getMessageInLang(mb_strtolower($language, 'UTF-8'),'HOMEPAGE_PAGE_ROOT_TITLE').' '.$translator->getMessageInLang(mb_strtolower($language, 'UTF-8'),'COMMON_IN').' ...';
               }
               if ( !empty($_POST['wellcome2_'.$language.'_reset']) ) {
                  $values['wellcome2_'.$language] = '... '.$values['title'];
               }
            }

             $form->setFormPost($values);
         }

      // Back from attaching material
      elseif ( $backfrom == CS_MATERIAL_TYPE ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_MATERIAL_TYPE, $current_iid);
         $with_anchor = true;
         $session_post_vars[CS_MATERIAL_TYPE] = $attach_ids;
         $form->setFormPost($session_post_vars);
      }

      // Back from attaching PROJECT
      elseif ( $backfrom == CS_PROJECT_TYPE ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_PROJECT_TYPE, $current_iid);
         $with_anchor = true;
         $session_post_vars[CS_PROJECT_TYPE] = $attach_ids;
         $form->setFormPost($session_post_vars);
      }

      // Back from attaching DISCUSSION
      elseif ( $backfrom == CS_DISCUSSION_TYPE ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_DISCUSSION_TYPE, $current_iid);
         $with_anchor = true;
         $session_post_vars[CS_DISCUSSION_TYPE] = $attach_ids;
         $form->setFormPost($session_post_vars);
      }

      // Back from attaching TODO
      elseif ( $backfrom == CS_TODO_TYPE ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_TODO_TYPE, $current_iid);
         $with_anchor = true;
         $session_post_vars[CS_TODO_TYPE] = $attach_ids;
         $form->setFormPost($session_post_vars);
      }

      // Back from attaching DATE
      elseif ( $backfrom == CS_DATE_TYPE ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_DATE_TYPE, $current_iid);
         $with_anchor = true;
         $session_post_vars[CS_DATE_TYPE] = $attach_ids;
         $form->setFormPost($session_post_vars);
      }

      // Back from attaching ANNOUNCEMENT
      elseif ( $backfrom == CS_ANNOUNCEMENT_TYPE ) {
         $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         $attach_ids = attach_return(CS_ANNOUNCEMENT_TYPE, $current_iid);
         $with_anchor = true;
         $session_post_vars[CS_ANNOUNCEMENT_TYPE] = $attach_ids;
         $form->setFormPost($session_post_vars);
      }
      // Back from attaching topics
         elseif ( $backfrom == CS_TOPIC_TYPE ) {
            $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
            $attach_ids = attach_return(CS_TOPIC_TYPE, $current_iid);
            $with_anchor = true;
            $session_post_vars[CS_TOPIC_TYPE] = $attach_ids;
            $form->setFormPost($session_post_vars);
         }

         // Load form data from database
         elseif ( isset($item) ) {
            $form->setItem($item);
            if ( $item->isProjectRoom() ) {
               $community_room_list = $item->getCommunityList();
               if ( !$community_room_list->isEmpty() ) {
                  $community_room_array = array();
                  $community_room_item = $community_room_list->getFirst();
                  while ( $community_room_item ) {
                     $temp_array = array();
                     $temp_array['name'] = $community_room_item->getTitle();
                     $temp_array['id'] = (int)$community_room_item->getItemID();
                     $community_room_array[] = $temp_array;
                     $community_room_item = $community_room_list->getNext();
                  }
                  if ( !empty($community_room_array)) {
                     $session->setValue($current_iid.'_add_community_rooms', $community_room_array);
                  }
               }
            }
         }

         if ($session->issetValue($current_iid.'_add_community_rooms')) {
            $form->setSessionCommunityRoomArray($session->getValue($current_iid.'_add_community_rooms'));
         }
         $form->prepareForm();
         $form->loadValues();

         // Save item
         if ( !empty($command) and
              (isOption($command, $translator->getMessage('COMMON_SAVE_BUTTON'))
              or isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) )
            ) {
             $correct = $form->check();
             if ( $correct
                  and empty($_FILES['logo']['tmp_name'])
                  and !empty($_POST['hidden_logo_name'])
                ) {
                $session_item = $environment->getSessionItem();
                if ( isset($session_item) ) {
                   $_FILES['logo']['tmp_name'] = $session_item->getValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_logo_temp_name');
                   $_FILES['logo']['name']     = $session_item->getValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_logo_name');
                   $session_item->unsetValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_logo_temp_name');
                   $session_item->unsetValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_logo_name');
                }
             }
             if ( $correct
                  and empty($_FILES['picture']['tmp_name'])
                  and !empty($_POST['hidden_picture_name'])
                ) {
                $session_item = $environment->getSessionItem();
                if ( isset($session_item) ) {
                   $_FILES['picture']['tmp_name'] = $session_item->getValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_picture_temp_name');
                   $_FILES['picture']['name']     = $session_item->getValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_picture_name');
                   $session_item->unsetValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_picture_temp_name');
                   $session_item->unsetValue($environment->getCurrentContextID().'_pref_'.$room_iid.'_picture_name');
                }
             }
             if ( $correct
                  and ( !isset($c_virus_scan)
                        or !$c_virus_scan
                        or !isset($_FILES['logo'])
                        or page_edit_virusscan_isClean($_FILES['logo']['tmp_name'],$_FILES['logo']['name'])
                      )
                  and ( !isset($c_virus_scan)
                        or !$c_virus_scan
                        or !isset($_FILES['picture'])
                        or page_edit_virusscan_isClean($_FILES['picture']['tmp_name'],$_FILES['picture']['name'])
                      )
                ) {
                $new_flag = false;
                if (!isset($item)) {
                   if (!empty($_POST['type'])) {
                      $manager = $environment->getManager($_POST['type']);
                      $item = $manager->getNewItem();
                      $user = $environment->getCurrentUserItem();
                      $item->setCreatorItem($user);
                      $item->setCreationDate(getCurrentDateTimeInMySQL());
                      if ($environment->inCommunityRoom()) {
                         $item->setContextID($environment->getCurrentPortalID());
                      }elseif ($environment->inPrivateRoom()) {
                         $item->setContextID($environment->getCurrentPortalID());
                      }else {
                         $item->setContextID($environment->getCurrentContextID());
                      }
                      if ( $environment->inServer() and $item->isPortal() ) {
                         $item->setOpenForGuests();
                      }
                      $item->open();

                      if ( $environment->inPortal() or $environment->inCommunityRoom() ) {
                         $current_context = $environment->getCurrentContextItem();
                         $item->setRoomContext($current_context->getRoomContext());
                      }

                      $new_flag = true;
                   }
                }

                // Set modificator and modification date
                $user = $environment->getCurrentUserItem();
                $item->setModificatorItem($user);
                $item->setModificationDate(getCurrentDateTimeInMySQL());



                  //******************************************//
       // Set attributes
       if ( isset($_POST['title']) ) {
       	  $text_converter = $environment->getTextConverter();
       	 
       	  // sanitize title
       	  $item->setTitle($text_converter->_htmlentities_cleanbadcode($_POST['title']));
          //$item->setTitle($_POST['title']);
       }
       if (isset($_POST['public'])) {
          $item->setPublic($_POST['public']);
       }
                  // show title
       if ( (isset($_POST['show_title']) and !empty($_POST['show_title'])) or $environment->inPrivateRoom()) {
          $item->setShowTitle();
       } else {
          $item->setNotShowTitle();
       }
                  // logo: save and/or delete current logo
       if ( isset($_POST['delete_logo']) ) {
          $disc_manager = $environment->getDiscManager();
          if ( $disc_manager->existsFile($item->getLogoFilename()) ) {
             $disc_manager->unlinkFile($item->getLogoFilename());
          }
          $item->setLogoFilename('');
       }
       if ( !empty($_FILES['logo']['name']) ) {
          $logo = $item->getLogoFilename();
          $disc_manager = $environment->getDiscManager();
          if ( !empty ($logo) ) {
             if ( $disc_manager->existsFile($item->getLogoFilename()) ) {
                $disc_manager->unlinkFile($item->getLogoFilename());
             }
             $item->setLogoFilename('');
          }
          $filename = 'cid'.$environment->getCurrentContextID().'_logo_'.$_FILES['logo']['name'];
          $disc_manager->copyFile($_FILES['logo']['tmp_name'],$filename,true);
          $item->setLogoFilename($filename);
       }
       // picture: save and/or delete current picture
       if ( isset($_POST['delete_picture']) ) {
          $disc_manager = $environment->getDiscManager();
          if ( $disc_manager->existsFile($item->getpictureFilename()) ) {
             $disc_manager->unlinkFile($item->getpictureFilename());
          }
          $item->setpictureFilename('');
       }
       if ( !empty($_FILES['picture']['name']) ) {
          $picture = $item->getpictureFilename();
          $disc_manager = $environment->getDiscManager();
          if ( !empty ($picture) ) {
             if ( $disc_manager->existsFile($item->getpictureFilename()) ) {
                $disc_manager->unlinkFile($item->getpictureFilename());
             }
             $item->setpictureFilename('');
          }
          $filename = 'cid'.$environment->getCurrentContextID().'_picture_'.$_FILES['picture']['name'];
          $disc_manager->copyFile($_FILES['picture']['tmp_name'],$filename,true);
          $item->setpictureFilename($filename);
       }
       // check member
       if ( isset($_POST['member_check']) ) {
          if ($_POST['member_check'] == 'never') {
                        $requested_user_manager = $environment->getUserManager();
                        $requested_user_manager->setContextLimit($environment->getCurrentContextID());
                        $requested_user_manager->setRegisteredLimit();
                        $requested_user_manager->select();
                        $requested_user_list = $requested_user_manager->get();
                        if (!empty($requested_user_list)){
                           $requested_user = $requested_user_list->getFirst();
                           while($requested_user){
                              $requested_user->makeUser();
                              $requested_user->save();
                              $task_manager = $environment->getTaskManager();
                              $task_list = $task_manager->getTaskListForItem($requested_user);
                              if (!empty($task_list)){
                                 $task = $task_list->getFirst();
                                 while($task){
                                    if ($task->getStatus() == 'REQUEST' and ($task->getTitle() == 'TASK_USER_REQUEST' or $task->getTitle() == 'TASK_PROJECT_MEMBER_REQUEST')) {
                                       $task->setStatus('CLOSED');
                                       $task->save();
                                    }
                                    $task = $task_list->getNext();
                                 }
                              }
                              $requested_user = $requested_user_list->getNext();
                           }
                        }

             $item->setCheckNewMemberNever();
          } elseif ($_POST['member_check'] == 'always') {
             $item->setCheckNewMemberAlways();
          } elseif ($_POST['member_check'] == 'sometimes') {
             $item->setCheckNewMemberSometimes();
          } elseif ( $_POST['member_check'] == 'withcode'
                     and !empty($_POST['code'])
                   ) {
             $item->setCheckNewMemberWithCode();
             $item->setCheckNewMemberCode($_POST['code']);
          }
       }
       // open for guests
       if ( isset($_POST['open_for_guests']) ) {
          if ($_POST['open_for_guests'] == 'open') {
             $item->setOpenForGuests();
          } elseif ($_POST['open_for_guests'] == 'closed') {
             $item->setClosedForGuests();
          }
       }
       // Room association
       if ( isset($_POST['room_assignment']) ) {
          if ($_POST['room_assignment'] == 'open') {
             $item->setAssignmentOpenForAnybody();
          } elseif ($_POST['room_assignment'] == 'closed') {
             $item->setAssignmentOnlyOpenForRoomMembers();
          }
       }
       // time (clock pulses)
       if (isset($_POST['time2']) and !empty($_POST['time2'])){
          if (in_array('cont',$_POST['time2'])) {
             $item->setContinuous();
          } else {
             $item->setTimeListByID2($_POST['time2']);
             $item->setNotContinuous();
          }
       } elseif ($item->isProjectRoom()) {
          $item->setTimeListByID2(array());
          $item->setNotContinuous();
       }

       if ( isset($_POST['language']) ) {
          $language = $_POST['language'];
          if ($_POST['language'] == 'enabled') {
             $language = 'user';
          }
          $item->setLanguage($language);
       }
       if ( isset($_POST['context'])) {
          $old_context = $item->getRoomContext();
          if ($old_context != $_POST['context']){
             $item->setRoomContext($_POST['context']);
          }
       }
       $languages = $environment->getAvailableLanguageArray();
       if ( $item->isPortal() ) {
          $description = $item->getDescriptionWellcome1Array();
          foreach ($languages as $language) {
             if ( !empty($_POST['wellcome1_'.$language.'_reset']) ) {
                unset($description[mb_strtoupper($language, 'UTF-8')]);
             } elseif ( isset($_POST['wellcome1_'.$language])
                        and !empty($_POST['wellcome1_'.$language])
                        and $_POST['wellcome1_'.$language] != $translator->getMessageInLang(mb_strtolower($language, 'UTF-8'),'HOMEPAGE_PAGE_ROOT_TITLE').' '.$translator->getMessageInLang(mb_strtolower($language, 'UTF-8'),'COMMON_IN').' ...'
                ) {
                $description[mb_strtoupper($language, 'UTF-8')] = $_POST['wellcome1_'.$language];
             } elseif(isset($description[mb_strtoupper($language, 'UTF-8')])) {
                unset($description[mb_strtoupper($language, 'UTF-8')]);
             }
          }
          $item->setDescriptionWellcome1Array($description);
          $description = $item->getDescriptionWellcome2Array();
          foreach ($languages as $language) {
             if ( !empty($_POST['wellcome2_'.$language.'_reset']) ) {
                unset($description[mb_strtoupper($language, 'UTF-8')]);
             } elseif ( isset($_POST['wellcome2_'.$language])
                  and $_POST['wellcome2_'.$language] != '... '.$item->getTitle()
                ) {
                $description[mb_strtoupper($language, 'UTF-8')] = $_POST['wellcome2_'.$language];
             } elseif ( isset($description[mb_strtoupper($language, 'UTF-8')]) ) {
                unset($description[mb_strtoupper($language, 'UTF-8')]);
             }
          }
          $item->setDescriptionWellcome2Array($description);

          $languages = $environment->getAvailableLanguageArray();
          $description = $item->getDescriptionArray();
          foreach ($languages as $language) {
             if (!empty($_POST['description_'.$language])) {
                $description[mb_strtoupper($language, 'UTF-8')] = $_POST['description_'.$language];
             } else {
                $description[mb_strtoupper($language, 'UTF-8')] = '';
             }
          }
          $item->setDescriptionArray($description);

       }elseif($item->isServer()){
          if (!empty($_POST['description'])) {
             $desc_array = array();
             $desc_array['DE'] = $_POST['description'];
             $item->setDescriptionArray($desc_array);
          }
       }else{
          $description = $item->getDescription();
          if (!empty($_POST['description'])) {
             $description = $_POST['description'];
          }
          $item->setDescription($description);
       }


                  $community_room_array = array();
                  if ( isset($_POST['communityroomlist']) ) {
                     $community_room_array = $_POST['communityroomlist'];
                  }
                  if ( isset($_POST['communityrooms']) and !in_array($_POST['communityrooms'],$community_room_array) and $_POST['communityrooms'] > 0) {
                     $community_room_array[] = $_POST['communityrooms'];
                  }
             if ( $item->isProjectRoom() ) {
                     $item->setCommunityListByID($community_room_array);
                        }

       if ( $environment->getCurrentModule() == CS_PROJECT_TYPE
          or $environment->getCurrentModule() == CS_MYROOM_TYPE
                  ) {
          // Set links to connected rubrics
          if ( isset($_POST[CS_MATERIAL_TYPE]) ) {
             $item->setMaterialListByID($_POST[CS_MATERIAL_TYPE]);
          } else {
             $item->setMaterialListByID(array());
          }
            if ( isset($_POST[CS_ANNOUNCEMENT_TYPE]) ) {
               $item->setLinkedItemsByID(CS_ANNOUNCEMENT_TYPE,$_POST[CS_ANNOUNCEMENT_TYPE]);
            } else {
               $item->setLinkedItemsByID(CS_ANNOUNCEMENT_TYPE,array());
            }

            if ( isset($_POST[CS_DATE_TYPE]) ) {
               $item->setLinkedItemsByID(CS_DATE_TYPE,$_POST[CS_DATE_TYPE]);
            } else {
               $item->setLinkedItemsByID(CS_DATE_TYPE,array());
            }

            if ( isset($_POST[CS_TODO_TYPE]) ) {
               $item->setLinkedItemsByID(CS_TODO_TYPE,$_POST[CS_TODO_TYPE]);
            } else {
               $item->setLinkedItemsByID(CS_TODO_TYPE,array());
            }

            if ( isset($_POST[CS_DISCUSSION_TYPE]) ) {
               $item->setLinkedItemsByID(CS_DISCUSSION_TYPE,$_POST[CS_DISCUSSION_TYPE]);
            } else {
               $item->setLinkedItemsByID(CS_DISCUSSION_TYPE,array());
            }

            if ( isset($_POST[CS_PROJECT_TYPE]) ) {
               $item->setLinkedItemsByID(CS_PROJECT_TYPE,$_POST[CS_PROJECT_TYPE]);
            } else {
               $item->setLinkedItemsByID(CS_PROJECT_TYPE,array());
            }
          if ( isset($_POST[CS_TOPIC_TYPE]) ) {
             $item->setTopicListByID($_POST[CS_TOPIC_TYPE]);
          } else {
             $item->setTopicListByID(array());
          }
       }
       // server: default sender email address
       if ($environment->inServer() and $item->isServer()) {
          if ( isset($_POST['server_default_sender_address']) and !empty($_POST['server_default_sender_address']) ) {
             $item->setDefaultSenderAddress($_POST['server_default_sender_address']);
          } else {
             $item->setDefaultSenderAddress('');
          }

       }
       // time (clock pulses)
       if ( isset($_POST['show_time'])
          and !empty($_POST['show_time'])
                  ) {
          if ($_POST['show_time'] == 1) {
             $item->setShowTime();
          } else {
             $item->setNotShowTime();
          }
       }

         // template (private room)
         $template_copy = false;
         if ( $environment->inPrivateRoom()
              and $item->isPrivateRoom()
              and !empty($_POST['template_select'])
              and $_POST['template_select'] != $item->getTemplateID()
            ) {
            $item->setTemplateID($_POST['template_select']);
            $template_copy = true;
         }

         if ( $environment->inServer()
              and $item->isServer()
              and !empty($_POST['server_portal_option'])
            ) {
            $item->setDefaultPortalItemID($_POST['server_portal_option']);
         }

         // URL for PORTAL
         if ( isset($_POST['url']) ) {
            $url = $_POST['url'];
            $url = str_replace('http://','',$url);
            $url = str_replace('https://','',$url);
            if ( strstr($url,'?') ) {
               $url = mb_substr($url,0,strpos($url,'?'));
            }
            $url = str_replace('/commsy.php','',$url);
            $url = str_replace('/index.php','',$url);
            if ( substr($url,strlen($url)-1) == '/' ) {
               $url = substr($url,0,strlen($url)-1);
            }
            $item->setUrl($url);
            unset($url);
         }

                  //******************************************//

                  // Save item
                  if ( $new_flag
                       and !$item->isPortal()
                       and !$item->isServer()
                     ) {
                     $current_portal_item = $environment->getCurrentPortalItem();
                     if ($current_portal_item->withHtmlTextArea()) {
                        $item->setHtmlTextAreaStatus($current_portal_item->getHtmlTextAreaStatus());
                     }
                     unset($current_portal_item);

                     // disable RRS-Feed for new project and community rooms
                     $item->turnRSSOff();
                  }
                  $item->save();
                  $form_view->setItemIsSaved();
                  $is_saved = true;
                  // server: set default authentication at initialize portal
                  if ( $environment->inServer() and $item->isPortal() and $new_flag) {
                     $auth_source_manager = $environment->getAuthSourceManager();
                     $auth_source_item = $auth_source_manager->getNewItem();
                     $auth_source_item->setContextID($item->getItemID());
                     $auth_source_item->setTitle('CommSy');
                     $auth_source_item->setCommSyDefault();
                     $auth_source_item->setAllowAddAccount();
                     $auth_source_item->setAllowChangeUserID();
                     $auth_source_item->setAllowDeleteAccount();
                     $auth_source_item->setAllowChangeUserData();
                     $auth_source_item->setAllowChangePassword();
                     $auth_source_item->setShow();
                     $auth_source_item->setModificatorItem($item->getModificatorItem());
                     $auth_source_item->save();
                     $item->setAuthDefault($auth_source_item->getItemID());
                     $item->save();
                  }

               // template select
               if ( isset($_POST['template_select'])
                    and $_POST['template_select'] > 99
                    and $_POST['template_select'] != 'disabled'
                    and ( $item->isProjectRoom()
                          or $item->isCommunityRoom()
                        )
                    and $new_flag
                  ) {
                  // copy all entries from the template into the new room
                  include_once('include/inc_room_copy.php');
               }

               // template select (private room)
               elseif ($template_copy) {
                  if ( $item->isPrivateRoom()
                       and ( $_POST['template_select'] > 99
                             or $_POST['template_select'] == -1
                           )
                     ) {
                     include_once('include/inc_room_copy_private.php');
                  }
               }

               if ( !isset($_GET['option'])
                    and !($new_flag)
                    and !( ($environment->inCommunityRoom() and $environment->getCurrentModule() == CS_PROJECT_TYPE)
                           or ($environment->inPrivateRoom() and $environment->getCurrentModule() == CS_MYROOM_TYPE)
                         )
                  ) {
                  $params = array();
                  $params['option'] = $_POST['option'];
                  $params['reload'] = 'yes';
                  redirect($environment->getCurrentContextID(), $environment->getCurrentModule(),$environment->getCurrentFunction(), $params);
               }

               if ($item->isProjectRoom() || $item->isCommunityRoom() || $item->isPrivateRoom()) {
                   global $symfonyContainer;
                   $calendarsService = $symfonyContainer->get('commsy.calendars_service');
                   $calendarsService->createCalendar($item, null, null, true);
               }

               // Redirect
               if (!$new_flag or !empty($_GET['type'])) {
                  if ( $environment->getCurrentModule() == CS_PROJECT_TYPE
                        or $environment->getCurrentModule() == CS_MYROOM_TYPE
                     ) {
                     $params = array();
                     $params['iid'] = $item->getItemID();
                     redirect($environment->getCurrentContextID(),$environment->getCurrentModule(),'detail',$params);
                     unset($params);
                  }
               } else {
                  if ($environment->inPortal()) {
                     $params = array();
                     $params['room_id'] = $item->getItemID();
                     redirect($environment->getCurrentContextID(),'home','index',$params);
                     unset($params);
                  } elseif ($environment->inServer()) {
                     redirect($item->getItemID(),'home','index','');
                  } elseif ( $environment->inPrivateRoom() ) {
                     $params = array();
                     $params['iid'] = $item->getItemID();
                     redirect($environment->getCurrentContextID(),'myroom','detail',$params);
                     unset($params);
                  } else {
                     $params = array();
                     $params['iid'] = $item->getItemID();
                     redirect($environment->getCurrentContextID(),CS_PROJECT_TYPE,'detail',$params);
                     unset($params);
                  }
               }
            }
         }

         if (isset($item) and !$item->mayEditRegular($current_user)) {
            $form_view->warnChanger();
            $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = true;
            $params['width'] = 500;
            $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
            unset($params);
            $errorbox->setText($translator->getMessage('COMMON_EDIT_AS_MODERATOR'));
            $page->add($errorbox);
         }
         if ($is_saved){
            $form_view->setItemIsSaved();
         }

         include_once('functions/curl_functions.php');
         $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
         $form_view->setForm($form);
         if ( $environment->inPortal() or $environment->inServer() ) {
            $page->addForm($form_view);
         } else {
            $page->add($form_view);
         }
      }
   }
}
?>