<?PHP
// $Id$
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

$portal_id = $environment->getCurrentPortalId();

// Get the translator object
$translator = $environment->getTranslationObject();

if (!empty($_GET['iid'])) {
   $iid = $_GET['iid']; // item id of the room
}

$user = $environment->getCurrentUser();
if($user->isRoot()){

   // Find out what to do
   if ( isset($_GET['to']) ) {
      $direction = $_GET['to'];
   } else {
      $direction = '';
   }

   // get grouprooms of room
   $group_room_id_array = array();
   if($direction == 'backup'){
      $group_room_manager = $environment->getGroupRoomManager();
      $group_room_manager->setProjectroomLimit($iid);
      $group_room_manager->select();
      $group_room_list = $group_room_manager->get();
      $group_room_item = $group_room_list->getFirst();
      while($group_room_item){
         $group_room_id_array[] = $group_room_item->getItemID();
         $group_room_item = $group_room_list->getNext();
      }
   } elseif ($direction == 'live') {
      $zzz_group_room_manager = $environment->getZzzGroupRoomManager();
      $zzz_group_room_manager->setProjectroomLimit($iid);
      $zzz_group_room_manager->select();
      $group_room_list = $zzz_group_room_manager->get();
      $group_room_item = $group_room_list->getFirst();
      while($group_room_item){
         $group_room_id_array[] = $group_room_item->getItemID();
         $group_room_item = $group_room_list->getNext();
      }
   }

   move_room($iid, $direction);

   foreach($group_room_id_array as $group_room_id){
      move_room($group_room_id, $direction);
   }

}
// back to index page
$params = array();
$params['room_id'] = $iid;
redirect($portal_id,'home','index',$params);

