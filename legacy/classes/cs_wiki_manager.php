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

include_once('functions/text_functions.php');

/** date functions are needed for method _newVersion()
 */
include_once('functions/date_functions.php');

/**
 * Wiki Manager
 */

class cs_wiki_manager extends cs_manager {

  /** update an wiki - internal, do not use -> use method save
    * this method updates a wiki
    *
    * @param string
    */
  function _update_edit_password ($password) {

  }

  /** update an wiki - internal, do not use -> use method save
    * this method updates a wiki
    *
    * @param string
    */
  function _update_admin_password ($password) {

  }

  /** update an wiki - internal, do not use -> use method save
    * this method updates a wiki
    *
    * @param string
    */
  function _update_skin ($skin_name) {

  }

  /** update an wiki - internal, do not use -> use method save
    * this method updates a wiki
    *
    * @param string
    */
  function _update_title ($wiki_title) {

  }

  /** create a wiki - internal, do not use -> use method save
    * this method creates a wiki
    *
    */
   function createWiki ($item) {
      $old_dir = getcwd();
      global $c_pmwiki_path_file;
      chdir($c_pmwiki_path_file);

      $directory_handle = @opendir('wikis');
      if (!$directory_handle) {
         mkdir('wikis');
      }
      chdir('wikis');


      if ($item->isPortal()){
         $directory_handle = @opendir($item->getItemID());
         if (!$directory_handle) {
            mkdir($item->getItemID());
         } else {
            closedir($directory_handle);
         }
      }else{
         $directory_handle = @opendir($item->getContextID());
         if (!$directory_handle) {
            mkdir($item->getContextID());
         } else {
            closedir($directory_handle);
         }
         chdir($item->getContextID());
         $directory_handle2 = @opendir($item->getItemID());
         if (!$directory_handle2) {
            mkdir($item->getItemID());
         } else {
            closedir($directory_handle2);
         }
      }

      chdir($item->getItemID());
      if ( !file_exists('index.php') ) {
         global $c_commsy_path_file;
         copy($c_commsy_path_file.'/etc/pmwiki/wiki_index.php','index.php');
      }
      global $c_pmwiki_absolute_path_file;
      $str = "<?php include('".$c_pmwiki_absolute_path_file."/pmwiki.php');?>";
      file_put_contents('index.php',$str);

      if ( $item->isPortal() ) {
         $directory_handle = @opendir('wiki.d');
         if (!$directory_handle) {
            mkdir('wiki.d');
            chdir('wiki.d');
            copy($c_commsy_path_file.'/etc/pmwiki/Main.NewWiki','Main.NewWiki');
            copy($c_commsy_path_file.'/etc/pmwiki/PmWikiDe.SideBar','PmWikiDe.SideBar');
            copy($c_commsy_path_file.'/etc/pmwiki/Site.SideBar','Site.SideBar');
            copy($c_commsy_path_file.'/etc/pmwiki/Main.HomePage','Main.HomePage');
            copy($c_commsy_path_file.'/etc/pmwiki/Main.WikiList','Main.WikiList');
            chdir('..');
         }
         $directory_handle = @opendir('phpinc');
         if (!$directory_handle) {
            mkdir('phpinc');
            chdir('phpinc');
            copy($c_commsy_path_file.'/etc/pmwiki/installwiki.php','installwiki.php');
            copy($c_commsy_path_file.'/etc/pmwiki/wikilist.php','wikilist.php');
            copy($c_commsy_path_file.'/etc/pmwiki/newwikilist.php','newwikilist.php');
            chdir('..');
         }
      } else {
         $directory_handle = @opendir('wiki.d');
         if (!$directory_handle) {
            mkdir('wiki.d');
         }
      }
      $directory_handle = @opendir('local');
      if (!$directory_handle) {
         mkdir('local');
      } else {
         closedir($directory_handle);
      }

      chdir('local');

      if ( !file_exists('config.php') ) {
         global $c_commsy_path_file;
         copy($c_commsy_path_file.'/etc/pmwiki/wiki_config.php','config.php');
      }

      $str  = '<?php'.LF;

      $str .= '$COMMSY_ROOM_ID = "'.$item->getItemID().'";'.LF;
      $str .= 'session_name(\'SESSID-\'.$COMMSY_ROOM_ID);'.LF;
      if ( $item->isPortal() ) {
         $str .= '$COMMSY_PORTAL_ID = "'.$item->getItemID().'";'.LF;
      } else {
         $str .= '$COMMSY_PORTAL_ID = "'.$item->getContextID().'";'.LF;
      }
      global $c_commsy_url_path;
      global $c_commsy_domain;
      $str .= '$PATH_TO_COMMSY_SERVER = "'.$c_commsy_domain.$c_commsy_url_path.'";'.LF;
      $str .= '$COMMSY_SKIN = "'.$item->getWikiSkin().'";'.LF;
      $str .= '$COMMSY_EDIT_PASSWD = "'.$item->getWikiEditPW().'";'.LF;
      $str .= '$COMMSY_ADMIN_PASSWD = "'.$item->getWikiAdminPW().'";'.LF;
      $str .= '$COMMSY_UPLOAD_PASSWD = "'.$item->getWikiEditPW().'";'.LF;
      $str .= '$COMMSY_READ_PASSWD = "'.$item->getWikiReadPW().'";'.LF;
      $str .= '$COMMSY_WIKI_TITLE = "'.addslashes($item->getWikiTitle()).'";'.LF;
      if($item->WikiShowCommSyLogin() == "1"){
        $str .= '$SHOW_COMMSY_LOGIN = "1";'.LF;
      } else {
        $str .= '$SHOW_COMMSY_LOGIN = "0";'.LF;
      }
      $language = $item->getLanguage();
      if (!empty($language) and mb_strtoupper($language, 'UTF-8')!='USER'){
         $str .= '$COMMSY_LANGUAGE = "'.mb_strtolower($item->getLanguage(), 'UTF-8').'";'.LF;
      }

      // Clean-URLs
      $str .= LF.'global $FarmDirUrl;'.LF;
      $str .= '$EnablePathInfo = 1;'.LF;
      $str .= '$ScriptUrl = $FarmDirUrl."/wikis/".$COMMSY_PORTAL_ID."/".$COMMSY_ROOM_ID;'.LF;

      $str .= LF.'global $FarmD;'.LF.LF;
      if ( $item->isPortal() ) {
         $str .= '@require_once("$FarmD/cookbook/phpinc-markup.php");'.LF;
      }

      // mail send mechanism (change to wiki config)
      $str .= '@include_once("$FarmD/cookbook/sendmail/sendmail.php");'.LF;

      // date time format
      $str .= '@include_once("$FarmD/cookbook/EZDate.php");'.LF;
      $str .= 'if (function_exists(\'EZDate\')) {'.LF;
      $str .= '   $WITH_EZDATE = true;'.LF;
      $str .= '}'.LF;

      // section edit
      if ( $item->wikiWithSectionEdit() ) {
         $str .= '$group_temp = PageVar($pagename, \'$Group\');'.LF;
         $str .= 'if(!file_exists($FarmD . \'/wikis/\' . $COMMSY_PORTAL_ID . \'/\' . $COMMSY_ROOM_ID . \'/wiki.d/\' . $group_temp  . \'.ForumConfig\')){'.LF;
         if ( !$item->wikiWithHeaderForSectionEdit() ) {
            $str .= '$SectionEditWithoutHeaders = true;'.LF;
         }
         $str .= '@include_once("$FarmD/cookbook/sectionedit.php");'.LF;
         $str .= '}'.LF.LF;
      }

      $str .= '$WITH_DIFF = true;'.LF;
      $str .= '$WITH_PRINT = true;'.LF;


      // Additional features - not activated by default.
      // modify in comsy_config.php to activate.
      if ( $item->WikiEnableFCKEditor() == "1" ) {
         $str .= LF.'$SHOW_FCKEDITOR = "1";'.LF;
      }

      if ( $item->WikiEnableSearch() == "1" ) {
         $str .= '$SHOW_SEARCH = "1";'.LF;
      }

      if ( $item->WikiEnableSitemap() == "1" ) {
         $str .= '$SHOW_SITEMAP = "1";'.LF.LF;
      }

      if ( $item->WikiEnableStatistic() == "1" ) {
         $str .= 'global $WorkDir;'.LF;
         $str .= "@include_once(\$FarmD.'/cookbook/totalcounter.php');".LF;
         $str .= "@include_once(\$FarmD.'/cookbook/totalcounterlink.php');".LF;
         $str .= '$SHOW_STATISTIC_ACTION = "1";'.LF;
         $str .= '$TotalCounterTimeBins["LastYears"]["max"] = 5;'.LF.LF;
      }

      if ( $item->WikiEnableRss() == "1" ) {
         $str .= '$EnableRssLink  = "1";'.LF;
         $str .= '$EnableSitewideFeed = 1;'.LF;
         $str .= '$EnableAtomLink = 0;'.LF;
         $str .= '@include_once("$FarmD/cookbook/feedlinks.php");'.LF;
         $str .= "\$FeedFmt['rss']['item']['title'] = '{\$Group} / {\$Title} : {\$LastModified}';".LF;
//        $str .= '$change = "Auf der Seite &lt;b&gt;{\$Title}&lt;/br&gt; hat es eine Änderung gegeben! &lt;br&gt;&lt;br&gt;";'.LF;
         $str .= "\$FeedFmt['rss']['item']['description'] = \$change . ' {\$LastModifiedSummary} - ge&auml;ndert von: {\$LastModifiedBy}';".LF.LF;
      }

      if ( $item->WikiEnableCalendar() == "1" ) {
         $str .= '@include_once("$FarmD/cookbook/wikilog.php");'.LF;
         if($this->_environment->getCurrentContextItem()->getLanguage() == "de"){
            $str .= '@include_once("$FarmD/cookbook/wikilog-i18n-de.php");'.LF;
         } else if ($this->_environment->getCurrentContextItem()->getLanguage() == "en") {
            $str .= '@include_once("$FarmD/cookbook/wikilog-i18n-en.php");'.LF;
         }
         $str .= '$SHOW_CALENDAR = "1";'.LF.LF;
      }

//      if ( $item->WikiEnableNotice() == "1" ) {
         $str .= '$GUIButtons["stickyNote"] = array(700, "(:note Comment: |", ":)\\n", "$[Text]",'.LF;
         $str .= '"$GUIButtonDirUrlFmt/sticky.gif\"$[Yellow Sticky Note]\"");'.LF;
         $str .= '@include_once("$FarmD/cookbook/postitnotes.php");'.LF;
         $str .= '$SHOW_NOTICE = "1";'.LF.LF;
//      }

      if ( $item->WikiEnableGallery() == "1" ) {
         $str .= '@include_once("$FarmD/cookbook/gallery.php");'.LF;
         $str .= '$SHOW_GALLERY = "1";'.LF.LF;
      }

      if ( $item->WikiEnablePdf() == "1" ) {
         $str .= '@include_once("$FarmD/cookbook/pmwiki2pdf/pmwiki2pdf.php");'.LF;
         $str .= '@include_once("$FarmD/cookbook/pmwiki2pdflink.php");'.LF;
         $str .= '$SHOW_PDF = "1";'.LF.LF;
      }

//      if ( $item->WikiEnableSwf() == "1" ) {
         $str .= '@include_once("$FarmD/cookbook/swf.php");'.LF;
         $str .= '$ENABLE_SWF = "1";'.LF.LF;
//      }

//      if ( $item->WikiEnableWmplayer() == "1" ) {
         $str .= '@include_once("$FarmD/cookbook/wmplayer.php");'.LF;
         $str .= "\$UploadExts['wma'] = 'audio/wma';".LF;
         $str .= "\$UploadExts['wmv'] = 'video/wmv';".LF;
         $str .= '$ENABLE_WMPLAYER = "1";'.LF.LF;
//      }

//      if ( $item->WikiEnableQuicktime() == "1" ) {
         $str .= '@include_once("$FarmD/cookbook/quicktime.php");'.LF;
         $str .= '$ENABLE_QUICKTIME = "1";'.LF.LF;
//      }

//      if ( $item->WikiEnableYoutubeGoogleVimeo() == "1" ) {
         $str .= '@include_once("$FarmD/cookbook/swf-sites2.php");'.LF;
         $str .= '$ENABLE_YOUTUBEGOOGLEVIMEO = "1";'.LF.LF;
//      }

      if ( $item->WikiEnableDiscussion() == "1" ) {
         if($item->getWikiDiscussionArray()){
            chdir('..');
            $directory_handle = @opendir('wiki.d');
            if (!$directory_handle) {
               mkdir('wiki.d');
             }
            $firstForum = true; // needed for generated list of forums


           $directory_handle = @opendir('uploads');
           if (!$directory_handle) {
              mkdir('uploads');
            }
            chdir('uploads');
            $directory_handle = @opendir('Profiles');
           if (!$directory_handle) {
              mkdir('Profiles');
              global $c_commsy_path_file;
              copy($c_commsy_path_file.'/etc/pmwiki/nobody_m.gif','Profiles/nobody_m.gif');
            }
            chdir('..');

            // alle anderen user...

            foreach($item->getWikiDiscussionArray() as $discussion){
               global $c_commsy_path_file;

                // Titel fuer Wiki-Gruppe vorbereiten
                $titleForForm = $discussion;
//                $discussionArray = explode (' ', $discussion);
//                for ($index = 0; $index < sizeof($discussionArray); $index++) {
//                    $discussionArray[$index] = str_replace("ä", "ae", $discussionArray[$index]);
//                    $discussionArray[$index] = str_replace("Ä", "Ae", $discussionArray[$index]);
//                    $discussionArray[$index] = str_replace("ö", "oe", $discussionArray[$index]);
//                    $discussionArray[$index] = str_replace("Ö", "Oe", $discussionArray[$index]);
//                    $discussionArray[$index] = str_replace("ü", "ue", $discussionArray[$index]);
//                    $discussionArray[$index] = str_replace("Ü", "Ue", $discussionArray[$index]);
//                    $discussionArray[$index] = str_replace("ß", "ss", $discussionArray[$index]);
//                    $first_letter = substr($discussionArray[$index], 0, 1);
//                    $rest = substr($discussionArray[$index], 1);
//                    $first_letter = strtoupper($first_letter);
//                    $discussionArray[$index] = $first_letter . $rest;
//                }
//                $discussion = implode('',$discussionArray);

                $discussion = $this->getDiscussionWikiName($titleForForm);

                // check delete

                 $keep_discussion = false;
                 if(isset($_POST['enable_discussion_discussions'])){
                     foreach($_POST['enable_discussion_discussions'] as $discussionKeep){
                        if(($this->getDiscussionWikiName($discussionKeep) == $discussion) or ($titleForForm == $_POST['new_discussion'])) {
                            $keep_discussion = true;
                        }
                     }
                 } else {
                    if ( !empty($_POST['new_discussion'])
                         and $titleForForm == $_POST['new_discussion']
                       ) {
                       $keep_discussion = true;
                    }
                 }

                if($keep_discussion){
                    // Site.Forum generieren
                    if(!file_exists('wiki.d/Site.Forum')){
                        copy($c_commsy_path_file.'/etc/pmwiki/Site.Forum','wiki.d/Site.Forum');
                    }
                    $file_forum_contents = file_get_contents('wiki.d/Site.Forum');
                    $file_forum_contents_array = explode("\n", $file_forum_contents);
                    for ($index = 0; $index < sizeof($file_forum_contents_array); $index++) {
                       if(stripos($file_forum_contents_array[$index], 'text=Foren:') !== false){
                                if($firstForum){
                                    $file_forum_contents_array[$index] = 'text=Foren:';
                                    $firstForum = false;
                                }
                                $file_forum_contents_array[$index] .= '%0a*[['. $discussion . 'Forum.' . $discussion . 'Forum' . '|' . $titleForForm . ']]';
                            }
                    }
                    $file_forum_contents = implode("\n", $file_forum_contents_array);
                    file_put_contents('wiki.d/Site.Forum', $file_forum_contents);

                   $str .= '$FoxPagePermissions[\'' . $discussion . 'Forum.*\'] = \'all\';'.LF;
                   $str .= '$FoxPagePermissions[\'' . $discussion . 'Forum.*\'] = \'add,copy\';'.LF;


                   if(!file_exists('wiki.d/' . $discussion . 'Forum.CreateNewTopic')){
                        copy($c_commsy_path_file.'/etc/pmwiki/Forum.CreateNewTopic','wiki.d/' . $discussion . 'Forum.CreateNewTopic');
                        $file_contents = file_get_contents('wiki.d/' . $discussion . 'Forum.CreateNewTopic');
                        $file_contents =  $file_contents . "\n" . 'title='. $titleForForm;
                        file_put_contents('wiki.d/' . $discussion . 'Forum.CreateNewTopic', $file_contents);
                    }
                    if(!file_exists('wiki.d/' . $discussion . 'Forum.' . $discussion . 'Forum')){
                        copy($c_commsy_path_file.'/etc/pmwiki/Forum.Forum','wiki.d/' . $discussion . 'Forum.' . $discussion . 'Forum');
                        $file_contents = file_get_contents('wiki.d/' . $discussion . 'Forum.' . $discussion . 'Forum');
                        $file_contents_array = explode("\n", $file_contents);
                        for ($index = 0; $index < sizeof($file_contents_array); $index++) {
                            if(stripos($file_contents_array[$index], 'name=Forum.Forum') !== false){
                                $file_contents_array[$index] = 'name=' . $discussion . 'Forum.' . $discussion . 'Forum';
                            }
                        }
                        $file_contents = implode("\n", $file_contents_array);
                        $file_contents =  $file_contents . "\n" . 'title='. $titleForForm;
                        file_put_contents('wiki.d/' . $discussion . 'Forum.' . $discussion . 'Forum', $file_contents);
                    }
                    if(!file_exists('wiki.d/' . $discussion . 'Forum.ForumConfig')){
                        copy($c_commsy_path_file.'/etc/pmwiki/Forum.ForumConfig','wiki.d/' . $discussion . 'Forum.ForumConfig');
                        $file_contents = file_get_contents('wiki.d/' . $discussion . 'Forum.ForumConfig');
                        $file_contents =  $file_contents . "\n" . 'title='. $titleForForm;
                        file_put_contents('wiki.d/' . $discussion . 'Forum.ForumConfig', $file_contents);
                    }
                    if(!file_exists('wiki.d/' . $discussion . 'Forum.Willkommen')){
                        copy($c_commsy_path_file.'/etc/pmwiki/Forum.Willkommen','wiki.d/' . $discussion . 'Forum.Willkommen');
                        $file_contents = file_get_contents('wiki.d/' . $discussion . 'Forum.Willkommen');
                        $file_contents =  $file_contents . "\n" . 'title='. $titleForForm;
                        file_put_contents('wiki.d/' . $discussion . 'Forum.Willkommen', $file_contents);
                    }

                    if ( $item->WikiEnableDiscussionNotification() == "1" ) {

                        if ( $item->WikiEnableDiscussionNotificationGroups() == "1" ) {
                            // CommSy-Gruppen erstellen, zuordnung erfolgt über diese Gruppen.
                            // Die Notification-Listen werden erst angelegt, wenn sich Benutzer
                            // in die Gruppen eintragen.
//                            $translator = $this->_environment->getTranslationObject();
//                            $this->updateGroupNotificationFiles();
//                            $tempDir = getcwd();
//                            chdir($old_dir);
//                            $group_manager = $this->_environment->getGroupManager();
//                            $group_manager->reset();
//                            $group_manager->select();
//                            $group_list = $group_manager->get();
//                            $group_array = $group_list->to_array();
//                            $group_existing = false;
//                            foreach($group_array as $group){
//                                if($group->getName() == $translator->getMessage('WIKI_DISCUSSION_GROUP_TITLE') . ' ' . $titleForForm){
//                                    $group_existing = true;
//                                }
//                            }
//                            if(!$group_existing){
//                                $new_group = $group_manager->getNewItem();
//                                $new_group->setName($translator->getMessage('WIKI_DISCUSSION_GROUP_TITLE') . ' ' . $titleForForm);
//                                $currentUser = $this->_environment->getCurrentUser();
//                                $new_group->setCreatorItem($currentUser);
//                                $new_group->save();
//                            }
//                            chdir($tempDir);
                            $str .= '$COMMSY_DISCUSSION_NOTIFICATION_GROUPS = "1";'.LF;
                        } else {
//                            $this->deleteAllDiscussionGroups();
//                            $tempDir = getcwd();
//                            chdir($old_dir);
//                            $user_manager = $this->_environment->getUserManager();
//                            $user_manager->reset();
//                            $user_manager->setContextLimit($this->_environment->getCurrentContextID());
//                            $user_manager->setUserLimit();
//                            $user_manager->select();
//                            $user_list = $user_manager->get();
//                            $user_array = $user_list->to_array();
//                            $this->updateNotificationFile($discussion, $user_array);
//                            chdir($tempDir);
                        }
                        $str .= '$COMMSY_DISCUSSION_NOTIFICATION = "1";'.LF;
                        $str .= '@include_once("$FarmD/cookbook/foxnotify.php");'.LF;
                        $this->updateNotification();
                    } else {
//                        $this->updateGroupNotificationFiles();
                        global $c_use_soap_for_wiki;
                        if(!$c_use_soap_for_wiki){
                           $this->removeNotification();
                        } else {
                           $this->removeNotification_soap();
                        }
                    }

                    // Profile der vorhandenen CommSy-Benutzer anlegen
                    $tempDir = getcwd();
                    chdir($old_dir);
                    $user_manager = $this->_environment->getUserManager();
                    $user_manager->reset();
                    $user_manager->setContextLimit($this->_environment->getCurrentContextID());
                    $user_manager->setUserLimit();
                    //$user_manager->setGroupLimit($selgroup);
                    $user_manager->select();
                    $user_list = $user_manager->get();
                    $user_array = $user_list->to_array();
                    foreach($user_array as $user){
                          $this->updateWikiProfileFile($user);
                    }
                    chdir($tempDir);
                } else {
                  global $c_use_soap_for_wiki;
                  if(!$c_use_soap_for_wiki){
                     $this->deleteDiscussion($titleForForm);
                  } else {
                     $this->deleteDiscussion_soap($titleForForm);
                  }
                    $item->WikiRemoveDiscussion($titleForForm);
                }
            }

            chdir('local');
        }
        $str .= '$COMMSY_DISCUSSION = "1";'.LF.LF;
      }

      // LI-Pedia Erweiterungen
        $str .= '# FoxCommentBox'.LF;
        $str .= '$FoxPagePermissions[\'*.*\'] = \'all\';'.LF.LF;
        //$str .= '$EnableCommentPostCaptchaRequired = true;'.LF.LF;

//      if ( $item->WikiEnableRater() == "1" ) {
         $str .= '# Rater'.LF;
         $str .= '$Rater[\'star_color\'] = "yellow";'.LF;
         $str .= '$Rater[\'reverse_buttons\'] = 0; '.LF;
         $str .= '$Rater[\'button_text\'] = "Bewerten";'.LF;
         $str .= '$Rater[\'zero_choice\'] = 0;'.LF;
         $str .= '$Rater[\'header\'] = "";'.LF;
         $str .= '$Rater[\'5star\'] = "Ausgezeichnet";'.LF;
         $str .= '$Rater[\'4star\'] = "Sehr gut";'.LF;
         $str .= '$Rater[\'3star\'] = "Gut";'.LF;
         $str .= '$Rater[\'2star\'] = "Ausreichend";'.LF;
         $str .= '$Rater[\'1star\'] = "Schlecht";'.LF;
         $str .= '$Rater[\'0star\'] = "";'.LF;
         $str .= '$Rater[\'ip_voting_restrictions\'] = false; # if you want to allow multiple votes from the same IP'.LF;
         $str .= '$Rater[\'ip_vote_qty\'] = 3; # if you want to allow as many as 3 votes from the same IP'.LF;
         $str .= '$Rater[\'stats\'] = "({votes} Stimmen)";'.LF;
         $str .= '$Rater[\'thankyou_msg\'] = "[Danke f&uuml;r die Bewertung!]";'.LF;
         $str .= '$Rater[\'not_selected_msg\'] = "[Sie haben keine Bewertung ausgew&auml;hlt.]";'.LF;
         $str .= '$Rater[\'already_rated_msg\'] = "[Sie haben diese Seite schon bewertet. Sie durften nur] ".$Rater[\'ip_vote_qty\']." [Stimmen abgeben].";'.LF;
         $str .= '$Rater[\'notauthorised_msg\'] = "[Sie d&uuml;rfen diese Seite nicht bewerten!]";'.LF;
         $str .= '$Rater[\'not_rated\'] = "[Noch nicht bewertet]";'.LF;
         $str .= 'include_once($FarmD.\'/cookbook/rater.php\');'.LF;
         $str .= '$ENABLE_RATER = "1";'.LF.LF;
//      }

      if ( $item->WikiEnableListCategories() == "1" ) {
         $str .= '# Categories'.LF;
         $str .= 'include_once($FarmD.\'/cookbook/listcategories.php\');'.LF;
         $str .= '$ListCategories_SizedlistMinFontSize = 7;'.LF;
         $str .= '$ListCategories_SizedlistMaxFontSize = 16;'.LF;
         $str .= '$ListCategories_SizedlistNum = 20;'.LF;
         $str .= '$ListCategories_ExcludeCategories = "/^(GroupFooter)$/";'.LF;
         $str .= '$ENABLE_LISTCATEGORIES = "1";'.LF.LF;
      }

      if ( $item->WikiNewPageTemplate() != "-1" ) {
         $str .= '$EditTemplatesFmt = \'' . $item->WikiNewPageTemplate() . '\';'.LF.LF;
      }

      // Li-Pedia Erweiterungen

     // Wiki Authetifizierung

       global $c_commsy_domain;
       global $c_commsy_url_path;

       $url = $c_commsy_domain . $c_commsy_url_path . '/soap.php';
       $portalid = $this->_environment->getCurrentPortalID();
       $cid = $this->_environment->getCurrentContextID();

       if($item->WikiUseCommSyLogin() == '1'){
           $str .= '$AuthUser[\'admin\'] = crypt(\'' . $item->getWikiAdminPW() . '\');'.LF;
           $str .= '$AuthUser[\'edit\'] = crypt(\'' . $item->getWikiEditPW() . '\');'.LF;
           $str .= '$AuthUser[\'read\'] = crypt(\'' . $item->getWikiReadPW() . '\');'.LF;
           $str .= '$AuthUser[\'@admins\'] = array(\'admin\');'.LF;
           $str .= '$AuthUser[\'@editors\'] = array(\'edit\');'.LF;
           $str .= '$AuthUser[\'@readers\'] = array(\'read\');'.LF;
           $str .= '$AuthUser[\'commsy\'] = array(\'url\' => \'' . $url . '\', \'portal_id\' => \'' . $portalid . '\', \'cid\' => \'' . $cid . '\');'.LF;
           $str .= 'include_once("$FarmD/cookbook/authusercommsy.php");'.LF;
           $str .= 'include_once("$FarmD/scripts/authuser.php");'.LF;
           $str .= '$DefaultPasswords[\'admin\'] = \'@admins\';'.LF;
           $str .= '$DefaultPasswords[\'attr\'] = \'@admins\';'.LF;
           $str .= '$EnableUpload = 1;'.LF;
           $str .= '$DefaultPasswords[\'upload\'] = \'@editors\';'.LF;
           $str .= '$UploadMaxSize = 1000000000;'.LF;
           $str .= '$DefaultPasswords[\'edit\'] = \'@editors\';'.LF;
           if($item->getWikiReadPW() != ''){
              $str .= '$DefaultPasswords[\'read\'] = \'@readers @editors\';'.LF;
           }
           /*
           if ( $item->isWikiPortalReadAccess() ) {
              $str .= '$COMMSY_AUTH_READ_ACCESS_PORTAL = 1;'.LF;
           }
           if ( $item->isWikiRoomModWriteAccess() ) {
              $str .= '$COMMSY_AUTH_WRITE_ACCESS_ROOM_MOD = 1;'.LF;
           }
           */
       } else {
          $str .= 'if ( !empty($COMMSY_ADMIN_PASSWD) ) {'.LF;
         $str .= '   $DefaultPasswords[\'admin\'] = crypt($COMMSY_ADMIN_PASSWD);'.LF;
         $str .= '   $DefaultPasswords[\'attr\'] = crypt($COMMSY_ADMIN_PASSWD);'.LF;
         $str .= '}'.LF;
         $str .= 'if ( !empty($COMMSY_UPLOAD_PASSWD) ) {'.LF;
         $str .= '   $EnableUpload = 1;'.LF;
         $str .= '   $DefaultPasswords[\'upload\'] = crypt($COMMSY_UPLOAD_PASSWD);'.LF;
         $str .= '   $UploadMaxSize = 1000000000;'.LF;
         $str .= '}'.LF;
         $str .= 'if ( !empty($COMMSY_EDIT_PASSWD) ) {'.LF;
         $str .= '   $DefaultPasswords[\'edit\'] = crypt($COMMSY_EDIT_PASSWD);'.LF;
         $str .= '}'.LF;
         $str .= 'if ( !empty($COMMSY_READ_PASSWD) ) {'.LF;
         $str .= '   $DefaultPasswords[\'read\'] = crypt($COMMSY_READ_PASSWD) . \' \' . crypt($COMMSY_EDIT_PASSWD);'.LF;
         $str .= '}'.LF;
       }

     // Wiki Authetifizierung

      $str .= '?>';

      file_put_contents('commsy_config.php',$str);

      if(!file_exists('.htaccess')){
         global $c_commsy_path_file;
         copy($c_commsy_path_file.'/etc/pmwiki/htaccess-local','.htaccess');
      }

      chdir('..');
      if(!file_exists('.htaccess')){
         global $c_commsy_path_file;
         copy($c_commsy_path_file.'/etc/pmwiki/htaccess-root','.htaccess');

         $file_forum_contents = file_get_contents('.htaccess');
         $file_forum_contents_array = explode("\n", $file_forum_contents);
         for ($index = 0; $index < sizeof($file_forum_contents_array); $index++) {
            if(stripos($file_forum_contents_array[$index], 'RewriteBase') !== false){
               $temp_room_id = $item->getItemID();
            if ( $item->isPortal() ) {
               $temp_portal_id = $item->getItemID();
            } else {
               $temp_portal_id = $item->getContextID();
            }
            global $c_pmwiki_path_url;
            $temp_pmwiki_path_url = str_replace('http://', '', $c_pmwiki_path_url);
            $temp_pmwiki_path_url = str_replace('https://', '', $c_pmwiki_path_url);
            $temp_pmwiki_path_url = mb_stristr($temp_pmwiki_path_url, '/');
               $file_forum_contents_array[$index] = 'RewriteBase '.'/wikis/'.$temp_portal_id.'/'.$temp_room_id.'/';
            }
         }
         $file_forum_contents = implode("\n", $file_forum_contents_array);
         file_put_contents('.htaccess', $file_forum_contents);
      }

      chdir($old_dir);
   }

