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

include_once('functions/curl_functions.php');

// Get the translator object
$translator = $environment->getTranslationObject();

if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}
$is_saved = false;

$context_item = $environment->getCurrentContextItem();
$_GET['iid'] = $context_item->getItemID();
include_once('include/inc_delete_entry.php');

// Find out what to do
if ( isset($_POST['option']) and $_POST['option'] == $translator->getMessage('COMMON_DELETE_ROOM')) {
   $_GET['action'] = 'delete';
}
if ( isset($_GET['action']) and $_GET['action'] == 'delete' ) {
   $current_user_item = $environment->getCurrentUserItem();
   if ( !empty($context_item) ) {
      if ( $current_user_item->isModerator()
           or ( isset($context_item)
                and $context_item->isModeratorByUserID($current_user_item->getUserID(),$current_user_item->getAuthSource())
              )
         ) {
         $params = $environment->getCurrentParameterArray();
         $page->addDeleteBox(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),$params));
      }
   }
   unset($current_user_item);
}

// Check access rights
if ($current_user->isGuest()) {
   if (!$context_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
      $params['cid'] = $context_item->getItemId();
      redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
} elseif ( !$context_item->isOpen() and !$context_item->isTemplate() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);
   $command = 'error';
} elseif (!$current_user->isModerator()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
   $command = 'error';
}

