<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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

// headline
$this->_flushHeadline('db: sync grouproom linked user items');

$success = true;
$group_manager = $this->_environment->getGroupManager();

$group_manager->unsetContextLimit();
$group_manager->select();
$groupList = $group_manager->get();

$groupItem = $groupList->getFirst();
while ($groupItem) {
    if ($groupItem->isSystemLabel()) {
        $groupItem = $groupList->getNext();
        continue;
    } elseif ($groupItem->isGrouproomActivated()) {
        $groupRoom = $groupItem->getGroupRoomItem();

        $groupRoomUserList = $groupRoom->getUserList();

        $link_manager = $this->_environment->getLinkItemManager();
        $link_manager->setLinkedItemLimit($groupItem);
        $link_manager->select();
        $linkList = $link_manager->get();

        if ($groupRoomUserList->getCount() < $linkList->getCount()) {

            $linkListItem = $linkList->getFirst();
            while ($linkListItem) {
                if (!$groupRoom->isUser($linkListItem->getSecondLinkedItem())) {
                    pr("Removed member ".$linkListItem->getSecondLinkedItem()->getItemID()." from group ".$groupItem->getItemID());
                    $groupItem->removeMember($linkListItem->getSecondLinkedItem());
                }

                $linkListItem = $linkList->getNext();
            }
        } elseif ($groupRoomUserList->getCount() > $linkList->getCount()) {
            // add link
            $linkListItem = $linkList->getFirst();
            while ($linkListItem) {
                $linkIdArray[] = $linkListItem->getSecondLinkedItemID();
                $linkListItem = $linkList->getNext();
            }

            $userItem = $groupRoomUserList->getFirst();
            while ($userItem) {
                $related_user = $userItem ->getRelatedUserItemInContext($groupItem->getContextID());
                if(!in_array($related_user->getItemID(), $linkIdArray)) {
                    // add link
                    $itemExists = $link_manager->getItemByFirstAndSecondID( $groupItem->getItemID(), $related_user->getItemID());
                    if(!$itemExists) {
                        $newLinkItem = $link_manager->getNewItem();
                        $newLinkItem->setFirstLinkedItem($groupItem);
                        $newLinkItem->setSecondLinkedItem($related_user);
                        $newLinkItem->save();
                        pr("Added link from member ". $related_user->getItemID(). " to group ".$groupItem->getItemID());
                    }
                    
                }
                $userItem = $groupRoomUserList->getNext();
            }
            
        }
    }

    $groupItem = $groupList->getNext();
}

$this->_flushHTML(BRLF);