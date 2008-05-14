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
      if ($item->isPortal()){
         $str = "<?php include('../../pmwiki.php');?>";
      }else{
         $str ="<?php include('../../../pmwiki.php');?>";
      }
      file_put_contents('index.php',$str);

      if ($item->isPortal()){
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
      $str .= '$SHOW_COMMSY_LOGIN = "'.$item->WikiShowCommSyLogin().'";'.LF;
      $language = $item->getLanguage();
      if (!empty($language) and strtoupper($language)!='USER'){
         $str .= '$COMMSY_LANGUAGE = "'.strtolower($item->getLanguage()).'";'.LF;
      }
      if ( $item->isPortal() ) {
         $str .= 'require_once("$FarmD/cookbook/phpinc-markup.php");'.LF;
      }

      // section edit
      if ( $item->wikiWithSectionEdit() ) {
         if ( !$item->wikiWithHeaderForSectionEdit() ) {
            $str .= '$SectionEditWithoutHeaders = true;'.LF;
         }
         $str .= 'global $FarmD;'.LF;
         $str .= '@include_once("$FarmD/cookbook/sectionedit.php");'.LF;
      }

      // Additional features for mediweb - not activated by default.
      // modify in comsy_config.php to activate.
      $str .= LF.'$SHOW_FCKEDITOR = "' . $item->WikiEnableFCKEditor() . '";'.LF;
      $str .= '$SHOW_SEARCH = "' . $item->WikiEnableSearch() . '";'.LF;
      $str .= '$SHOW_SITEMAP = "' . $item->WikiEnableSitemap() . '";'.LF.LF;
      
      $str .= "include_once(\$FarmD.'/cookbook/totalcounter.php');".LF;
      $str .= "include_once(\$FarmD.'/cookbook/totalcounterlink.php');".LF;
      $str .= '$SHOW_STATISTIC_ACTION = "' . $item->WikiEnableStatistic() . '";'.LF.LF;

      $str .= '$EnableRssLink  = ' . $item->WikiEnableRss() . ';'.LF;
      $str .= '$EnableSitewideFeed = 1;'.LF;
      $str .= '$EnableAtomLink = 0;'.LF;
      $str .= 'include_once("$FarmD/cookbook/feedlinks.php");'.LF;
      $str .= "\$FeedFmt['rss']['item']['title'] = '{\$Group} / {\$Title} : {\$LastModified}';".LF;
      $str .= '$change = "Auf der Seite ... hat es eine Änderung gegeben! &lt;br&gt;&lt;br&gt;";'.LF;
      $str .= "\$FeedFmt['rss']['item']['description'] = \$change . ' {\$LastModifiedSummary}';".LF.LF;
      
      $str .= 'include_once("$FarmD/cookbook/wikilog.php");'.LF;
      $str .= 'include_once("$FarmD/cookbook/wikilog-i18n-de.php");'.LF;
      $str .= '$SHOW_CALENDAR = "' . $item->WikiEnableCalendar() . '";'.LF.LF;
      
      if($item->WikiEnableSwf() == "1"){
        $str .= 'include_once("$FarmD/cookbook/swf.php");'.LF.LF;
      }
      
      if($item->WikiEnableWmplayer() == "1"){
        $str .= 'include_once("$FarmD/cookbook/wmplayer.php");'.LF;
        $str .= "\$UploadExts['wma'] = 'audio/wma';".LF;
        $str .= "\$UploadExts['wmv'] = 'video/wmv';".LF.LF;
      }
      
      if($item->WikiEnableQuicktime() == "1"){
        $str .= 'include_once("$FarmD/cookbook/quicktime.php");'.LF.LF;
      }
      
      if($item->WikiEnableYoutubeGoogleVimeo() == "1"){
        $str .= 'include_once("$FarmD/cookbook/swf-sites2.php");'.LF.LF;
      }
      
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


}
?>