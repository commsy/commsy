<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Dr. Iver Jackewitz
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

// warning: this script must copy data from all room types
//          be carefull when coding

// copy data
$new_id_array = array();

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
$data_type_array[] = CS_STEP_TYPE;

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
$data_type_array[] = CS_STEP_TYPE;
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
   // (:item 12345:) fehlt
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
   // (:item 12345:) fehlt
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