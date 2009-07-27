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

// init $success
$success = true;

// headline
$this->_flushHTML('update wiki'.BRLF);

//ini_set("memory_limit","1000M");
//set_time_limit(600);
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
   // Backup der Inhalte
   $old_dir = getcwd();
   chdir($c_pmwiki_path_file);
   $directory_handle = @opendir('wikis/' . $portal->getItemID() . '/' . $room->getItemID() . '/wiki.d');
   if ($directory_handle) {
      chdir('wikis/' . $portal->getItemID() . '/' . $room->getItemID() . '/wiki.d');
      pr('updateWikiNavigation');
      if(file_exists('Site.SideBar') and
         file_exists('Main.SideBar')){
         pr('found both');
      }
      if(file_exists('Site.SideBar') and
        !file_exists('Main.SideBar')){
//         copy('Site.SideBar', 'Site.SideBar.backup');
//         updateNavigationForCommSyExports('Site.SideBar');
//         rename('Site.SideBar', 'Main.SideBar');
         pr('found Site.SideBar');
      }
      if(!file_exists('Site.SideBar') and
         file_exists('Main.SideBar')){
//         rename('Site.SideBar', 'Main.SideBar');
         pr('found Main.SideBar');
      }
      if(!file_exists('Site.SideBar') and
         !file_exists('Main.SideBar')){
         pr('found nothing');
      }
   }
   chdir($old_dir);
}

function updateNavigationForCommSyExports($file){
   $file_contents = file_get_contents($file);
   $file_contents_array = explode("\n", $file_contents);
   for ($index = 0; $index < sizeof($file_contents_array); $index++) {
      if(stripos($file_contents_array[$index], 'text=') !== false){
         // neue Inhalte einfügen
         $text = $file_contents_array[$index];
         if(stristr($text, '(:include Main.CommSyMaterialienNavi:)') or
            stristr($text, '(:include Main.CommSyDiskussionenNavi:)')){
           pr('update');
         }
      }
   }
   $file_contents = implode("\n", $file_contents_array);
   file_put_contents($file, $file_contents);
}
?>