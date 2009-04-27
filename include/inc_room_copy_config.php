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

// copy room settings

// room context
$new_room->setRoomContext($old_room->getRoomContext());

// config of home
$new_room->setHomeConf($old_room->getHomeConf());

// time spread
if ( $old_room->isProjectRoom()
     or $old_room->isGroupRoom()
   ) {
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

// chat
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
$new_room->setTodoManagmentStatus($old_room->getTodoManagmentStatus());
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

// config of email message tags
$new_room->setEmailTextArray($old_room->getEmailTextArray());

// design
if ( $old_room->isDesign7() ) {
   $new_room->setDesignTo7();
} elseif( $old_room->isDesign6() ) {
   $new_room->setDesignTo6();
}

// title and logo
if ( $old_room->isPrivateRoom() ) {
   $title = $old_room->getTitlePure();
   if ( empty($title)
        or $title == $translator->getMessage('COMMON_PRIVATEROOM')
      ) {
      $title = 'PRIVATEROOM';
   }
   $new_room->setTitle($title);

   $disc_manager = $environment->getDiscManager();
   if ( $old_room->getItemID() > 99 ) {
      if ( $disc_manager->copyImageFromRoomToRoom($old_room->getLogoFilename(),$new_room->getItemID()) ) {
         $logo_file_name_new = str_replace($old_room->getItemID(),$new_room->getItemID(),$old_room->getLogoFilename());
         $new_room->setLogoFilename($logo_file_name_new);
      }
   } else {
      $new_room->setLogoFilename('');
      $disc_manager->unlinkFile($new_room->getLogoFilename());
   }
}

// wiki
if ( $old_room->existWiki() ) {
   $wiki_manager = $environment->getWikiManager();
   $wiki_manager->copyWiki($old_room,$new_room);
   unset($wiki_manager);
} else {
   $new_room->unsetWikiExists();
   // wiki config and wiki data will not be deleted
}

// information box
if ( $old_room->withInformationBox() ) {
   $new_room->setwithInformationBox('yes');
} else {
   $new_room->setwithInformationBox('no');
}
?>