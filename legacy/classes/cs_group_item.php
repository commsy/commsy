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

/** upper class of the label item
 */
include_once('classes/cs_label_item.php');
include_once('functions/text_functions.php');

/** class for a label
 * this class implements a commsy label. A label can be a group, a topic, a label, ...
 */
class cs_group_item extends cs_label_item {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param string label_type type of the label
    */
   public function __construct ( $environment ) {
      cs_label_item::__construct($environment,CS_GROUP_TYPE);
   }

   public function isGroupRoomActivated () {
      $retour = false;
      $value = $this->_getGroupRoomActive();
      if ( $value == '1' ) {
         $retour = true;
      }
      return $retour;
   }

   private function _getGroupRoomActive () {
      $retour = '';
      if ( $this->_issetExtra('GROUP_ROOM_ACTIVE') ) {
         $retour = $this->_getExtra('GROUP_ROOM_ACTIVE');
      }
      return $retour;
   }

   public function setGroupRoomActive () {
      $this->_setExtra('GROUP_ROOM_ACTIVE','1');
   }

   public function unsetGroupRoomActive () {
      $this->_setExtra('GROUP_ROOM_ACTIVE','-1');
   }

   public function setGroupRoomItemID ( $value ) {
      if ( !empty($value) ) {
         $this->_setExtra('GROUP_ROOM_ID',(int)$value);
      }
   }

   public function unsetGroupRoomItemID () {
      $this->_unsetExtra('GROUP_ROOM_ID');
   }

   public function getGroupRoomItemID () {
      $retour = '';
      if ( $this->_issetExtra('GROUP_ROOM_ID') ) {
         $retour = $this->_getExtra('GROUP_ROOM_ID');
      }
      return $retour;
   }

   public function setDiscussionNotificationArray ( $value ) {
      if ( !empty($value) ) {
         $value = implode('§§', $value);
         $this->_setExtra('DISCUSSION_NOTIFICATION_ARRAY',(string)$value);
      } else {
         $this->_unsetExtra('DISCUSSION_NOTIFICATION_ARRAY');
      }
   }

   public function getDiscussionNotificationArray () {
      $retour = array();
      if ( $this->_issetExtra('DISCUSSION_NOTIFICATION_ARRAY') ) {
         $retour = $this->_getExtra('DISCUSSION_NOTIFICATION_ARRAY');
         $retour = explode('§§', $retour);
      }
      return $retour;
   }

   public function getGroupRoomItem () {
      $retour = NULL;
      if ( $this->_issetGroupRoomItemID() ) {
         $item_id = $this->getGroupRoomItemID();
         $grouproom_manager = $this->_environment->getGroupRoomManager();
         $group_room = $grouproom_manager->getItem($item_id);
         if ( isset($group_room) and !empty($group_room) and !$group_room->isDeleted() ) {
            $retour = $group_room;
         }
      }
      return $retour;
   }

   private function _issetGroupRoomItemID () {
      $retour = false;
      $item_id = $this->getGroupRoomItemID();
      if ( !empty($item_id) ) {
         $retour = true;
      }
      return $retour;
   }

