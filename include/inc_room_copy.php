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

################################################################
# copy a room (project rooms only)
# like a template (copy data but no users)
# into a new room
################################################################

// initialisation
$old_room_id = $_POST['template_select'];
$room_manager = $environment->getRoomManager();
$old_room = $room_manager->getItem($old_room_id);
$new_room = $item;
$user_manager = $environment->getUserManager();
$creator_item = $user_manager->getItem($new_room->getCreatorID());
if ($creator_item->getContextID() == $new_room->getItemID()) {
   $creator_id = $creator_item->getItemID();
} else {
   $user_manager->resetLimits();
   $user_manager->setContextLimit($new_room->getItemID());
   $user_manager->setUserIDLimit($creator_item->getUserID());
   $user_manager->setAuthSourceLimit($creator_item->getAuthSource());
   $user_manager->setModeratorLimit();
   $user_manager->select();
   $user_list = $user_manager->get();
   if ($user_list->isNotEmpty() and $user_list->getCount() == 1) {
      $creator_item = $user_list->getFirst();
      $creator_id = $creator_item->getItemID();
   } else {
      include_once('functions/error_functions.php');
      trigger_error('can not get creator of new room',E_USER_ERROR);
   }
}
$new_id_array = array();

// copy room settings

// room context
$new_room->setRoomContext($old_room->getRoomContext());

// config of home
$new_room->setHomeConf($old_room->getHomeConf());

// time spread
if ( $old_room->isProjectRoom() ){
   $new_room->setTimeSpread($old_room->getTimeSpread());
}

// config of extras
$extra_config = $old_room->getExtraConfig();
unset($extra_config['TEMPLATE']);
$new_room->setExtraConfig($extra_config);

// config of colors
$new_room->setColorArray($old_room->getColorArray());

// config of usage infos
$new_room->setUsageInfoArray($old_room->getUsageInfoArray());
$new_room->setUsageInfoHeaderArray($old_room->getUsageInfoHeaderArray());
$new_room->setUsageInfoTextArray($old_room->getUsageInfoTextArray());

$new_room->setUsageInfoFormArray($old_room->getUsageInfoFormArray());
$new_room->setUsageInfoFormHeaderArray($old_room->getUsageInfoFormHeaderArray());
$new_room->setUsageInfoFormTextArray($old_room->getUsageInfoFormTextArray());

// config of path
if ( $old_room->withPath() ) {
   $new_room->setWithPath();
} else {
   $new_room->setWithoutPath();
}

// config of tags
if ( $old_room->isTagMandatory() ) {
   $new_room->setTagMandatory();
} else {
   $new_room->unsetTagMandatory();
}
if ( $old_room->isTagEditedByAll() ) {
   $new_room->setTagEditedByAll();
} else {
   $new_room->setTagEditedByModerator();
}
if ( $old_room->withTags() ) {
   $new_room->setWithTags();
} else {
   $new_room->setWithoutTags();
}
if ( $old_room->withChatLink() ) {
   $new_room->setWithChatLink();
} else {
   $new_room->setWithoutChatLink();
}
if ( $old_room->isChatLinkActive() ) {
   $new_room->setChatLinkActive();
} else {
   $new_room->setChatLinkInactive();
}
$new_room->setDiscussionStatus($old_room->getDiscussionStatus());
$new_room->setDetailBoxConf($old_room->getDetailBoxConf());
$new_room->setListBoxConf($old_room->getListBoxConf());
$new_room->setHomeRightConf($old_room->getHomeRightConf());
$new_room->setDatesPresentationStatus($old_room->getDatesPresentationStatus());
$new_room->setHtmlTextAreaStatus($old_room->getHtmlTextAreaStatus());

// config of buzzwords
if ( $old_room->isBuzzwordMandatory() ) {
   $new_room->setBuzzwordMandatory();
} else {
   $new_room->unsetBuzzwordMandatory();
}
if ( $old_room->withBuzzwords() ) {
   $new_room->setWithBuzzwords();
} else {
   $new_room->setWithoutBuzzwords();
}

// config of tags
if ( $old_room->isTagMandatory() ) {
   $new_room->setTagMandatory();
} else {
   $new_room->unsetTagMandatory();
}
if ( $old_room->withTags() ) {
   $new_room->setWithTags();
} else {
   $new_room->setWithoutTags();
}

// config of email message tags
$new_room->setEmailTextArray($old_room->getEmailTextArray());

// save new room
$new_room->save();

// copy data
$data_type_array   = array();
$data_type_array[] = CS_ANNOUNCEMENT_TYPE;
$data_type_array[] = CS_DATE_TYPE;
$data_type_array[] = CS_DISCUSSION_TYPE;
$data_type_array[] = CS_LABEL_TYPE;
$data_type_array[] = CS_MATERIAL_TYPE;
$data_type_array[] = CS_FILE_TYPE;
$data_type_array[] = CS_TODO_TYPE;
$data_type_array[] = CS_TAG_TYPE;

