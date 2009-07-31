<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Iver Jackewitz
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

// new version of the update mechanism
// -----------------------------------
// the following is part of the method "asHTML"
// from the object cs_update_view.php

set_time_limit(0);

$memory_limit2 = 1000 * 1024 * 1024;
$memory_limit = ini_get('memory_limit');
if ( !empty($memory_limit) ) {
   if ( strstr($memory_limit,'M') ) {
      $memory_limit = substr($memory_limit,0,strlen($memory_limit)-1);
      $memory_limit = $memory_limit * 1024 * 1024;
   } elseif ( strstr($memory_limit,'K') ) {
      $memory_limit = substr($memory_limit,0,strlen($memory_limit)-1);
      $memory_limit = $memory_limit * 1024;
   }
}
if ( $memory_limit < $memory_limit2 ) {
   ini_set('memory_limit',$memory_limit2);
   $memory_limit3 = ini_get('memory_limit');
   if ( $memory_limit3 != $memory_limit2 ) {
      $this->_flushHTML('Can not set memory limit. Please try 1000M in your php.ini and run this script again.'.BRLF);
      exit();
   }
}

// init $success
$success = true;

// headline
$this->_flushHTML('update wiki'.BRLF);

$portal_manager = $this->_environment->getPortalManager();
$portal_manager->setContextLimit($this->_environment->getCurrentContextID());
$portal_manager->select();
$portal_list = $portal_manager->get();
$portal = $portal_list->getFirst();
while ( $portal ) {
   $room_manager = $this->_environment->getRoomManager();
   $room_manager->setContextLimit($portal->getItemID());
   $room_manager->select();
   $room_list = $room_manager->get();
   $room = $room_list->getFirst();
   while ( $room ) {
      $wiki_manager = $this->_environment->getWikiManager();
      if($room->existWiki()){
         $wiki_manager->updateWiki($portal, $room);
         updateWikiNavigation($portal, $room);
      }
      $room = $room_list->getNext();
   }
   $portal = $portal_list->getNext();
}

function updateWikiNavigation($portal, $room){
   global $c_commsy_path_file;
   global $c_pmwiki_path_file;
   $old_dir = getcwd();
   chdir($c_pmwiki_path_file);
   $directory_handle = @opendir('wikis/' . $portal->getItemID() . '/' . $room->getItemID() . '/wiki.d');
   if ($directory_handle) {
      chdir('wikis/' . $portal->getItemID() . '/' . $room->getItemID() . '/wiki.d');
      if(file_exists('Site.SideBar') and
         file_exists('Main.SideBar')){
         correctLinksInSideBar('Site.SideBar');
         copyLinks('Site.SideBar', 'Main.SideBar');
         updateNavigationForCommSyExports('Main.SideBar');
         addUpdateComment('Main.SideBar');
         rename('Site.SideBar', 'Site.SideBarBackup');
      } else if(!file_exists('Site.SideBar') and
                file_exists('Main.SideBar')){
         updateNavigationForStandardLinks('Main.SideBar');
         updateNavigationForCommSyExports('Main.SideBar');
         addUpdateComment('Main.SideBar');
      } else if(file_exists('Site.SideBar') and
                !file_exists('Main.SideBar')){
         correctLinksInSideBar('Site.SideBar');
         updateNavigationForCommSyExports('Site.SideBar');
         copy('Site.SideBar', 'Site.SideBarBackup');
         rename('Site.SideBar', 'Main.SideBar');
      } else if(!file_exists('Site.SideBar') and
                !file_exists('Main.SideBar')){
      }
   }
   chdir($old_dir);
}

function updateNavigationForCommSyExports($file){
   $file_contents = file_get_contents($file);
   $file_contents_array = explode("\n", $file_contents);
   for ($index = 0; $index < sizeof($file_contents_array); $index++) {
      if(stripos($file_contents_array[$index], 'text=') !== false){
         $text = $file_contents_array[$index];
         if(stristr($text, '(:include Main.CommSyMaterialienNavi:)') or
            stristr($text, '(:include Main.CommSyDiskussionenNavi:)')){
         } else {
            $file_contents = file_get_contents($file);
            $file_contents_array = explode("\n", $file_contents);
            for ($index = 0; $index < sizeof($file_contents_array); $index++) {
               if(stripos($file_contents_array[$index], 'text=') !== false){
                  $text = $file_contents_array[$index];
                  $text = $text . '%0a%0a(:include Main.CommSyMaterialienNavi:)%0a(:include Main.CommSyDiskussionenNavi:)';
                  $file_contents_array[$index] = $text;
               }
            }
            $file_contents = implode("\n", $file_contents_array);
            file_put_contents($file, $file_contents);
         }
      }
   }
   $file_contents = implode("\n", $file_contents_array);
   file_put_contents($file, $file_contents);
}

