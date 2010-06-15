<?PHP
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Manuel Gonzalez Vazquez, Johannes Schultze
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

// Get the current user
$current_user = $environment->getCurrentUserItem();
$translator = $environment->getTranslationObject();
$is_saved = false;

// get iid
if ( !empty($_GET['iid']) ) {
   $current_iid = $_GET['iid'];
} elseif ( !empty($_POST['iid']) ) {
   $current_iid = $_POST['iid'];
} else{
   $current_context_item = $environment->getCurrentContextItem();
   $current_iid = $current_context_item->getItemID();
}

// hier muss auf den aktuellen Kontext referenziert werden,
// da sonst später diese Einstellung wieder überschrieben wird
// in der commsy.php beim Speichern der Aktivität
$current_context_item = $environment->getCurrentContextItem();
if ($current_iid == $current_context_item->getItemID()) {
   $item = $current_context_item;
} else {
   if ($environment->inProjectRoom() or $environment->inCommunityRoom()) {
      $room_manager = $environment->getRoomManager();
   } elseif ($environment->inPortal()) {
      $room_manager = $environment->getPortalManager();
   }
   $item = $room_manager->getItem($current_iid);
}

if(!$session->issetValue($current_iid.'_add_rss') or empty($_POST)){
   $rss_array = $item->getPortletRSSArray();
   $session->setValue($current_iid.'_add_rss', $rss_array);
}


