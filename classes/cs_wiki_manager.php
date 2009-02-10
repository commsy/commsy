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

/** class for database connection to the database table "homepage"
 * this class implements a database manager for the table "homepage_page"
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
      if (!empty($language) and strtoupper($language)!='USER'){
         $str .= '$COMMSY_LANGUAGE = "'.strtolower($item->getLanguage()).'";'.LF;
      }
      $str .= LF.'global $FarmD;'.LF.LF;
      if ( $item->isPortal() ) {
         $str .= '@require_once("$FarmD/cookbook/phpinc-markup.php");'.LF;
      }

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

      if ( $item->WikiEnableNotice() == "1" ) {
         $str .= '$GUIButtons["stickyNote"] = array(700, "(:note Comment: |", ":)\\n", "$[Text]",'.LF;
         $str .= '"$GUIButtonDirUrlFmt/sticky.gif\"$[Yellow Sticky Note]\"");'.LF;
         $str .= '@include_once("$FarmD/cookbook/postitnotes.php");'.LF;
         $str .= '$SHOW_NOTICE = "1";'.LF.LF;
      }

      if ( $item->WikiEnableGallery() == "1" ) {
         $str .= '@include_once("$FarmD/cookbook/gallery.php");'.LF;
         $str .= '$SHOW_GALLERY = "1";'.LF.LF;
      }

      if ( $item->WikiEnablePdf() == "1" ) {
         $str .= '@include_once("$FarmD/cookbook/pmwiki2pdf/pmwiki2pdf.php");'.LF;
         $str .= '@include_once("$FarmD/cookbook/pmwiki2pdflink.php");'.LF;
         $str .= '$SHOW_PDF = "1";'.LF.LF;
      }

      if ( $item->WikiEnableSwf() == "1" ) {
         $str .= '@include_once("$FarmD/cookbook/swf.php");'.LF;
         $str .= '$ENABLE_SWF = "1";'.LF.LF;
      }

      if ( $item->WikiEnableWmplayer() == "1" ) {
         $str .= '@include_once("$FarmD/cookbook/wmplayer.php");'.LF;
         $str .= "\$UploadExts['wma'] = 'audio/wma';".LF;
         $str .= "\$UploadExts['wmv'] = 'video/wmv';".LF;
         $str .= '$ENABLE_WMPLAYER = "1";'.LF.LF;
      }

      if ( $item->WikiEnableQuicktime() == "1" ) {
         $str .= '@include_once("$FarmD/cookbook/quicktime.php");'.LF;
         $str .= '$ENABLE_QUICKTIME = "1";'.LF.LF;
      }

      if ( $item->WikiEnableYoutubeGoogleVimeo() == "1" ) {
         $str .= '@include_once("$FarmD/cookbook/swf-sites2.php");'.LF;
         $str .= '$ENABLE_YOUTUBEGOOGLEVIMEO = "1";'.LF.LF;
      }

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
                    if($titleForForm == $_POST['new_discussion']){
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
//                                if($group->getName() == getMessage('WIKI_DISCUSSION_GROUP_TITLE') . ' ' . $titleForForm){
//                                    $group_existing = true;
//                                }
//                            }
//                            if(!$group_existing){
//                                $new_group = $group_manager->getNewItem();
//                                $new_group->setName(getMessage('WIKI_DISCUSSION_GROUP_TITLE') . ' ' . $titleForForm);
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
                        $this->removeNotification();
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
                    $this->deleteDiscussion($titleForForm);
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

      if ( $item->WikiEnableRater() == "1" ) {
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
      }

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


  function moveWiki($item,$old_context_id){
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
      $this->_rcopy_rf($c_pmwiki_absolute_path_file.'/wikis/'.$old_context_id.'/'.$item->getItemID(),$c_pmwiki_absolute_path_file.'/wikis/'.$item->getContextID().'/'.$item->getItemID());
      $this->_rmdir_rf($c_pmwiki_absolute_path_file.'/wikis/'.$old_context_id.'/'.$item->getItemID());
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
    if ($dirHandle = opendir($quelle)) {
        chdir($quelle);
        while ($file = readdir($dirHandle)) {
            if ($file == '.' || $file == '..') continue;
            if (is_dir($file)){
                mkdir($ziel.'/'.$file);
                $this->_rcopy_rf($quelle.'/'.$file,$ziel.'/'.$file);
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

// Updates the Profiles.-File for the $user
function updateWikiProfileFile($user){
      global $c_commsy_path_file;
      global $c_pmwiki_path_file;

      $old_dir = getcwd();
      chdir($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID());

      // The Profiles-File has to be named Profiles.FirstnameLastname with capital 'F' and 'L'
      $firstnameFirstLetter = substr($user->getFirstname(), 0, 1);
      $firstnameRest = substr($user->getFirstname(), 1);
      $firstname = strtoupper($firstnameFirstLetter) . $firstnameRest;
      $lastnameFirstLetter = substr($user->getLastname(), 0, 1);
      $lastnameRest = substr($user->getLastname(), 1);
      $lastname = strtoupper($lastnameFirstLetter) . $lastnameRest;
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
              if($user->getPicture() != '' and file_exists($c_commsy_path_file . '/var/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/' . $user->getPicture())){
                    $tempString .= $user->getPicture() . '%0a';
                    copy($c_commsy_path_file . '/var/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/' . $user->getPicture(),'uploads/Profiles/' . $user->getPicture());
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

function updateNotification(){
   $this->removeNotification();

   $context_item = $this->_environment->getCurrentContextItem();

   global $c_commsy_path_file;
   $old_dir = getcwd();
   chdir($c_commsy_path_file);
   if($context_item->WikiEnableDiscussionNotificationGroups() != "1"){
      // Alle Foren mit allen Nutzern füllen
      $discussion_array = $context_item->getWikiDiscussionArray();
      foreach($discussion_array as $discussion){
         $user_manager = $this->_environment->getUserManager();
         $user_manager->reset();
         $user_manager->setContextLimit($this->_environment->getCurrentContextID());
         $user_manager->setUserLimit();
         $user_manager->select();
         $user_list = $user_manager->get();
         $user_array = $user_list->to_array();
         $this->updateNotificationFile($this->getDiscussionWikiName($discussion), $user_array);
      }
   } else {
      // Gruppen durchgehen
      $context_item = $this->_environment->getCurrentContextItem();
      $discussion_array = $context_item->getWikiDiscussionArray();
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
         $this->updateNotificationFile($this->getDiscussionWikiName($discussion), $discussion_member);
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
        $first_letter = substr($discussionArray[$index], 0, 1);
        $rest = substr($discussionArray[$index], 1);
        $first_letter = strtoupper($first_letter);
        $discussionArray[$index] = $first_letter . $rest;
    }
    $discussion = implode('',$discussionArray);
        return $discussion;
}

//------------------------------------------
//------------- Materialexport -------------
function exportMaterialToWiki($current_item_id){
   global $c_commsy_path_file;
   global $c_pmwiki_path_file;
   global $c_pmwiki_absolute_path_file;
   global $c_pmwiki_path_url;
   global $c_commsy_domain;
   global $c_commsy_url_path;

   // Verzeichnis fuer Die angehaengten Dateien im Wiki
   $dir_wiki_uploads = $c_pmwiki_absolute_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads';
   $directory_handle_uploads = @opendir($dir_wiki_uploads);
   if (!$directory_handle_uploads) {
      mkdir($dir_wiki_uploads);
   }
   $dir_wiki_file = $c_pmwiki_absolute_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/CommSy';
   $directory_handle_file = @opendir($dir_wiki_file);
   if (!$directory_handle_file) {
      mkdir($dir_wiki_file);
   }

   // Material Item
   $material_manager = $this->_environment->getMaterialManager();
   $material_version_list = $material_manager->getVersionList($current_item_id);
   $material_item = $material_version_list->getFirst();

   // Informationen
   $author = $material_item->getAuthor();
   $informations = '!' . $material_item->getTitle() . '%0a%0a';
   $informations .= '(:table border=0 style="margin-left:0px;":)%0a';
   $informations .= '(:cell:)\'\'\'AutorInnen:\'\'\' %0a(:cell:)' . $author . ' %0a';

   // Kurzfassung fuer Wiki vorbereiten
   $description = $material_item->getDescription();
   if(!preg_match('$<!-- KFC TEXT -->[\S|\s]*<!-- KFC TEXT -->$', $description)){
      $description = _text_php2html_long($description);
   }

   global $class_factory;
   $params = array();
   $params['environment'] = $this->_environment;
   $wiki_view = $class_factory->getClass(WIKI_VIEW,$params);

   $wiki_view->setItem($material_item);
   $description = $wiki_view->formatForWiki($description);
   $description = $this->encodeUmlaute($description);
   $description = $this->encodeUrl($description);
   $html_wiki_file = 'CommSy.Material' . $current_item_id . '.html';
   $old_dir = getcwd();
   chdir($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/CommSy');
   file_put_contents($html_wiki_file, $description);
   $command = escapeshellcmd('html2wiki --dialect PmWiki --encoding iso-8859-1 ' . $html_wiki_file);
   $returnwiki = '';
   $returnstatus = '';
   $htmlwiki = exec($command, $returnwiki, $returnstatus);
   if($returnstatus == 0){
      // Mit Perl
      $returnwiki = implode('%0a', $returnwiki);
   } else {
      // Ohne Perl
      $c_pmwiki_path_url_upload = preg_replace('~http://[^/]*~', '', $c_pmwiki_path_url);
      $returnwiki = '(:includeupload /' . $c_pmwiki_path_url_upload . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/CommSy/' . $html_wiki_file .':)';
   }
   chdir($old_dir);

   $informations .= '(:cellnr:)\'\'\'Kurzfassung:\'\'\' %0a(:cell:)' . $returnwiki . ' %0a';

   // Dateien
   $file_list = $material_item->getFileList();
   if(!$file_list->isEmpty()){
      $file_array = $file_list->to_array();
      $file_link_array = array();
      foreach ($file_array as $file) {
         $new_filename = $this->encodeUrl($file->getDiskFileNameWithoutFolder());
         $new_filename = preg_replace("/cid([0-9]*)_/", "", $new_filename);
         copy($c_commsy_path_file . '/' . $file->getDiskFileName(),$c_pmwiki_absolute_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/CommSy/' . $new_filename);
         $new_link = $this->encodeUrlToHtml($file->getFileName());
         $file_link_array[] = '[[' . $c_pmwiki_path_url . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/CommSy/' . $new_filename . '|' . $new_link . ']]';
      }
      $file_links = implode('\\\\%0a', $file_link_array);
      $informations .= '(:cellnr:)\'\'\'Dateien:\'\'\' %0a(:cell:)' . $file_links . ' %0a';
   }

   // Abschnitte
   $section_list = $material_item->getSectionList();
   $section_descriptions = '';
   if(!$section_list->isEmpty()){
      $size = $section_list->getCount();
      $section_link_array = array();
      $section_description_array = array();
      for ($index = 1; $index <= $size; $index++) {
         $section = $section_list->get($index);
         $section_link_array[] = '[[#' . $section->getTitle() . '|' . $section->getTitle() . ']]';

         // Abschnitt fuer Wiki vorbereiten
         $description = $section->getDescription();
         if(!preg_match('$<!-- KFC TEXT -->[\S|\s]*<!-- KFC TEXT -->$', $description)){
            $description = _text_php2html_long($section->getDescription());
         }
         $params = array();
         $params['environment'] = $this->_environment;
         $params['with_modifying_actions'] = true;
         $wiki_view = $this->_class_factory->getClass(WIKI_VIEW,$params);
         unset($params);
         $wiki_view->setItem($section);
         $description = $wiki_view->formatForWiki($description);
         $description = $this->encodeUmlaute($description);
         $description = $this->encodeUrl($description);
         $html_wiki_file = 'CommSy.Material' . $current_item_id . '.section.' . $section->getItemID() . '.html';
         $html_wiki_file = $this->encodeUmlaute($html_wiki_file);
         $html_wiki_file = $this->encodeUrl($html_wiki_file);
         $old_dir = getcwd();
         chdir($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/CommSy');
         file_put_contents($html_wiki_file, $description);
         $command = escapeshellcmd('html2wiki --dialect PmWiki --encoding iso-8859-1 ' . $html_wiki_file);
         $htmlwiki = exec($command, $returnwiki, $returnstatus);
         if($returnstatus == 0){
            // Mit Perl
            $returnwiki = implode('%0a', $returnwiki);
         } else {
            // Ohne Perl
            $c_pmwiki_path_url_upload = preg_replace('~http://[^/]*~', '', $c_pmwiki_path_url);
            $returnwiki = '(:includeupload /' . $c_pmwiki_path_url_upload . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/CommSy/' . $html_wiki_file .':)';
         }
         chdir($old_dir);
         $description_section_link = str_replace(' ', '', $section->getTitle());

         // Dateien (Abschnitte)
         $files = '%0a%0a';
         $file_list = $section->getFileList();
         if(!$file_list->isEmpty()){
            $file_array = $file_list->to_array();
            $file_link_array = array();
            foreach ($file_array as $file) {
               $new_filename = $this->encodeUrl($file->getDiskFileNameWithoutFolder());
               $new_filename = preg_replace("/cid([0-9]*)_/", "", $new_filename);
               copy($c_commsy_path_file . '/' . $file->getDiskFileName(),$c_pmwiki_absolute_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/CommSy/' . $new_filename);
               $new_link = $this->encodeUrlToHtml($file->getFileName());
               $file_link_array[] = '[[' . $c_pmwiki_path_url . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/CommSy/' . $new_filename . '|' . $new_link . ']]';
            }
            $file_links = implode('\\\\%0a', $file_link_array);
            $files .= '(:table border=0 style="margin-left:0px;":)%0a';
            $files .= '(:cell:)\'\'\'Dateien:\'\'\' %0a(:cell:)' . $file_links . ' %0a';
            $files .= '(:tableend:) %0a';
         }

         $section_description_array[] = '%0a----%0a%0a====%0a%0a!!' . $section->getTitle() . '%0a[[#' . $description_section_link . ']]%0a' . $returnwiki . $files;

      }
      $section_links = implode('\\\\%0a', $section_link_array);
      $informations .= '(:cellnr:)\'\'\'Abschnitte:\'\'\' %0a(:cell:)' . $section_links . ' %0a';
      $informations .= '(:tableend:) %0a';
      $section_descriptions = implode('\\\\%0a', $section_description_array);
   }

   // Link zurueck ins CommSy
   $link = '[[' . $c_commsy_domain .  $c_commsy_url_path . '/commsy.php?cid=' . $this->_environment->getCurrentContextID() . '&mod=material&fct=detail&iid=' . $current_item_id . '|"' . $material_item->getTitle() . '" im CommSy]]';

   $old_dir = getcwd();
   chdir($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID());
   // Kurzfassung fuer Wiki vorbereiten
   copy($c_commsy_path_file.'/etc/pmwiki/Main.Material','wiki.d/CommSy.Material' . $current_item_id);
   $file_contents = file_get_contents('wiki.d/CommSy.Material' . $current_item_id);
   $file_contents_array = explode("\n", $file_contents);
   for ($index = 0; $index < sizeof($file_contents_array); $index++) {
       if(stripos($file_contents_array[$index], 'name=') !== false){
           $file_contents_array[$index] = 'name=CommSy.Material' . $current_item_id;
       }
       if(stripos($file_contents_array[$index], 'text=') !== false){
          $file_contents_array[$index] = 'text=' . $informations . $section_descriptions . '%0a%0a----%0a\\\\%0a' . $link;
       }
   }
   $file_contents = implode("\n", $file_contents_array);
   $file_contents =  $file_contents . "\n" . 'title=' . $material_item->getTitle();
   file_put_contents('wiki.d/CommSy.Material' . $current_item_id, $file_contents);

   chdir($old_dir);

   $material_item->setExportToWiki('1');
   $material_item->save();

   $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
   $modifiers = $link_modifier_item_manager->getModifiersOfItem($material_item->getItemID());

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

         $server_item = $this->_environment->getServerItem();
         $default_sender_address = $server_item->getDefaultSenderAddress();
         if (!empty($default_sender_address)) {
             $mail->set_from_email($default_sender_address);
         } else {
             $mail->set_from_email('@');
         }

         $subject = $translator->getMessage('MATERIAL_EXPORT_WIKI_MAIL_SUBJECT').': '.$room_title;
         $mail->set_subject($subject);

         $body = $translator->getMessage('MATERIAL_EXPORT_WIKI_MAIL_BODY', $room_title, $material_item->getTitle(), $material_item->getExportToWikiLink());
         $mail->set_message($body);
         $mail->setSendAsHTML();

         $mail->send();

         $user_item = $user_list->getNext();
      }
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

function existsMaterialToWiki($current_item_id){
   global $c_pmwiki_path_file;
   $wiki_file = 'CommSy.Material' . $current_item_id;
   return file_exists($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wiki.d/' . $wiki_file);
}

function getExportToWikiLink($current_item_id){
   global $c_pmwiki_path_url;
   $wiki_file = 'CommSy.Material' . $current_item_id;
   return '<a href="' . $c_pmwiki_path_url . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/index.php?n=' . $wiki_file . '">' . $wiki_file . '</a>';
}

//------------- Materialexport -------------
//------------------------------------------

function getGroupsForWiki($complete){
   global $c_pmwiki_path_file;
//	$old_dir = getcwd();
//	chdir($c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wiki.d');
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
                      && (!stristr($group[0], 'Site'))
                      && (!stristr($group[0], 'SiteAdmin'))
                      && (!stristr($group[0], 'Main'))
                      && (!stristr($group[0], 'Discussion_Backup_'))
                      && (!stristr($group[0], 'FoxNotifyLists'))
                      && (!stristr($group[0], 'Profiles'))){
                         $result['groups'][] = $group[0];
                         $found = false;
                         $dir2=opendir($directory);
                         while($fileGroupAttributes=readdir($dir2)) {
                          if (!is_dir($fileGroupAttributes) && $fileGroupAttributes != "." && $fileGroupAttributes != ".."){
                             if(stristr($fileGroupAttributes, $group[0] . '.GroupAttributes')){
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

function setWikiGroupsAsPublic($groups){
   global $c_pmwiki_path_file;

   $directory = $c_pmwiki_path_file . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wiki.d';
   $directory_handle = @opendir($directory);
   if($directory_handle){
      if($dir=opendir($directory)){
          while($file=readdir($dir)) {
              if (!is_dir($file) && $file != "." && $file != ".."){
                    if(stristr($file, '.GroupAttributes')){
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
      $this->setWikiGroupAsPublic($group);
   }
}

}
?>