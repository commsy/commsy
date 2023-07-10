<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

/** class for a label
 * this class implements a commsy label. A label can be a group, a topic, a label, ...
 */
class cs_group_item extends cs_label_item
{
    /** constructor
     * the only available constructor, initial values for internal variables.
     *
     * @param cs_environment $environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment, CS_GROUP_TYPE);
    }

    public function isGroupRoomActivated(): bool
    {
        return !$this->isSystemLabel();
    }

    public function setGroupRoomItemID($value)
    {
        if (!empty($value)) {
            $this->_setExtra('GROUP_ROOM_ID', (int) $value);
        }
    }

    public function unsetGroupRoomItemID()
    {
        $this->_unsetExtra('GROUP_ROOM_ID');
    }

    public function getGroupRoomItemID()
    {
        return $this->_issetExtra('GROUP_ROOM_ID') ? $this->_getExtra('GROUP_ROOM_ID') : '';
    }

    public function getGroupRoomItem(): ?cs_grouproom_item
    {
        if ($this->_issetGroupRoomItemID()) {
            $grouproom_manager = $this->_environment->getGroupRoomManager();
            $group_room = $grouproom_manager->getItem($this->getGroupRoomItemID());
            if (isset($group_room) and !empty($group_room) and !$group_room->isDeleted()) {
                return $group_room;
            }
        }

        return null;
    }

    private function _issetGroupRoomItemID(): bool
    {
        return !empty($this->getGroupRoomItemID());
    }

    /** save news item
     * this methode save the news item into the database.
     */
    public function save(bool $saveGrouproom = true): void
    {
        $current_user_item = null;
        $save_time = false;

        /** @var cs_room_item $parentRoom */
        $parentRoom = $this->getContextItem();
        $portal = $parentRoom->getContextItem();

        if ($saveGrouproom) {
            if (!$this->_issetGroupRoomItemID() && $this->isGroupRoomActivated()) {
                $new_group_room = true;

                // initiate group room
                $grouproom_manager = $this->_environment->getGroupRoomManager();
                $grouproom_item = $grouproom_manager->getNewItem();
                $grouproom_item->setTitle(html_entity_decode($this->getTitle()));
                $grouproom_item->setContextID($portal->getId());
                $grouproom_item->setLinkedProjectRoomItemID($parentRoom->getItemID());
                $grouproom_item->setCheckNewMemberNever();
                $language = $parentRoom->getLanguage();
                $grouproom_item->setLanguage($language);
                if ('user' == $language) {
                    $language = 'de';
                }
                $grouproom_item->setDescriptionByLanguage($this->getDescription(), $language);
                $grouproom_item->open();
                $grouproom_item->setHtmlTextAreaStatus($parentRoom->getHtmlTextAreaStatus());

                // disable RRS-Feed for new project and community rooms
                $grouproom_item->turnRSSOff();

                $item_id = $this->getItemID();
                if (!empty($item_id)) {
                    $grouproom_item->setLinkedGroupItemID($item_id);
                } else {
                    $save2 = true;
                }

                // picture / logo
                $logo = $this->getPicture();

                // Zeitpunkte
                $save_time = $portal->showTime();

                $grouproom_item->saveOnlyItem();

                // add member of group to the group room
                $current_user_item = $this->_environment->getCurrentUserItem();
                $member_list = $this->getMemberItemList();

                foreach ($member_list as $member_item) {
                    if ($member_item->getItemID() != $current_user_item->getItemID()) {
                        $private_room_user_item = $member_item->getRelatedPrivateRoomUserItem();
                        $new_member_item = $private_room_user_item->cloneData();
                        $new_member_item->setContextID($grouproom_item->getItemID());
                        $new_member_item->makeUser();

                        if ($portal->getConfigurationHideMailByDefault()) {
                            $new_member_item->setEmailNotVisible();
                        }

                        $picture = $private_room_user_item->getPicture();
                        if (!empty($picture)) {
                            $value_array = explode('_', $picture);
                            $value_array[0] = 'cid'.$new_member_item->getContextID();
                            $new_picture_name = implode('_', $value_array);
                            $disc_manager = $this->_environment->getDiscManager();
                            $disc_manager->copyImageFromRoomToRoom($picture, $new_member_item->getContextID());
                            $new_member_item->setPicture($new_picture_name);
                        }

                        $new_member_item->save();
                        $new_member_item->setCreatorID2ItemID();
                    }
                }

                // add current user to the group as a member
                $add_member = !$this->isMember($current_user_item);
            } elseif ($this->_issetGroupRoomItemID()) {
                $grouproom_item = $this->getGroupRoomItem();
                if (isset($grouproom_item) and !empty($grouproom_item)) {
                    $grouproom_item->setTitle(html_entity_decode($this->getTitle()));

                    // description
                    $language = $parentRoom->getLanguage();
                    $grouproom_item->setLanguage($language);
                    if ('user' == $language) {
                        $language = 'de';
                    }
                    $grouproom_item->setDescriptionByLanguage($this->getDescription(), $language);

                    // picture / logo
                    $logo = $this->getPicture();
                    if (empty($logo)) {
                        $grouproom_item->setLogoFilename('');
                    }
                    $save2 = true;
                }
            }
        }

        $label_manager = $this->_environment->getLabelManager();
        $this->_save($label_manager);

        if ($save_time) {
            if ($parentRoom->isContinuous()) {
                $grouproom_item->setContinuous();
                $save2 = true;
            }
            $time_list = $parentRoom->getTimeList();
            if ($time_list->isNotEmpty()) {
                $grouproom_item->setTimeList($time_list);
                $save2 = true;
            }
        }
        if (isset($logo) and !empty($logo)) {
            $disc_manager = $this->_environment->getDiscManager();
            $disc_manager->copyImageFromRoomToRoom($logo, $grouproom_item->getItemID());
            $grouproom_item->setLogoFilename($disc_manager->getLastSavedFileName());
            $save2 = true;
        }
        if (isset($save2) and $save2 and $saveGrouproom) {
            $grouproom_item->setLinkedGroupItemID($this->getItemID());
            $grouproom_item->saveOnlyItem();
        }
        if (isset($new_group_room) and $new_group_room) {
            $this->setGroupRoomItemID($grouproom_item->getItemID());
            $this->_save($label_manager);
        }

        // add current user to the group as a member
        if (isset($add_member) and $add_member) {
            $this->addMember($current_user_item);
        }

        $this->updateElastic();
    }

    /** save news item
     * this methode save the news item into the database.
     */
    public function saveOnlyItem()
    {
        $this->save(false);
    }

    /** delete group item
     * this methode delete the group item
     * with the group room.
     */
    public function delete()
    {
        $room = $this->getGroupRoomItem();
        if (isset($room)) {
            $room->delete();
        }
        parent::delete();
    }

     /** returns whether the given user may edit the group item or not
      * for CommSy 9: only the moderators or groups creator may edit
      * the group item.
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
         return $user_item->isModerator() || $user_item->getItemId() == $this->getCreatorID();
     }
}