// Check access rights
if ( isset($item) and !$item->mayEdit($current_user) ) {
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
   $form = $class_factory->getClass(CONFIGURATION_PRIVATEROOM_HOME_FORM,array('environment' => $environment));



   if ( isOption($command, $translator->getMessage('PORTLET_CONFIGURATION_RSS_ADD_BUTTON')) ) {
       $focus_element_onload = 'rss_title';
       $post_rss_ids = array();
       $new_rss_ids = array();
       if ( isset($_POST['rsslist']) ) {
          $post_rss_ids = $_POST['rsslist'];
       }
       if ( $session->issetValue($current_iid.'_add_rss') ) {
          $rss_array = $session->getValue($current_iid.'_add_rss');
       } else {
          $rss_array = array();
       }
       if ( !empty($_POST['rss_title']) and !empty($_POST['rss_adress'])) {
          $temp_array = array();
          $temp_array['title'] = $_POST['rss_title'];
          $temp_array['adress'] = $_POST['rss_adress'];
          $temp_array['display'] = $_POST['rss_display'];
          $rss_array[] = $temp_array;
          $new_rss_ids[] = $_POST['rss_title'].': '.$temp_array['adress'];
       }

       if ( count($rss_array) > 0 ) {
          $session->setValue($current_iid.'_add_rss', $rss_array);
       } else {
          $session->unsetValue($current_iid.'_add_rss');
       }
       $post_rss_ids = array_merge($post_rss_ids, $new_rss_ids);
    }




   // display form
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);

   // Load form data from postvars
   if ( !empty($_POST) ) {
      $values = $_POST;
      if ( isset($post_rss_ids) AND !empty($post_rss_ids) ) {
          $values['rsslist'] = $post_rss_ids;
      }
      if ( isOption($command, $translator->getMessage('PORTLET_CONFIGURATION_RSS_ADD_BUTTON')) ) {
         unset($values['rss_title']);
         unset($values['rss_adress']);
         unset($values['rss_display']);
      }
      if ( isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {
         unset($values['rss_title']);
         unset($values['rss_adress']);
         unset($values['rss_display']);
      }
      $form->setFormPost($values);
   }

    // Load form data from database
   elseif ( isset($item) ) {
      $form->setItem($item);
   }

   if ( !empty($command) and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON'))) {
      if ( $form->check() ) {

         // Set modificator and modification date
         $current_user = $environment->getCurrentUserItem();
         $item->setModificatorItem($current_user);
         $item->setModificationDate(getCurrentDateTimeInMySQL());


         if ( isset($_POST['column_count']) and !empty($_POST['column_count']) ) {
            $item->setPortletColumnCount($_POST['column_count']);
         }

         if ( isset($_POST['new_entry_list']) and !empty($_POST['new_entry_list']) ) {
            $item->setPortletShowNewEntryList();
         }else{
            $item->unsetPortletShowNewEntryList();
         }
         if ( isset($_POST['new_entry_list_count']) and !empty($_POST['new_entry_list_count']) ) {
            $item->setPortletNewEntryListCount($_POST['new_entry_list_count']);
         }

         if ( isset($_POST['active_rooms']) and !empty($_POST['active_rooms']) ) {
            $item->setPortletShowActiveRoomList();
         }else{
            $item->unsetPortletShowActiveRoomList();
         }
         if ( isset($_POST['active_rooms_count']) and !empty($_POST['active_rooms_count']) ) {
            $item->setPortletActiveRoomCount($_POST['active_rooms_count']);
         }

         if ( isset($_POST['search_box']) and !empty($_POST['search_box']) ) {
            $item->setPortletShowSearchBox();
         }else{
            $item->unsetPortletShowSearchBox();
         }

         if ( isset($_POST['dokuverser_box']) and !empty($_POST['dokuverser_box']) ) {
            $item->setPortletShowDokuverserBox();
         }else{
            $item->unsetPortletShowDokuverserBox();
         }

         if ( isset($_POST['buzzword_box']) and !empty($_POST['buzzword_box']) ) {
            $item->setPortletShowBuzzwordBox();
         }else{
            $item->unsetPortletShowBuzzwordBox();
         }

         if ( isset($_POST['configuration_box']) and !empty($_POST['configuration_box']) ) {
            $item->setPortletShowConfigurationBox();
         }else{
            $item->unsetPortletShowConfigurationBox();
         }
         
         if ( isset($_POST['new_item_box']) and !empty($_POST['new_item_box']) ) {
            $item->setPortletShowNewItemBox();
         }else{
            $item->unsetPortletShowNewItemBox();
         }

         if ( isset($_POST['weather_box']) and !empty($_POST['weather_box']) ) {
            $item->setPortletShowWeatherBox();
         }else{
            $item->unsetPortletShowWeatherBox();
         }

         if ( isset($_POST['clock_box']) and !empty($_POST['clock_box']) ) {
            $item->setPortletShowClockBox();
         }else{
            $item->unsetPortletShowClockBox();
         }

         if ( isset($_POST['twitter']) and !empty($_POST['twitter']) ) {
            $item->setPortletShowTwitter();
         }else{
            $item->unsetPortletShowTwitter();
         }
         if ( isset($_POST['twitter_account']) and !empty($_POST['twitter_account']) ) {
            $item->setPortletTwitterAccount($_POST['twitter_account']);
         }


         if ( isset($_POST['youtube']) and !empty($_POST['youtube']) ) {
            $item->setPortletShowYouTube();
         }else{
            $item->unsetPortletShowYouTube();
         }
         if ( isset($_POST['youtube_account']) and !empty($_POST['youtube_account']) ) {
            $item->setPortletYouTubeAccount($_POST['youtube_account']);
         }

         if ( isset($_POST['flickr']) and !empty($_POST['flickr']) ) {
            $item->setPortletShowFlickr();
         }else{
            $item->unsetPortletShowFlickr();
         }
         if ( isset($_POST['flickr_id']) and !empty($_POST['flickr_id']) ) {
            $item->setPortletFlickrID($_POST['flickr_id']);
         }

         if ( isset($_POST['show_rss']) and !empty($_POST['show_rss']) ) {
            $item->setPortletShowRSS();
         }else{
            $item->unsetPortletShowRSS();
         }
         $portlet_rss_array = array();
         if ( isset($_POST['rsslist']) and !empty($_POST['rsslist']) ) {
            $array = $session->getValue($current_iid.'_add_rss');
            $portlet_rss_array = array();
            foreach ($_POST['rsslist'] as $rss_title){
               foreach($array as $rss){
                  if ($rss_title == $rss['title']){
                     $portlet_rss_array[]	= $rss;
                  }
               }
            }
            if ( isset($_POST['rss_title']) and !empty($_POST['rss_title']) and isset($_POST['rss_adress']) and !empty($_POST['rss_adress']) ) {
               $rss = array();
               $rss['title'] = $_POST['rss_title'];
               $rss['adress'] = $_POST['rss_adress'];
               $rss['display'] = '1';
               $portlet_rss_array[]	= $rss;
            }
         }elseif( isset($_POST['rss_title']) and !empty($_POST['rss_title']) and isset($_POST['rss_adress']) and !empty($_POST['rss_adress']) ){
             $rss = array();
             $rss['title'] = $_POST['rss_title'];
             $rss['adress'] = $_POST['rss_adress'];
             $rss['display'] = '1';
             $portlet_rss_array[]	= $rss;
         }
         $item->setPortletRSSArray($portlet_rss_array);


         $item->save();
         $session->unsetValue($current_iid.'_add_rss');

         $form_view->setItemIsSaved();
         $is_saved = true;
      }
   }


   if ($session->issetValue($current_iid.'_add_rss')) {
      $form->setSessionRSSArray($session->getValue($current_iid.'_add_rss'));
   }


   $form->prepareForm();
   $form->loadValues();

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