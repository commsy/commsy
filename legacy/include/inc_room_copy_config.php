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

// copy this room settings
// standard
$copy_array = array();
$copy_array['context'] = true;
$copy_array['homeconf'] = true;
$copy_array['timespread'] = true;
$copy_array['extras'] = true;
$copy_array['plugins'] = true;
$copy_array['color'] = true;
$copy_array['todostatus'] = true;
$copy_array['usageinfo'] = true;
$copy_array['topicpath'] = true;
$copy_array['tag'] = true;
$copy_array['chat'] = true;
$copy_array['discussionstatus'] = true;
$copy_array['todomanagementstatus'] = true;
$copy_array['detailboxconf'] = true;
$copy_array['listboxconf'] = true;
$copy_array['homerightconf'] = true;
$copy_array['datespresentationstatus'] = true;
$copy_array['htmltextareastatus'] = true;
$copy_array['buzzword'] = true;
$copy_array['netnavigation'] = true;
$copy_array['emailtext'] = true;
$copy_array['title'] = true;
$copy_array['logo'] = true;
$copy_array['BGImage'] = true;
$copy_array['wiki'] = true;
$copy_array['informationbox'] = true;
$copy_array['myentrydisplayconf'] = false;
$copy_array['grouproomfct'] = false;
$copy_array['rss'] = true;
$copy_array['language'] = true;
$copy_array['visibilitydefaults'] = true;

// now adaption for special rooms
if ( $old_room->isProjectRoom() ) {
   $copy_array['grouproomfct'] = true;
}

if ( !$old_room->isPrivateRoom() ) {
   $copy_array['title'] = false;
}

// new private room
// only copy entry rubric
if ( $old_room->isPrivateRoom() ) {
   $copy_array['homeconf'] = false;
   $copy_array['timespread'] = false;
   $copy_array['plugins'] = false;
   $copy_array['color'] = false;
   $copy_array['usageinfo'] = false;
   $copy_array['chat'] = false;
   $copy_array['detailboxconf'] = false;
   $copy_array['listboxconf'] = false;
   $copy_array['homerightconf'] = false;
   $copy_array['datespresentationstatus'] = false;
   $copy_array['htmltextareastatus'] = false;
   $copy_array['emailtext'] = false;
   $copy_array['title'] = false;
   $copy_array['logo'] = false;
   $copy_array['wiki'] = false;
   $copy_array['informationbox'] = false;
   $copy_array['myentrydisplayconf'] = true;
}

// room context
if ( $copy_array['context'] ) {
   $new_room->setRoomContext($old_room->getRoomContext());
}

// config of home
if ( $copy_array['homeconf'] ) {
   $new_room->setHomeConf($old_room->getHomeConf());
}

// time spread
if ( $copy_array['timespread'] ) {
   if ( $old_room->isProjectRoom()
        or $old_room->isGroupRoom()
      ) {
      $new_room->setTimeSpread($old_room->getTimeSpread());
   }
}

// config of extras
if ( $copy_array['extras'] ) {
   $extra_config = $old_room->getExtraConfig();
   unset($extra_config['TEMPLATE']);
   $new_room->setExtraConfig($extra_config);
}

// config of plugins
if ( $copy_array['plugins'] ) {
   $new_room->setPluginConfig($old_room->getPluginConfig());
}

// config of colors
if ( $copy_array['color'] ) {
   $new_room->setColorArray($old_room->getColorArray());
   $new_room->generateLayoutImages();
}

//ToDos
if ( $copy_array['todostatus'] ) {
   $new_room->setExtraToDoStatusArray($old_room->getExtraToDoStatusArray());
}

// config of usage infos
if ( $copy_array['usageinfo'] ) {
   $new_room->setUsageInfoArray($old_room->getUsageInfoArray());
   $new_room->setUsageInfoHeaderArray($old_room->getUsageInfoHeaderArray());
   $new_room->setUsageInfoTextArray($old_room->getUsageInfoTextArray());
   $new_room->setUsageInfoFormArray($old_room->getUsageInfoFormArray());
   $new_room->setUsageInfoFormHeaderArray($old_room->getUsageInfoFormHeaderArray());
   $new_room->setUsageInfoFormTextArray($old_room->getUsageInfoFormTextArray());
}

// config of path
if ( $copy_array['topicpath'] ) {
   if ( $old_room->withPath() ) {
      $new_room->setWithPath();
   } else {
      $new_room->setWithoutPath();
   }
}

// config of tags
if ( $copy_array['tag'] ) {
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
   if ( $old_room->isTagsShowExpanded() ) {
      $new_room->setTagsShowExpanded();
   } else {
      $new_room->unsetTagsShowExpanded();
   }
}

// chat
if ( $copy_array['chat'] ) {
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
}