   /** save news item
    * this methode save the news item into the database
    */
   function save ( $save_other = true ) {
      if ( !$this->_issetGroupRoomItemID() and $this->isGroupRoomActivated() and $save_other ) {
          $new_group_room = true;
         // initiate group room
         $grouproom_manager = $this->_environment->getGroupRoomManager();
         $grouproom_item = $grouproom_manager->getNewItem();
         $grouproom_item->setTitle($this->getTitle());
         $grouproom_item->setContextID($this->_environment->getCurrentPortalID());
         $grouproom_item->setLinkedProjectRoomItemID($this->getContextID());
         $grouproom_item->setCheckNewMemberNever();
         $current_context = $this->_environment->getCurrentContextItem();
         $language = $current_context->getLanguage();
         $grouproom_item->setLanguage($language);
         if ( $language == 'user' ) {
            $language = 'de';
         }
         $grouproom_item->setDescriptionByLanguage($this->getDescription(),$language);
         $grouproom_item->open();
         $grouproom_item->setHtmlTextAreaStatus($current_context->getHtmlTextAreaStatus());

         // disable RRS-Feed for new project and community rooms
         $grouproom_item->turnRSSOff();

         $item_id = $this->getItemID();
         if ( !empty($item_id) ) {
            $grouproom_item->setLinkedGroupItemID($item_id);
         } else {
            $save2 = true;
         }

         // picture / logo
         $logo = $this->getPicture();

         // Zeitpunkte
         $portal_item = $this->_environment->getCurrentPortalItem();
         if ( $portal_item->showTime() ) {
            $save_time = true;
         }

         $grouproom_item->saveOnlyItem();

         // add member of group to the group room
         $current_user_item = $this->_environment->getCurrentUserItem();
         $member_list = $this->getMemberItemList();

         if ( $member_list->isNotEmpty() ) {
            $member_item = $member_list->getFirst();
            while ( $member_item ) {
               if ( $member_item->getItemID() != $current_user_item->getItemID() ) {
                  $private_room_user_item = $member_item->getRelatedPrivateRoomUserItem();
                  $new_member_item = $private_room_user_item->cloneData();
                  $new_member_item->setContextID($grouproom_item->getItemID());
                  $new_member_item->makeUser();

                  if ($portal_item->getConfigurationHideMailByDefault()) {
                     $new_member_item->setEmailNotVisible();
                  }

                  $picture = $private_room_user_item->getPicture();
                  if ( !empty($picture) ) {
                     $value_array = explode('_',$picture);
                     $value_array[0] = 'cid'.$new_member_item->getContextID();
                     $new_picture_name = implode('_',$value_array);
                     $disc_manager = $this->_environment->getDiscManager();
                     $disc_manager->copyImageFromRoomToRoom($picture,$new_member_item->getContextID());
                     $new_member_item->setPicture($new_picture_name);
                  }

                  $new_member_item->save();
                  $new_member_item->setCreatorID2ItemID();
               }
               $member_item = $member_list->getNext();
            }
         }
         // add current user to the group as a member
         if ( !$this->isMember($current_user_item) ) {
            $add_member = true;
         }

      } elseif ( $this->_issetGroupRoomItemID()
                 and $save_other
               ) {
         $grouproom_item = $this->getGroupRoomItem();
         if ( isset($grouproom_item) and !empty($grouproom_item) ) {
            $grouproom_item->setTitle($this->getTitle());

            // desctiption
            $current_context = $this->_environment->getCurrentContextItem();
            $language = $current_context->getLanguage();
            $grouproom_item->setLanguage($language);
            if ( $language == 'user' ) {
               $language = 'de';
            }
            $grouproom_item->setDescriptionByLanguage($this->getDescription(),$language);
            // picture / logo
            $logo = $this->getPicture();
            if ( empty($logo) ) {
               $grouproom_item->setLogoFilename('');
            }
            $save2 = true;
         }
      }

      $label_manager = $this->_environment->getLabelManager();
      $this->_save($label_manager);

      if ( isset($save_time) and $save_time ) {
         $context_item = $this->_environment->getCurrentContextItem();
         if ( $context_item->isContinuous() ) {
            $grouproom_item->setContinuous();
            $save2 = true;
         }
         $time_list = $context_item->getTimeList();
         if ( $time_list->isNotEmpty() ) {
            $grouproom_item->setTimeList($time_list);
            $save2 = true;
         }
      }
      if ( isset($logo) and !empty($logo) ) {
         $disc_manager = $this->_environment->getDiscManager();
         $disc_manager->copyImageFromRoomToRoom($logo,$grouproom_item->getItemID());
         $grouproom_item->setLogoFilename($disc_manager->getLastSavedFileName());
         $save2 = true;
      }
      if ( isset($save2) and $save2 and $save_other ) {
         $grouproom_item->setLinkedGroupItemID($this->getItemID());
         $grouproom_item->saveOnlyItem();
      }
      if ( isset($new_group_room) and $new_group_room ) {
         $this->setGroupRoomItemID($grouproom_item->getItemID());
         $this->_save($label_manager);
      }
      // add current user to the group as a member
      if ( isset($add_member) and $add_member ) {
         $this->addMember($current_user_item);
      }

      $this->updateElastic();

      unset($current_user_item);
   }

   /** save news item
    * this methode save the news item into the database
    */
   function saveOnlyItem () {
      $this->save(false);
   }

   function updateWikiNotification(){
      $wiki_manager = $this->_environment->getWikiManager();
      $wiki_manager->updateNotification();
   }

   /** delete group item
    * this methode delete the group item
    * with the group room
    */
   function delete() {
      $room = $this->getGroupRoomItem();
      if ( isset($room) ) {
         $room->delete();
      }
      parent::delete();
   }

    /** returns whether the given user may edit the group item or not
     * for CommSy 9: only the moderators or groups creator may edit
     * the group item
     */
    public function mayEdit(cs_user_item $user_item)
    {
        $mayEditItem = parent::mayEdit($user_item);
        if (!$mayEditItem) {
            return false;
        }

        // NOTE: the logic here overrides superclass implementations of this method which effectively treats the
        // "Only editable by creator" (aka \cs_item::isPublic) option as always being checked; this prevents regular
        // group or room members from messing with the group or its group room; see #391(activity-3)
        return ($user_item->isModerator() || $user_item->getItemId() == $this->getCreatorID());
    }
}
?>