if ($command != 'error') { // only if user is allowed to edit colors

   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(CONFIGURATION_ROOM_OPTIONS_FORM,$class_params);
   unset($class_params);
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);

   if ( isOption($command, $translator->getMessage('PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON')) ) {
      $post_community_room_array = array();
      $focus_element_onload = 'communityrooms';
      $post_community_room_ids = array();
      $new_community_room_ids = array();
      $new_buzzword_ids = array();
      $current_iid = $context_item->getItemID();
      $post_community_room_array = array();
      $community_old_room_array[] = array();
      if ( isset($_POST['communityroomlist']) ) {
         $post_community_room_ids = $_POST['communityroomlist'];
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
      $post_community_room_ids = array_merge($post_community_room_ids, $new_community_room_ids);
      foreach($post_community_room_ids as $ids){
         $community_manager = $environment->getCommunityManager();
         $community_manager->reset();
         $community_item = $community_manager->getItem($ids);
         $temp_array['name'] = $community_item->getTitle();
         $temp_array['id'] = $community_item->getItemID();
         $post_community_room_array[] = $temp_array;
      }
   }

   if ( !empty($_POST)) {
      if ( !empty($_POST['color_choice']) and $_POST['color_choice']=='-1'){
         $_POST['color_choice'] = '';
      }
      $values = $_POST;
      if ( isset($post_community_room_ids) AND !empty($post_community_room_ids) ) {
         $values['communityroomlist'] = $post_community_room_ids;
         $form->setSessionCommunityRoomArray($post_community_room_array);
      }
      $form->setFormPost($values);
   } elseif ( isset($context_item) ) {
      $form->setItem($context_item);
   }

   $form->prepareForm();
   $form->loadValues();

   if ( !empty($command) and isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
     redirect($environment->getCurrentContextID(),'configuration', 'index', '');
   }
   // Save item
   elseif ( !empty($command) and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {
      $correct = $form->check();
      if ($correct){
         if ( isset($_POST['title']) ) {
            $title = $_POST['title'];
            if ( $title == $translator->getMessage('COMMON_PRIVATROOM')
                 or !empty($_POST['title_reset'])
               ) {
               $title = 'PRIVATEROOM';
            }
            $context_item->setTitle($title);
         }
         if ( (isset($_POST['show_title']) and !empty($_POST['show_title'])) ) {
            $context_item->setShowTitle();
         } else {
            $context_item->setNotShowTitle();
         }
         if (isset($_POST['time2']) and !empty($_POST['time2'])){
            if (in_array('cont',$_POST['time2'])) {
               $context_item->setContinuous();
            } else {
               $context_item->setTimeListByID($_POST['time2']);
               $context_item->setNotContinuous();
            }
         } elseif ($context_item->isProjectRoom()) {
            $context_item->setTimeListByID(array());
            $context_item->setNotContinuous();
         }
         
         // logo: save and/or delete current logo
         if ( isset($_POST['delete_logo']) ) {
            $disc_manager = $environment->getDiscManager();
            if ( $disc_manager->existsFile($context_item->getLogoFilename()) ) {
               $disc_manager->unlinkFile($context_item->getLogoFilename());
            }
            $context_item->setLogoFilename('');
         }
         if ( !empty($_FILES['logo']['name']) ) {
            $logo = $context_item->getLogoFilename();
            $disc_manager = $environment->getDiscManager();
            if ( !empty ($logo) ) {
               if ( $disc_manager->existsFile($context_item->getLogoFilename()) ) {
                  $disc_manager->unlinkFile($context_item->getLogoFilename());
               }
               $context_item->setLogoFilename('');
            }
            $filename = 'cid'.$environment->getCurrentContextID().'_logo_'.$_FILES['logo']['name'];
            $disc_manager->copyFile($_FILES['logo']['tmp_name'],$filename,true);
            $context_item->setLogoFilename($filename);
         }


         if ($context_item->isProjectRoom()){
            $community_room_array = array();
            if ( isset($_POST['communityroomlist']) ) {
               $community_room_array = $_POST['communityroomlist'];
            }
            if ( isset($_POST['communityrooms']) and !in_array($_POST['communityrooms'],$community_room_array) and $_POST['communityrooms'] > 0) {
               $community_room_array[] = $_POST['communityrooms'];
            }
            $context_item->setCommunityListByID($community_room_array);
         }

         if ($context_item->isCommunityRoom()){
            // Room association
            if ( isset($_POST['room_assignment']) ) {
               if ($_POST['room_assignment'] == 'open') {
                  $context_item->setAssignmentOpenForAnybody();
               } elseif ($_POST['room_assignment'] == 'closed') {
                  $context_item->setAssignmentOnlyOpenForRoomMembers();
               }
            }
         }
         if ( isset($_POST['language']) ) {
            $language = $_POST['language'];
            if ($_POST['language'] == 'enabled') {
               $language = 'user';
            }
            $old_language = $context_item->getLanguage();
            if ( $old_language != $language ) {
               $context_item->setLanguage($language);
               $environment->unsetSelectedLanguage();
            }
         }
         $languages = $environment->getAvailableLanguageArray();
         $description = $context_item->getDescription();
         if (!empty($_POST['description'])) {
            $context_item->setDescription($_POST['description']);
         }else{
            $context_item->setDescription('');
         }

         if ( !empty($_POST['rss'])) {
            if ($_POST['rss'] == 'yes') {
               $context_item->turnRSSOn();
            }elseif ($_POST['rss'] == 'no') {
               $context_item->turnRSSOff();
            }
         }

         if ($context_item->isPrivateRoom()) {
	         if ( (isset($_POST['email_to_commsy']) and !empty($_POST['email_to_commsy'])) ) {
	            $context_item->setEmailToCommSy();
	         } else {
	            $context_item->unsetEmailToCommSy();
	         }
            if ( (isset($_POST['email_to_commsy_secret']) and !empty($_POST['email_to_commsy_secret'])) ) {
	            $context_item->setEmailToCommSySecret($_POST['email_to_commsy_secret']);
	         } else {
	            $context_item->setEmailToCommSySecret('');
	         }
         }
         
         $redirect = false;

         // save room_item
         $context_item->save();
         if ( $redirect ) {
            redirect($environment->getCurrentContextID(),'configuration','index');
         }

         $context_item->generateLayoutImages();

         $environment->setCurrentContextItem($context_item);
         $class_params= array();
         $class_params['environment'] = $environment;
         $form = $class_factory->getClass(CONFIGURATION_ROOM_OPTIONS_FORM,$class_params);
         unset($class_params);
         $form->setItem($context_item);
         $form->prepareForm();
         $form->loadValues();
         $form_view->setItemIsSaved();
         $is_saved = true;
      }
   }


   $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
   $form_view->setForm($form);
   if ( $environment->inPortal() or $environment->inServer() ){
      $page->addForm($form_view);
   }else{
      $page->add($form_view);
   }
}
function generate_colour_gradient($height, $rgb){
    $image = imagecreate(1, $height);

    $rgb = str_replace('#', '', $rgb);

    $r = hexdec(mb_substr($rgb, 0, 2));
    $g = hexdec(mb_substr($rgb, 2, 2));
    $b = hexdec(mb_substr($rgb, 4, 2));

    $border = ImageColorAllocate($image,$r,$g,$b);

    for ($i=0; $i<($height/2); $i++) {
        $line = ImageColorAllocate($image,$r-(($r/255)*($i*5)),$g-(($g/255)*($i*5)),$b-(($b/255)*($i*5)));
        imageline($image, 0, $i, 0, $i, $line);
        imageline($image, 0, (($height-1)-$i), 500, (($height-1)-$i), $line);
    }
    return $image;
}
?>