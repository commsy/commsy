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
while($groupItem) {
    if($groupItem->isSystemLabel()) {
        $groupItem = $groupList->getNext();
        continue;
    } else if($groupItem->isGrouproomActivated()) {
        $groupRoom = $groupItem->getGroupRoomItem();

        $grouproomUserList = $groupRoom->getUserList();

        $link_manager = $this->_environment->getLinkItemManager();
        $link_manager->setLinkedItemLimit($groupItem);
        $link_manager->select();
        $linkList = $link_manager->get();

        if($grouproomUserList->getCount() != $linkList->getCount()) {

            $linkListItem = $linkList->getFirst();
            while($linkListItem) {
                if(!$groupRoom->isUser($linkListItem->getSecondLinkedItem())) {
                    pr("Removed member ".$linkListItem->getSecondLinkedItem()->getItemID()." from group ".$groupItem->getItemID());
                    $groupItem->removeMember($linkListItem->getSecondLinkedItem());
                }

                $linkListItem = $linkList->getNext();
            }
        }
    }

    $groupItem = $groupList->getNext();
}

$this->_flushHTML(BRLF);