   function deleteWiki ($item) {
      $old_dir = getcwd();
      global $c_pmwiki_path_file;
      chdir($c_pmwiki_path_file);
      $directory_handle = @opendir('wikis');
      if ($directory_handle) {
         chdir('wikis');
         if ($item->isPortal()){
            $directory_handle = @opendir($item->getItemID());
            if ($directory_handle) {
               chdir($item->getItemID());
               $directory_handle2 = @opendir('wiki.d');
               if ($directory_handle2) {
                  $this->_rmdir_rf('wiki.d');
               }
               $directory_handle3 = @opendir('phpinc');
               if ($directory_handle3) {
                  $this->_rmdir_rf('phpinc');
               }
               $directory_handle4 = @opendir('uploads');
               if ($directory_handle4) {
                  $this->_rmdir_rf('uploads');
               }
               $directory_handle5 = @opendir('local');
               if ($directory_handle5) {
                  $this->_rmdir_rf('local');
               }
               if (file_exists('index.php')){
                  unlink('index.php');
               }
            }
         }else{
            $directory_handle = @opendir($item->getContextID());
            if ($directory_handle) {
               chdir($item->getContextID());
               $directory_handle2 = @opendir($item->getItemID());
               if ($directory_handle2) {
                  $this->_rmdir_rf($item->getItemID());
               }
            }
         }
      }
      chdir($old_dir);
//      $this->deleteAllDiscussionGroups();
   }