function updateNavigationForStandardLinks($file){
   $file_contents = file_get_contents($file);
   $file_contents_array = explode("\n", $file_contents);
   for ($index = 0; $index < sizeof($file_contents_array); $index++) {
      if(stripos($file_contents_array[$index], 'text=') !== false){
         $text = $file_contents_array[$index];
         $file_contents = file_get_contents($file);
         $file_contents_array = explode("\n", $file_contents);
         for ($index = 0; $index < sizeof($file_contents_array); $index++) {
            if(stripos($file_contents_array[$index], 'text=') !== false){
               $text = $file_contents_array[$index];
               $text = substr($text, 5, strlen($text)-5);
               $text = 'text=%25sidehead%25 Navigation%0a%0a* [[Main/HomePage]]%0a* [[PmWikiDe/Anleitung]]%0a%0a' . $text;
               if(!stristr($text, '(:include Site.Navi:)')){
                  $text = $text . '%0a%0a(:include Site.Navi:)';
               }
               $file_contents_array[$index] = $text;
            }
         }
         $file_contents = implode("\n", $file_contents_array);
         file_put_contents($file, $file_contents);
      }
   }
   $file_contents = implode("\n", $file_contents_array);
   file_put_contents($file, $file_contents);
}

function correctLinksInSideBar($file){
   $file_contents = file_get_contents($file);
   $file_contents_array = explode("\n", $file_contents);
   for ($index = 0; $index < sizeof($file_contents_array); $index++) {
      if(stripos($file_contents_array[$index], 'text=') !== false){
         $text = $file_contents_array[$index];
         preg_match_all('~\[\[[a-zA-Z0-9]*?\]\]~u', $text, $matches);
         foreach($matches as $match){
            if(is_array($match)){
               foreach($match as $link){
                  $new_link = str_replace('[[', '[[Site.', $link);
                  $text = str_replace($link, $new_link, $text);
               }
            }
         }
         $file_contents_array[$index] = $text;
      }
   }
   $file_contents = implode("\n", $file_contents_array);
   file_put_contents($file, $file_contents);
}

function copyLinks($from, $to){
   // Inhalte Suchen
   $site_content = '';
   $file_contents = file_get_contents($from);
   $file_contents_array = explode("\n", $file_contents);
   for ($index = 0; $index < sizeof($file_contents_array); $index++) {
      if(stripos($file_contents_array[$index], 'text=') !== false){
         $text = $file_contents_array[$index];
         $text = substr($text, 5, strlen($text)-5);
         $site_content = $text;
      }
   }

   // Inhalte hinzufuegen
   $file_contents = file_get_contents($to);
   $file_contents_array = explode("\n", $file_contents);
   for ($index = 0; $index < sizeof($file_contents_array); $index++) {
      if(stripos($file_contents_array[$index], 'text=') !== false){
         $text = $file_contents_array[$index];
         $text = $text = str_replace('(:include Main.CommSyMaterialienNavi:)%0a(:include Main.CommSyDiskussionenNavi:)', '', $text);
         $text = $text . '%0a%0a' . $site_content . '%0a%0a(:include Main.CommSyMaterialienNavi:)%0a(:include Main.CommSyDiskussionenNavi:)';
         $file_contents_array[$index] = $text;
      }
   }
   $file_contents = implode("\n", $file_contents_array);
   file_put_contents($to, $file_contents);
}

function addUpdateComment($file){
   $file_contents = file_get_contents($file);
   $file_contents_array = explode("\n", $file_contents);
   for ($index = 0; $index < sizeof($file_contents_array); $index++) {
      if(stripos($file_contents_array[$index], 'text=') !== false){
         $text = $file_contents_array[$index];
         $file_contents = file_get_contents($file);
         $file_contents_array = explode("\n", $file_contents);
         for ($index = 0; $index < sizeof($file_contents_array); $index++) {
            if(stripos($file_contents_array[$index], 'text=') !== false){
               $text = $file_contents_array[$index];
               $text = $text . '%0a%0a(:comment Durch ein Update der Wiki-Version musste die Navigaton angepasst werden. Die Seite Main.SideBar wird nun für alle Seite als Navigation genutzt. Dadurch wurde die Navigation in Ihrem Wiki evtl. verändert. Frühere Anpassungen der Main.SideBar sind weiterhin enthalten. Frühere Anpassungen der Site.SideBar wurden in die Main.Sidebar kopiert. Ein Backup der Site.SideBar ist als Site.SideBarBackup zugreifbar:)';
               $file_contents_array[$index] = $text;
            }
         }
         $file_contents = implode("\n", $file_contents_array);
         file_put_contents($file, $file_contents);
      }
   }
   $file_contents = implode("\n", $file_contents_array);
   file_put_contents($file, $file_contents);
}
?>