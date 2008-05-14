<?PHP
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez, Johannes Schultze
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

// Check access rights
if ( isset($item) and !$item->mayEdit($current_user) ) {
   include_once('classes/cs_errorbox_view.php');
   $errorbox = new cs_errorbox_view($environment, true);
   $errorbox->setText(getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
}

elseif ( isset($item) and !$item->withWikiFunctions() ) {
   include_once('classes/cs_errorbox_view.php');
   $errorbox = new cs_errorbox_view($environment, true);
   $errorbox->setText(getMessage('ACCESS_NOT_GRANTED'));
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
   include_once('classes/cs_configuration_wiki_form.php');
   $form = new cs_configuration_wiki_form($environment);
   // display form
   include_once('classes/cs_configuration_form_view.php');
   $form_view = new cs_configuration_form_view($environment);

   // Load form data from postvars
   if ( !empty($_POST) ) {
      $form->setFormPost($_POST);
   }

    // Load form data from database
   elseif ( isset($item) ) {
      $form->setItem($item);
   }

   $skin_array = array();
   global $c_pmwiki_path_file;
   $directory_handle = @opendir($c_pmwiki_path_file.'/pub/skins');
   if ($directory_handle) {
      while (false !== ($dir = readdir($directory_handle))) {
         if ( $dir != 'home'
              and $dir != '...'
              and $dir != '..'
              and $dir != '.'
              and $dir != 'print'
              and $dir != 'svs'
              and $dir != 'CVS'
            ) {
            $skin_array[] = $dir;
         }
      }
   }

   $form->setSkinArray($skin_array);
   $form->prepareForm();
   $form->loadValues();

   // Save item
   if ( !empty($command) and
        isOption($command, getMessage('WIKI_DELETE_BUTTON'))   ) {

      $current_user = $environment->getCurrentUserItem();
      $item->setModificatorItem($current_user);
      $item->setModificationDate(getCurrentDateTimeInMySQL());
      $item->unsetWikiExists();
      $item->setWikiInActive();
      // Save item
      $item->save();

      // delete wiki
      $wiki_manager = $environment->getWikiManager();
      $wiki_manager->deleteWiki($item);
      $form_view->setItemIsSaved();
      $is_saved = true;
   } elseif ( !empty($command) and
        (isOption($command, getMessage('WIKI_SAVE_BUTTON'))
         or isOption($command, getMessage('COMMON_CHANGE_BUTTON')) ) ) {

      if ( $form->check() ) {

         // Set modificator and modification date
         $current_user = $environment->getCurrentUserItem();
         $item->setModificatorItem($current_user);
         $item->setModificationDate(getCurrentDateTimeInMySQL());

         if ( isset($_POST['wikilink']) and !empty($_POST['wikilink']) and $_POST['wikilink'] == 1) {
            $item->setWikiHomeLink();
         } else {
            $item->unsetWikiHomeLink();
         }
         if ( isset($_POST['wikilink2']) and !empty($_POST['wikilink2']) and $_POST['wikilink2'] == 1) {
            $item->setWikiPortalLink();
         } else {
            $item->unsetWikiPortalLink();
         }
         if ( isset($_POST['skin_choice']) and !empty($_POST['skin_choice']) ) {
            $item->setWikiSkin($_POST['skin_choice']);
         }
         if ( isset($_POST['wikititle']) and !empty($_POST['wikititle']) ) {
            $item->setWikiTitle($_POST['wikititle']);
         } else {
            $item->setWikiTitle($item->getTitle());
         }

         if ( isset($_POST['admin']) and !empty($_POST['admin']) ) {
            $item->setWikiAdminPW($_POST['admin']);
         }
         if ( isset($_POST['edit']) and !empty($_POST['edit']) ) {
            $item->setWikiEditPW($_POST['edit']);
         } else {
            $item->setWikiEditPW('');
         }
         if ( isset($_POST['read']) and !empty($_POST['read']) ) {
            $item->setWikiReadPW($_POST['read']);
         } else {
            $item->setWikiReadPW('');
         }
         if ( isset($_POST['show_login_box']) ) {
            $item->setWikiShowCommSyLogin();
         } else {
            $item->unsetWikiShowCommSyLogin();
         }
         
         //  new features
         if ( isset($_POST['enable_fckeditor']) ) {
            $item->setWikiEnableFCKEditor();
         } else {
            $item->unsetWikiEnableFCKEditor();
         }
         
         if ( isset($_POST['enable_sitemap']) ) {
            $item->setWikiEnableSitemap();
         } else {
            $item->unsetWikiEnableSitemap();
         }
         
         if ( isset($_POST['enable_statistic']) ) {
            $item->setWikiEnableStatistic();
         } else {
            $item->unsetWikiEnableStatistic();
         }
         
         if ( isset($_POST['enable_search']) ) {
            $item->setWikiEnableSearch();
         } else {
            $item->unsetWikiEnableSearch();
         }
         
         if ( isset($_POST['enable_rss']) ) {
            $item->setWikiEnableRss();
         } else {
            $item->unsetWikiEnableRss();
         }
         
         if ( isset($_POST['enable_swf']) ) {
            $item->setWikiEnableSwf();
         } else {
            $item->unsetWikiEnableSwf();
         }
         
         if ( isset($_POST['enable_wmplayer']) ) {
            $item->setWikiEnableWmplayer();
         } else {
            $item->unsetWikiEnableWmplayer();
         }
         
         if ( isset($_POST['enable_quicktime']) ) {
            $item->setWikiEnableQuicktime();
         } else {
            $item->unsetWikiEnableQuicktime();
         }
         
         if ( isset($_POST['enable_youtube_google_vimeo']) ) {
            $item->setWikiEnableYoutubeGoogleVimeo();
         } else {
            $item->unsetWikiEnableYoutubeGoogleVimeo();
         }
         
         // /new features

         // section edit
         if ( isset($_POST['wiki_section_edit']) ) {
            $item->setWikiWithSectionEdit();
         } else {
            $item->setWikiWithoutSectionEdit();
         }
         if ( isset($_POST['wiki_section_edit_header']) ) {
            $item->setWikiWithHeaderForSectionEdit();
         } else {
            $item->setWikiWithoutHeaderForSectionEdit();
         }

         $item->setWikiExists();
         $item->setWikiActive();

         // Save item
         $item->save();

         // create new wiki
         $wiki_manager = $environment->getWikiManager();
         $wiki_manager->createWiki($item);
         $form_view->setItemIsSaved();
         $is_saved = true;
      }
   }

   if (isset($item) and !$item->mayEditRegular($current_user)) {
      $form_view->warnChanger();
      include_once('classes/cs_errorbox_view.php');
      $errorbox = new cs_errorbox_view($environment, true, 500);
      $errorbox->setText(getMessage('COMMON_EDIT_AS_MODERATOR'));
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