   public function moveWiki($item, $old_context_id ) {
      $old_dir = getcwd();
      global $c_pmwiki_path_file,$c_pmwiki_absolute_path_file;
      global $c_commsy_path_file;
      chdir($c_pmwiki_path_file);

      $directory_handle = @opendir('wikis');
      if (!$directory_handle) {
         mkdir('wikis');
      }
      chdir('wikis');

      $directory_handle = @opendir($item->getContextID());
      if (!$directory_handle) {
         mkdir($item->getContextID());
      } else {
         closedir($directory_handle);
      }

      chdir($item->getContextID());
      $directory_handle = @opendir($item->getItemID());
      if (!$directory_handle) {
         mkdir($item->getItemID());
      } else {
         closedir($directory_handle);
      }
      $this->_rmove_rf($c_pmwiki_absolute_path_file.'/wikis/'.$old_context_id.'/'.$item->getItemID(),$c_pmwiki_absolute_path_file.'/wikis/'.$item->getContextID().'/'.$item->getItemID());
      $this->_rmdir_rf($c_pmwiki_absolute_path_file.'/wikis/'.$old_context_id.'/'.$item->getItemID());
      chdir($old_dir);
   }

   public function copyWiki ($old_item, $new_item) {
      $this->_copyWikiConfig($old_item, $new_item);
      $this->_copyWikiData($old_item, $new_item);
      $this->createWiki($new_item);
   }

   private function _copyWikiConfig ($old_room, $new_room) {
      $new_room->setWikiExists();
      if ( $old_room->isWikiActive() ) {
         $new_room->setWikiActive();
      } else {
         $new_room->setWikiInactive();
      }
      if ( $old_room->issetWikiHomeLink() ) {
         $new_room->setWikiHomeLink();
      } else {
         $new_room->unsetWikiHomeLink();
      }
      if ( $old_room->issetWikiPortalLink() ) {
         $new_room->setWikiPortalLink();
      } else {
         $new_room->unsetWikiPortalLink();
      }
      $new_room->setWikiSkin($old_room->getWikiSkin());
      $new_room->setWikiTitle($old_room->getWikiTitle());
      $new_room->setWikiAdminPW($old_room->getWikiAdminPW());
      $new_room->setWikiEditPW($old_room->getWikiEditPW());
      $new_room->setWikiReadPW($old_room->getWikiReadPW());
      if ( $old_room->WikiShowCommSyLogin() != -1 ) {
         $new_room->setWikiShowCommSyLogin();
      } else {
         $new_room->unsetWikiShowCommSyLogin();
      }
      if ( $old_room->WikiEnableFCKEditor() != -1 ) {
         $new_room->setWikiEnableFCKEditor();
      } else {
         $new_room->unsetWikiEnableFCKEditor();
      }
      if ( $old_room->WikiEnableSitemap() != -1 ) {
         $new_room->setWikiEnableSitemap();
      } else {
         $new_room->unsetWikiEnableSitemap();
      }
      if ( $old_room->WikiEnableStatistic() != -1 ) {
         $new_room->setWikiEnableStatistic();
      } else {
         $new_room->unsetWikiEnableStatistic();
      }
      if ( $old_room->WikiEnableSearch() != -1 ) {
         $new_room->setWikiEnableSearch();
      } else {
         $new_room->unsetWikiEnableSearch();
      }
      if ( $old_room->WikiEnableRss() != -1 ) {
         $new_room->setWikiEnableRss();
      } else {
         $new_room->unsetWikiEnableRss();
      }
      if ( $old_room->WikiEnableCalendar() != -1 ) {
         $new_room->setWikiEnableCalendar();
      } else {
         $new_room->unsetWikiEnableCalendar();
      }
      if ( $old_room->WikiEnableGallery() != -1 ) {
         $new_room->setWikiEnableGallery();
      } else {
         $new_room->unsetWikiEnableGallery();
      }
      if ( $old_room->WikiEnableNotice() != -1 ) {
         $new_room->setWikiEnableNotice();
      } else {
         $new_room->unsetWikiEnableNotice();
      }
      if ( $old_room->WikiEnablePdf() != -1 ) {
         $new_room->setWikiEnablePdf();
      } else {
         $new_room->unsetWikiEnablePdf();
      }
      if ( $old_room->WikiEnableRater() != -1 ) {
         $new_room->setWikiEnableRater();
      } else {
         $new_room->unsetWikiEnableRater();
      }
      if ( $old_room->WikiEnableListCategories() != -1 ) {
         $new_room->setWikiEnableListCategories();
      } else {
         $new_room->unsetWikiEnableListCategories();
      }
      if ( $old_room->WikiNewPageTemplate() != -1 ) {
         $new_room->setWikiNewPageTemplate();
      } else {
         $new_room->unsetWikiNewPageTemplate();
      }
      if ( $old_room->WikiEnableSwf() != -1 ) {
         $new_room->setWikiEnableSwf();
      } else {
         $new_room->unsetWikiEnableSwf();
      }
      if ( $old_room->WikiEnableWmplayer() != -1 ) {
         $new_room->setWikiEnableWmplayer();
      } else {
         $new_room->unsetWikiEnableWmplayer();
      }
      if ( $old_room->WikiEnableQuicktime() != -1 ) {
         $new_room->setWikiEnableQuicktime();
      } else {
         $new_room->unsetWikiEnableQuicktime();
      }
      if ( $old_room->WikiEnableYoutubeGoogleVimeo() != -1 ) {
         $new_room->setWikiEnableYoutubeGoogleVimeo();
      } else {
         $new_room->unsetWikiEnableYoutubeGoogleVimeo();
      }
      if ( $old_room->wikiWithSectionEdit() ) {
         $new_room->setWikiWithSectionEdit();
      } else {
         $new_room->setWikiWithoutSectionEdit();
      }
      if ( $old_room->wikiWithHeaderForSectionEdit() ) {
         $new_room->setWikiWithHeaderForSectionEdit();
      } else {
         $new_room->setWikiWithoutHeaderForSectionEdit();
      }
      if ( $old_room->WikiEnableDiscussion() != -1 ) {
         $new_room->setWikiEnableDiscussion();
      } else {
         $new_room->unsetWikiEnableDiscussion();
      }
      if ( $old_room->WikiEnableDiscussionNotification() != -1 ) {
         $new_room->setWikiEnableDiscussionNotification();
      } else {
         $new_room->unsetWikiEnableDiscussionNotification();
      }
      if ( $old_room->WikiEnableDiscussionNotificationGroups() != -1 ) {
         $new_room->setWikiEnableDiscussionNotificationGroups();
      } else {
         $new_room->unsetWikiEnableDiscussionNotificationGroups();
      }
      $discussion_array = $old_room->getWikiDiscussionArray();
      if ( !empty($discussion_array)
           and $discussion_array
           and is_array($discussion_array)
           and count($discussion_array) > 0
         ) {
         foreach ( $discussion_array as $discussion ) {
            $new_room->WikiSetNewDiscussion($discussion);
         }
      }
      if ( $old_room->withWikiUseCommSyLogin() ) {
         $new_room->setWikiUseCommSyLogin();
      } else {
         $new_room->unsetWikiUseCommSyLogin();
      }
      if ( $old_room->WikiCommunityReadAccess() != -1 ) {
         $new_room->setWikiCommunityReadAccess();
      } else {
         $new_room->unsetWikiCommunityReadAccess();
      }
      if ( $old_room->WikiCommunityWriteAccess() != -1 ) {
         $new_room->setWikiCommunityWriteAccess();
      } else {
         $new_room->unsetWikiCommunityWriteAccess();
      }
      if ( $old_room->WikiPortalReadAccess() != -1 ) {
         $new_room->setWikiPortalReadAccess();
      } else {
         $new_room->unsetWikiPortalReadAccess();
      }
      if ( $old_room->WikiRoomModWriteAccess() != -1 ) {
         $new_room->setWikiRoomModWriteAccess();
      } else {
         $new_room->unsetWikiRoomModWriteAccess();
      }
   }

   private function _copyWikiData ($old_item, $new_item) {
      $old_dir = getcwd();
      global $c_pmwiki_path_file,$c_pmwiki_absolute_path_file;
      chdir($c_pmwiki_path_file);

      $directory_handle = @opendir('wikis');
      if (!$directory_handle) {
         mkdir('wikis');
      }
      chdir('wikis');

      $directory_handle = @opendir($new_item->getContextID());
      if (!$directory_handle) {
         mkdir($new_item->getContextID());
      } else {
         closedir($directory_handle);
      }

      chdir($new_item->getContextID());
      $directory_handle = @opendir($new_item->getItemID());
      if (!$directory_handle) {
         mkdir($new_item->getItemID());
      } else {
         closedir($directory_handle);
      }
      $this->_rcopy_rf($c_pmwiki_absolute_path_file.'/wikis/'.$old_item->getContextID().'/'.$old_item->getItemID(),$c_pmwiki_absolute_path_file.'/wikis/'.$new_item->getContextID().'/'.$new_item->getItemID());
      chdir($old_dir);
   }

function _rmdir_rf($dirname) {
    if ($dirHandle = opendir($dirname)) {
        chdir($dirname);
        while ($file = readdir($dirHandle)) {
            if ($file == '.' || $file == '..') continue;
            if (is_dir($file)) $this->_rmdir_rf($file);
            else unlink($file);
        }
        chdir('..');
        rmdir($dirname);
        closedir($dirHandle);
    }
}

function _rcopy_rf($quelle, $ziel) {
   if ( file_exists($quelle)
        and $dirHandle = opendir($quelle)
      ) {
        chdir($quelle);
        while ($file = readdir($dirHandle)) {
            if ($file == '.' || $file == '..') continue;
            if (is_dir($file)){
                if ( !file_exists($ziel.'/'.$file) ) {
                   mkdir($ziel.'/'.$file);
                }
                $this->_rcopy_rf($quelle.'/'.$file,$ziel.'/'.$file);
            }
            else{
               $this->_file_copy($quelle.'/'.$file,$ziel.'/'.$file);
            }
        }
        chdir('..');
        closedir($dirHandle);
    }
}

function _rmove_rf($quelle, $ziel) {
    if ($dirHandle = opendir($quelle)) {
        chdir($quelle);
        while ($file = readdir($dirHandle)) {
            if ($file == '.' || $file == '..') continue;
            if (is_dir($file)){
                if ( !file_exists($ziel.'/'.$file) ) {
                   mkdir($ziel.'/'.$file);
                }
                $this->_rmove_rf($quelle.'/'.$file,$ziel.'/'.$file);
            }
            else{
               $this->_file_move($quelle.'/'.$file,$ziel.'/'.$file);
            }
        }
        chdir('..');
        closedir($dirHandle);
    }
}

function _file_move ($quelle, $ziel)
{
    // kopiert datei und loescht sie danach
    $fertigverschoben = 4;
    if (file_exists($quelle))
    {
        $fertigverschoben--;
        if (!file_exists($ziel))
        {
            $fertigverschoben--;
            if (copy ($quelle, $ziel))
            {
                $fertigverschoben--;
                if (unlink ($quelle)) $fertigverschoben--;
                else unlink ($ziel);
            }
        }
    }
    return $fertigverschoben;
    // gibt errorcode zurueck,
    // 0 = alles okay,
    // 1 = konnte quelle nicht loeschen,
    // 2 = konnte ziel nicht erstellen (copy),
    // 3 = ziel existiert bereits,
    // 4 = quelle nicht gefunden
}// ende file_move