foreach ($data_type_array as $type) {
   $manager = $environment->getManager($type);
   $id_array = $manager->copyDataFromRoomToRoom($old_room_id,$new_room->getItemID(),$creator_id);
   $new_id_array = $new_id_array + $id_array;
}
unset($data_type_array);

// copy secondary data
$data_type_array   = array();
$data_type_array[] = CS_ANNOTATION_TYPE;
$data_type_array[] = CS_DISCARTICLE_TYPE;
$data_type_array[] = CS_SECTION_TYPE;

foreach ($data_type_array as $type) {
   $manager = $environment->getManager($type);
   $id_array = $manager->copyDataFromRoomToRoom($old_room_id,$new_room->getItemID(),$creator_id,$new_id_array);
   $new_id_array = $new_id_array + $id_array;
}
unset($data_type_array);

// copy links
$data_type_array   = array();
$data_type_array[] = CS_LINK_TYPE;
$data_type_array[] = CS_LINKITEM_TYPE;
$data_type_array[] = CS_LINKITEMFILE_TYPE;
$data_type_array[] = CS_TAG2TAG_TYPE;

foreach ($data_type_array as $type) {
   $manager = $environment->getManager($type);
   $id_array = $manager->copyDataFromRoomToRoom($old_room_id,$new_room->getItemID(),$creator_id,$new_id_array);
   $new_id_array = $new_id_array + $id_array;
}
unset($data_type_array);


if ($old_room->withInformationBox()){
   $new_room->setwithInformationBox('yes');
   $id =$old_room->getInformationBoxEntryID();
   if (isset($new_id_array[$id])){
      $new_room->setInformationBoxEntryID($new_id_array[$id]);
   }
}


// link modifier item
$manager = $environment->getLinkModifierItemManager();
foreach ($id_array as $value) {
   if ( !mb_stristr($value,CS_FILE_TYPE) ) {
      $manager->markEdited($value,$creator_id);
   }
}

// now change all old item ids in descriptions with new IDs
// copy data
$data_type_array   = array();
$data_type_array[] = CS_ANNOUNCEMENT_TYPE;
$data_type_array[] = CS_DATE_TYPE;
$data_type_array[] = CS_LABEL_TYPE;
$data_type_array[] = CS_MATERIAL_TYPE;
$data_type_array[] = CS_TODO_TYPE;
$data_type_array[] = CS_ANNOTATION_TYPE;
$data_type_array[] = CS_DISCARTICLE_TYPE;
$data_type_array[] = CS_SECTION_TYPE;
#$data_type_array[] = CS_HOMEPAGE_TYPE;
foreach ($data_type_array as $type) {
   $manager = $environment->getManager($type);
   $manager->refreshInDescLinks($new_room->getItemID(),$new_id_array);
}
unset($data_type_array);


// now change all old item ids in usage infos with new IDs
$array = $new_room->getUsageInfoTextArray();
$new_array = array();
foreach ( $array as $key => $value ) {
   preg_match_all('~\[[0-9]*(\]|\|)~u', $value, $matches);
   if ( isset($matches[0]) ) {
      foreach ($matches[0] as $match) {
         $id = mb_substr($match,1);
         $last_char = mb_substr($id,mb_strlen($id));
         $id = mb_substr($id,0,mb_strlen($id)-1);
         if ( isset($new_id_array[$id]) ) {
            $value = str_replace('['.$id.$last_char,'['.$new_id_array[$id].$last_char,$value);
         }
      }
      $new_array[$key] = $value;
   }
}

$new_room->setUsageInfoTextArray($new_array);

$array = $new_room->getUsageInfoFormTextArray();
$new_array = array();
foreach ( $array as $key => $value ) {
   preg_match_all('~\[[0-9]*(\]|\|)~u', $value, $matches);
   if ( isset($matches[0]) ) {
      foreach ($matches[0] as $match) {
         $id = mb_substr($match,1);
         $last_char = mb_substr($id,mb_strlen($id));
         $id = mb_substr($id,0,mb_strlen($id)-1);
         if ( isset($new_id_array[$id]) ) {
            $value = str_replace('['.$id.$last_char,'['.$new_id_array[$id].$last_char,$value);
         }
      }
      $new_array[$key] = $value;
   }
}
$new_room->setUsageInfoFormTextArray($new_array);

$new_room->save();

############################################
# FLAG: group rooms
############################################
if ( $old_room->showGroupRoomFunctions() ) {
   // group rooms will not copied
   $group_manager = $environment->getGroupManager();
   $group_manager->setContextLimit($new_room->getItemID());
   $group_manager->select();
   $group_list = $group_manager->get();
   if ( $group_list->isNotEmpty() ) {
      $group_item = $group_list->getFirst();
      while ($group_item) {
         if ( $group_item->isGroupRoomActivated() ) {
            $group_item->unsetGroupRoomActive();
            $group_item->unsetGroupRoomItemID();
            $group_item->save();
         }
         $group_item = $group_list->getNext();
      }
   }
}
?>