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

// copy this room settings and data
// standard
$copy_array = array();
$copy_array['informationbox'] = true;
$copy_array['usageinfo'] = true;
$copy_array['grouproom'] = false;

// now adaption for special rooms
if ( $old_room->isProjectRoom() ) {
   $copy_array['grouproom'] = true;
}

// new private room
// only copy entry rubric
if ( $old_room->isPrivateRoom() ) {
   $copy_array['usageinfo'] = false;
   $copy_array['informationbox'] = false;
}

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


if ( $copy_array['informationbox'] ) {
   if ($old_room->withInformationBox()){
      $new_room->setwithInformationBox('yes');
      $id =$old_room->getInformationBoxEntryID();
      if (isset($new_id_array[$id])){
         $new_room->setInformationBoxEntryID($new_id_array[$id]);
      }
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
foreach ($data_type_array as $type) {
   $manager = $environment->getManager($type);
   $manager->refreshInDescLinks($new_room->getItemID(),$new_id_array);
}
unset($data_type_array);

$arrayContextID = array();
$arrayContextID[$old_room_id] = $new_room->getItemID();
$new_id_array = $new_id_array + $arrayContextID;

// now change all old item ids in usage infos with new IDs
if ( $copy_array['usageinfo'] ) {
   $array = $new_room->getUsageInfoTextArray();
   $new_array = array();
   foreach ( $array as $key => $value ) {
      $replace = false;
      preg_match_all('~\[[0-9]*(\]|\|)~u', $value, $matches);
      if ( isset($matches[0]) ) {
         foreach ($matches[0] as $match) {
            $id = mb_substr($match,1);
            $last_char = mb_substr($id,mb_strlen($id));
            $id = mb_substr($id,0,mb_strlen($id)-1);
            if ( isset($new_id_array[$id]) ) {
               $value = str_replace('['.$id.$last_char,'['.$new_id_array[$id].$last_char,$value);
               $replace = true;
            }
         }
         $new_array[$key] = $value;
      }
      preg_match_all('~\(:item ([0-9]*) ~u', $value, $matches);
      if ( isset($matches[1])
           and !empty($matches[1])
         ) {
         foreach ($matches[1] as $match) {
            $id = $match;
            if ( isset($new_id_array[$id]) ) {
               $value = str_replace('(:item '.$id,'(:item '.$new_id_array[$id],$value);
               $replace = true;
            }
         }
         $new_array[$key] = $value;
      }
      #cid=([0-9]*)
      preg_match_all('~iid=([0-9]*) ~u', $value, $matches);
      if ( isset($matches[0])
           and !empty($matches[0])
         ) {
         foreach ($matches[1] as $match) {
            $id = $match;
            if ( isset($new_id_array[$id]) ) {
               $value = str_replace('iid='.$id,'iid='.$new_id_array[$id],$value);
               $replace = true;
            }
         }
         $new_array[$key] = $value;
      }

      preg_match_all('~cid=([0-9]*) ~xu', $value, $matches);
      if ( isset($matches[0])
           and !empty($matches[0])
         ) {
         foreach ($matches[1] as $match) {
            $id = $match;
            if ( isset($new_id_array[$id]) ) {
               $value = str_replace('cid='.$id,'cid='.$new_room->getItemID(),$value);
               $replace = true;
            }
         }
         $new_array[$key] = $value;
      }

      // html textarea security
      if ( !empty($new_array[$key])
           and $replace
         ) {
         if ( strstr($new_array[$key],'<!-- KFC TEXT') ) {
            include_once('functions/security_functions.php');
            $new_array[$key] = renewSecurityHash($new_array[$key]);
         }
      }
   }

   $new_room->setUsageInfoTextArray($new_array);

   $array = $new_room->getUsageInfoFormTextArray();
   $new_array = array();
   foreach ( $array as $key => $value ) {
      $replace = false;
      preg_match_all('~\[[0-9]*(\]|\|)~u', $value, $matches);
      if ( isset($matches[0]) ) {
         foreach ($matches[0] as $match) {
            $id = mb_substr($match,1);
            $last_char = mb_substr($id,mb_strlen($id));
            $id = mb_substr($id,0,mb_strlen($id)-1);
            if ( isset($new_id_array[$id]) ) {
               $value = str_replace('['.$id.$last_char,'['.$new_id_array[$id].$last_char,$value);
               $replace = true;
            }
         }
         $new_array[$key] = $value;
      }
      preg_match_all('~\(:item ([0-9]*) ~u', $value, $matches);
      if ( isset($matches[1])
           and !empty($matches[1])
         ) {
         foreach ($matches[1] as $match) {
            $id = $match;
            if ( isset($new_id_array[$id]) ) {
               $value = str_replace('(:item '.$id,'(:item '.$new_id_array[$id],$value);
               $replace = true;
            }
         }
         $new_array[$key] = $value;
      }

      preg_match_all('~iid=([0-9]*) ~u', $value, $matches);
      if ( isset($matches[0])
           and !empty($matches[0])
         ) {
         foreach ($matches[1] as $match) {
            $id = $match;
            if ( isset($new_id_array[$id]) ) {
               $value = str_replace('iid='.$id,'iid='.$new_id_array[$id],$value);
               $replace = true;
            }
         }
         $new_array[$key] = $value;
      }

      preg_match_all('~cid=([0-9]*) ~xu', $value, $matches);
      if ( isset($matches[0])
           and !empty($matches[0])
         ) {
         foreach ($matches[1] as $match) {
            $id = $match;
            if ( isset($new_id_array[$id]) ) {
               $value = str_replace('cid='.$id,'cid='.$new_room->getItemID(),$value);
               $replace = true;
            }
         }
         $new_array[$key] = $value;
     }

      // html textarea security
      if ( !empty($new_array[$key])
           and $replace
         ) {
         if ( strstr($new_array[$key],'<!-- KFC TEXT') ) {
            include_once('functions/security_functions.php');
            $new_array[$key] = renewSecurityHash($new_array[$key]);
         }
      }
   }
   $new_room->setUsageInfoFormTextArray($new_array);
}

// information box
if ( $copy_array['informationbox'] ) {
   if ( $old_room->withInformationBox()
        and isset($new_id_array)
        and !empty($new_id_array[$old_room->getInformationBoxEntryID()])
      ) {
      $new_room->setInformationBoxEntryID($new_id_array[$old_room->getInformationBoxEntryID()]);
   }
}

$new_room->save();

// save all newly created items which also causes Elastic to index them
global $symfonyContainer;
$itemService = $symfonyContainer->get(\App\Utils\ItemService::class);
$itemManager = $environment->getItemManager();
/** @var \cs_list $itemList list of cs_items */
$itemList = $itemManager->getItemList($new_id_array);
foreach ($itemList as $item) {
    $itemId = $item->getItemID();
    if ($itemId != $new_room->getItemID()) {
        $typedItem = $itemService->getTypedItem($itemId);
        $typedItem->save();
    }
}

############################################
# FLAG: group rooms
############################################
if ( $copy_array['informationbox'] ) {
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
}

unset($copy_array);
?>