   private function _file_copy ($quelle, $ziel) {
      // kopiert datei
      $fertigverschoben = 3;
      if ( file_exists($quelle) ) {
         $fertigverschoben--;
         if ( !file_exists($ziel) ) {
            $fertigverschoben--;
            if ( copy($quelle, $ziel) ) {
                $fertigverschoben--;
            }
        }
    }
    return $fertigverschoben;
    // gibt errorcode zurueck,
    // 0 = alles okay,
    // 1 = konnte ziel nicht erstellen (copy),
    // 2 = ziel existiert bereits,
    // 3 = quelle nicht gefunden
}// ende file_copy

// Updates the Profiles.-File for the $user
function updateWikiProfileFile($user){
      global $c_commsy_path_file;
      global $c_pmwiki_path_file;

      $old_dir = getcwd();
      chdir($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID());

      // The Profiles-File has to be named Profiles.FirstnameLastname with capital 'F' and 'L'
      $firstnameFirstLetter = mb_substr($user->getFirstname(), 0, 1);
      if(mb_ereg_match('[a-z]', $firstnameFirstLetter)){
         $firstnameRest = mb_substr($user->getFirstname(), 1);
         $firstname = mb_strtoupper($firstnameFirstLetter, 'UTF-8') . $firstnameRest;
      } else {
         $firstname = $user->getFirstname();
      }
      $lastnameFirstLetter = mb_substr($user->getLastname(), 0, 1);
      if(mb_ereg_match('[a-z]', $lastnameFirstLetter)){
         $lastnameRest = mb_substr($user->getLastname(), 1);
         $lastname = mb_strtoupper($lastnameFirstLetter, 'UTF-8') . $lastnameRest;
      } else {
         $lastname = $user->getLastname();
      }
      $firstname = str_replace(' ', '', $firstname);
      $lastname = str_replace(' ', '', $lastname);
      $name_for_profile = $firstname . $lastname;

//      $useridFirstLetter = substr($user->getUserID(), 0, 1);
//      $useridRest = substr($user->getUserID(), 1);
//      $userid = strtoupper($useridFirstLetter) . $useridRest;

      if(!file_exists('wiki.d/Profiles.' . $name_for_profile)){
            copy($c_commsy_path_file.'/etc/pmwiki/Profiles.Profile','wiki.d/Profiles.' . $name_for_profile);
      }

      $file_contents = file_get_contents('wiki.d/Profiles.' . $name_for_profile);
      $file_contents_array = explode("\n", $file_contents);
      for ($index = 0; $index < sizeof($file_contents_array); $index++) {
          if(stripos($file_contents_array[$index], 'author=') !== false){
              $file_contents_array[$index] = 'author=' . $firstname . ' ' . $lastname;
          } else if (stripos($file_contents_array[$index], 'name=') !== false){
              $file_contents_array[$index] = 'name=Profiles.' . $name_for_profile;
          } else if (stripos($file_contents_array[$index], 'text=') !== false){
              //my personal info:%0a(:email: Mail:[[mailto:<<EMAIL>>|<<EMAIL>>]] , Telefon: <<PHONE>>:)%0a(:info:%0aAttach:Profiles.<<PROFILE>>/<<IMAGE>>%0a<<DESCRIPTION>>%0a:)
              $tempString =  'text=my personal info:%0a(:email: Mail:[[mailto:' . $user->getEmail() . '|' . $user->getEmail() . ']] , Telefon: ' . $user->getTelephone() . ':)%0a(:info:%0aAttach:Profiles.' . $name_for_profile . '/';
              $disc_manager = $this->_environment->getDiscManager();
              $disc_manager->setPortalID($this->_environment->getCurrentPortalID());
              $disc_manager->setContextID($this->_environment->getCurrentContextID());
              $path_to_files = $disc_manager->getFilePath();
              unset($disc_manager);
              if($user->getPicture() != '' and file_exists($c_commsy_path_file . '/' . $path_to_files . $user->getPicture())){
                    $tempString .= $user->getPicture() . '%0a';
                    $disc_manager = $this->_environment->getDiscManager();
                    $disc_manager->makeDirectoryR('uploads/Profiles');
                    unset($disc_manager);
                    copy($c_commsy_path_file . '/' . $path_to_files . $user->getPicture(),'uploads/Profiles/' . $user->getPicture());
#              if($user->getPicture() != '' and file_exists($c_commsy_path_file . '/var/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/' . $user->getPicture())){
#                    $tempString .= $user->getPicture() . '%0a';
#                    copy($c_commsy_path_file . '/var/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/' . $user->getPicture(),'uploads/Profiles/' . $user->getPicture());
              } else {
                    $tempString .= 'nobody_m.gif%0a';
              }
              $tempString .= '%0a:)'; //$user->getDescription() . '%0a:)';
              $file_contents_array[$index] = $tempString;
          }
      }
      $file_contents = implode("\n", $file_contents_array);
      file_put_contents('wiki.d/Profiles.' . $name_for_profile, $file_contents);
      chdir($old_dir);
}

function updateWikiProfileFile_soap($user){
      global $c_commsy_path_file;
      global $c_pmwiki_path_file;

      $old_dir = getcwd();
      chdir($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID());

      // The Profiles-File has to be named Profiles.FirstnameLastname with capital 'F' and 'L'
      $firstnameFirstLetter = mb_substr($user->getFirstname(), 0, 1);
      if(mb_ereg_match('[a-z]', $firstnameFirstLetter)){
         $firstnameRest = mb_substr($user->getFirstname(), 1);
         $firstname = mb_strtoupper($firstnameFirstLetter, 'UTF-8') . $firstnameRest;
      } else {
         $firstname = $user->getFirstname();
      }
      $lastnameFirstLetter = mb_substr($user->getLastname(), 0, 1);
      if(mb_ereg_match('[a-z]', $lastnameFirstLetter)){
         $lastnameRest = mb_substr($user->getLastname(), 1);
         $lastname = mb_strtoupper($lastnameFirstLetter, 'UTF-8') . $lastnameRest;
      } else {
         $lastname = $user->getLastname();
      }
      $firstname = str_replace(' ', '', $firstname);
      $lastname = str_replace(' ', '', $lastname);
      $name_for_profile = $firstname . $lastname;

      // Get the SOAP-client
      $client = $this->getSoapClient();

      // remove the old file


      // create and save the new file
      $profiles_source = file_get_contents($c_commsy_path_file.'/etc/pmwiki/Profiles.Profile');
      $file_contents_array = explode("\n", $profiles_source);
      for ($index = 0; $index < sizeof($file_contents_array); $index++) {
          if(stripos($file_contents_array[$index], 'author=') !== false){
              $file_contents_array[$index] = 'author=' . $firstname . ' ' . $lastname;
          } else if (stripos($file_contents_array[$index], 'name=') !== false){
              $file_contents_array[$index] = 'name=Profiles.' . $name_for_profile;
          } else if (stripos($file_contents_array[$index], 'text=') !== false){
              //my personal info:%0a(:email: Mail:[[mailto:<<EMAIL>>|<<EMAIL>>]] , Telefon: <<PHONE>>:)%0a(:info:%0aAttach:Profiles.<<PROFILE>>/<<IMAGE>>%0a<<DESCRIPTION>>%0a:)
              $tempString =  'text=my personal info:%0a(:email: Mail:[[mailto:' . $user->getEmail() . '|' . $user->getEmail() . ']] , Telefon: ' . $user->getTelephone() . ':)%0a(:info:%0aAttach:Profiles.' . $name_for_profile . '/';
              $disc_manager = $this->_environment->getDiscManager();
              $disc_manager->setPortalID($this->_environment->getCurrentPortalID());
              $disc_manager->setContextID($this->_environment->getCurrentContextID());
              $path_to_files = $disc_manager->getFilePath();
              unset($disc_manager);
              if($user->getPicture() != '' and file_exists($c_commsy_path_file . '/' . $path_to_files . $user->getPicture())){
                    $tempString .= $user->getPicture() . '%0a';
                    copy($c_commsy_path_file . '/' . $path_to_files . $user->getPicture(),'uploads/Profiles/' . $user->getPicture());
#              if($user->getPicture() != '' and file_exists($c_commsy_path_file . '/var/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/' . $user->getPicture())){
#                    $tempString .= $user->getPicture() . '%0a';
#                    copy($c_commsy_path_file . '/var/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/' . $user->getPicture(),'uploads/Profiles/' . $user->getPicture());
              } else {
                    $tempString .= 'nobody_m.gif%0a';
              }
              $tempString .= '%0a:)'; //$user->getDescription() . '%0a:)';
              $file_contents_array[$index] = $tempString;
          }
      }

      $file_contents = implode("\n", $file_contents_array);
      $client->createPage('Profiles.' . $name_for_profile, $file_contents, $this->_environment->getSessionID());
      chdir($old_dir);
}

// Entscheidung 30.09.2008 - Eintraege bleiben unveraendert im Forum
//function updateWikiRemoveUser($user){
//    //updateNotification();
//}

// Updates the $discussion-notification file. All notifications are removed
// and replaced by those in $user_array
function updateNotificationFile($discussion, $user_array){
    global $c_commsy_path_file;
    global $c_pmwiki_path_file;

    $discussion = $this->getDiscussionWikiName($discussion);

    $old_dir = getcwd();
    chdir($c_pmwiki_path_file);
    $directory_handle = @opendir('wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID());
    if ($directory_handle) {
        chdir('wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID());

        if(!file_exists('wiki.d/FoxNotifyLists.' . $discussion . 'Forum')){
            copy($c_commsy_path_file.'/etc/pmwiki/FoxNotifyLists.Forum','wiki.d/FoxNotifyLists.' . $discussion . 'Forum');
        }
        $file_contents = file_get_contents('wiki.d/FoxNotifyLists.' . $discussion . 'Forum');
        $file_contents_array = explode("\n", $file_contents);
        for ($index = 0; $index < sizeof($file_contents_array); $index++) {
            if(stripos($file_contents_array[$index], 'name=FoxNotifyLists.Forum') !== false){
                $file_contents_array[$index] = 'name=FoxNotifyLists.' . $discussion . 'Forum';
            }
            if(stripos($file_contents_array[$index], 'text=') !== false){
                $notify = 'text=';
                foreach($user_array as $user){
                    $notify .= 'notify=' . $user->getEmail() . '%0a';
                }
                $file_contents_array[$index] = $notify;
            }
        }
        $file_contents = implode("\n", $file_contents_array);
        file_put_contents('wiki.d/FoxNotifyLists.' . $discussion . 'Forum', $file_contents);
    }
    chdir($old_dir);
}

function updateNotificationFile_soap($discussion, $user_array){
    global $c_commsy_path_file;
    global $c_pmwiki_path_file;

    $discussion = $this->getDiscussionWikiName($discussion);

    // Client holen
    $client = $this->getSoapClient();
    // Datei vorhanden?
    $exists_file = $client->getPageExists('FoxNotifyLists.' . $discussion . 'Forum', $this->_environment->getSessionID());
    if($exists_file){
      // Source holen
      $wiki_source = $client->getReadPage('FoxNotifyLists.' . $discussion . 'Forum', $this->_environment->getSessionID());
      // Source bearbeiten
      $wiki_source_array = explode("\n", $wiki_source);
      for ($index = 0; $index < sizeof($wiki_source_array); $index++) {
          if(stripos($wiki_source_array[$index], 'name=FoxNotifyLists.Forum') !== false){
              $wiki_source_array[$index] = 'name=FoxNotifyLists.' . $discussion . 'Forum';
          }
          if(stripos($wiki_source_array[$index], 'text=') !== false){
              $notify = 'text=';
              foreach($user_array as $user){
                  $notify .= 'notify=' . $user->getEmail() . '%0a';
                  //$notify .= 'notify=' . $user->getEmail() . ' ';
              }
              $wiki_source_array[$index] = $notify;
          }
      }
      $wiki_source = implode("\n", $wiki_source_array);
      // Datei speichern
      $client->createPage('FoxNotifyLists.' . $discussion . 'Forum', $wiki_source, $this->_environment->getSessionID());
    } else {
      // Datei nicht vorhanden
      // Source generieren
      $file_contents = file_get_contents($c_commsy_path_file.'/etc/pmwiki/FoxNotifyLists.Forum');
      $file_contents_array = explode("\n", $file_contents);
      for ($index = 0; $index < sizeof($file_contents_array); $index++) {
          if(stripos($file_contents_array[$index], 'name=FoxNotifyLists.Forum') !== false){
              $file_contents_array[$index] = 'name=FoxNotifyLists.' . $discussion . 'Forum';
          }
          if(stripos($file_contents_array[$index], 'text=') !== false){
              $notify = 'text=';
              foreach($user_array as $user){
                  $notify .= 'notify=' . $user->getEmail() . '%0a';
                  //$notify .= 'notify=' . $user->getEmail() . ' ';
              }
              $file_contents_array[$index] = $notify;
          }
      }
      $file_contents = implode("\n", $file_contents_array);
      // Datei speichern
      $client->createPage('FoxNotifyLists.' . $discussion . 'Forum', $file_contents, $this->_environment->getSessionID());
    }
}

function deleteDiscussion($discussion){
    $discussionChecked = $this->getDiscussionWikiName($discussion);
    chdir('wiki.d');
    if($dir=opendir(getcwd())){
        while($file=readdir($dir)) {
            if (!is_dir($file) && $file != "." && $file != ".."){
                if((stripos($file, $discussionChecked) !== false) and !(stripos($file, 'Discussion_Backup_') !== false)){
                    rename($file, 'Discussion_Backup_' . $file);
                }
            }
        }
    }
    if(file_exists('Site.Forum')){
        $file_forum_contents = file_get_contents('Site.Forum');
        $file_forum_contents_array = explode("\n", $file_forum_contents);
        for ($index = 0; $index < sizeof($file_forum_contents_array); $index++) {
            if(stripos($file_forum_contents_array[$index], 'text=Foren:') !== false){
                $file_forum_contents_array[$index] = str_replace('%0a*[['. $discussionChecked . 'Forum.' . $discussionChecked . 'Forum' . '|' . $discussion . ']]', '', $file_forum_contents_array[$index]);
            }
        }
        $file_forum_contents = implode("\n", $file_forum_contents_array);
        $result = file_put_contents('Site.Forum', $file_forum_contents);
    }
    $this->updateNotification();
    chdir('..');
}

function deleteDiscussion_soap($discussion){
   $discussionChecked = $this->getDiscussionWikiName($discussion);

   // client holen
   $client = $this->getSoapClient();
   $discussion_array = $client->getPageNames($discussionChecked);
   foreach($discussion_array as $discussion_file){
      $client->backupPage($discussion_file, 'Discussion_Backup_' . $discussion_file, $this->_environment->getSessionID());
   }
   $exists_file = $client->getPageExists('Site.Forum', $this->_environment->getSessionID());
   if($exists_file){
      $wiki_source = $client->getReadPage('Site.Forum', $this->_environment->getSessionID());
      // Source bearbeiten
      $wiki_source_array = explode("\n", $wiki_source);
      for ($index = 0; $index < sizeof($wiki_source_array); $index++) {
         if(stripos($wiki_source_array[$index], 'text=Foren:') !== false){
            $wiki_source_array[$index] = str_replace('%0a*[['. $discussionChecked . 'Forum.' . $discussionChecked . 'Forum' . '|' . $discussion . ']]', '', $wiki_source_array[$index]);
         }
      }
      $wiki_source = implode("\n", $wiki_source_array);
      $client->createPage('Site.Forum', $wiki_source, $this->_environment->getSessionID());
   }
}

function removeNotification(){
   global $c_commsy_path_file;
   global $c_pmwiki_path_file;
   $old_dir = getcwd();
   chdir($c_commsy_path_file);
   chdir($c_pmwiki_path_file);
   $directory_handle = @opendir('wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID());
    if ($directory_handle) {
       chdir('wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wiki.d/');
       if($dir=opendir(getcwd())){
           while($file=readdir($dir)) {
               if (!is_dir($file) && $file != "." && $file != ".."){
                   if(stripos($file, 'FoxNotifyLists.') !== false){
                       unlink($file);
                   }
               }
           }
       }
   }
   chdir($old_dir);
}

function removeNotification_soap(){
   $client = $this->getSoapClient();
   $notification_array = $client->getPageNames('FoxNotifyLists');
   if(!empty($notification_array)){
      foreach($notification_array as $notification_file){
         $client->removePage($notification_file, $this->_environment->getSessionID());
      }
   }
}

function updateNotification(){
   global $c_use_soap_for_wiki;
   if(!$c_use_soap_for_wiki){
      $this->removeNotification();
   } else {
      $this->removeNotification_soap();
   }
   $context_item = $this->_environment->getCurrentContextItem();
   global $c_commsy_path_file;
   $old_dir = getcwd();
   chdir($c_commsy_path_file);
   if($context_item->WikiEnableDiscussionNotificationGroups() != "1"){
      // Alle Foren mit allen Nutzern füllen
      $discussion_array = $context_item->getWikiDiscussionArray();
      if ( !empty($discussion_array) ) {
         foreach($discussion_array as $discussion){
            $user_manager = $this->_environment->getUserManager();
            $user_manager->reset();
            $user_manager->setContextLimit($this->_environment->getCurrentContextID());
            $user_manager->setUserLimit();
            $user_manager->select();
            $user_list = $user_manager->get();
            $user_array = $user_list->to_array();
            global $c_use_soap_for_wiki;
            if(!$c_use_soap_for_wiki){
               $this->updateNotificationFile($this->getDiscussionWikiName($discussion), $user_array);
            } else {
               $this->updateNotificationFile_soap($this->getDiscussionWikiName($discussion), $user_array);
            }
         }
      }
   } else {
      // Gruppen durchgehen
      $context_item = $this->_environment->getCurrentContextItem();
      $discussion_array = $context_item->getWikiDiscussionArray();
      if ( !empty($discussion_array) ) {
         foreach($discussion_array as $discussion){
            $group_manager = $this->_environment->getGroupManager();
            $group_manager->resetCache();
            $group_manager->reset();
            $group_manager->select();
            $group_ids = $group_manager->getIDArray();
            $discussion_member = array();
            foreach($group_ids as $group_id){
                $group = $group_manager->getItem($group_id);
                $group_discussions = $group->getDiscussionNotificationArray();
                if ( !empty($group_discussions) ) {
                   foreach($group_discussions as $group_discussion){
                      if($group_discussion == $discussion){
                         $user_array = $group->getMemberItemList()->to_array();
                         foreach($user_array as $user){
                            if(!in_array($user, $discussion_member)){
                               $discussion_member[] = $user;
                            }
                         }
                      }
                   }
                }
            }
            global $c_use_soap_for_wiki;
            if(!$c_use_soap_for_wiki){
               $this->updateNotificationFile($this->getDiscussionWikiName($discussion), $discussion_member);
            } else {
               $this->updateNotificationFile_soap($this->getDiscussionWikiName($discussion), $discussion_member);
            }
         }
      }
   }
   chdir($old_dir);
}

function getDiscussionWikiName($discussion){
    $discussionArray = explode (' ', $discussion);
    for ($index = 0; $index < sizeof($discussionArray); $index++) {
        $discussionArray[$index] = str_replace("ä", "ae", $discussionArray[$index]);
        $discussionArray[$index] = str_replace("Ä", "Ae", $discussionArray[$index]);
        $discussionArray[$index] = str_replace("ö", "oe", $discussionArray[$index]);
        $discussionArray[$index] = str_replace("Ö", "Oe", $discussionArray[$index]);
        $discussionArray[$index] = str_replace("ü", "ue", $discussionArray[$index]);
        $discussionArray[$index] = str_replace("Ü", "Ue", $discussionArray[$index]);
        $discussionArray[$index] = str_replace("ß", "ss", $discussionArray[$index]);
        $first_letter = mb_substr($discussionArray[$index], 0, 1);
        if(mb_ereg_match('[a-z]', $first_letter)){
           $rest = mb_substr($discussionArray[$index], 1);
           $first_letter = mb_strtoupper($first_letter, 'UTF-8');
           $discussionArray[$index] = $first_letter . $rest;
        }
    }
    $discussion = implode('',$discussionArray);
    return $discussion;
}

//------------------------------------------
//------------- Materialexport -------------
function exportItemToWiki($current_item_id,$rubric){
   global $c_commsy_path_file;
   global $c_pmwiki_path_file;
   global $c_pmwiki_absolute_path_file;
   global $c_pmwiki_path_url;
   global $c_commsy_domain;
   global $c_commsy_url_path;

   $translator = $this->_environment->getTranslationObject();

   // Verzeichnis fuer Die angehaengten Dateien im Wiki
   $dir_wiki_uploads = $c_pmwiki_absolute_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads';
   $directory_handle_uploads = @opendir($dir_wiki_uploads);
   if (!$directory_handle_uploads) {
      mkdir($dir_wiki_uploads);
   }
   $dir_wiki_file = $c_pmwiki_absolute_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/Main';
   $directory_handle_file = @opendir($dir_wiki_file);
   if (!$directory_handle_file) {
      mkdir($dir_wiki_file);
   }

   $author = '';
   $description = '';
   if ($rubric == CS_MATERIAL_TYPE){
      // Material Item
      $material_manager = $this->_environment->getMaterialManager();
      $material_version_list = $material_manager->getVersionList($current_item_id);
      $item = $material_version_list->getFirst();
      // Informationen
      $author = $item->getAuthor();
      if (empty($author)){
         $author = $item->getModificatorItem()->getFullName();
         $description = $item->getDescription();
      }
      $informations = '!' . $item->getTitle() . '%0a%0a';
      $informations .= '(:table border=0 style="margin-left:0px;":)%0a';
      $informations .= '(:cell:)\'\'\'AutorInnen:\'\'\' %0a(:cell:)' . $author . ' %0a';
      // Kurzfassung fuer Wiki vorbereiten
      if(!preg_match('~<!-- KFC TEXT -->[\S|\s]*<!-- KFC TEXT -->~u', $description)){
         $text_converter = $this->_environment->getTextConverter();
         $description = $text_converter->text_for_wiki_export($description);
         //$description = _text_php2html_long($description);
      }
   }elseif($rubric == CS_DISCUSSION_TYPE){
      // Discussion Item
      $discussion_manager = $this->_environment->getDiscussionManager();
      $item = $discussion_manager->getItem($current_item_id);
      $informations = '!' . $item->getTitle() . '%0a%0a';
   }
   if ($rubric == CS_MATERIAL_TYPE or $rubric == CS_DISCUSSION_TYPE){
       global $class_factory;
       $params = array();
       $params['environment'] = $this->_environment;
       $wiki_view = $class_factory->getClass(WIKI_VIEW,$params);
       $wiki_view->setItem($item);
       $description = $wiki_view->formatForWiki($description);
       $description = $this->encodeUmlaute($description);
       $description = $this->encodeUrl($description);
       if ($rubric == CS_MATERIAL_TYPE){
          $html_wiki_file = 'Main.CommSyMaterial' . $current_item_id . '.html';
       }elseif($rubric == CS_DISCUSSION_TYPE){
          $html_wiki_file = 'Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION'). $current_item_id . '.html';
       }
       $old_dir = getcwd();
       chdir($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/Main');
       file_put_contents($html_wiki_file, $description);
       $command = escapeshellcmd('html2wiki --dialect PmWiki --encoding utf-8 ' . $html_wiki_file);
       $returnwiki = '';
       $returnstatus = '';
       $htmlwiki = exec($command, $returnwiki, $returnstatus);
//       if($returnstatus == 0){
//          // Mit Perl
//          $returnwiki = implode('%0a', $returnwiki);
//       } else {
//          // Ohne Perl
          // es muss eine zusätzliche Leerzeile am Anfang eingefügt werden:
          $temp_description = file_get_contents($html_wiki_file);
          $temp_description = '<br />' . "\n" . $temp_description;
          file_put_contents($html_wiki_file, $temp_description);
          $c_pmwiki_path_url_upload = preg_replace('~http://[^/]*~u', '', $c_pmwiki_path_url);
          $returnwiki = '(:includeupload /' . $c_pmwiki_path_url_upload . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/Main/' . $html_wiki_file .':)';
//       }
       chdir($old_dir);
       if ($rubric == CS_MATERIAL_TYPE){
          $informations .= '(:cellnr:)\'\'\'Kurzfassung:\'\'\' %0a(:cell:)' . $returnwiki . ' %0a';
       }
       // Dateien
       $file_list = $item->getFileList();
       if(!$file_list->isEmpty()){
          $file_array = $file_list->to_array();
          $file_link_array = array();
          foreach ($file_array as $file) {
             $new_filename = $this->encodeUrl($file->getDiskFileNameWithoutFolder());
             $new_filename = preg_replace('~cid([0-9]*)_~u', '', $new_filename);
             $new_filename = $new_filename.'.'.$file->getExtension();
             copy($c_commsy_path_file . '/' . $file->getDiskFileName(),$c_pmwiki_absolute_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/Main/' . $new_filename);
             $new_link = $this->encodeUrlToHtml($file->getFileName());
             $file_link_array[] = '[[' . $c_pmwiki_path_url . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/Main/' . $new_filename . '|' . $new_link . ']]';
          }
          $file_links = implode('\\\\%0a', $file_link_array);
          $informations .= '(:cellnr:)\'\'\'Dateien:\'\'\' %0a(:cell:)' . $file_links . ' %0a';
       }

       if ($rubric == CS_MATERIAL_TYPE){
          // Abschnitte
          $sub_item_list = $item->getSectionList();
       }elseif($rubric == CS_DISCUSSION_TYPE){
          $discussionarticles_manager = $this->_environment->getDiscussionArticlesManager();
          $discussionarticles_manager->setDiscussionLimit($item->getItemID(),array());
          $discussion_type = $item->getDiscussionType();
          if ($discussion_type=='threaded'){
             $discussionarticles_manager->setSortPosition();
          }
          if ( isset($_GET['status']) and $_GET['status'] == 'all_articles' ) {
             $discussionarticles_manager->setDeleteLimit(false);
          }
          $discussionarticles_manager->select();
          $sub_item_list = $discussionarticles_manager->get();
       }
       $sub_item_descriptions = '';
       if(!$sub_item_list->isEmpty()){
          $size = $sub_item_list->getCount();
          $index_start = 1;
          if($rubric == CS_DISCUSSION_TYPE and $size >0){
             $size = $size-1;
             $index_start = 0;
          }
          $sub_item_link_array = array();
          $sub_item_description_array = array();
          for ($index = $index_start; $index <= $size; $index++) {
             $sub_item = $sub_item_list->get($index);
             if($rubric == CS_DISCUSSION_TYPE){
                $sub_item_link_array[] = '(:cellnr width=50%:)'.($index+1).'. [[#' . $sub_item->getSubject() . '|' . $sub_item->getSubject() . ']] %0a(:cell width=30%:)' . $sub_item->getCreatorItem()->getFullName() . ' %0a(:cell:)' . getDateTimeInLang($sub_item->getModificationDate()) . '%0a';
             }else{
                $sub_item_link_array[] = '[[#' . $sub_item->getTitle() . '|' . $sub_item->getTitle() . ']]';
             }
             // Abschnitt fuer Wiki vorbereiten
             $description = $sub_item->getDescription();
             if(!preg_match('~<!-- KFC TEXT -->[\S|\s]*<!-- KFC TEXT -->~u', $description)){
                $text_converter = $this->_environment->getTextConverter();
                $description = $text_converter->text_for_wiki_export($description);
                //$description = _text_php2html_long($sub_item->getDescription());
             }
             $params = array();
             $params['environment'] = $this->_environment;
             $params['with_modifying_actions'] = true;
             $wiki_view = $this->_class_factory->getClass(WIKI_VIEW,$params);
             unset($params);
             $wiki_view->setItem($sub_item);
             $description = $wiki_view->formatForWiki($description);
             $description = $this->encodeUmlaute($description);
             $description = $this->encodeUrl($description);
             if ($rubric == CS_MATERIAL_TYPE){
                $html_wiki_file = 'Main.CommSyMaterial' . $current_item_id . '.sub_item.' . $sub_item->getItemID() . '.html';
             }elseif($rubric == CS_DISCUSSION_TYPE){
                $html_wiki_file = 'Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION'). $current_item_id . '.sub_item.' . $sub_item->getItemID() . '.html';
             }
             $html_wiki_file = $this->encodeUmlaute($html_wiki_file);
             $html_wiki_file = $this->encodeUrl($html_wiki_file);
             $old_dir = getcwd();
             chdir($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/Main');
             file_put_contents($html_wiki_file, $description);
             $command = escapeshellcmd('html2wiki --dialect PmWiki --encoding utf-8 ' . $html_wiki_file);
             $htmlwiki = exec($command, $returnwiki, $returnstatus);
//             if($returnstatus == 0){
//                // Mit Perl
//                $returnwiki = implode('%0a', $returnwiki);
//             } else {
//                // Ohne Perl
                $temp_description = file_get_contents($html_wiki_file);
                $temp_description = '<br />' . "\n" . $temp_description;
                file_put_contents($html_wiki_file, $temp_description);
                $c_pmwiki_path_url_upload = preg_replace('~http://[^/]*~u', '', $c_pmwiki_path_url);
                $returnwiki = '(:includeupload /' . $c_pmwiki_path_url_upload . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/Main/' . $html_wiki_file .':)';
//             }
             chdir($old_dir);
             $description_sub_item_link = str_replace(' ', '', $sub_item->getTitle());

             // Dateien (Abschnitte)
             $files = '%0a%0a';
             $file_list = $sub_item->getFileList();
             if(!$file_list->isEmpty()){
                $file_array = $file_list->to_array();
                $file_link_array = array();
                foreach ($file_array as $file) {
                   $new_filename = $this->encodeUrl($file->getDiskFileNameWithoutFolder());
                   $new_filename = preg_replace('~cid([0-9]*)_~u', '', $new_filename);
                   $new_filename = $new_filename.'.'.$file->getExtension();
                   copy($c_commsy_path_file . '/' . $file->getDiskFileName(),$c_pmwiki_absolute_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/Main/' . $new_filename);
                   $new_link = $this->encodeUrlToHtml($file->getFileName());
                   $file_link_array[] = '[[' . $c_pmwiki_path_url . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/Main/' . $new_filename . '|' . $new_link . ']]';
                }
                $file_links = implode('\\\\%0a', $file_link_array);
                $files .= '(:table border=0 style="margin-left:0px;":)%0a';
                $files .= '(:cell:)\'\'\'Dateien:\'\'\' %0a(:cell:)' . $file_links . ' %0a';
                $files .= '(:tableend:) %0a';
             }

             $sub_item_description_array[] = '%0a----%0a%0a====%0a%0a!!' . $sub_item->getTitle() . '%0a[[#' . $description_sub_item_link . ']]%0a' . $returnwiki . $files;

          }

          if ($rubric == CS_MATERIAL_TYPE){
             $sub_item_links = implode('\\\\%0a', $sub_item_link_array);
             $informations .= '(:cellnr:)\'\'\''.$translator->getMessage('MATERIAL_SECTIONS').':\'\'\' %0a(:cell:)' . $sub_item_links . ' %0a';
          }elseif ($rubric == CS_DISCUSSION_TYPE){
             $sub_item_links = implode('', $sub_item_link_array);
             $informations .= '(:cellnr:)%0a(:cell:)%0a';
             $informations .= '(:table border=0 style="margin-left:0px;":)%0a';
             $informations .= $sub_item_links;
             $informations .= '(:tableend:) %0a';
          }
          $informations .= '(:tableend:) %0a';
          $sub_item_descriptions = implode('\\\\%0a', $sub_item_description_array);
       }
       $buzzword_text = '';
       $buzzword_list = $item->getBuzzwordList();
       $buzzword = $buzzword_list->getFirst();
       $buzzword_file_text = '';
       $commentbox_text = '';
       while ($buzzword){
          if (!empty ($buzzword_text)){
             $buzzword_text .= ', ';
          }
          if (!empty ($buzzword_file_text)){
             $buzzword_file_text .= ',';
          }
          $buzzword_title = cs_ucfirst(str_replace('.','',str_replace(' ','',$buzzword->getTitle())));
          $buzzword_text .= '[[!'.$buzzword_title.']]';
          if(!file_exists($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wiki.d/' . 'Category.'.$buzzword_title)){
             copy($c_commsy_path_file.'/etc/pmwiki/Category.Keyword',$c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wiki.d/' . 'Category.'.$buzzword_title);
             $file_buzzword_contents = file_get_contents($c_commsy_path_file.'/etc/pmwiki/Category.Keyword',$c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wiki.d/' . 'Category.'.$buzzword_title);
             $file_buzzword_contents = str_replace('CS_KEYWORD',$buzzword_title,$file_buzzword_contents);
             file_put_contents($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wiki.d/' . 'Category.'.$buzzword_title, $file_buzzword_contents);
          }
          $buzzword_file_text .= 'Category.'.$buzzword_title;
          $buzzword = $buzzword_list->getNext();
       }
       if (!empty ($buzzword_text)){
          $buzzword_text = '%0a\\\\%0a'.$translator->getMessage('COMMON_BUZZWORDS').': '.$buzzword_text;
       }
       if ($item->getItemType() == CS_MATERIAL_TYPE){
          $wiki_file = 'Main.CommSyMaterial' . $current_item_id.'-Comments';
       }elseif($item->getItemType() == CS_DISCUSSION_TYPE){
          $wiki_file = 'Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION').$current_item_id.'-Comments';
       }
       if(file_exists($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wiki.d/' . $wiki_file)){
          $commentbox_text ='%0a%0a----%0a\\\\%0a'.'(:include Site.FoxCommentBox:)';
       }

       // Link zurueck ins CommSy
       global $c_single_entry_point;
       $link = '[[' . $c_commsy_domain .  $c_commsy_url_path . '/'.$c_single_entry_point.'?cid=' . $this->_environment->getCurrentContextID() . '&mod='.$rubric.'&fct=detail&iid=' . $current_item_id . '|"' . $item->getTitle() . '" im CommSy]]';

       $old_dir = getcwd();
       chdir($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID());
       // Kurzfassung fuer Wiki vorbereiten
       if ($rubric == CS_MATERIAL_TYPE){
          copy($c_commsy_path_file.'/etc/pmwiki/Main.Material','wiki.d/Main.CommSyMaterial' . $current_item_id);
          $file_contents = file_get_contents('wiki.d/Main.CommSyMaterial' . $current_item_id);
       }elseif($rubric == CS_DISCUSSION_TYPE){
          copy($c_commsy_path_file.'/etc/pmwiki/Main.'.$translator->getMessage('COMMON_DISCUSSION'),'wiki.d/Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION'). $current_item_id);
          $file_contents = file_get_contents('wiki.d/Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION'). $current_item_id);
       }
       $file_contents_array = explode("\n", $file_contents);
       for ($index = 0; $index < sizeof($file_contents_array); $index++) {
           if(stripos($file_contents_array[$index], 'name=') !== false){
               if ($rubric == CS_MATERIAL_TYPE){
                   $file_contents_array[$index] = 'name=Main.CommSyMaterial' . $current_item_id;
               }elseif($rubric == CS_DISCUSSION_TYPE){
                   $file_contents_array[$index] = 'name=Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION') . $current_item_id;
               }
           }
           if(stripos($file_contents_array[$index], 'text=') !== false){
              if ($rubric == CS_MATERIAL_TYPE){
                 $title_text = '(:title CommSy-Material "' . $item->getTitle() . '":)';
              }elseif($rubric == CS_DISCUSSION_TYPE){
                 $title_text = '(:title CommSy-'.$translator->getMessage('COMMON_DISCUSSION').' "' . $item->getTitle() . '":)';
              }
              $file_contents_array[$index] = 'text=' . $informations . $sub_item_descriptions . '%0a%0a----%0a\\\\%0a' . $buzzword_text.'%0a\\\\%0a'. $link. $commentbox_text . '%0a%0a' . $title_text;
           }
           if(stripos($file_contents_array[$index], 'targets=') !== false and !empty($buzzword_file_text)){
              $file_contents_array[$index] = 'targets='.$buzzword_file_text;
           }
       }
       $file_contents = implode("\n", $file_contents_array);
       if(!strstr($file_contents,'targets=') and !empty($buzzword_file_text)){
          $file_contents .='"\n"'.'targets='.$buzzword_file_text;
       }

       if ($rubric == CS_MATERIAL_TYPE){
           $file_contents =  $file_contents . "\n" . 'title=CommSy-Material "' . $item->getTitle() . '"';
           file_put_contents('wiki.d/Main.CommSyMaterial' . $current_item_id, $file_contents);
       }elseif($rubric == CS_DISCUSSION_TYPE){
           $file_contents =  $file_contents . 'title=CommSy-'.$translator->getMessage('COMMON_DISCUSSION').' "' . $item->getTitle() . '"';
           file_put_contents('wiki.d/Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION').'' . $current_item_id, $file_contents);
       }

       chdir($old_dir);

       $item->setExportToWiki('1');
       $item->save();

       $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
       $modifiers = $link_modifier_item_manager->getModifiersOfItem($item->getItemID());

       $user_manager = $this->_environment->getUserManager();
       $user_manager->reset();
       $user_manager->setContextLimit($this->_environment->getCurrentContextID());
       $user_manager->setIDArrayLimit($modifiers);
       $user_manager->select();
       $user_list = $user_manager->get();

       if ($user_list->getCount() >= 1) {
          $user_item = $user_list->getFirst();

          include_once('classes/cs_mail.php');
          $translator = $this->_environment->getTranslationObject();

          while($user_item){
             $mail = new cs_mail();
             $mail->set_to($user_item->getEmail());

             $room = $this->_environment->getCurrentContextItem();
             $room_title = '';
             if (isset($room)){
                $room_title = $room->getTitle();
             }
             $from = $translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_title);
             $mail->set_from_name($from);

              global $symfonyContainer;
              $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
              $mail->set_from_email($emailFrom);

             $subject = $translator->getMessage('MATERIAL_EXPORT_WIKI_MAIL_SUBJECT').': '.$room_title;
             $mail->set_subject($subject);

             $body = $translator->getMessage('MATERIAL_EXPORT_WIKI_MAIL_BODY', $room_title, $user_item->getFullname(), $item->getTitle(), $item->getExportToWikiLink());
             $mail->set_message($body);
             $mail->setSendAsHTML();

             $mail->send();

             $user_item = $user_list->getNext();
          }
       }
   }
   $this->updateExportLists($rubric);
}