if ( $copy_array['discussionstatus'] ) {
   $new_room->setDiscussionStatus($old_room->getDiscussionStatus());
}
if ( $copy_array['todomanagementstatus'] ) {
   $new_room->setTodoManagmentStatus($old_room->getTodoManagmentStatus());
}
if ( $copy_array['detailboxconf'] ) {
   $new_room->setDetailBoxConf($old_room->getDetailBoxConf());
}
if ( $copy_array['listboxconf'] ) {
   $new_room->setListBoxConf($old_room->getListBoxConf());
}
if ( $copy_array['homerightconf'] ) {
   $new_room->setHomeRightConf($old_room->getHomeRightConf());
}
if ( $copy_array['datespresentationstatus'] ) {
   $new_room->setDatesPresentationStatus($old_room->getDatesPresentationStatus());
}
if ( $copy_array['htmltextareastatus'] ) {
   $new_room->setHtmlTextAreaStatus($old_room->getHtmlTextAreaStatus());
}
// config of buzzwords
if ( $copy_array['buzzword'] ) {
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
   if ( $old_room->isBuzzwordShowExpanded() ) {
      $new_room->setBuzzwordShowExpanded();
   } else {
      $new_room->unsetBuzzwordShowExpanded();
   }
}

// config of netnavigation
if ( $copy_array['netnavigation'] ) {
   if ( $old_room->isNetnavigationShowExpanded() ) {
      $new_room->setNetnavigationShowExpanded();
   } else {
      $new_room->unsetNetnavigationShowExpanded();
   }
   if ( $old_room->withNetnavigation() ) {
      $new_room->setWithNetnavigation();
   } else {
      $new_room->setWithoutNetnavigation();
   }
}

// config of email message tags
if ( $copy_array['emailtext'] ) {
   $new_room->setEmailTextArray($old_room->getEmailTextArray());
}

// title and logo
if ( $copy_array['title'] ) {
   if($old_room->isPrivateRoom()) {
      $title = $old_room->getTitlePure();
      if ( empty($title)
           or $title == $translator->getMessage('COMMON_PRIVATEROOM')
         ) {
         $title = 'PRIVATEROOM';
      }
      $new_room->setTitle($title);
   } else {
      $new_room->setTitle($old_room->getTitle());
   }
}
if ( $copy_array['logo'] ) {
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
if ( $copy_array['BGImage'] ) {
   $disc_manager = $environment->getDiscManager();
   if ( $old_room->getItemID() > 99 ) {
      if ( $disc_manager->copyImageFromRoomToRoom($old_room->getBGImageFilename(),$new_room->getItemID()) ) {
         $logo_file_name_new = str_replace($old_room->getItemID(),$new_room->getItemID(),$old_room->getBGImageFilename());
         $new_room->setBGImageFilename($logo_file_name_new);
      }
   } else {
      $new_room->setBGImageFilename('');
      $disc_manager->unlinkFile($new_room->getBGImageFilename());
   }
}

// wiki
//if ( $copy_array['wiki'] ) {
//   if ( $old_room->existWiki() ) {
//      $wiki_manager = $environment->getWikiManager();
//      $wiki_manager->copyWiki($old_room,$new_room);
//      unset($wiki_manager);
//   } else {
//      $new_room->unsetWikiExists();
//      // wiki config and wiki data will not be deleted
//   }
//}

// information box
if ( $copy_array['informationbox'] ) {
   if ( $old_room->withInformationBox() ) {
      $new_room->setwithInformationBox('yes');
   } else {
      $new_room->setwithInformationBox('no');
   }
}

// my entry display configuration
if ( $copy_array['myentrydisplayconf'] ) {
   $new_room->setMyEntriesDisplayConfig($old_room->getMyEntriesDisplayConfig());
}

// grouproom functions
if ( $copy_array['grouproomfct'] ) {
   if ( $old_room->withGrouproomFunctions() ) {
      $new_room->setWithGrouproomFunctions();
   } else {
      $new_room->setWithGrouproomFunctions();
   }
   if ( $old_room->isGrouproomActive() ) {
      $new_room->setGrouproomActive();
   } else {
      $new_room->setGrouproomInactive();
   }
}

// rss
if ($copy_array['rss']) {
    if ($old_room->isRSSOn()) {
        $new_room->turnRSSOn();
    } else {
        $new_room->turnRSSOff();
    }
}

// config of language
if ( $copy_array['language'] ) {
    $new_room->setLanguage($old_room->getLanguage());
}

// config of dates presentation status
if ( $copy_array['visibilitydefaults'] ) {
    if ($old_room->isActionBarVisibleAsDefault()) {
        $new_room->setActionBarVisibilityDefault('1');
    } else {
        $new_room->setActionBarVisibilityDefault('-1');
    }

    if ($old_room->isReferenceBarVisibleAsDefault()) {
        $new_room->setReferenceBarVisibilityDefault('1');
    } else {
        $new_room->setReferenceBarVisibilityDefault('-1');
    }

    if ($old_room->isDetailsBarVisibleAsDefault()) {
        $new_room->setDetailsBarVisibilityDefault('1');
    } else {
        $new_room->setDetailsBarVisibilityDefault('-1');
    }

    if ($old_room->isAnnotationsBarVisibleAsDefault()) {
        $new_room->setAnnotationsBarVisibilityDefault('1');
    } else {
        $new_room->setAnnotationsBarVisibilityDefault('-1');
    }
}

unset($copy_array);
?>