function move_room($iid, $direction){
   global $environment;

   if($direction == 'backup'){
      // Managers that need data from other tables
      $hash_manager = $environment->getHashManager();
      $hash_manager->moveFromDbToBackup($iid);

      $link_modifier_item_manager = $environment->getLinkModifierItemManager();
      $link_modifier_item_manager->moveFromDbToBackup($iid);

      $link_item_file_manager = $environment->getLinkItemFileManager();
      $link_item_file_manager->moveFromDbToBackup($iid);

      $noticed_manager = $environment->getNoticedManager();
      $noticed_manager->moveFromDbToBackup($iid);

      $reader_manager = $environment->getReaderManager();
      $reader_manager->moveFromDbToBackup($iid);

      // Plain copy of the rest
      $annotation_manager = $environment->getAnnotationManager();
      $annotation_manager->moveFromDbToBackup($iid);

      $announcement_manager = $environment->getAnnouncementManager();
      $announcement_manager->moveFromDbToBackup($iid);

      $dates_manager = $environment->getDatesManager();
      $dates_manager->moveFromDbToBackup($iid);

      $discussion_manager = $environment->getDiscussionManager();
      $discussion_manager->moveFromDbToBackup($iid);

      $discussionarticles_manager = $environment->getDiscussionarticleManager();
      $discussionarticles_manager->moveFromDbToBackup($iid);

      $file_manager = $environment->getFileManager();
      $file_manager->moveFromDbToBackup($iid);

      $homepage_link_manager = $environment->getHomepageLinkManager();
      $homepage_link_manager->moveFromDbToBackup($iid);

      $homepage_manager = $environment->getHomepageManager();
      $homepage_manager->moveFromDbToBackup($iid);

      $item_manager = $environment->getItemManager();
      $item_manager->moveFromDbToBackup($iid);

      $labels_manager = $environment->getLabelManager();
      $labels_manager->moveFromDbToBackup($iid);

      $links_manager = $environment->getLinkManager();
      $links_manager->moveFromDbToBackup($iid);

      $link_item_manager = $environment->getLinkItemManager();
      $link_item_manager->moveFromDbToBackup($iid);

      $material_manager = $environment->getMaterialManager();
      $material_manager->moveFromDbToBackup($iid);

      $section_manager = $environment->getSectionManager();
      $section_manager->moveFromDbToBackup($iid);

      $step_manager = $environment->getStepManager();
      $step_manager->moveFromDbToBackup($iid);

      $tag_manager = $environment->getTagManager();
      $tag_manager->moveFromDbToBackup($iid);

      $tag2tag_manager = $environment->getTag2TagManager();
      $tag2tag_manager->moveFromDbToBackup($iid);

      $task_manager = $environment->getTaskManager();
      $task_manager->moveFromDbToBackup($iid);

      $todo_manager = $environment->getTodoManager();
      $todo_manager->moveFromDbToBackup($iid);

      $user_manager = $environment->getUserManager();
      $user_manager->moveFromDbToBackup($iid);

      $room_manager = $environment->getRoomManager();
      $room_manager->moveFromDbToBackup($iid);
   } elseif ($direction == 'live') {
      // Managers that need data from other tables
      $hash_manager = $environment->getHashManager();
      $hash_manager->moveFromBackupToDb($iid);

      $link_modifier_item_manager = $environment->getLinkModifierItemManager();
      $link_modifier_item_manager->moveFromBackupToDb($iid);

      $link_item_file_manager = $environment->getLinkItemFileManager();
      $link_item_file_manager->moveFromBackupToDb($iid);

      $noticed_manager = $environment->getNoticedManager();
      $noticed_manager->moveFromBackupToDb($iid);

      $reader_manager = $environment->getReaderManager();
      $reader_manager->moveFromBackupToDb($iid);

      // Plain copy of the rest
      $annotation_manager = $environment->getAnnotationManager();
      $annotation_manager->moveFromBackupToDb($iid);

      $announcement_manager = $environment->getAnnouncementManager();
      $announcement_manager->moveFromBackupToDb($iid);

      $dates_manager = $environment->getDatesManager();
      $dates_manager->moveFromBackupToDb($iid);

      $discussion_manager = $environment->getDiscussionManager();
      $discussion_manager->moveFromBackupToDb($iid);

      $discussionarticles_manager = $environment->getDiscussionarticleManager();
      $discussionarticles_manager->moveFromBackupToDb($iid);

      $file_manager = $environment->getFileManager();
      $file_manager->moveFromBackupToDb($iid);

      $homepage_link_manager = $environment->getHomepageLinkManager();
      $homepage_link_manager->moveFromBackupToDb($iid);

      $homepage_manager = $environment->getHomepageManager();
      $homepage_manager->moveFromBackupToDb($iid);

      $item_manager = $environment->getItemManager();
      $item_manager->moveFromBackupToDb($iid);

      $labels_manager = $environment->getLabelManager();
      $labels_manager->moveFromBackupToDb($iid);

      $links_manager = $environment->getLinkManager();
      $links_manager->moveFromBackupToDb($iid);

      $link_item_manager = $environment->getLinkItemManager();
      $link_item_manager->moveFromBackupToDb($iid);

      $material_manager = $environment->getMaterialManager();
      $material_manager->moveFromBackupToDb($iid);

      $section_manager = $environment->getSectionManager();
      $section_manager->moveFromBackupToDb($iid);

      $step_manager = $environment->getStepManager();
      $step_manager->moveFromBackupToDb($iid);

      $tag_manager = $environment->getTagManager();
      $tag_manager->moveFromBackupToDb($iid);

      $tag2tag_manager = $environment->getTag2TagManager();
      $tag2tag_manager->moveFromBackupToDb($iid);

      $task_manager = $environment->getTaskManager();
      $task_manager->moveFromBackupToDb($iid);

      $todo_manager = $environment->getTodoManager();
      $todo_manager->moveFromBackupToDb($iid);

      $user_manager = $environment->getUserManager();
      $user_manager->moveFromBackupToDb($iid);

      $room_manager = $environment->getRoomManager();
      $room_manager->moveFromBackupToDb($iid);
   }
}
?>