function exportItemToWiki_soap($current_item_id,$rubric){
   global $c_commsy_path_file;
   global $c_pmwiki_path_file;
   global $c_pmwiki_absolute_path_file;
   global $c_pmwiki_path_url;
   global $c_commsy_domain;
   global $c_commsy_url_path;

   $translator = $this->_environment->getTranslationObject();

   $client = $this->getSoapClient();
   $client->createDir('uploads/Main', $this->_environment->getSessionID());

   $author = '';
   $description = '';
   if ($rubric == CS_MATERIAL_TYPE){
      // Material Item
      $material_manager = $this->_environment->getMaterialManager();
      $material_version_list = $material_manager->getVersionList($current_item_id);
      $item = $material_version_list->getFirst();
      // Informationen
      $author = $item->getAuthor();
      if (empty($author)){
         $author = $item->getModificatorItem()->getFullName();
         $description = $item->getDescription();
      }
      $informations = '!' . $item->getTitle() . '%0a%0a';
      $informations .= '(:table border=0 style="margin-left:0px;":)%0a';
      $informations .= '(:cell:)\'\'\'AutorInnen:\'\'\' %0a(:cell:)' . $author . ' %0a';
      // Kurzfassung fuer Wiki vorbereiten
      if(!preg_match('~<!-- KFC TEXT -->[\S|\s]*<!-- KFC TEXT -->~u', $description)){
         $text_converter = $this->_environment->getTextConverter();
         $description = $text_converter->text_for_wiki_export($description);
         //$description = _text_php2html_long($description);
      }
   }elseif($rubric == CS_DISCUSSION_TYPE){
      // Discussion Item
      $discussion_manager = $this->_environment->getDiscussionManager();
      $item = $discussion_manager->getItem($current_item_id);
      $informations = '!' . $item->getTitle() . '%0a%0a';
   }
   if ($rubric == CS_MATERIAL_TYPE or $rubric == CS_DISCUSSION_TYPE){
       global $class_factory;
       $params = array();
       $params['environment'] = $this->_environment;
       $wiki_view = $class_factory->getClass(WIKI_VIEW,$params);
       $wiki_view->setItem($item);
       $description = $wiki_view->formatForWiki($description);
       $description = $this->encodeUmlaute($description);
       $description = $this->encodeUrl($description);
       if ($rubric == CS_MATERIAL_TYPE){
          $html_wiki_file = 'Main.CommSyMaterial' . $current_item_id . '.html';
       }elseif($rubric == CS_DISCUSSION_TYPE){
          $html_wiki_file = 'Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION'). $current_item_id . '.html';
       }
       $old_dir = getcwd();

       $description = '<br />' . "\n" . $description;
       $client->uploadFile($html_wiki_file, base64_encode($description), 'uploads/Main', $this->_environment->getSessionID());

       $c_pmwiki_path_url_upload = preg_replace('~http://[^/]*~u', '', $c_pmwiki_path_url);
       $returnwiki = '(:includeupload /' . $c_pmwiki_path_url_upload . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/Main/' . $html_wiki_file .':)';

       if ($rubric == CS_MATERIAL_TYPE){
          $informations .= '(:cellnr:)\'\'\'Kurzfassung:\'\'\' %0a(:cell:)' . $returnwiki . ' %0a';
       }
       // Dateien
       $file_list = $item->getFileList();
       if(!$file_list->isEmpty()){
          $file_array = $file_list->to_array();
          $file_link_array = array();
          foreach ($file_array as $file) {
             $new_filename = $this->encodeUrl($file->getDiskFileNameWithoutFolder());
             $new_filename = preg_replace('~cid([0-9]*)_~u', '', $new_filename);
             $new_filename = $new_filename.'.'.$file->getExtension();
             $temp_file = file_get_contents ($c_commsy_path_file . '/' . $file->getDiskFileName());
             $client->uploadFile($new_filename, base64_encode($temp_file), 'uploads/Main', $this->_environment->getSessionID());
             $new_link = $this->encodeUrlToHtml($file->getFileName());
             $file_link_array[] = '[[' . $c_pmwiki_path_url . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/Main/' . $new_filename . '|' . $new_link . ']]';
          }
          $file_links = implode('\\\\%0a', $file_link_array);
          $informations .= '(:cellnr:)\'\'\'Dateien:\'\'\' %0a(:cell:)' . $file_links . ' %0a';
       }

       if ($rubric == CS_MATERIAL_TYPE){
          // Abschnitte
          $sub_item_list = $item->getSectionList();
       }elseif($rubric == CS_DISCUSSION_TYPE){
          $discussionarticles_manager = $this->_environment->getDiscussionArticlesManager();
          $discussionarticles_manager->setDiscussionLimit($item->getItemID(),array());
          $discussion_type = $item->getDiscussionType();
          if ($discussion_type=='threaded'){
             $discussionarticles_manager->setSortPosition();
          }
          if ( isset($_GET['status']) and $_GET['status'] == 'all_articles' ) {
             $discussionarticles_manager->setDeleteLimit(false);
          }
          $discussionarticles_manager->select();
          $sub_item_list = $discussionarticles_manager->get();
       }
       $sub_item_descriptions = '';
       if(!$sub_item_list->isEmpty()){
          $size = $sub_item_list->getCount();
          $index_start = 1;
          if($rubric == CS_DISCUSSION_TYPE and $size >0){
             $size = $size-1;
             $index_start = 0;
          }
          $sub_item_link_array = array();
          $sub_item_description_array = array();
          for ($index = $index_start; $index <= $size; $index++) {
             $sub_item = $sub_item_list->get($index);
             if($rubric == CS_DISCUSSION_TYPE){
                $sub_item_link_array[] = '(:cellnr width=50%:)'.($index+1).'. [[#' . $sub_item->getSubject() . '|' . $sub_item->getSubject() . ']] %0a(:cell width=30%:)' . $sub_item->getCreatorItem()->getFullName() . ' %0a(:cell:)' . getDateTimeInLang($sub_item->getModificationDate()) . '%0a';
             }else{
                $sub_item_link_array[] = '[[#' . $sub_item->getTitle() . '|' . $sub_item->getTitle() . ']]';
             }
             // Abschnitt fuer Wiki vorbereiten
             $description = $sub_item->getDescription();
             if(!preg_match('~<!-- KFC TEXT -->[\S|\s]*<!-- KFC TEXT -->~u', $description)){
                $text_converter = $this->_environment->getTextConverter();
                $description = $text_converter->text_for_wiki_export($description);
                //$description = _text_php2html_long($sub_item->getDescription());
             }
             $params = array();
             $params['environment'] = $this->_environment;
             $params['with_modifying_actions'] = true;
             $wiki_view = $this->_class_factory->getClass(WIKI_VIEW,$params);
             unset($params);
             $wiki_view->setItem($sub_item);
             $description = $wiki_view->formatForWiki($description);
             $description = $this->encodeUmlaute($description);
             $description = $this->encodeUrl($description);
             if ($rubric == CS_MATERIAL_TYPE){
                $html_wiki_file = 'Main.CommSyMaterial' . $current_item_id . '.sub_item.' . $sub_item->getItemID() . '.html';
             }elseif($rubric == CS_DISCUSSION_TYPE){
                $html_wiki_file = 'Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION'). $current_item_id . '.sub_item.' . $sub_item->getItemID() . '.html';
             }
             $html_wiki_file = $this->encodeUmlaute($html_wiki_file);
             $html_wiki_file = $this->encodeUrl($html_wiki_file);
             $description = '<br />' . "\n" . $description;
             $client->uploadFile($html_wiki_file, base64_encode($description), 'uploads/Main', $this->_environment->getSessionID());
             $c_pmwiki_path_url_upload = preg_replace('~http://[^/]*~u', '', $c_pmwiki_path_url);
             $returnwiki = '(:includeupload /' . $c_pmwiki_path_url_upload . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/Main/' . $html_wiki_file .':)';
             $description_sub_item_link = str_replace(' ', '', $sub_item->getTitle());

             // Dateien (Abschnitte)
             $files = '%0a%0a';
             $file_list = $sub_item->getFileList();
             if(!$file_list->isEmpty()){
                $file_array = $file_list->to_array();
                $file_link_array = array();
                foreach ($file_array as $file) {
                   $new_filename = $this->encodeUrl($file->getDiskFileNameWithoutFolder());
                   $new_filename = preg_replace('~cid([0-9]*)_~u', '', $new_filename);
                   $new_filename = $new_filename.'.'.$file->getExtension();
                   $temp_file = file_get_contents ($c_commsy_path_file . '/' . $file->getDiskFileName());
                   $client->uploadFile($new_filename, base64_encode($temp_file), 'uploads/Main', $this->_environment->getSessionID());
                   $new_link = $this->encodeUrlToHtml($file->getFileName());
                   $file_link_array[] = '[[' . $c_pmwiki_path_url . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/Main/' . $new_filename . '|' . $new_link . ']]';
                }
                $file_links = implode('\\\\%0a', $file_link_array);
                $files .= '(:table border=0 style="margin-left:0px;":)%0a';
                $files .= '(:cell:)\'\'\'Dateien:\'\'\' %0a(:cell:)' . $file_links . ' %0a';
                $files .= '(:tableend:) %0a';
             }

             $sub_item_description_array[] = '%0a----%0a%0a====%0a%0a!!' . $sub_item->getTitle() . '%0a[[#' . $description_sub_item_link . ']]%0a' . $returnwiki . $files;

          }

          if ($rubric == CS_MATERIAL_TYPE){
             $sub_item_links = implode('\\\\%0a', $sub_item_link_array);
             $informations .= '(:cellnr:)\'\'\''.$translator->getMessage('MATERIAL_SECTIONS').':\'\'\' %0a(:cell:)' . $sub_item_links . ' %0a';
          }elseif ($rubric == CS_DISCUSSION_TYPE){
             $sub_item_links = implode('', $sub_item_link_array);
             $informations .= '(:cellnr:)%0a(:cell:)%0a';
             $informations .= '(:table border=0 style="margin-left:0px;":)%0a';
             $informations .= $sub_item_links;
             $informations .= '(:tableend:) %0a';
          }
          $informations .= '(:tableend:) %0a';
          $sub_item_descriptions = implode('\\\\%0a', $sub_item_description_array);
       }
       $buzzword_text = '';
       $buzzword_list = $item->getBuzzwordList();
       $buzzword = $buzzword_list->getFirst();
       $buzzword_file_text = '';
       $commentbox_text = '';
       while ($buzzword){
          if (!empty ($buzzword_text)){
             $buzzword_text .= ', ';
          }
          if (!empty ($buzzword_file_text)){
             $buzzword_file_text .= ',';
          }
          $buzzword_title = cs_ucfirst(str_replace('.','',str_replace(' ','',$buzzword->getTitle())));
          $buzzword_text .= '[[!'.$buzzword_title.']]';
          $exists_file = $client->getPageExists('Category.'.$buzzword_title, $this->_environment->getSessionID());
          if(!$exists_file){
            $file_buzzword_contents = file_get_contents($c_commsy_path_file.'/etc/pmwiki/Category.Keyword');
            $file_buzzword_contents = str_replace('CS_KEYWORD',$buzzword_title,$file_buzzword_contents);
            $client->createPage('Category.'.$buzzword_title, $file_buzzword_contents, $this->_environment->getSessionID());
          }
          $buzzword_file_text .= 'Category.'.$buzzword_title;
          $buzzword = $buzzword_list->getNext();
       }
       if (!empty ($buzzword_text)){
          $buzzword_text = '%0a\\\\%0a'.$translator->getMessage('COMMON_BUZZWORDS').': '.$buzzword_text;
       }
       if ($item->getItemType() == CS_MATERIAL_TYPE){
          $wiki_file = 'Main.CommSyMaterial' . $current_item_id.'-Comments';
       }elseif($item->getItemType() == CS_DISCUSSION_TYPE){
          $wiki_file = 'Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION').$current_item_id.'-Comments';
       }
       $exists_file = $client->getPageExists($wiki_file, $this->_environment->getSessionID());
       if(!$exists_file){
         $commentbox_text ='%0a%0a----%0a\\\\%0a'.'(:include Site.FoxCommentBox:)';
       }

       // Link zurueck ins CommSy
       global $c_single_entry_point;
       $link = '[[' . $c_commsy_domain .  $c_commsy_url_path . '/'.$c_single_entry_point.'?cid=' . $this->_environment->getCurrentContextID() . '&mod='.$rubric.'&fct=detail&iid=' . $current_item_id . '|"' . $item->getTitle() . '" im CommSy]]';

       $old_dir = getcwd();
       // Kurzfassung fuer Wiki vorbereiten
       if ($rubric == CS_MATERIAL_TYPE){
          $file_contents = file_get_contents($c_commsy_path_file.'/etc/pmwiki/Main.Material');
       }elseif($rubric == CS_DISCUSSION_TYPE){
          $file_contents = file_get_contents($c_commsy_path_file.'/etc/pmwiki/Main.'.$translator->getMessage('COMMON_DISCUSSION'));
       }
       $file_contents_array = explode("\n", $file_contents);
       for ($index = 0; $index < sizeof($file_contents_array); $index++) {
           if(stripos($file_contents_array[$index], 'name=') !== false){
               if ($rubric == CS_MATERIAL_TYPE){
                   $file_contents_array[$index] = 'name=Main.CommSyMaterial' . $current_item_id;
               }elseif($rubric == CS_DISCUSSION_TYPE){
                   $file_contents_array[$index] = 'name=Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION') . $current_item_id;
               }
           }
           if(stripos($file_contents_array[$index], 'text=') !== false){
              if ($rubric == CS_MATERIAL_TYPE){
                 $title_text = '(:title CommSy-Material "' . $item->getTitle() . '":)';
              }elseif($rubric == CS_DISCUSSION_TYPE){
                 $title_text = '(:title CommSy-'.$translator->getMessage('COMMON_DISCUSSION').' "' . $item->getTitle() . '":)';
              }
              $file_contents_array[$index] = 'text=' . $informations . $sub_item_descriptions . '%0a%0a----%0a\\\\%0a' . $buzzword_text.'%0a\\\\%0a'. $link. $commentbox_text . '%0a%0a' . $title_text;
           }
           if(stripos($file_contents_array[$index], 'targets=') !== false and !empty($buzzword_file_text)){
              $file_contents_array[$index] = 'targets='.$buzzword_file_text;
           }
       }
       $file_contents = implode("\n", $file_contents_array);
       if(!strstr($file_contents,'targets=') and !empty($buzzword_file_text)){
          $file_contents .='"\n"'.'targets='.$buzzword_file_text;
       }

       if ($rubric == CS_MATERIAL_TYPE){
           $file_contents =  $file_contents . "\n" . 'title=CommSy-Material "' . $item->getTitle() . '"';
           $client->createPage('Main.CommSyMaterial' . $current_item_id, $file_contents, $this->_environment->getSessionID());
       }elseif($rubric == CS_DISCUSSION_TYPE){
           $file_contents =  $file_contents . 'title=CommSy-'.$translator->getMessage('COMMON_DISCUSSION').' "' . $item->getTitle() . '"';
           $client->createPage('Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION').'' . $current_item_id, $file_contents, $this->_environment->getSessionID());
       }

       $item->setExportToWiki('1');
       $item->save();

       $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
       $modifiers = $link_modifier_item_manager->getModifiersOfItem($item->getItemID());

       $user_manager = $this->_environment->getUserManager();
       $user_manager->reset();
       $user_manager->setContextLimit($this->_environment->getCurrentContextID());
       $user_manager->setIDArrayLimit($modifiers);
       $user_manager->select();
       $user_list = $user_manager->get();

       if ($user_list->getCount() >= 1) {
          $user_item = $user_list->getFirst();

          include_once('classes/cs_mail.php');
          $translator = $this->_environment->getTranslationObject();

          while($user_item){
             $mail = new cs_mail();
             $mail->set_to($user_item->getEmail());

             $room = $this->_environment->getCurrentContextItem();
             $room_title = '';
             if (isset($room)){
                $room_title = $room->getTitle();
             }
             $from = $translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_title);
             $mail->set_from_name($from);

              global $symfonyContainer;
              $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
              $mail->set_from_email($emailFrom);

             $subject = $translator->getMessage('MATERIAL_EXPORT_WIKI_MAIL_SUBJECT').': '.$room_title;
             $mail->set_subject($subject);

             $body = $translator->getMessage('MATERIAL_EXPORT_WIKI_MAIL_BODY', $room_title, $this->_environment->getCurrentUserItem()->getFullname(), $item->getTitle(), $item->getExportToWikiLink());
             $mail->set_message($body);
             $mail->setSendAsHTML();

             $mail->send();

             $user_item = $user_list->getNext();
          }
       }
   }
   $this->updateExportLists($rubric);
}

function removeItemFromWiki_soap($current_item_id,$rubric){
   // todo
}

function updateExportLists($rubric){
   global $c_pmwiki_path_file;
   global $c_commsy_path_file;

   $translator = $this->_environment->getTranslationObject();

   $old_dir = getcwd();
   chdir($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wiki.d');
   if ($rubric == CS_DISCUSSION_TYPE){
      if(file_exists('Main.CommSyDiskussionen')){
         unlink('Main.CommSyDiskussionen');
      }
      if(!file_exists('Main.CommSyDiskussionenNavi')){
         copy($c_commsy_path_file.'/etc/pmwiki/Main.CommSyDiskussionenNavi','Main.CommSyDiskussionenNavi');
      }
      copy($c_commsy_path_file.'/etc/pmwiki/Main.CommSyDiskussionen','Main.CommSyDiskussionen');
      $exported_discussions = array();
      $directory_handle = @opendir('.');
      if ($directory_handle) {
         if($dir=opendir(getcwd())){
            while($file=readdir($dir)) {
               if (!is_dir($file) && $file != "." && $file != ".." && $file != 'Main.CommSyDiskussionenNavi' && $file != 'Main.CommSyDiskussionen'){
                  if((stripos($file, 'Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION')) !== false)
                      and (stripos($file, '.count') === false)){
                      $exported_discussions[] = $file;
                  }
               }
            }
         }
      }
      $file_contents = file_get_contents('Main.CommSyDiskussionen');
      $file_contents_array = explode("\n", $file_contents);
      for ($index = 0; $index < sizeof($file_contents_array); $index++) {
         if(stripos($file_contents_array[$index], 'text=') !== false){
            $text = '';
            foreach($exported_discussions as $exported_discussion){
               $text .= '%0a* [[' . $exported_discussion . '|+]]';
            }
            $file_contents_array[$index] = 'text=' . $text;
         }
      }
      $file_contents = implode("\n", $file_contents_array);
      file_put_contents('Main.CommSyDiskussionen', $file_contents);
   }elseif($rubric == CS_MATERIAL_TYPE){
      if(file_exists('Main.CommSyMaterialien')){
         unlink('Main.CommSyMaterialien');
      }
      if(!file_exists('Main.CommSyMaterialienNavi')){
         copy($c_commsy_path_file.'/etc/pmwiki/Main.CommSyMaterialienNavi','Main.CommSyMaterialienNavi');
      }
      copy($c_commsy_path_file.'/etc/pmwiki/Main.CommSyMaterialien','Main.CommSyMaterialien');
      $exported_discussions = array();
      $directory_handle = @opendir('.');
      if ($directory_handle) {
         if($dir=opendir(getcwd())){
            while($file=readdir($dir)) {
               if (!is_dir($file) && $file != "." && $file != ".." && $file != 'Main.CommSyMaterialienNavi' && $file != 'Main.CommSyMaterialien'){
                  if((stripos($file, 'Main.CommSyMaterial') !== false)
                      and (stripos($file, '.count') === false)){
                      $exported_discussions[] = $file;
                  }
               }
            }
         }
      }
      $file_contents = file_get_contents('Main.CommSyMaterialien');
      $file_contents_array = explode("\n", $file_contents);
      for ($index = 0; $index < sizeof($file_contents_array); $index++) {
         if(stripos($file_contents_array[$index], 'text=') !== false){
            $text = '';
            foreach($exported_discussions as $exported_discussion){
               $text .= '%0a* [[' . $exported_discussion . '|+]]';
            }
            $file_contents_array[$index] = 'text=' . $text;
         }
      }
      $file_contents = implode("\n", $file_contents_array);
      file_put_contents('Main.CommSyMaterialien', $file_contents);
   }
   chdir($old_dir);
}

function updateExportLists_soap($rubric){
   global $c_pmwiki_path_file;
   global $c_commsy_path_file;

   $translator = $this->_environment->getTranslationObject();

   $client = $this->getSoapClient();
   if ($rubric == CS_DISCUSSION_TYPE){
      $exists_file = $client->getPageExists('Main.CommSyDiskussionenNavi', $this->_environment->getSessionID());
      if(!$exists_file){
         $file_contents = file_get_contents($c_commsy_path_file.'/etc/pmwiki/Main.CommSyDiskussionenNavi');
         $client->createPage('Main.CommSyDiskussionenNavi', $file_contents, $this->_environment->getSessionID());
      }
      $exported_discussions = array();
      $dicussion_array = $client->getPageNames('Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION'));
      foreach($dicussion_array as $discussion_file){
         if($discussion_file != 'Main.CommSyDiskussionenNavi' && $discussion_file != 'Main.CommSyDiskussionen'){
            if((stripos($discussion_file, 'Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION')) !== false)
                and (stripos($discussion_file, '.count') === false)){
                $exported_discussions[] = $discussion_file;
            }
         }
      }
      $file_contents = file_get_contents($c_commsy_path_file.'/etc/pmwiki/Main.CommSyDiskussionen');
      $file_contents_array = explode("\n", $file_contents);
      for ($index = 0; $index < sizeof($file_contents_array); $index++) {
         if(stripos($file_contents_array[$index], 'text=') !== false){
            $text = '';
            foreach($exported_discussions as $exported_discussion){
               $text .= '%0a* [[' . $exported_discussion . '|+]]';
            }
            $file_contents_array[$index] = 'text=' . $text;
         }
      }
      $file_contents = implode("\n", $file_contents_array);
      $client->createPage('Main.CommSyDiskussionen', $file_contents, $this->_environment->getSessionID());
   }elseif($rubric == CS_MATERIAL_TYPE){
      $exists_file = $client->getPageExists('Main.CommSyMaterialienNavi', $this->_environment->getSessionID());
      if(!$exists_file){
         $file_contents = file_get_contents($c_commsy_path_file.'/etc/pmwiki/Main.CommSyMaterialienNavi');
         $client->createPage('Main.CommSyMaterialienNavi', $file_contents, $this->_environment->getSessionID());
      }
      $exported_discussions = array();
      $dicussion_array = $client->getPageNames('Main.CommSyCommSyMaterial');
      foreach($dicussion_array as $discussion_file){
         if($discussion_file != 'Main.CommSyMaterialienNavi' && $discussion_file != 'Main.CommSyMaterialien'){
            if((stripos($discussion_file, 'Main.CommSyMaterial') !== false)
                and (stripos($discussion_file, '.count') === false)){
                $exported_discussions[] = $discussion_file;
            }
         }
      }
      $file_contents = file_get_contents($c_commsy_path_file.'/etc/pmwiki/Main.CommSyMaterialien');
      $file_contents_array = explode("\n", $file_contents);
      for ($index = 0; $index < sizeof($file_contents_array); $index++) {
         if(stripos($file_contents_array[$index], 'text=') !== false){
            $text = '';
            foreach($exported_discussions as $exported_discussion){
               $text .= '%0a* [[' . $exported_discussion . '|+]]';
            }
            $file_contents_array[$index] = 'text=' . $text;
         }
      }
      $file_contents = implode("\n", $file_contents_array);
      $client->createPage('Main.CommSyMaterialien', $file_contents, $this->_environment->getSessionID());

   }
}

function encodeUmlaute($html){
        $html = str_replace("ä", "&auml;", $html);
        $html = str_replace("Ä", "&Auml;", $html);
        $html = str_replace("ö", "&ouml;", $html);
        $html = str_replace("Ö", "&Ouml;", $html);
        $html = str_replace("ü", "&uuml;", $html);
        $html = str_replace("Ü", "&Uuml;", $html);
        $html = str_replace("ß", "&szlig;", $html);
        return $html;
}

function encodeUrl($html){
        $html = str_replace("%E4", "ae", $html);
        $html = str_replace("%C4", "AE", $html);
        $html = str_replace("%F6", "oe", $html);
        $html = str_replace("%D6", "OE", $html);
        $html = str_replace("%FC", "ue", $html);
        $html = str_replace("%DC", "UE", $html);
        $html = str_replace("%DF", "ss", $html);
        return $html;
}

function encodeUrlToHtml($html){
        $html = str_replace("%E4", "&auml;", $html);
        $html = str_replace("%C4", "&Auml;", $html);
        $html = str_replace("%F6", "&ouml;", $html);
        $html = str_replace("%D6", "&Ouml;", $html);
        $html = str_replace("%FC", "&uuml;", $html);
        $html = str_replace("%DC", "&Uuml;", $html);
        $html = str_replace("%DF", "&szlig;", $html);
        return $html;
}

function existsItemToWiki($current_item_id){
   $translator = $this->_environment->getTranslationObject();
   global $c_pmwiki_path_file;
   $manager = $this->_environment->getItemManager();
   $item = $manager->getItem($current_item_id);
   if ($item->getItemType() == CS_MATERIAL_TYPE){
      $wiki_file = 'Main.CommSyMaterial' . $current_item_id;
   }elseif($item->getItemType() == CS_DISCUSSION_TYPE){
      $wiki_file = 'Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION').$current_item_id;
   }
   return file_exists($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wiki.d/' . $wiki_file);
}

function existsItemToWiki_soap($current_item_id){
   $translator = $this->_environment->getTranslationObject();
   global $c_pmwiki_path_file;
   $manager = $this->_environment->getItemManager();
   $item = $manager->getItem($current_item_id);
   if ($item->getItemType() == CS_MATERIAL_TYPE){
      $wiki_file = 'Main.CommSyMaterial' . $current_item_id;
   }elseif($item->getItemType() == CS_DISCUSSION_TYPE){
      $wiki_file = 'Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION').$current_item_id;
   }
   $client = $this->getSoapClient();
   return $client->getPageExists($wiki_file, $this->_environment->getSessionID());
}

function getExportToWikiLink($current_item_id){
   $translator = $this->_environment->getTranslationObject();
   global $c_pmwiki_path_url;
   $manager = $this->_environment->getItemManager();
   $item = $manager->getItem($current_item_id);
   if ($item->getItemType() == CS_MATERIAL_TYPE){
      $wiki_file = 'Main.CommSyMaterial' . $current_item_id;
   }elseif($item->getItemType() == CS_DISCUSSION_TYPE){
      $wiki_file = 'Main.CommSy'.$translator->getMessage('COMMON_DISCUSSION'). $current_item_id;
   }
   return '<a target="_blank" href="' . $c_pmwiki_path_url . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/index.php?n=' . $wiki_file . '&commsy_session_id='.$this->_environment->getSessionID().'">' . $wiki_file . '</a>';
}

//------------- Materialexport -------------
//------------------------------------------

function getGroupsForWiki($complete){
   global $c_pmwiki_path_file;
//   $old_dir = getcwd();
//   chdir($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wiki.d');
//    chdir($old_dir);

    $result = array('groups' => array(), 'public' => array());
    $directory = $c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wiki.d';
   $directory_handle = @opendir($directory);
   if($directory_handle){
      if($dir=opendir($directory)){
          while($file=readdir($dir)) {
              if (!is_dir($file) && $file != "." && $file != ".."){
                    $group = explode('.', $file);
                    if((!in_array($group[0], $result['groups']))
                      && ($group[0] != '')
                      && (!mb_stristr($group[0], 'Site'))
                      && (!mb_stristr($group[0], 'SiteAdmin'))
                      && (!mb_stristr($group[0], 'Main'))
                      && (!mb_stristr($group[0], 'Discussion_Backup_'))
                      && (!mb_stristr($group[0], 'FoxNotifyLists'))
                      && (!mb_stristr($group[0], 'Profiles'))){
                         $result['groups'][] = $group[0];
                         $found = false;
                         $dir2=opendir($directory);
                         while($fileGroupAttributes=readdir($dir2)) {
                          if (!is_dir($fileGroupAttributes) && $fileGroupAttributes != "." && $fileGroupAttributes != ".."){
                             if(mb_stristr($fileGroupAttributes, $group[0] . '.GroupAttributes')){
                                $file_contents = file_get_contents($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wiki.d/' . $group[0] . '.GroupAttributes');
                             $file_contents_array = explode("\n", $file_contents);
                             for ($index = 0; $index < sizeof($file_contents_array); $index++) {
                                 if(stripos($file_contents_array[$index], 'passwdread=$1$83L9njI9$fEsgQxzfx7xSVvRK5accZ0') !== false){
                                     $result['public'][] = 'selected';
                                        $found = true;
                                 }
                             }
                               }
                          }
                         }
                         if(!$found){
                            $result['public'][] = '';
                         }
                    }
              }
          }
       }
   }

   // zusätzlich noch in der wiki-config nach den bisher freigegebenen Dateien suchen.

   return $result;
}

function setWikiGroupAsPublic($group){
    global $c_commsy_path_file;
    global $c_pmwiki_path_file;

    $old_dir = getcwd();
    chdir($c_pmwiki_path_file);
    $directory_handle = @opendir('wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID());
    if ($directory_handle) {
        chdir('wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID());

        if(!file_exists('wiki.d/' . $group . '.GroupAttributes')){
            copy($c_commsy_path_file.'/etc/pmwiki/Group.GroupAttributes','wiki.d/' . $group . '.GroupAttributes');
        }
        $file_contents = file_get_contents('wiki.d/' . $group . '.GroupAttributes');
        $file_contents_array = explode("\n", $file_contents);
        for ($index = 0; $index < sizeof($file_contents_array); $index++) {
            if(stripos($file_contents_array[$index], 'name=Group.GroupAttributes') !== false){
                $file_contents_array[$index] = 'name=' . $group . '.GroupAttributes';
            }
        }
        $file_contents = implode("\n", $file_contents_array);
        file_put_contents('wiki.d/' . $group . '.GroupAttributes', $file_contents);
    }
    chdir($old_dir);
}

function setWikiGroupAsPublic_soap($group){
   global $c_commsy_path_file;

   $client = $this->getSoapClient();
   $file_contents = file_get_contents($c_commsy_path_file.'/etc/pmwiki/Group.GroupAttributes');
   $file_contents_array = explode("\n", $file_contents);
   for ($index = 0; $index < sizeof($file_contents_array); $index++) {
      if(stripos($file_contents_array[$index], 'name=Group.GroupAttributes') !== false){
         $file_contents_array[$index] = 'name=' . $group . '.GroupAttributes';
      }
   }
   $file_contents = implode("\n", $file_contents_array);
   $client->createPage($group . '.GroupAttributes', $file_contents, $this->_environment->getSessionID());
}

function setWikiGroupsAsPublic($groups){
   global $c_pmwiki_path_file;

   $directory = $c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wiki.d';
   $directory_handle = @opendir($directory);
   if($directory_handle){
      if($dir=opendir($directory)){
          while($file=readdir($dir)) {
              if (!is_dir($file) && $file != "." && $file != ".."){
                    if(mb_stristr($file, '.GroupAttributes')){
                       unlink($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wiki.d/' . $file);
                    }
              }
          }
      }
   }

   if(isset($groups) && !(count($groups) == 0)){
      $groups[] = 'Main';
   }

   foreach($groups as $group){
      //$this->setWikiGroupAsPublic($group);
      global $c_use_soap_for_wiki;
      if(!$c_use_soap_for_wiki){
         $this->setWikiGroupAsPublic($group);
      } else {
         $this->setWikiGroupAsPublic_soap($group);
      }
   }
}

function setWikiGroupsAsPublic_soap($groups){
   $client = $this->getSoapClient();
   $groups_array = $client->getPageNames('.GroupAttributes');
   foreach($groups_array as $group){
      $client->removePage($group, $this->_environment->getSessionID());
   }

   if(isset($groups) && !(count($groups) == 0)){
      $groups[] = 'Main';
   }

   foreach($groups as $group){
      //$this->setWikiGroupAsPublic($group);
      global $c_use_soap_for_wiki;
      if(!$c_use_soap_for_wiki){
         $this->setWikiGroupAsPublic($group);
      } else {
         $this->setWikiGroupAsPublic_soap($group);
      }
   }
}

function getSoapWsdlUrl(){
   global $c_pmwiki_path_url;
   $portal_id = $this->_environment->getCurrentPortalID();
   $context_id = $this->_environment->getCurrentContextID();
   if ( $portal_id == $context_id ) {
      return $c_pmwiki_path_url . '/wikis/' . $this->_environment->getCurrentPortalID() . '/index.php?action=soap&wsdl=1';
   } else {
      return $c_pmwiki_path_url . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/index.php?action=soap&wsdl=1';
   }
}

function getSoapClient(){
    $options = array("trace" => 1, "exceptions" => 0);
    $c_pmwiki_use_soap_without_proxy = $this->_environment->getConfiguration('c_pmwiki_use_soap_without_proxy');
    if ( empty($c_pmwiki_use_soap_without_proxy)
         or !$c_pmwiki_use_soap_without_proxy
       ) {
       global $symfonyContainer;
       $c_proxy_ip = $symfonyContainer->getParameter('commsy.settings.proxy_ip');
       $c_proxy_port = $symfonyContainer->getParameter('commsy.settings.proxy_port');

       if ($c_proxy_ip) {
          $options['proxy_host'] = $c_proxy_ip;
       }
       if ($c_proxy_port) {
          $options['proxy_port'] = $c_proxy_port;
       }
    }
    return new SoapClient($this->getSoapWsdlUrl(), $options);
}

function getGroupsForWiki_soap($complete){
   $client = $this->getSoapClient();
   $groups = $client->getGroupNames();
   $result = array('groups' => array(), 'public' => array());
   foreach($groups as $group){
      if((!in_array($group, $result['groups']))
           && ($group != '')
           && (!mb_stristr($group, 'Site'))
           && (!mb_stristr($group, 'SiteAdmin'))
           && (!mb_stristr($group, 'Main'))
           && (!mb_stristr($group, 'Discussion_Backup_'))
           && (!mb_stristr($group, 'FoxNotifyLists'))
           && (!mb_stristr($group, 'Profiles'))
           && (!mb_stristr($group, 'PmWikiDe'))
           && (!mb_stristr($group, 'Calendar'))
           && (!mb_stristr($group, 'Category'))
           && (!mb_stristr($group, 'FoxTemplates'))
           && (!mb_stristr($group, 'index'))
           && (!mb_stristr($group, 'README'))
           && (!mb_stristr($group, 'Forum'))
           && (!mb_stristr($group, 'LICENSE'))){
            $result['groups'][] = $group;
           $page_data_array = $client->getReadPage($group . '.GroupAttributes');
           $found = false;
           foreach($page_data_array as $page_data){
              if(stripos($page_data, '$1$83L9njI9$fEsgQxzfx7xSVvRK5accZ0') !== false){
                   $result['public'][] = 'selected';
                   $found = true;
               }
           }
           if(!$found){
              $result['public'][] = '';
           }
       }
   }
   return $result;
}

function getWikiNavigation(){
   $client = $this->getSoapClient();
   return $client->getWikiNavigation($this->_environment->getSessionID());
}

function soapCallToWiki(){
   $client = new SoapClient($this->getSoapWsdlUrl(), array("trace" => 1, "exceptions" => 0));
   //$test = $client->getPage('Main.GroupAttributes');      // - OK - UTF-8 check
   //$test = $client->getPageSource('Main.HomePage');   // - OK - UTF-8 check
   //$test = $client->getGroupNames();               // - OK
   //$test = $client->getPageNames();               // - OK
   //$test = $client->getPageData();               // - OK
   //$test = $client->getRevision('Main.HomePage');   // - OK
   //$test = $client->getMinRevision('Main.HomePage');   // - OK
   //$test = $client->getRevisions('Main.HomePage');   // - OK
   //$test = $client->getChanges('Main', '0');         // - OK
   //$test = $client->getFileStorageMethod();         // - OK
   //$test = $client->getFileList('Main.HomePage');   // - OK
   //$test = $client->getFileInfo('Main.HomePage', 'Main.CommSyMaterial131.html');   // - OK
   //$test = $client->getUploadInfo('Main.HomePage', 'Main.CommSyMaterial131.html'); // - check Auth!
   //$test = $client->getProperties();               // - OK
   //$test = $client->getVersion();                  // - OK

   #pr($client->__getLastRequestHeaders());
   #pr($client->__getLastResponseHeaders());
   #pr($client->__getLastResponse());
}

function updateWiki($portal, $room){
   global $c_commsy_path_file;
   global $c_pmwiki_path_file;
   // Backup der Inhalte
   $backup_time = time();
   $old_dir = getcwd();
   chdir($c_pmwiki_path_file);
   $directory_handle = @opendir('wikis/' . $portal->getItemID());
   if ($directory_handle) {
      chdir('wikis/' . $portal->getItemID());
      if(file_exists($room->getItemID())){
         $this->recurse_copy_dir($room->getItemID(), $room->getItemID() . '.backup_' . $backup_time);
         // Altes Wiki-Verzeichnis loeschen
         // nicht über deleteWiki() damit die Einstellungen erhalten bleiben
         $this->deleteDirectory($room->getItemID());
      }
   }
   chdir($old_dir);
   // Neuanlegen des Wikis
   $this->createWiki($room);
   // Inhalte kopieren
   chdir($c_pmwiki_path_file);
   $directory_handle_wikid = @opendir('wikis/' . $portal->getItemID() . '/' . $room->getItemID() . '.backup_' . $backup_time . '/wiki.d');
   if ($directory_handle_wikid) {
      $this->recurse_copy_dir('wikis/' . $portal->getItemID() . '/' . $room->getItemID() . '.backup_' . $backup_time . '/wiki.d', 'wikis/' . $portal->getItemID() . '/' . $room->getItemID() . '/wiki.d');
   }
   $directory_handle_pub = @opendir('wikis/' . $portal->getItemID() . '/' . $room->getItemID() . '.backup_' . $backup_time . '/pub');
   if ($directory_handle_pub) {
      $this->recurse_copy_dir('wikis/' . $portal->getItemID() . '/' . $room->getItemID() . '.backup_' . $backup_time . '/pub', 'wikis/' . $portal->getItemID() . '/' . $room->getItemID() . '/pub');
   }
   $directory_handle_uploads = @opendir('wikis/' . $portal->getItemID() . '/' . $room->getItemID() . '.backup_' . $backup_time . '/uploads');
   if ($directory_handle_uploads) {
      $this->recurse_copy_dir('wikis/' . $portal->getItemID() . '/' . $room->getItemID() . '.backup_' . $backup_time . '/uploads', 'wikis/' . $portal->getItemID() . '/' . $room->getItemID() . '/uploads');
   }
   // Backup loeschen
   //$this->deleteDirectory('wikis/' . $portal->getItemID() . '/' . $room->getItemID() . '.backup_' . $backup_time);
   chdir($old_dir);
}

function recurse_copy_dir($src,$dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                $this->recurse_copy_dir($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// von http://us2.php.net/manual/en/function.rmdir.php
function deleteDirectory($dir) {
   if (!file_exists($dir)) return true;
   if (!is_dir($dir) || is_link($dir)) return unlink($dir);
   foreach (scandir($dir) as $item) {
      if ($item == '.' || $item == '..') continue;
      if (!$this->deleteDirectory($dir . "/" . $item)) {
         chmod($dir . "/" . $item, 0777);
         if (!$this->deleteDirectory($dir . "/" . $item)) return false;
      };
   }
   return rmdir($dir);
}

function closeWiki(){
   $client = $this->getSoapClient();
   $file_contents_array = explode("\n", $client->getWikiConfiguration($this->_environment->getSessionID()));

   $wiki_closed_found = false;
   $wiki_closed_added = false;
   foreach($file_contents_array as $file_contents_line){
      if(stristr($file_contents_line, '$WIKI_CLOSED = "1";')){
         $wiki_closed_found = true;
      }
   }

   if(!$wiki_closed_found){
      for ($index = 0; $index < sizeof($file_contents_array); $index++) {
         if(stripos($file_contents_array[$index], '?>') !== false){
            $file_contents_array[$index] = '$WIKI_CLOSED = "1";';
            $wiki_closed_added = true;
         }
      }
      if($wiki_closed_added){
         $file_contents_array[] = '?>';
      }
      $file_contents = implode("\n", $file_contents_array);
      $client->saveWikiConfiguration($file_contents, $this->_environment->getSessionID());
   }
}

function openWiki(){
   $client = $this->getSoapClient();
   $file_contents_array = explode("\n", $client->getWikiConfiguration($this->_environment->getSessionID()));

   $wiki_closed_found = false;
   $wiki_closed_added = false;
   foreach($file_contents_array as $file_contents_line){
      if(stristr($file_contents_line, '$WIKI_CLOSED = "1";')){
         $wiki_closed_found = true;
      }
   }

   if($wiki_closed_found){
      for ($index = 0; $index < sizeof($file_contents_array); $index++) {
         if(stripos($file_contents_array[$index], '$WIKI_CLOSED = "1";') !== false){
            unset($file_contents_array[$index]);
         }
      }
      $file_contents = implode("\n", $file_contents_array);
      $client->saveWikiConfiguration($file_contents, $this->_environment->getSessionID());
   }
}
}

function pr_soap($client){
   pr($client->__getLastRequestHeaders());
   pr($client->__getLastResponseHeaders());
   pr($client->__getLastResponse());
}

function pr_user_soap($client, $user){
   pr_user($client->__getLastRequestHeaders(), $user);
   pr_user($client->__getLastResponseHeaders(), $user);
   pr_user($client->__getLastResponse(), $user);
}
?>